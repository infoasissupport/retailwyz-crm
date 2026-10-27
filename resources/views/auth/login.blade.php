@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <h1 class="h4 mb-4">{{ config('app.name') }}</h1>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <x-form.input name="email" label="Email" type="email" required autofocus autocomplete="username" />
                <x-form.input name="password" label="Password" type="password" required autocomplete="current-password" />
                <x-form.checkbox name="remember" label="Remember me" />

                <button type="submit" class="btn btn-primary w-100">Log in</button>
            </form>
        </div>
    </div>
@endsection
