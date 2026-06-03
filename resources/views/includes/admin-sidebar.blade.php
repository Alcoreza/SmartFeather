<aside class="admin-sidebar">
    <div class="admin-sidebar-top">
        <div class="admin-sidebar-brand">
            <div class="admin-sidebar-logo">
                <img src="{{ asset('images/AppLogoSmartFeather.png') }}" alt="SmartFeather">
            </div>
        </div>

        <div class="admin-sidebar-divider"></div>

        <nav class="admin-sidebar-nav">
            <a href="{{ route('admin.dashboard') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="admin-sidebar-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.houses') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.houses') ? 'active' : '' }}">
                <span class="admin-sidebar-icon">⌂</span>
                <span>Houses</span>
            </a>

            <a href="{{ route('admin.workers') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.workers') ? 'active' : '' }}">
                <span class="admin-sidebar-icon">◉</span>
                <span>Workers</span>
            </a>

            <a href="{{ route('admin.sensors') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.sensors') || request()->routeIs('admin.sensor-maintenance') ? 'active' : '' }}">
                <span class="sidebar-icon">⌁</span>
                <span>Sensors</span>
            </a>

            <a href="{{ route('admin.profile') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                <span class="admin-sidebar-icon">👤</span>
                <span>Profile</span>
            </a>

            <div style="margin-top: 48px; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 28px;">
            <a href="{{ route('logout') }}" class="admin-sidebar-link" style="color: #fff; background: #c91c16; border-radius: 20px; display: flex; align-items: center; gap: 14px; font-weight: 600;">
                <span class="admin-sidebar-icon">⎋</span>
                <span>Logout</span>
            </a>
        </div>
        </nav>
    </div>
</aside>
