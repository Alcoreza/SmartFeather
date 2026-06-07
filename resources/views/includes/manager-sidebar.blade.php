<aside class="manager-sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <div class="sidebar-logo">
                <img src="{{ asset('images/AppLogoSmartFeather.png') }}" alt="SmartFeather">
            </div>

            <button type="button" class="sidebar-menu-toggle" data-sidebar-toggle aria-label="Open navigation" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div class="sidebar-divider"></div>

        <nav class="sidebar-nav">
            <a href="{{ route('manager.dashboard') }}"
                class="sidebar-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('manager.houses') }}"
                class="sidebar-link {{ request()->routeIs('manager.houses') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"></path><path d="M5 9.5V21h14V9.5"></path><path d="M9 21v-7h6v7"></path></svg>
                </span>
                <span>Farm Management</span>
            </a>

            <a href="{{ route('manager.workers') }}"
                class="sidebar-link {{ request()->routeIs('manager.workers') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <span>Workers</span>
            </a>

            <a href="{{ route('manager.tasks') }}"
                class="sidebar-link {{ request()->routeIs('manager.tasks') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </span>
                <span>Tasks</span>
            </a>

            <a href="{{ route('manager.inventory') }}"
                class="sidebar-link {{ request()->routeIs('manager.inventory') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                </span>
                <span>Inventory</span>
            </a>

            <a href="{{ route('manager.sensors') }}"
                class="sidebar-link {{ request()->routeIs('manager.sensors') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 20h.01"></path><path d="M8.5 16.5a5 5 0 0 1 7 0"></path><path d="M5 13a10 10 0 0 1 14 0"></path><path d="M2 9.5a15 15 0 0 1 20 0"></path></svg>
                </span>
                <span>Sensors</span>
            </a>

            <a href="{{ route('manager.biosecurity-logs') }}"
                class="sidebar-link {{ request()->routeIs('manager.biosecurity-logs') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                </span>
                <span>Biosecurity Logs</span>
            </a>

            <a href="{{ route('manager.farm-activity-records') }}"
                class="sidebar-link {{ request()->routeIs('manager.farm-activity-records') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path></svg>
                </span>
                <span>Farm Activity Records</span>
            </a>

            <a href="{{ route('manager.reports') }}"
                class="sidebar-link {{ request()->routeIs('manager.reports') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>
                </span>
                <span>Reports</span>
            </a>

            <a href="{{ route('manager.profile') }}"
                class="sidebar-link {{ request()->routeIs('manager.profile') ? 'active' : '' }}">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <span>Profile</span>
            </a>

            <div class="sidebar-footer">
                <a href="{{ route('logout') }}" class="sidebar-link sidebar-logout" data-logout-trigger>
                    <span class="sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
                    </span>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>
</aside>
