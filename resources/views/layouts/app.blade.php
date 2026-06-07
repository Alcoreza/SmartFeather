<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laravel App')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/AppLogoSmartFeather-favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/AppLogoSmartFeather-favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    @if (request()->routeIs('login'))
        <link rel="preload" as="image" href="{{ asset('images/backgrounds/poultry-login-bg.jpg') }}" fetchpriority="high">
    @elseif (request()->is('manager/*') || request()->is('admin/*'))
        <link rel="preload" as="image" href="{{ asset('images/backgrounds/poultry-dashboard-bg.jpg') }}" fetchpriority="high">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>
    <main>
        @yield('content')
    </main>

    <div class="logout-confirm-backdrop" id="logoutConfirmModal" aria-hidden="true">
        <div class="logout-confirm-card" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle">
            <div class="logout-confirm-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <path d="M16 17l5-5-5-5"></path>
                    <path d="M21 12H9"></path>
                </svg>
            </div>
            <h2 id="logoutConfirmTitle">Log out?</h2>
            <p>You will need to sign in again to continue using SmartFeather.</p>
            <div class="logout-confirm-actions">
                <button type="button" class="logout-confirm-btn cancel" id="cancelLogoutBtn">Cancel</button>
                <button type="button" class="logout-confirm-btn confirm" id="confirmLogoutBtn">Log out</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @stack('scripts')
</body>

</html>
