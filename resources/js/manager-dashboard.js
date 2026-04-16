let monitoringChart = null;
let monitoringSlides = [];
let activeSlideIndex = 0;

document.addEventListener("DOMContentLoaded", async () => {
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

    await renderRealtimeMonitoring();
    await renderMonitoringCarousel();
});

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
                <div class="radial-gauge ${item.status}" style="--gauge-value: ${degrees}deg;">
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
                    <div class="resource-bar ${isWater ? "water-bar" : "feed-bar"}" style="height: ${safeValue}%;"></div>
                </div>
                <div class="resource-percent ${isWater ? "water-text" : ""}">${safeValue}${item.unit}</div>
                <div class="resource-label">${item.label}</div>
            </div>
            ${index === 0 ? '<div class="panel-divider"></div>' : ""}
        `;
        })
        .join("");
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
    const dots = document.querySelectorAll(".graph-dot");

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

        createOrUpdateChart(canvas, monitoringSlides[0]);
        updateGraphCaption(caption, monitoringSlides[0]);
        updateDots(dots, 0);

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.dataset.slide);
                if (Number.isNaN(index) || !monitoringSlides[index]) {
                    return;
                }

                activeSlideIndex = index;
                createOrUpdateChart(canvas, monitoringSlides[index]);
                updateGraphCaption(caption, monitoringSlides[index]);
                updateDots(dots, index);
            });
        });
    } catch (error) {
        console.error("Failed to load monitoring graph data.", error);
    }
}

function createOrUpdateChart(canvas, slide) {
    if (monitoringChart) {
        monitoringChart.destroy();
    }

    monitoringChart = new Chart(canvas, {
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
            animation: {
                duration: 450,
            },
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: "#687068",
                    },
                },
                y: {
                    beginAtZero: false,
                    grid: {
                        color: "rgba(90, 96, 90, 0.14)",
                    },
                    ticks: {
                        color: "#687068",
                    },
                },
            },
        },
    });
}

function updateGraphCaption(caption, slide) {
    if (!caption) {
        return;
    }

    caption.textContent = slide.label;
}

function updateDots(dots, activeIndex) {
    dots.forEach((dot, index) => {
        dot.classList.toggle("active", index === activeIndex);
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
