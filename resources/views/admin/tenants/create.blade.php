@extends('layouts.app')

@section('content')

<form action="{{ route('admin.tenant.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">

        <div class="col-12 ">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Add Tenant Photo & Eid or Passport</h4>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo" value="{{ old('profile_photo') }}" name="profile_photo">
                            @error('profile_photo') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-body">
                            <label for="id_document" class="form-label">ID Document</label>
                            <input type="file" class="form-control" id="id_document" value="{{ old('id_document') }}" name="id_document">
                            @error('id_document') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

            </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Tenant Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">

                            <div class="mb-3">
                                <label for="name" class="form-label">Tenant Full Name</label>
                                <input type="text" id="name" value="{{ old('name') }}" name="name" class="form-control" placeholder="Full Name">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="col-lg-6">

                            <div class="mb-3">
                                <label for="email" class="form-label">Tenant Email</label>
                                <input type="email" id="email" value="{{ old('email') }}" name="email" class="form-control" placeholder="Enter Email">
                                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">
                                <label for="phone" class="form-label">Tenant Phone</label>
                                <input type="number" id="phone" value="{{ old('phone') }}" name="phone" class="form-control" placeholder="Like (+971 50 123 4567)">
                                @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="col-lg-6">

                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" id="dob" value="{{ old('dob') }}" name="dob" class="form-control" placeholder="Enter Date Of Birth">
                                @error('dob') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">
                                <label for="eid_passport_no" class="form-label">Tenant EiD/ Passport No</label>
                                <input type="text" id="eid_passport_no" value="{{ old('eid_passport_no') }}" name="eid_passport_no" class="form-control" placeholder="Enter EiD/ Passport No">
                                @error('eid_passport_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="col-lg-6"></div>

                        <div class="col-lg-12">

                            <div class="mb-3">
                                <label for="address" class="form-label">Tenant Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter address">{{ old('address') }}</textarea>
                                @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>

                        <div class="col-lg-4">

                            <div class="mb-3">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Full Name</label>
                                <input type="text" id="emergency_contact_name" value="{{ old('emergency_contact_name') }}" name="emergency_contact_name" class="form-control" placeholder="Enter Full Name">
                                @error('emergency_contact_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="col-lg-4">

                            <div class="mb-3">
                                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="text" id="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" name="emergency_contact_phone" class="form-control" placeholder="Enter Phone Number">
                                @error('emergency_contact_phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>

                        <div class="col-lg-4">

                            <div class="mb-3">
                                <label for="emergency_contact_email" class="form-label">Emergency Contact Email</label>
                                <input type="email" id="emergency_contact_email" value="{{ old('emergency_contact_email') }}" name="emergency_contact_email" class="form-control" placeholder="Enter Email">
                                @error('emergency_contact_email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">

                                <label for="emergency_contact_relationship" class="form-label">Relationship with Tenant</label>
                                <select class="form-control" id="emergency_contact_relationship" data-choices data-choices-groups data-placeholder="emergency_contact_relationship" name="emergency_contact_relationship">
                                    <option value="">Select Relationship</option>
                                    <optgroup label="">
                                        <option value="parent" {{ old('emergency_contact_relationship') == 'parent' ? 'selected' : '' }}>Parent</option>
                                        <option value="spouse" {{ old('emergency_contact_relationship') == 'spouse' ? 'selected' : '' }}>Spouse</option>
                                        <option value="sibling" {{ old('emergency_contact_relationship') == 'sibling' ? 'selected' : '' }}>Sibling</option>
                                        <option value="child" {{ old('emergency_contact_relationship') == 'child' ? 'selected' : '' }}>Child</option>
                                        <option value="relative" {{ old('emergency_contact_relationship') == 'relative' ? 'selected' : '' }}>Relative</option>
                                        <option value="friend" {{ old('emergency_contact_relationship') == 'friend' ? 'selected' : '' }}>Friend</option>
                                        <option value="colleague" {{ old('emergency_contact_relationship') == 'colleague' ? 'selected' : '' }}>Colleague</option>
                                        <option value="other" {{ old('emergency_contact_relationship') == 'other' ? 'selected' : '' }}>Other</option>
                                    </optgroup>
                                    @error('emergency_contact_relationship') <span class="text-danger">{{ $message }}</span> @enderror
                                </select>

                            </div>
                        </div>

                        <div class="col-lg-4">

                            <div class="mb-3">
                                <label for="instagram-url" class="form-label">Instagram URL</label>
                                <input type="url" id="instagram-url" class="form-control" placeholder="Enter URL">
                            </div>

                        </div>
                        <div class="col-lg-4">

                            <div class="mb-3">
                                <label for="twitter-url" class="form-label">Twitter URL</label>
                                <input type="url" id="twitter-url" class="form-control" placeholder="Enter URL">
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    <div class="col-lg-2">
                        <button id="submitBtn" class="btn btn-outline-primary w-100">Create Tenant</button>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('admin.tenant.index') }}" class="btn btn-danger w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>
@endsection

@section('script')
@endsection
