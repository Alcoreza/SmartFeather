document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");
    const button = form?.querySelector('button[type="submit"]');

    if (!form || !button) {
        return;
    }

    form.addEventListener("submit", () => {
        button.classList.add("opacity-75");
        button.textContent = "Loading...";
    });
});
