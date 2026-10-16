<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Anatech')</title>
    <link rel="icon" href="{{ asset('images/logo-mark.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    @vite('resources/css/storefront.css')
</head>
<body>
    <nav class="site-nav py-3 mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('catalog.index') }}" class="brand">
                <img src="{{ asset('images/logo-wordmark.png') }}" alt="Anatech" class="brand-logo">
            </a>

            @auth
                <div class="d-flex align-items-center flex-wrap gap-2 justify-content-end">
                    <span class="text-muted me-2">Hi, {{ auth()->user()->name }}</span>
                    @if (auth()->user()->isAdmin())
                        <a href="/admin" class="btn btn-dark btn-sm">Admin Panel</a>
                    @endif
                    <a href="{{ route('account.purchases') }}" class="btn btn-outline-dark btn-sm">My Purchases</a>
                    <a href="{{ route('account.reviews') }}" class="btn btn-outline-dark btn-sm">My Reviews</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Login</a>
            @endauth
        </div>
    </nav>

    <main class="container pb-5">
        @if (session('status'))
            <div class="alert alert-info">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
