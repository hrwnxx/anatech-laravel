@extends('layouts.storefront')

@section('title', 'Login | Anatech')

@section('content')
    <div class="col-md-5 col-lg-4 mx-auto">
        <div class="text-center mb-3">
            <a href="{{ route('catalog.index') }}" class="brand">
                <img src="{{ asset('images/logo-wordmark.png') }}" alt="Anatech" class="brand-logo">
            </a>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary btn-sm mb-3">&larr; Back to Catalog</a>

        <div class="card p-4">
            <h3 class="text-center">Login</h3>

            @if ($googleError)
                <div class="alert alert-danger">{{ $googleError }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <input type="email" name="email" class="form-control mb-2" placeholder="Email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                <input type="password" name="password" class="form-control mb-2" placeholder="Password" autocomplete="current-password" required>
                <label class="form-check mb-3">
                    <input type="checkbox" name="remember" value="1" class="form-check-input" @checked(old('remember'))>
                    <span class="form-check-label">Remember me</span>
                </label>
                <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>

            <a href="/auth/google/redirect" class="btn btn-outline-dark w-100 mt-2">
                <i class="bi bi-google me-2"></i>Continue with Google
            </a>

            <hr>
            <small class="text-muted d-block">New here? Accounts are set up by our staff when you make a purchase in-store.</small>
        </div>
    </div>
@endsection
