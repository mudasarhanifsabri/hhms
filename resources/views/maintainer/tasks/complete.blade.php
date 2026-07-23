@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Complete Task', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        <form action="{{ route('maintainer.task.complete', $task->id) }}" method="POST" enctype="multipart/form-data" class="pwa-form">
            @csrf
            <input type="hidden" name="completion_date" value="{{ now()->format('Y-m-d') }}">
            <div class="pwa-field">
                <label for="completion_notes">Completion Notes <b>*</b></label>
                <textarea id="completion_notes" name="completion_notes" rows="4" required placeholder="AC is working fine now. Gas refilled and tested."></textarea>
            </div>
            @include('maintainer.partials.photo-picker', ['name' => 'final_images[]', 'label' => 'Completion Photos'])
            <div class="pwa-field">
                <label for="final_remark">Final Remark <b>*</b></label>
                <input type="text" id="final_remark" name="final_remark" required placeholder="Task completed.">
            </div>
            <div class="pwa-attachment-list">
                <label class="pwa-file-line"><i class="ri-file-pdf-2-line"></i><span>Invoice</span><input type="file" name="invoice_attachment" accept=".pdf,image/*" data-upload-preview data-safe-preview></label>
                <div class="pwa-upload-preview" data-upload-preview-list></div>
                <label class="pwa-file-line"><i class="ri-receipt-line"></i><span>Receipt</span><input type="file" name="receipt_attachment" accept=".pdf,image/*" data-upload-preview data-safe-preview></label>
                <div class="pwa-upload-preview" data-upload-preview-list></div>
                <label class="pwa-file-line"><i class="ri-shield-check-line"></i><span>Warranty</span><input type="file" name="warranty_attachment" accept=".pdf,image/*" data-upload-preview data-safe-preview></label>
                <div class="pwa-upload-preview" data-upload-preview-list></div>
            </div>
            <button class="pwa-primary-button green" type="submit">Complete Task</button>
        </form>
    </div>
</div>
@endsection
