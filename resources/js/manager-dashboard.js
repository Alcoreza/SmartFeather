let monitoringChart = null;
let monitoringSlides = [];
let activeSlideIndex = 0;
let activeMonitoringHouseIndex = 0;

let environmentChart = null;
let environmentSlides = [];
let activeEnvironmentSlideIndex = 0;

let resourceChart = null;
let resourceSlides = [];
let activeResourceSlideIndex = 0;

document.addEventListener("DOMContentLoaded", () => {
    resetInitialRealtimeWidgets();

    const cards = document.querySelectorAll(".dashboard-card");

    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(18px)";

        setTimeout(
            () => {
                card.style.transition =
                    "opacity 0.45s ease, transform 0.45s ease";
                card.style.opacity = "1";
                card.style.transform = "translateY(0)";
            },
            120 + index * 100,
        );
    });

    renderRealtimeMonitoring();
    renderMonitoringCarousel();
    renderHouseSensorSummaryCarousels();
});

function resetInitialRealtimeWidgets() {
    document.querySelectorAll(".radial-gauge").forEach((gauge) => {
        gauge.style.setProperty("--gauge-value", "0deg");
    });

    document.querySelectorAll(".resource-bar").forEach((bar) => {
        bar.style.height = "0%";
    });
}

async function renderRealtimeMonitoring() {
    try {
        const response = await fetch("/api/manager/dashboard/realtime");
        const data = await response.json();

        renderEnvironment(data.environment || []);
        renderResources(data.resources || []);
    } catch (error) {
        console.error("Failed to load realtime dashboard data.", error);
    }
}

function renderEnvironment(items) {
    const grid = document.getElementById("environmentGrid");

    if (!grid || !items.length) {
        return;
    }

    grid.innerHTML = items
        .map((item, index) => {
            const degrees = getGaugeDegrees(item.value, item.min, item.max);

            return `
            <div class="sensor-card">
                <div class="radial-gauge ${item.status}" data-gauge-value="${degrees}" style="--gauge-value: 0deg;">
                    <div class="radial-gauge-inner">
                        <span class="sensor-value">${item.value}${item.unit}</span>
                    </div>
                </div>
                <div class="sensor-label">${item.label}</div>
            </div>
            ${index === 0 ? '<div class="panel-divider"></div>' : ""}
        `;
        })
        .join("");

    animateRealtimeGauges(grid);
}

function renderResources(items) {
    const grid = document.getElementById("resourceGrid");

    if (!grid || !items.length) {
        return;
    }

    grid.innerHTML = items
        .map((item, index) => {
            const safeValue = Math.max(0, Math.min(item.value, 100));
            const isWater = item.type === "water";

            return `
            <div class="resource-card">
                <div class="resource-bar-shell">
                    <div class="resource-bar ${isWater ? "water-bar" : "feed-bar"}" data-bar-height="${safeValue}" style="height: 0%;"></div>
                </div>
                <div class="resource-percent ${isWater ? "water-text" : ""}">${safeValue}${item.unit}</div>
                <div class="resource-label">${item.label}</div>
            </div>
            ${index === 0 ? '<div class="panel-divider"></div>' : ""}
        `;
        })
        .join("");

    animateResourceBars(grid);
}

function animateRealtimeGauges(container) {
    const gauges = container.querySelectorAll("[data-gauge-value]");

    gauges.forEach((gauge, index) => {
        const target = Number(gauge.dataset.gaugeValue || 0);
        animateGaugeValue(gauge, target, 900, index * 120);
    });
}

function animateResourceBars(container) {
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

function animateGaugeValue(gauge, target, duration = 900, delay = 0) {
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

function getGaugeDegrees(value, min, max) {
    const range = max - min;

    if (range <= 0) {
        return 0;
    }

    const percent = Math.max(0, Math.min((value - min) / range, 1));
    return percent * 360;
}

async function renderMonitoringCarousel() {
    const canvas = document.getElementById("monitoringChart");
    const caption = document.getElementById("graphCaption");
    const dots = document.querySelectorAll(".monitoring-dot");
    const pager = document.getElementById("houseGraphPager");
    const pagerDots = document.getElementById("houseGraphPageDots");
    const prevButton = document.getElementById("houseGraphPrev");
    const nextButton = document.getElementById("houseGraphNext");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    try {
        const response = await fetch(
            "/api/manager/dashboard/monitoring-graphs",
        );
        const data = await response.json();

        monitoringSlides = Array.isArray(data.slides) ? data.slides : [];

        if (!monitoringSlides.length) {
            return;
        }

        renderMonitoringHousePage(canvas, caption, pager, pagerDots, prevButton, nextButton);
        updateDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !monitoringSlides[index]) {
                    return;
                }

                activeSlideIndex = index;
                renderMonitoringHousePage(canvas, caption, pager, pagerDots, prevButton, nextButton);
                updateDots(dots, index);
            });
        });

        prevButton?.addEventListener("click", () => {
            activeMonitoringHouseIndex = Math.max(0, activeMonitoringHouseIndex - 1);
            renderMonitoringHousePage(canvas, caption, pager, pagerDots, prevButton, nextButton);
        });

        nextButton?.addEventListener("click", () => {
            const houseCount = getMonitoringHouseCount(monitoringSlides[activeSlideIndex]);
            activeMonitoringHouseIndex = Math.min(houseCount - 1, activeMonitoringHouseIndex + 1);
            renderMonitoringHousePage(canvas, caption, pager, pagerDots, prevButton, nextButton);
        });

        pagerDots?.addEventListener("click", (event) => {
            const dot = event.target.closest("[data-house-graph-page]");

            if (!dot) {
                return;
            }

            activeMonitoringHouseIndex = Number(dot.dataset.houseGraphPage || 0);
            renderMonitoringHousePage(canvas, caption, pager, pagerDots, prevButton, nextButton);
        });
    } catch (error) {
        console.error("Failed to load monitoring graph data.", error);
    }
}

function renderMonitoringHousePage(canvas, caption, pager, pagerDots, prevButton, nextButton) {
    const slide = monitoringSlides[activeSlideIndex];

    if (!slide) {
        return;
    }

    const houseCount = getMonitoringHouseCount(slide);
    activeMonitoringHouseIndex = Math.max(0, Math.min(activeMonitoringHouseIndex, houseCount - 1));

    const pageSlide = getMonitoringHousePageSlide(slide, activeMonitoringHouseIndex);

    createOrUpdateChart(canvas, pageSlide);
    updateGraphCaption(caption, pageSlide);
    updateMonitoringHousePager(pager, pagerDots, prevButton, nextButton, pageSlide, houseCount);
}

function getMonitoringHouseCount(slide) {
    return Array.isArray(slide?.datasets) && slide.datasets.length ? slide.datasets.length : 1;
}

function getMonitoringHousePageSlide(slide, houseIndex) {
    if (!Array.isArray(slide.datasets) || !slide.datasets.length) {
        return slide;
    }

    const dataset = slide.datasets[houseIndex] || slide.datasets[0];

    return {
        ...slide,
        houseLabel: dataset.label,
        datasets: [dataset],
    };
}

function updateMonitoringHousePager(pager, pagerDots, prevButton, nextButton, slide, houseCount) {
    if (!pager) {
        return;
    }

    pager.hidden = houseCount <= 1;

    if (pagerDots) {
        pagerDots.innerHTML = Array.from({ length: houseCount }, (_, index) => `
            <button
                type="button"
                class="house-graph-page-dot ${index === activeMonitoringHouseIndex ? "active" : ""}"
                data-house-graph-page="${index}"
                aria-label="Show house ${index + 1}"
            ></button>
        `).join("");
    }

    if (prevButton) {
        prevButton.disabled = activeMonitoringHouseIndex <= 0;
    }

    if (nextButton) {
        nextButton.disabled = activeMonitoringHouseIndex >= houseCount - 1;
    }
}

function createOrUpdateChart(canvas, slide) {
    if (monitoringChart) {
        monitoringChart.destroy();
    }

    const context = canvas.getContext("2d");
    const barGradient = createBarGradient(context, canvas, slide.borderColor);
    const datasets = Array.isArray(slide.datasets) && slide.datasets.length
        ? slide.datasets
        : [
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
          ];

    monitoringChart = new Chart(canvas, {
        type: "bar",
        data: {
            labels: slide.labels,
            datasets,
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
                    display: datasets.length > 1,
                    position: "bottom",
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        color: "#3f473f",
                        font: {
                            weight: 700,
                        },
                    },
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
                            const label = context.dataset.label || slide.label;
                            return `${label}: ${context.parsed.y}${slide.unit || ""}`;
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
                    ...(slide.maxValue ? { max: slide.maxValue } : {}),
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

function createBarGradient(context, canvas, color) {
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

function updateGraphCaption(caption, slide) {
    if (!caption) {
        return;
    }

    caption.textContent = slide.houseLabel ? `${slide.label} - ${slide.houseLabel}` : slide.label;
}

function updateDots(dots, activeIndex) {
    dots.forEach((dot, index) => {
        dot.classList.toggle("active", index === activeIndex);
    });
}

async function renderEnvironmentCarousel() {
    const canvas = document.getElementById("environmentChart");
    const caption = document.getElementById("envGraphCaption");
    const dots = document.querySelectorAll(".env-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    try {
        const response = await fetch(
            "/api/manager/dashboard/environment-by-house",
        );
        const data = await response.json();

        environmentSlides = Array.isArray(data.slides) ? data.slides : [];

        if (!environmentSlides.length) {
            return;
        }

        createOrUpdateEnvironmentChart(canvas, environmentSlides[0]);
        updateGraphCaption(caption, environmentSlides[0]);
        updateDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !environmentSlides[index]) {
                    return;
                }

                activeEnvironmentSlideIndex = index;
                createOrUpdateEnvironmentChart(canvas, environmentSlides[index]);
                updateGraphCaption(caption, environmentSlides[index]);
                updateDots(dots, index);
            });
        });
    } catch (error) {
        console.error("Failed to load environment monitoring data.", error);
    }
}

async function renderHouseSensorSummaryCarousels() {
    try {
        const response = await fetch("/api/manager/dashboard/house-sensor-summary");
        const data = await response.json();

        renderEnvironmentCarouselFromData(data.environment || {});
        renderResourceCarouselFromData(data.resources || {});
    } catch (error) {
        console.error("Failed to load house sensor summary data.", error);
    }
}

function renderEnvironmentCarouselFromData(data) {
    const canvas = document.getElementById("environmentChart");
    const caption = document.getElementById("envGraphCaption");
    const dots = document.querySelectorAll(".env-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    environmentSlides = Array.isArray(data.slides) ? data.slides : [];

    if (!environmentSlides.length) {
        return;
    }

    createOrUpdateEnvironmentChart(canvas, environmentSlides[0]);
    updateGraphCaption(caption, environmentSlides[0]);
    updateDots(dots, 0);

    dots.forEach((dot) => {
        dot.addEventListener("click", () => {
            const index = Number(dot.dataset.slide);
            if (Number.isNaN(index) || !environmentSlides[index]) {
                return;
            }

            activeEnvironmentSlideIndex = index;
            createOrUpdateEnvironmentChart(canvas, environmentSlides[index]);
            updateGraphCaption(caption, environmentSlides[index]);
            updateDots(dots, index);
        });
    });
}

function renderResourceCarouselFromData(data) {
    const canvas = document.getElementById("resourceChart");
    const caption = document.getElementById("resourceGraphCaption");
    const dots = document.querySelectorAll(".resource-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    resourceSlides = Array.isArray(data.slides) ? data.slides : [];

    if (!resourceSlides.length) {
        return;
    }

    createOrUpdateResourceChart(canvas, resourceSlides[0]);
    updateGraphCaption(caption, resourceSlides[0]);
    updateDots(dots, 0);

    dots.forEach((dot) => {
        dot.addEventListener("click", () => {
            const index = Number(dot.dataset.slide);
            if (Number.isNaN(index) || !resourceSlides[index]) {
                return;
            }

            activeResourceSlideIndex = index;
            createOrUpdateResourceChart(canvas, resourceSlides[index]);
            updateGraphCaption(caption, resourceSlides[index]);
            updateDots(dots, index);
        });
    });
}

function createOrUpdateEnvironmentChart(canvas, slide) {
    if (environmentChart) {
        environmentChart.destroy();
    }

    const context = canvas.getContext("2d");
    const barGradient = createBarGradient(context, canvas, slide.borderColor);

    environmentChart = new Chart(canvas, {
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
                    max: slide.maxValue || environmentalMaxForSlide(slide),
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

function environmentalMaxForSlide(slide) {
    const label = String(slide?.label || "").toLowerCase();

    return label.includes("ammonia") ? 30 : 45;
}

async function renderResourceCarousel() {
    const canvas = document.getElementById("resourceChart");
    const caption = document.getElementById("resourceGraphCaption");
    const dots = document.querySelectorAll(".resource-dot");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    try {
        const response = await fetch(
            "/api/manager/dashboard/resources-by-house",
        );
        const data = await response.json();

        resourceSlides = Array.isArray(data.slides) ? data.slides : [];

        if (!resourceSlides.length) {
            return;
        }

        createOrUpdateResourceChart(canvas, resourceSlides[0]);
        updateGraphCaption(caption, resourceSlides[0]);
        updateDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !resourceSlides[index]) {
                    return;
                }

                activeResourceSlideIndex = index;
                createOrUpdateResourceChart(canvas, resourceSlides[index]);
                updateGraphCaption(caption, resourceSlides[index]);
                updateDots(dots, index);
            });
        });
    } catch (error) {
        console.error("Failed to load resource monitoring data.", error);
    }
}

function createOrUpdateResourceChart(canvas, slide) {
    if (resourceChart) {
        resourceChart.destroy();
    }

    const context = canvas.getContext("2d");
    const barGradient = createBarGradient(context, canvas, slide.borderColor);

    resourceChart = new Chart(canvas, {
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
                    max: slide.maxValue || 100,
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

const profileModal = document.getElementById("profileModal");
const openProfileModalBtn = document.getElementById("openProfileModal");
const closeProfileModalBtn = document.getElementById("closeProfileModal");


async function populateProfileModal() {
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

if (openProfileModalBtn && profileModal) {
    openProfileModalBtn.addEventListener("click", async () => {
        await populateProfileModal();
        profileModal.classList.add("show");
        document.body.style.overflow = "hidden";
    });
}

if (closeProfileModalBtn && profileModal) {
    closeProfileModalBtn.addEventListener("click", () => {
        profileModal.classList.remove("show");
        document.body.style.overflow = "";
    });
}

if (profileModal) {
    profileModal.addEventListener("click", (event) => {
        if (event.target === profileModal) {
            profileModal.classList.remove("show");
            document.body.style.overflow = "";
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            profileModal.classList.remove("show");
            document.body.style.overflow = "";
        }
    });
}
