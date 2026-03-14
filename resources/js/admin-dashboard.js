let adminMonitoringChart = null;
let adminMonitoringSlides = [];

document.addEventListener("DOMContentLoaded", async () => {
    await renderAdminRealtimeMonitoring();
    await renderAdminMonitoringCarousel();
    setupAdminProfileModal();
});

async function renderAdminRealtimeMonitoring() {
    try {
        const response = await fetch("/api/admin/dashboard/realtime");
        const data = await response.json();

        renderAdminEnvironment(data.environment || []);
        renderAdminResources(data.resources || []);
    } catch (error) {
        console.error("Failed to load admin realtime dashboard data.", error);
    }
}

function renderAdminEnvironment(items) {
    const grid = document.getElementById("adminEnvironmentGrid");
    if (!grid || !items.length) return;

    grid.innerHTML = items
        .map((item, index) => {
            const degrees = getAdminGaugeDegrees(
                item.value,
                item.min,
                item.max,
            );

            return `
            <div class="admin-sensor-card">
                <div class="admin-radial-gauge ${item.status}" style="--gauge-value: ${degrees}deg;">
                    <div class="admin-radial-gauge-inner">
                        <span class="admin-sensor-value">${item.value}${item.unit}</span>
                    </div>
                </div>
                <div class="admin-sensor-label">${item.label}</div>
            </div>
            ${index === 0 ? '<div class="admin-panel-divider"></div>' : ""}
        `;
        })
        .join("");
}

function renderAdminResources(items) {
    const grid = document.getElementById("adminResourceGrid");
    if (!grid || !items.length) return;

    grid.innerHTML = items
        .map((item, index) => {
            const safeValue = Math.max(0, Math.min(item.value, 100));
            const isWater = item.type === "water";

            return `
            <div class="admin-resource-card">
                <div class="admin-resource-bar-shell">
                    <div class="admin-resource-bar ${isWater ? "admin-water-bar" : "admin-feed-bar"}" style="height: ${safeValue}%;"></div>
                </div>
                <div class="admin-resource-percent ${isWater ? "admin-water-text" : ""}">${safeValue}${item.unit}</div>
                <div class="admin-resource-label">${item.label}</div>
            </div>
            ${index === 0 ? '<div class="admin-panel-divider"></div>' : ""}
        `;
        })
        .join("");
}

function getAdminGaugeDegrees(value, min, max) {
    const range = max - min;
    if (range <= 0) return 0;

    const percent = Math.max(0, Math.min((value - min) / range, 1));
    return percent * 360;
}

async function renderAdminMonitoringCarousel() {
    const canvas = document.getElementById("adminMonitoringChart");
    const caption = document.getElementById("adminGraphCaption");
    const dots = document.querySelectorAll(".admin-graph-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    try {
        const response = await fetch("/api/admin/dashboard/monitoring-graphs");
        const data = await response.json();

        adminMonitoringSlides = Array.isArray(data.slides) ? data.slides : [];
        if (!adminMonitoringSlides.length) return;

        createOrUpdateAdminChart(canvas, adminMonitoringSlides[0]);
        updateAdminGraphCaption(caption, adminMonitoringSlides[0]);
        updateAdminDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !adminMonitoringSlides[index])
                    return;

                createOrUpdateAdminChart(canvas, adminMonitoringSlides[index]);
                updateAdminGraphCaption(caption, adminMonitoringSlides[index]);
                updateAdminDots(dots, index);
            });
        });
    } catch (error) {
        console.error("Failed to load admin monitoring graph data.", error);
    }
}

function createOrUpdateAdminChart(canvas, slide) {
    if (adminMonitoringChart) {
        adminMonitoringChart.destroy();
    }

    adminMonitoringChart = new Chart(canvas, {
        type: "line",
        data: {
            labels: slide.labels,
            datasets: [
                {
                    label: slide.label,
                    data: slide.values,
                    borderColor: slide.borderColor,
                    backgroundColor: slide.backgroundColor,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: slide.borderColor,
                    pointBorderColor: slide.borderColor,
                    borderWidth: 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
        },
    });
}

function updateAdminGraphCaption(caption, slide) {
    if (caption) {
        caption.textContent = slide.label;
    }
}

function updateAdminDots(dots, activeIndex) {
    dots.forEach((dot, index) => {
        dot.classList.toggle("active", index === activeIndex);
    });
}

function setupAdminProfileModal() {
    const modal = document.getElementById("adminProfileModal");
    const openButton = document.getElementById("openAdminProfileModal");
    const closeButton = document.getElementById("closeAdminProfileModal");

    if (openButton && modal) {
        openButton.addEventListener("click", () => {
            modal.classList.add("show");
            document.body.style.overflow = "hidden";
        });
    }

    if (closeButton && modal) {
        closeButton.addEventListener("click", () => {
            modal.classList.remove("show");
            document.body.style.overflow = "";
        });
    }

    if (modal) {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.classList.remove("show");
                document.body.style.overflow = "";
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                modal.classList.remove("show");
                document.body.style.overflow = "";
            }
        });
    }
}
