@extends('layouts.auth-mobile')
@section('title','Sign In')
@section('content')
<div class="eyebrow">Welcome back</div><h1>Sign in to your app</h1><p class="lead">Use the same account for your mobile app and desktop portal.</p>
<x-auth-session-status class="status" :status="session('status')" />
<form action="{{ route('login') }}" method="post">@csrf
    <div class="field"><div class="field-top"><label for="email">Email address</label></div><div class="control-wrap"><input class="control" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="username" inputmode="email" required autofocus><span class="control-icon" aria-hidden="true">&#9993;</span></div><x-input-error :messages="$errors->get('email')" class="error" /></div>
    <div class="field"><div class="field-top"><label for="password">Password</label><a class="field-link" href="{{ route('password.request') }}">Forgot password?</a></div><div class="control-wrap"><input class="control" id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button class="control-icon" type="button" data-password-toggle="password" aria-label="Show password">&#128065;</button></div><x-input-error :messages="$errors->get('password')" class="error" /></div>
    <label class="remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Keep me signed in on this device</label>
    <button class="primary" type="submit">Sign In</button>
</form>
<div class="secure-note"><span>&#128274;</span> Your account and documents are protected</div>
@endsection
