@extends('layouts.app')

@section('content')

<form action="{{ route('admin.agent.update', $agent->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-12 ">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Agent Photo & ID Document</h4>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            @error('profile_photo') <span class="text-danger">{{ $message }}</span> @enderror
                            @if ($agent->profile_photo)
                                <div class="mt-2">
                                    <img src="{{ \App\Support\MediaStorage::url($agent->profile_photo) }}" alt="Profile Photo" width="100" />
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="id_document" class="form-label">ID Document</label>
                            <input type="file" class="form-control" id="id_document" name="id_document" accept="image/*,.pdf">
                            @error('id_document') <span class="text-danger">{{ $message }}</span> @enderror
                            @if ($agent->id_document)
                                <div class="mt-2">
                                    <a href="{{ \App\Support\MediaStorage::url($agent->id_document) }}" target="_blank">View Current Document</a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="id_document_back" class="form-label">ID Document Back Side</label>
                            <input type="file" class="form-control" id="id_document_back" name="id_document_back" accept="image/*,.pdf">
                            @error('id_document_back') <span class="text-danger">{{ $message }}</span> @enderror
                            @if ($agent->id_document_back)
                                <div class="mt-2">
                                    <a href="{{ \App\Support\MediaStorage::url($agent->id_document_back) }}" target="_blank">View Current Back Side</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Agent Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Agent Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Full Name"
                                    value="{{ old('name', $agent->name) }}" required>
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Agent Email</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="Enter Email"
                                    value="{{ old('email', $agent->email) }}" required>
                                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Agent Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control" placeholder="Like (+971 50 123 4567)"
                                    value="{{ old('phone', $agent->phone) }}" required>
                                @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control"
                                    value="{{ old('dob', $agent->dob) }}" required>
                                @error('dob') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="eid_passport_no" class="form-label">Agent EiD/ Passport No</label>
                                <input type="text" id="eid_passport_no" name="eid_passport_no" class="form-control" placeholder="Enter EiD/ Passport No"
                                    value="{{ old('eid_passport_no', $agent->eid_passport_no) }}" required>
                                @error('eid_passport_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    <div class="col-lg-2">
                        <button id="submitBtn" class="btn btn-outline-primary w-100">Update Agent</button>
                    </div>
                    <div class="col-lg-2">
                        <a href="{{ route('admin.agent.index') }}" class="btn btn-danger w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>

@endsection

