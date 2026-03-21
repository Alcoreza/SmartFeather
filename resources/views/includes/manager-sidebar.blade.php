<aside class="manager-sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <div class="sidebar-logo">F</div>
        </div>

        <div class="sidebar-divider"></div>

        <nav class="sidebar-nav">
            <a href="{{ route('manager.dashboard') }}"
                class="sidebar-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}">
                <span class="sidebar-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('manager.houses') }}"
                class="sidebar-link {{ request()->routeIs('manager.houses') ? 'active' : '' }}">
                <span class="sidebar-icon">⌂</span>
                <span>Houses</span>
            </a>

            <a href="{{ route('manager.workers') }}"
                class="sidebar-link {{ request()->routeIs('manager.workers') ? 'active' : '' }}">
                <span class="sidebar-icon">◉</span>
                <span>Workers</span>
            </a>

            <a href="{{ route('manager.tasks') }}"
                class="sidebar-link {{ request()->routeIs('manager.tasks') ? 'active' : '' }}">
                <span class="sidebar-icon">✓</span>
                <span>Tasks</span>
            </a>

            <a href="{{ route('manager.inventory') }}"
                class="sidebar-link {{ request()->routeIs('manager.inventory') ? 'active' : '' }}">
                <span class="sidebar-icon">▣</span>
                <span>Inventory</span>
            </a>

            <a href="#" class="sidebar-link">
                <span class="sidebar-icon">⌁</span>
                <span>Sensors</span>
            </a>

            <a href="#" class="sidebar-link">
                <span class="sidebar-icon">▤</span>
                <span>Biosecurity Logs</span>
            </a>

            <a href="#" class="sidebar-link">
                <span class="sidebar-icon">▥</span>
                <span>Reports</span>
            </a>
        </nav>
    </div>
</aside>