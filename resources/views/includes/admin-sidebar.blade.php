<aside class="admin-sidebar">
    <div class="admin-sidebar-top">
        <div class="admin-sidebar-brand">
            <div class="admin-sidebar-logo">F</div>
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

            <a href="#" class="admin-sidebar-link">
                <span class="admin-sidebar-icon">⌁</span>
                <span>Sensors</span>
            </a>
        </nav>
    </div>
</aside>