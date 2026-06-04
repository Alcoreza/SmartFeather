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
});
