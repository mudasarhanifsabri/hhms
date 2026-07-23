@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Accept Task', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        <div class="pwa-alert">
            <i class="ri-information-line"></i>
            <p>You are about to accept this task. Update the expected completion date and add initial remarks.</p>
        </div>

        <form action="{{ route('maintainer.task.accept', $task->id) }}" method="POST" enctype="multipart/form-data" class="pwa-form">
            @csrf
            <div class="pwa-field">
                <label for="expected_completion_date">Expected Completion Date <b>*</b></label>
                <input type="date" id="expected_completion_date" name="expected_completion_date" required>
            </div>
            <div class="pwa-field">
                <label for="initial_remark">Initial Remark</label>
                <textarea id="initial_remark" name="initial_remark" rows="4" placeholder="I will check and update."></textarea>
            </div>
            @include('maintainer.partials.photo-picker', ['label' => 'Evidence Photos'])
            <button class="pwa-primary-button green" type="submit">Accept Task</button>
        </form>
    </div>
</div>
@endsection
