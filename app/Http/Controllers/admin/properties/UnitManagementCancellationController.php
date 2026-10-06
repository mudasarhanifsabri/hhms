<?php

namespace App\Http\Controllers\admin\properties;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\UnitManagementCancellation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UnitManagementCancellationController extends Controller
{
    public function create(Property $property)
    {
        $property->load(['landlord', 'building', 'ownerShares.owner']);
        $owners = $property->ownerShares->pluck('owner')->filter()->push($property->landlord)->filter()->unique('id')->values();

        return view('admin.properties.cancellations.create', compact('property', 'owners'));
    }

    public function store(Request $request, Property $property)
    {
        $property->load(['landlord', 'building', 'ownerShares.owner']);
        $ownerIds = $property->ownerShares->pluck('owner_id')->push($property->landlord_id)->filter()->unique()->all();
        $data = $request->validate([
            'owner_id' => ['required', Rule::in($ownerIds)],
            'letter_date' => ['required', 'date'],
            'effective_date' => ['required', 'date'],
            'property_description' => ['required', 'string', 'max:2000'],
            'owner_email' => ['required', 'email:rfc', 'max:255'],
            'company_signer_name' => ['required', 'string', 'max:255'],
            'company_signer_email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $owner = User::findOrFail($data['owner_id']);

        $cancellation = UnitManagementCancellation::create([
            ...$data,
            'property_id' => $property->id,
            'owner_name' => $owner->name,
            'reference_no' => $this->referenceNo(),
            'status' => 'sent_to_owner',
            'owner_token' => Str::random(64),
            'company_token' => Str::random(64),
            'owner_sent_at' => now(),
            'expires_at' => now()->addDays(30)->toDateString(),
        ]);
        $this->event($cancellation, 'admin', 'created_and_sent_to_owner', $request);
        $this->sendLink($cancellation, 'owner');

        return redirect()->route('admin.property.cancellations.show', [$property, $cancellation])
            ->with('success', 'Cancellation created. The owner signing link has been sent.');
    }

    public function show(Property $property, UnitManagementCancellation $cancellation)
    {
        abort_unless($cancellation->property_id === $property->id, 404);
        $cancellation->load(['events', 'property.building', 'owner']);

        return view('admin.properties.cancellations.show', compact('property', 'cancellation'));
    }

    public function resend(Request $request, Property $property, UnitManagementCancellation $cancellation, string $party)
    {
        abort_unless($cancellation->property_id === $property->id, 404);
        abort_unless(in_array($party, ['owner', 'company'], true), 404);
        if ($cancellation->cancelled_at) return back()->withErrors(['signing' => 'This cancellation process has been stopped.']);
        if ($party === 'owner' && $cancellation->owner_signed_at) return back()->withErrors(['signing' => 'The owner has already signed.']);
        if ($party === 'company' && ! $cancellation->owner_signed_at) return back()->withErrors(['signing' => 'Company signing starts after the owner signs.']);
        if ($party === 'company' && $cancellation->company_signed_at) return back()->withErrors(['signing' => 'The company has already signed.']);

        $cancellation->update([$party.'_sent_at' => now()]);
        $this->event($cancellation, 'admin', $party.'_link_resent', $request);
        $this->sendLink($cancellation, $party);

        return back()->with('success', ucfirst($party).' signing link resent.');
    }

    public function stop(Request $request, Property $property, UnitManagementCancellation $cancellation)
    {
        abort_unless($cancellation->property_id === $property->id, 404);
        if ($cancellation->completed_at) return back()->withErrors(['signing' => 'A fully signed cancellation cannot be stopped.']);
        if ($cancellation->cancelled_at) return back()->withErrors(['signing' => 'This cancellation process is already stopped.']);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ]);
        $cancellation->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
            'cancellation_reason' => $data['cancellation_reason'],
        ]);
        $this->event($cancellation, 'admin', 'cancellation_process_stopped', $request);

        return back()->with('success', 'Cancellation process stopped. Both signing links are now revoked.');
    }

    private function referenceNo(): string
    {
        do $reference = 'CXL-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        while (UnitManagementCancellation::where('reference_no', $reference)->exists());
        return $reference;
    }

    private function sendLink(UnitManagementCancellation $cancellation, string $party): void
    {
        $email = $party === 'owner' ? $cancellation->owner_email : $cancellation->company_signer_email;
        $name = $party === 'owner' ? $cancellation->owner_name : $cancellation->company_signer_name;
        $token = $party === 'owner' ? $cancellation->owner_token : $cancellation->company_token;
        try {
            Mail::html(view('emails.unit-management-cancellation-link', compact('cancellation', 'party', 'name', 'token'))->render(),
                fn ($message) => $message->to($email)->subject('Signature Required - Management Agreement Cancellation - '.$cancellation->property->name));
        } catch (Throwable $exception) {
            Log::warning('Cancellation signing email failed', ['cancellation_id' => $cancellation->id, 'party' => $party, 'error' => $exception->getMessage()]);
        }
    }

    private function event(UnitManagementCancellation $cancellation, string $role, string $event, Request $request): void
    {
        $cancellation->events()->create(['actor_role' => $role, 'event' => $event, 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 1000, '')]);
    }
}
