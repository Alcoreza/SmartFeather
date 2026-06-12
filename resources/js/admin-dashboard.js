let adminMonitoringChart = null;
let adminMonitoringSlides = [];
let adminEnvironmentChart = null;
let adminEnvironmentSlides = [];
let activeAdminEnvironmentSlideIndex = 0;
let adminResourceChart = null;
let adminResourceSlides = [];
let activeAdminResourceSlideIndex = 0;

document.addEventListener("DOMContentLoaded", async () => {
    resetInitialAdminRealtimeWidgets();

    await renderAdminRealtimeMonitoring();
    await renderAdminMonitoringCarousel();
    await renderAdminEnvironmentCarousel();
    await renderAdminResourceCarousel();
    setupAdminProfileModal();
});

function resetInitialAdminRealtimeWidgets() {
    document.querySelectorAll(".admin-radial-gauge").forEach((gauge) => {
        gauge.style.setProperty("--gauge-value", "0deg");
    });

    document.querySelectorAll(".admin-resource-bar").forEach((bar) => {
        bar.style.height = "0%";
    });
}

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
                <div class="admin-radial-gauge ${item.status}" data-gauge-value="${degrees}" style="--gauge-value: 0deg;">
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

    animateAdminRealtimeGauges(grid);
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
                    <div class="admin-resource-bar ${isWater ? "admin-water-bar" : "admin-feed-bar"}" data-bar-height="${safeValue}" style="height: 0%;"></div>
                </div>
                <div class="admin-resource-percent ${isWater ? "admin-water-text" : ""}">${safeValue}${item.unit}</div>
                <div class="admin-resource-label">${item.label}</div>
            </div>
            ${index === 0 ? '<div class="admin-panel-divider"></div>' : ""}
        `;
        })
        .join("");

    animateAdminResourceBars(grid);
}

function animateAdminRealtimeGauges(container) {
    const gauges = container.querySelectorAll("[data-gauge-value]");

    gauges.forEach((gauge, index) => {
        const target = Number(gauge.dataset.gaugeValue || 0);
        animateAdminGaugeValue(gauge, target, 900, index * 120);
    });
}

function animateAdminResourceBars(container) {
    const bars = container.querySelectorAll("[data-bar-height]");

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            bars.forEach((bar, index) => {
                setTimeout(() => {
                    bar.style.height = `${bar.dataset.barHeight || 0}%`;
                }, index * 120);
            });
        });
    });
}

function animateAdminGaugeValue(gauge, target, duration = 900, delay = 0) {
    const startTime = performance.now() + delay;

    function tick(now) {
        if (now < startTime) {
            requestAnimationFrame(tick);
            return;
        }

        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        gauge.style.setProperty("--gauge-value", `${target * eased}deg`);

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    }

    requestAnimationFrame(tick);
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
    const dots = document.querySelectorAll(".admin-monitoring-dot");

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

    adminMonitoringChart = createAdminBarChart(canvas, slide);
}

function createAdminBarChart(canvas, slide, maxValue = null) {
    const context = canvas.getContext("2d");
    const barGradient = createAdminBarGradient(context, canvas, slide.borderColor);

    return new Chart(canvas, {
        type: "bar",
        data: {
            labels: slide.labels,
            datasets: [
                {
                    label: slide.label,
                    data: slide.values,
                    borderColor: slide.borderColor,
                    backgroundColor: barGradient || slide.backgroundColor,
                    hoverBackgroundColor: slide.borderColor,
                    borderWidth: 0,
                    borderRadius: 12,
                    borderSkipped: false,
                    maxBarThickness: 42,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: "easeOutCubic",
                delay(context) {
                    if (context.type !== "data" || context.mode !== "default") {
                        return 0;
                    }

                    return context.dataIndex * 95;
                },
            },
            animations: {
                y: {
                    from(context) {
                        const chart = context.chart;
                        const scale = chart.scales.y;

                        return scale ? scale.getPixelForValue(0) : chart.chartArea.bottom;
                    },
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "rgba(6, 51, 32, 0.94)",
                    borderColor: "rgba(184, 239, 189, 0.32)",
                    borderWidth: 1,
                    cornerRadius: 12,
                    displayColors: false,
                    padding: 12,
                    titleColor: "#ffffff",
                    bodyColor: "#e9f7ec",
                    callbacks: {
                        label(context) {
                            return `${slide.label}: ${context.parsed.y}${slide.unit || ""}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                    ticks: {
                        color: "#687068",
                        font: {
                            weight: 700,
                        },
                    },
                },
                y: {
                    beginAtZero: true,
                    ...(maxValue ? { max: maxValue } : {}),
                    border: {
                        display: false,
                    },
                    grid: {
                        color: "rgba(90, 96, 90, 0.12)",
                    },
                    ticks: {
                        color: "#687068",
                        padding: 8,
                    },
                },
            },
        },
    });
}

function createAdminBarGradient(context, canvas, color) {
    if (!context) {
        return null;
    }

    const height = canvas.offsetHeight || canvas.height || 280;
    const gradient = context.createLinearGradient(0, 0, 0, height);

    if (color === "#b7791f") {
        gradient.addColorStop(0, "rgba(217, 158, 62, 0.96)");
        gradient.addColorStop(0.58, "rgba(183, 121, 31, 0.78)");
        gradient.addColorStop(1, "rgba(183, 121, 31, 0.3)");
        return gradient;
    }

    gradient.addColorStop(0, "rgba(41, 132, 77, 0.96)");
    gradient.addColorStop(0.58, "rgba(23, 100, 58, 0.78)");
    gradient.addColorStop(1, "rgba(23, 100, 58, 0.3)");
    return gradient;
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

async function renderAdminEnvironmentCarousel() {
    const canvas = document.getElementById("adminEnvironmentChart");
    const caption = document.getElementById("adminEnvGraphCaption");
    const dots = document.querySelectorAll(".admin-env-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    try {
        const response = await fetch("/api/admin/dashboard/environment-by-house");
        const data = await response.json();

        adminEnvironmentSlides = Array.isArray(data.slides) ? data.slides : [];
        if (!adminEnvironmentSlides.length) return;

        createOrUpdateAdminEnvironmentChart(canvas, adminEnvironmentSlides[0]);
        updateAdminGraphCaption(caption, adminEnvironmentSlides[0]);
        updateAdminDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !adminEnvironmentSlides[index]) return;

                activeAdminEnvironmentSlideIndex = index;
                createOrUpdateAdminEnvironmentChart(canvas, adminEnvironmentSlides[index]);
                updateAdminGraphCaption(caption, adminEnvironmentSlides[index]);
                updateAdminDots(dots, index);
            });
        });
    } catch (error) {
        console.error("Failed to load admin environment monitoring data.", error);
    }
}

function createOrUpdateAdminEnvironmentChart(canvas, slide) {
    if (adminEnvironmentChart) {
        adminEnvironmentChart.destroy();
    }

    adminEnvironmentChart = createAdminBarChart(canvas, slide, slide.maxValue || 35);
}

async function renderAdminResourceCarousel() {
    const canvas = document.getElementById("adminResourceChart");
    const caption = document.getElementById("adminResourceGraphCaption");
    const dots = document.querySelectorAll(".admin-resource-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    try {
        const response = await fetch("/api/admin/dashboard/resources-by-house");
        const data = await response.json();

        adminResourceSlides = Array.isArray(data.slides) ? data.slides : [];
        if (!adminResourceSlides.length) return;

        createOrUpdateAdminResourceChart(canvas, adminResourceSlides[0]);
        updateAdminGraphCaption(caption, adminResourceSlides[0]);
        updateAdminDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !adminResourceSlides[index]) return;

                activeAdminResourceSlideIndex = index;
                createOrUpdateAdminResourceChart(canvas, adminResourceSlides[index]);
                updateAdminGraphCaption(caption, adminResourceSlides[index]);
                updateAdminDots(dots, index);
            });
        });
    } catch (error) {
        console.error("Failed to load admin resource monitoring data.", error);
    }
}

function createOrUpdateAdminResourceChart(canvas, slide) {
    if (adminResourceChart) {
        adminResourceChart.destroy();
    }

    adminResourceChart = createAdminBarChart(canvas, slide, slide.maxValue || 100);
}

async function populateAdminProfileModal() {
    try {
        const response = await fetch('/api/user');
        if (!response.ok) throw new Error('Failed to fetch user info');
        const user = await response.json();
        document.getElementById('profileFirstName').value = user.FirstName || '';
        document.getElementById('profileMiddleName').value = user.MiddleName || '';
        document.getElementById('profileLastName').value = user.LastName || '';
        document.getElementById('profileSuffix').value = user.Suffix || '';
        document.getElementById('profileRole').value = user.Role || '';
        document.getElementById('profilePhone').value = user.PhoneNumber || '';
        document.getElementById('profileId').value = user.EmployeeId || '';
        document.getElementById('profileBirthday').value = user.Birthday || '';
        document.getElementById('profileGender').value = user.Gender || '';
        document.getElementById('profileAddress').value = user.Address || '';
    } catch (e) {
        // Optionally show error
    }
}

function setupAdminProfileModal() {
    const modal = document.getElementById("adminProfileModal");
    const openButton = document.getElementById("openAdminProfileModal");
    const closeButton = document.getElementById("closeAdminProfileModal");

    if (openButton && modal) {
        openButton.addEventListener("click", async () => {
            await populateAdminProfileModal();
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
