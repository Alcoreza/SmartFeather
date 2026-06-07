<aside class="admin-sidebar">
    <div class="admin-sidebar-top">
        <div class="admin-sidebar-brand">
            <div class="admin-sidebar-logo">
                <img src="{{ asset('images/AppLogoSmartFeather.png') }}" alt="SmartFeather">
            </div>

            <button type="button" class="admin-sidebar-menu-toggle" data-sidebar-toggle aria-label="Open navigation" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div class="admin-sidebar-divider"></div>

        <nav class="admin-sidebar-nav">
            <a href="{{ route('admin.dashboard') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="admin-sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.houses') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.houses') ? 'active' : '' }}">
                <span class="admin-sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"></path><path d="M5 9.5V21h14V9.5"></path><path d="M9 21v-7h6v7"></path></svg>
                </span>
                <span>Farm Management</span>
            </a>

            <a href="{{ route('admin.workers') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.workers') ? 'active' : '' }}">
                <span class="admin-sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <span>Workers</span>
            </a>

            <a href="{{ route('admin.sensors') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.sensors') || request()->routeIs('admin.sensor-maintenance') ? 'active' : '' }}">
                <span class="admin-sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 20h.01"></path><path d="M8.5 16.5a5 5 0 0 1 7 0"></path><path d="M5 13a10 10 0 0 1 14 0"></path><path d="M2 9.5a15 15 0 0 1 20 0"></path></svg>
                </span>
                <span>Sensors</span>
            </a>

            <a href="{{ route('admin.profile') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                <span class="admin-sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <span>Profile</span>
            </a>

            <div class="admin-sidebar-footer">
                <a href="{{ route('logout') }}" class="admin-sidebar-link admin-sidebar-logout" data-logout-trigger>
                    <span class="admin-sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
                    </span>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>
</aside>
