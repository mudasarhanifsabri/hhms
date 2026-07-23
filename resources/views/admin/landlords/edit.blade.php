@extends('layouts.app')

@section('content')

<form action="{{ route('admin.landlord.update', $landlord->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-12 ">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Landlord Photo & Eid or Passport</h4>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo">
                            @error('profile_photo') <span class="text-danger">{{ $message }}</span> @enderror
                            @if ($landlord->profile_photo)
                                <div class="mt-2">
                                    <img src="{{ asset($landlord->profile_photo) }}" alt="Profile Photo" width="100" />
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="id_document" class="form-label">ID Document</label>
                            <input type="file" class="form-control" id="id_document" name="id_document">
                            @error('id_document') <span class="text-danger">{{ $message }}</span> @enderror
                            @if ($landlord->id_document)
                                <div class="mt-2">
                                    <a href="{{ asset($landlord->id_document) }}" target="_blank">View Current Document</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Landlord Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Landlord Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Full Name"
                                    value="{{ old('name', $landlord->name) }}" required>
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Landlord Email</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="Enter Email"
                                    value="{{ old('email', $landlord->email) }}" required>
                                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Landlord Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control" placeholder="Like (+971 50 123 4567)"
                                    value="{{ old('phone', $landlord->phone) }}" required>
                                @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control"
                                    value="{{ old('dob', $landlord->dob) }}" required>
                                @error('dob') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="eid_passport_no" class="form-label">Landlord EiD/ Passport No</label>
                                <input type="text" id="eid_passport_no" name="eid_passport_no" class="form-control" placeholder="Enter EiD/ Passport No"
                                    value="{{ old('eid_passport_no', $landlord->eid_passport_no) }}" required>
                                @error('eid_passport_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">Landlord Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter address" required>{{ old('address', $landlord->address) }}</textarea>
                                @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Full Name</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" placeholder="Enter Full Name"
                                    value="{{ old('emergency_contact_name', $landlord->emergency_contact_name) }}" required>
                                @error('emergency_contact_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control" placeholder="Enter Phone Number"
                                    value="{{ old('emergency_contact_phone', $landlord->emergency_contact_phone) }}" required>
                                @error('emergency_contact_phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="emergency_contact_email" class="form-label">Emergency Contact Email</label>
                                <input type="email" id="emergency_contact_email" name="emergency_contact_email" class="form-control" placeholder="Enter Email"
                                    value="{{ old('emergency_contact_email', $landlord->emergency_contact_email) }}" required>
                                @error('emergency_contact_email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="emergency_contact_relationship" class="form-label">Relationship with Landlord</label>
                                <select class="form-control" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                                    <option value="">Select Relationship</option>
                                    @php
                                        $relationships = ['parent', 'spouse', 'sibling', 'child', 'relative', 'friend', 'colleague', 'other'];
                                    @endphp
                                    @foreach ($relationships as $relation)
                                        <option value="{{ $relation }}" {{ old('emergency_contact_relationship', $landlord->emergency_contact_relationship) == $relation ? 'selected' : '' }}>
                                            {{ ucfirst($relation) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('emergency_contact_relationship') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- You had Instagram and Twitter fields but no backend handling, so I kept them readonly --}}
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="instagram-url" class="form-label">Instagram URL</label>
                                <input type="url" id="instagram-url" class="form-control" placeholder="Enter URL" readonly>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="twitter-url" class="form-label">Twitter URL</label>
                                <input type="url" id="twitter-url" class="form-control" placeholder="Enter URL" readonly>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    <div class="col-lg-2">
                        <button id="submitBtn" class="btn btn-outline-primary w-100">Update Landlord</button>
                    </div>
                    <div class="col-lg-2">
                        <a href="{{ route('admin.landlord.index') }}" class="btn btn-danger w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>

@endsection
