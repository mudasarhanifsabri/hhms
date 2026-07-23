@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Add Remark', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        <form action="{{ route('maintainer.task.remark', $task->id) }}" method="POST" enctype="multipart/form-data" class="pwa-form">
            @csrf
            <div class="pwa-field">
                <label for="remark">Remark <b>*</b></label>
                <textarea id="remark" name="remark" rows="5" required placeholder="Checked the AC filter. It was very dirty. Cleaned it."></textarea>
            </div>
            <div class="pwa-field">
                <label for="status_update">Status Update</label>
                <select id="status_update" name="status_update">
                    <option value="">No change</option>
                    <option value="in_progress">In Progress</option>
                    <option value="waiting_approval">Waiting Approval</option>
                </select>
            </div>
            @include('maintainer.partials.photo-picker')
            <button class="pwa-primary-button purple" type="submit">Add Remark</button>
        </form>
    </div>
</div>
@endsection
