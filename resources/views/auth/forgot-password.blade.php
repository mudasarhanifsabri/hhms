@extends('layouts.auth-mobile')
@section('title','Forgot Password')
@section('content')
<div class="eyebrow">Account recovery</div><h1>Forgot your password?</h1><p class="lead">Enter your login email and we will send you a secure password-reset link.</p>
<x-auth-session-status class="status" :status="session('status')" />
<form action="{{ route('password.email') }}" method="post">@csrf
    <div class="field"><div class="field-top"><label for="email">Email address</label></div><div class="control-wrap"><input class="control" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" inputmode="email" required autofocus><span class="control-icon" aria-hidden="true">&#9993;</span></div><x-input-error :messages="$errors->get('email')" class="error" /></div>
    <button class="primary" type="submit">Send Reset Link</button>
</form>
<a class="back-link page-back" href="{{ route('login') }}"><span>&larr;</span> Back to sign in</a>
<div class="secure-note"><span>&#128274;</span> Reset links expire for your security</div>
@endsection
