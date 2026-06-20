document.addEventListener("DOMContentLoaded", () => {
    const LOGGED_OUT_FLAG = "smartfeather:logged-out";

    function isProtectedAppPage() {
        return window.location.pathname.startsWith("/manager/")
            || window.location.pathname.startsWith("/admin/");
    }

    function hideProtectedPage() {
        if (isProtectedAppPage()) {
            document.documentElement.style.visibility = "hidden";
        }
    }

    function showProtectedPage() {
        document.documentElement.style.visibility = "";
    }

    async function redirectIfSessionExpired(options = {}) {
        if (!isProtectedAppPage()) return;

        if (options.hideWhileChecking) {
            hideProtectedPage();
        }

        if (sessionStorage.getItem(LOGGED_OUT_FLAG) === "1") {
            window.location.replace("/login");
            return;
        }

        try {
            const response = await fetch("/api/user", {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                cache: "no-store",
                credentials: "same-origin",
            });

            if (response.status === 401) {
                sessionStorage.setItem(LOGGED_OUT_FLAG, "1");
                window.location.replace("/login");
                return;
            }

            showProtectedPage();
        } catch (error) {
            console.error("Session check failed.", error);
            window.location.replace("/login");
        }
    }

    window.addEventListener("pagehide", () => {
        hideProtectedPage();
    });

    window.addEventListener("pageshow", (event) => {
        const navigationEntry = performance.getEntriesByType("navigation")[0];
        const restoredFromHistory = event.persisted || navigationEntry?.type === "back_forward";

        if (restoredFromHistory) {
            redirectIfSessionExpired({ hideWhileChecking: true });
        }
    });

    document.querySelectorAll(".sidebar-nav, .admin-sidebar-nav").forEach((nav) => {
        let scrollTimer = null;

        nav.addEventListener("scroll", () => {
            nav.classList.add("is-scrolling");

            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(() => {
                nav.classList.remove("is-scrolling");
            }, 700);
        });
    });

    const sidebarToggles = document.querySelectorAll("[data-sidebar-toggle]");

    function closeResponsiveSidebars() {
        document.querySelectorAll(".manager-sidebar.is-open, .admin-sidebar.is-open").forEach((sidebar) => {
            sidebar.classList.remove("is-open");
            sidebar.querySelector("[data-sidebar-toggle]")?.setAttribute("aria-expanded", "false");
        });
    }

    sidebarToggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const sidebar = toggle.closest(".manager-sidebar, .admin-sidebar");
            if (!sidebar) return;

            const isOpen = sidebar.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", String(isOpen));
        });
    });

    const flockBatchToggle = document.querySelector("[data-flock-batch-toggle]");
    const flockBatchPanel = document.querySelector(".houses-side-column");
    const flockBatchPlaceholder = flockBatchPanel ? document.createComment("flock-batch-panel-placeholder") : null;

    if (flockBatchPanel && flockBatchPlaceholder) {
        flockBatchPanel.before(flockBatchPlaceholder);
    }

    function restoreFlockBatchPanel() {
        if (!flockBatchPanel || !flockBatchPlaceholder?.parentNode) return;

        flockBatchPlaceholder.parentNode.insertBefore(flockBatchPanel, flockBatchPlaceholder.nextSibling);
        flockBatchPanel.removeAttribute("style");
    }

    function positionFlockBatchPanel() {
        if (!flockBatchPanel || !flockBatchToggle) return;

        const isMobile = window.matchMedia("(max-width: 768px)").matches;

        if (!isMobile) {
            restoreFlockBatchPanel();
            return;
        }

        if (flockBatchPanel.parentNode !== document.body) {
            document.body.appendChild(flockBatchPanel);
        }

        const toggleRect = flockBatchToggle.getBoundingClientRect();
        const right = Math.max(12, window.innerWidth - toggleRect.right);
        flockBatchPanel.style.right = `${right}px`;
        flockBatchPanel.style.bottom = "auto";
        flockBatchPanel.style.maxHeight = "none";

        const visiblePanel = flockBatchPanel.querySelector(".cycle-panel") || flockBatchPanel;
        const panelHeight = visiblePanel.offsetHeight;
        const top = Math.max(12, toggleRect.top - panelHeight - 10);

        flockBatchPanel.style.top = `${top}px`;
    }

    function closeFlockBatchPanel() {
        if (!flockBatchPanel || !flockBatchToggle) return;

        flockBatchPanel.classList.remove("is-floating-open");
        flockBatchToggle.classList.remove("is-active");
        flockBatchToggle.setAttribute("aria-expanded", "false");
        if (!window.matchMedia("(max-width: 768px)").matches) {
            restoreFlockBatchPanel();
        }
    }

    flockBatchToggle?.addEventListener("click", () => {
        if (!flockBatchPanel) return;

        const isOpen = !flockBatchPanel.classList.contains("is-floating-open");

        if (isOpen) {
            positionFlockBatchPanel();
        }

        flockBatchPanel.classList.toggle("is-floating-open", isOpen);
        flockBatchToggle.classList.toggle("is-active", isOpen);
        flockBatchToggle.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (event) => {
        if (!flockBatchPanel?.classList.contains("is-floating-open")) return;
        if (flockBatchPanel.contains(event.target) || flockBatchToggle?.contains(event.target)) return;

        closeFlockBatchPanel();
    });

    window.addEventListener("resize", () => {
        if (!window.matchMedia("(max-width: 768px)").matches) {
            closeFlockBatchPanel();
            restoreFlockBatchPanel();
            return;
        }

        if (flockBatchPanel?.classList.contains("is-floating-open")) {
            positionFlockBatchPanel();
        }
    });

    window.addEventListener("scroll", () => {
        if (flockBatchPanel?.classList.contains("is-floating-open")) {
            positionFlockBatchPanel();
        }
    }, { passive: true });

    document.querySelectorAll(".sidebar-nav a, .admin-sidebar-nav a").forEach((link) => {
        link.addEventListener("click", () => {
            if (window.matchMedia("(max-width: 992px)").matches && !link.matches("[data-logout-trigger]")) {
                closeResponsiveSidebars();
            }
        });
    });

    const logoutModal = document.getElementById("logoutConfirmModal");
    const cancelLogoutBtn = document.getElementById("cancelLogoutBtn");
    const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");
    let pendingLogoutUrl = null;

    function openLogoutModal(url) {
        if (!logoutModal || !confirmLogoutBtn) {
            window.location.href = url;
            return;
        }

        pendingLogoutUrl = url;
        logoutModal.classList.add("show");
        logoutModal.setAttribute("aria-hidden", "false");
        document.body.classList.add("logout-confirm-open");
        cancelLogoutBtn?.focus();
    }

    function closeLogoutModal() {
        if (!logoutModal) return;

        logoutModal.classList.remove("show");
        logoutModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("logout-confirm-open");
        pendingLogoutUrl = null;
    }

    document.querySelectorAll("[data-logout-trigger]").forEach((trigger) => {
        trigger.addEventListener("click", (event) => {
            const url = trigger.getAttribute("data-logout-url") || trigger.getAttribute("href");
            if (!url) return;

            event.preventDefault();
            openLogoutModal(url);
        });
    });

    cancelLogoutBtn?.addEventListener("click", closeLogoutModal);

    confirmLogoutBtn?.addEventListener("click", () => {
        if (pendingLogoutUrl) {
            sessionStorage.setItem(LOGGED_OUT_FLAG, "1");
            hideProtectedPage();
            window.location.href = pendingLogoutUrl;
        }
    });

    logoutModal?.addEventListener("click", (event) => {
        if (event.target === logoutModal) {
            closeLogoutModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeResponsiveSidebars();
            closeFlockBatchPanel();
        }

        if (event.key === "Escape" && logoutModal?.classList.contains("show")) {
            closeLogoutModal();
        }
    });
});
