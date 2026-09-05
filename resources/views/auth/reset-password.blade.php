@extends('layouts.auth-mobile')
@section('title','Set New Password')
@section('content')
<div class="eyebrow">Secure your account</div><h1>Create a new password</h1><p class="lead">Choose a strong password you have not used for this account before.</p>
<form action="{{ route('password.store') }}" method="post">@csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="field"><div class="field-top"><label for="email">Email address</label></div><div class="control-wrap"><input class="control" id="email" type="email" name="email" value="{{ old('email',$request->email) }}" autocomplete="username" readonly><span class="control-icon" aria-hidden="true">&#9993;</span></div><x-input-error :messages="$errors->get('email')" class="error" /></div>
    <div class="field"><div class="field-top"><label for="password">New password</label></div><div class="control-wrap"><input class="control" id="password" type="password" name="password" placeholder="Minimum 8 characters" autocomplete="new-password" required autofocus><button class="control-icon" type="button" data-password-toggle="password" aria-label="Show password">&#128065;</button></div><div class="password-meter" aria-hidden="true"><span></span><span></span><span></span><span></span></div><x-input-error :messages="$errors->get('password')" class="error" /></div>
    <div class="field"><div class="field-top"><label for="password_confirmation">Confirm new password</label></div><div class="control-wrap"><input class="control" id="password_confirmation" type="password" name="password_confirmation" placeholder="Repeat your new password" autocomplete="new-password" required><button class="control-icon" type="button" data-password-toggle="password_confirmation" aria-label="Show password">&#128065;</button></div><x-input-error :messages="$errors->get('password_confirmation')" class="error" /></div>
    <button class="primary" type="submit">Update Password</button>
</form>
<a class="back-link page-back" href="{{ route('login') }}"><span>&larr;</span> Back to sign in</a>
@endsection
