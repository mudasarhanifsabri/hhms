@extends('layouts.tenant-pwa')
@section('content')
<main style="max-width:640px;margin:24px auto;padding:24px;background:white;border-radius:16px">
    <h1 style="font-size:24px">Complete your guest profile</h1>
    <p>We filled in the details available from your booking. Please review them and complete the missing information.</p>
    @if($errors->any())<div role="alert" style="color:#a02020">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <p><strong>Login email:</strong> {{ $tenant->email }}<br><small>Contact management if this email is incorrect.</small></p>
    <form method="POST" action="{{ route('tenant.profile.update') }}">@csrf @method('PUT')
    @foreach(['name'=>'Full name','phone'=>'Phone','eid_passport_no'=>'Passport / Emirates ID number','nationality'=>'Nationality','dob'=>'Date of birth','address'=>'Home address','emergency_contact_name'=>'Emergency contact name (optional)','emergency_contact_phone'=>'Emergency contact phone (optional)'] as $field=>$label)
        <label for="profile-{{ $field }}" style="display:block;margin:16px 0 6px">{{ $label }}</label>
        <input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field==='dob'?'date':'text' }}" value="{{ old($field,$field==='dob'?$tenant->dob?->toDateString():$tenant->{$field}) }}" @required(!str_starts_with($field,'emergency_')) style="width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdd5e0;border-radius:8px;font:inherit">
    @endforeach
        <button style="margin-top:24px;width:100%;padding:14px;background:#0b2f6b;color:white;border:0;border-radius:8px;font:inherit">Save & Continue</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px">@csrf<button style="background:none;border:0;color:#0b2f6b">Sign out</button></form>
</main>
@endsection
