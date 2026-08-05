@extends('layouts.app')

@section('content')
@php($results = session('update_results', []))

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">Update Software</h4>
                <p class="text-muted mb-0">Pull the latest code from GitHub and run Laravel update commands.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                Branch: {{ trim($branch) ?: 'unknown' }}
            </span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title mb-0">GitHub Update</h4>
            </div>
            <div class="card-body">
                <div class="alert {{ $hasGit ? 'alert-info' : 'alert-warning' }}">
                    @if($hasGit)
                        Current version: <strong>{{ $lastCommit }}</strong>
                    @else
                        This installation is not connected to a Git repository.
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.software-update.run') }}" onsubmit="return confirm('Update software from GitHub now? This can take a few minutes.');">
                    @csrf
                    <label class="form-check border rounded p-3 mb-3">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="confirm_update" value="1" required>
                        <span class="form-check-label fw-semibold">I confirm this server should pull the latest GitHub code.</span>
                    </label>
                    <button class="btn btn-primary w-100" type="submit" @disabled(! $hasGit)>
                        <i class="ri-download-cloud-2-line me-1"></i>Update From GitHub
                    </button>
                </form>

                <p class="text-muted small mt-3 mb-0">
                    The updater runs: git pull, composer install, migrations, and Laravel cache refresh.
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card">
            <div class="card-header bg-light-subtle d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Update Log</h4>
                <span class="text-muted small">{{ count($results) }} step(s)</span>
            </div>
            <div class="card-body">
                @forelse($results as $row)
                    @php($result = $row['result'])
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="mb-0">{{ $row['label'] }}</h6>
                            <span class="badge {{ $result['success'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ $result['success'] ? 'OK' : 'Failed' }}
                            </span>
                        </div>
                        <p class="text-muted small mb-2 mt-1">{{ implode(' ', $row['command']) }}</p>
                        @if($result['output'])
                            <pre class="mb-2 rounded bg-light p-2 small text-wrap">{{ $result['output'] }}</pre>
                        @endif
                        @if($result['error'])
                            <pre class="mb-0 rounded bg-danger-subtle p-2 small text-danger text-wrap">{{ $result['error'] }}</pre>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="ri-terminal-box-line fs-1 d-block mb-2"></i>
                        No update has been run in this session.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
