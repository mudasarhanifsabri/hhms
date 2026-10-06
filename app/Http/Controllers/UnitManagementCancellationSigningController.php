<?php

namespace App\Http\Controllers;

use App\Models\UnitDocument;
use App\Models\UnitManagementCancellation;
use App\Support\MediaStorage;
use App\Support\OwnerDocumentPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class UnitManagementCancellationSigningController extends Controller
{
    public function show(string $token)
    {
        [$cancellation, $party] = $this->resolve($token);
        if ($cancellation->cancelled_at) {
            return view('unit-cancellations.sign', compact('cancellation', 'party'));
        }
        if (! $cancellation->completed_at && $cancellation->expires_at->endOfDay()->isPast()) {
            $cancellation->update(['status' => 'expired']);
        }

        $viewedField = $party.'_viewed_at';
        if (! $cancellation->{$viewedField}) {
            $changes = [$viewedField => now()];
            if ($party === 'owner' && ! $cancellation->owner_signed_at && $cancellation->status !== 'expired') {
                $changes['status'] = 'owner_viewed';
            }
            $cancellation->update($changes);
            $this->event($cancellation, $party, 'link_viewed', request());
        }

        return view('unit-cancellations.sign', compact('cancellation', 'party'));
    }

    public function sign(Request $request, string $token)
    {
        [$cancellation, $party] = $this->resolve($token);
        if ($cancellation->cancelled_at) return back()->withErrors(['signature_data' => 'This cancellation process has been stopped and the signing link is revoked.']);
        if ($cancellation->expires_at->endOfDay()->isPast()) return back()->withErrors(['signature_data' => 'This signing link has expired.']);
        if ($party === 'company' && ! $cancellation->owner_signed_at) return back()->withErrors(['signature_data' => 'The owner must sign before the company can countersign.']);
        if (($party === 'owner' && $cancellation->owner_signed_at) || ($party === 'company' && $cancellation->company_signed_at)) return back()->with('success', 'This party has already signed.');

        $data = $request->validate([
            'signed_by_name' => ['required', 'string', 'max:255'],
            'signature_data' => ['required', 'string', 'max:3000000', 'starts_with:data:image/png;base64,'],
            'accepted' => ['accepted'],
        ]);
        $cancellation->update([
            $party.'_signature' => $data['signature_data'],
            $party.'_signed_name' => $data['signed_by_name'],
            $party.'_signed_at' => now(),
            'status' => $party === 'owner' ? 'awaiting_company_signature' : 'fully_signed',
            ...($party === 'owner' ? ['company_sent_at' => now()] : ['completed_at' => now()]),
        ]);
        $this->event($cancellation, $party, 'signed', $request);

        if ($party === 'owner') {
            $this->sendCompanyLink($cancellation->fresh('property'));
        } else {
            $this->complete($cancellation->fresh(['property.building', 'owner']));
        }

        return redirect()->route('unit-cancellations.show', $token)->with('success', $party === 'owner' ? 'Owner signature saved. The document is now awaiting company signature.' : 'Both signatures are complete. The final PDF is ready.');
    }

    public function pdf(string $token)
    {
        [$cancellation] = $this->resolve($token);
        abort_unless($cancellation->completed_at, 403, 'The final PDF is available after both parties sign.');
        abort_unless($cancellation->final_document_path, 404);

        return Storage::disk(MediaStorage::disk())->response(
            MediaStorage::path($cancellation->final_document_path),
            $cancellation->reference_no.'.pdf',
            ['Content-Type' => 'application/pdf'],
            'inline'
        );
    }

    private function complete(UnitManagementCancellation $cancellation): void
    {
        $pdf = OwnerDocumentPdf::output($this->html($cancellation));
        $path = 'unit-cancellations/'.$cancellation->reference_no.'.pdf';
        MediaStorage::put($path, $pdf);
        $cancellation->update(['final_document_path' => $path, 'document_hash' => hash('sha256', $pdf)]);
        UnitDocument::updateOrCreate(
            ['property_id' => $cancellation->property_id, 'reference_no' => $cancellation->reference_no],
            ['owner_id' => $cancellation->owner_id, 'type' => 'custom', 'custom_title' => 'Management Agreement Cancellation & Owner No Claims Confirmation', 'issue_date' => $cancellation->letter_date, 'file_path' => $path, 'notes' => 'Fully signed by owner and company.', 'source' => 'generated']
        );
        $this->event($cancellation, 'system', 'final_pdf_generated', request(), ['sha256' => hash('sha256', $pdf)]);
        $recipients = [
            $cancellation->owner_email => $cancellation->owner_token,
            $cancellation->company_signer_email => $cancellation->company_token,
        ];
        foreach ($recipients as $email => $token) {
            try { Mail::raw('The management agreement cancellation has been signed by both parties. Download the final PDF: '.route('unit-cancellations.pdf', $token), fn ($m) => $m->to($email)->subject('Fully Signed - '.$cancellation->reference_no)); }
            catch (Throwable $e) { Log::warning('Final cancellation email failed', ['id' => $cancellation->id, 'email' => $email, 'error' => $e->getMessage()]); }
        }
    }

    private function sendCompanyLink(UnitManagementCancellation $cancellation): void
    {
        try {
            Mail::html(view('emails.unit-management-cancellation-link', ['cancellation' => $cancellation, 'party' => 'company', 'name' => $cancellation->company_signer_name, 'token' => $cancellation->company_token])->render(),
                fn ($m) => $m->to($cancellation->company_signer_email)->subject('Countersign Required - Management Agreement Cancellation - '.$cancellation->property->name));
        } catch (Throwable $e) { Log::warning('Company cancellation email failed', ['id' => $cancellation->id, 'error' => $e->getMessage()]); }
    }

    private function resolve(string $token): array
    {
        $cancellation = UnitManagementCancellation::with(['property.building', 'owner'])->where('owner_token', $token)->orWhere('company_token', $token)->firstOrFail();
        return [$cancellation, hash_equals($cancellation->owner_token, $token) ? 'owner' : 'company'];
    }

    private function html(UnitManagementCancellation $cancellation): string
    {
        return view('unit-cancellations.document', compact('cancellation'))->render();
    }

    private function event(UnitManagementCancellation $cancellation, string $role, string $event, Request $request, array $metadata = []): void
    {
        $cancellation->events()->create(['actor_role' => $role, 'event' => $event, 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''), 'metadata' => $metadata ?: null]);
    }
}
