document.addEventListener("DOMContentLoaded", () => {
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
            window.location.href = pendingLogoutUrl;
        }
    });

    logoutModal?.addEventListener("click", (event) => {
        if (event.target === logoutModal) {
            closeLogoutModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && logoutModal?.classList.contains("show")) {
            closeLogoutModal();
        }
    });
});
