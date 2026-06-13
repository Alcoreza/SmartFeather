/**
 * Decision Support Module
 * Loads AI-powered farm management recommendations
 */

const DECISION_SUPPORT_API = '/api/manager/dashboard/decision-support';
let decisionSupportAutoRefresh = null;
let decisionSupportHouses = [];
let decisionSupportPage = 0;
let decisionSupportPenPages = {};

async function loadDecisionSupport(forceRefresh = false) {
    const container = document.getElementById('decisionSupportContent');
    if (!container) return;

    const refreshBtn = document.getElementById('refreshDecisionSupport');

    try {
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.classList.add('is-loading');
        }

        container.innerHTML = `
            <div class="decision-support-loading">
                <div class="spinner"></div>
                <p>Loading recommendations...</p>
            </div>
        `;

        const url = forceRefresh ? `${DECISION_SUPPORT_API}?refresh=1` : DECISION_SUPPORT_API;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch recommendations');

        const data = await response.json();
        renderDecisionSupport(container, data.recommendations);
    } catch (error) {
        console.error('Decision Support Error:', error);
        container.innerHTML = `
            <div class="decision-support-error">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 8v4M12 16h.01" fill="white"></path>
                </svg>
                <span>Unable to load recommendations. Please try again.</span>
            </div>
        `;
    } finally {
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.classList.remove('is-loading');
        }
    }
}

function renderDecisionSupport(container, recommendations) {
    if (!recommendations || recommendations.length === 0) {
        decisionSupportHouses = [];
        decisionSupportPage = 0;
        decisionSupportPenPages = {};
        renderDecisionSupportPage(container);
        return;
    }

    const groupedByHouse = {};
    recommendations.forEach((rec) => {
        const houseId = rec.house_id || 'unknown';
        if (!groupedByHouse[houseId]) {
            groupedByHouse[houseId] = {
                house_id: houseId,
                house_name: rec.house_name,
                pens: [],
            };
        }
        groupedByHouse[houseId].pens.push(rec);
    });

    decisionSupportHouses = Object.values(groupedByHouse)
        .map((house) => ({
            ...house,
            pens: latestRecommendationPerPen(house.pens),
        }))
        .sort((a, b) => formatHouseName(a.house_name).localeCompare(
            formatHouseName(b.house_name),
            undefined,
            { numeric: true },
        ));

    decisionSupportPage = Math.min(decisionSupportPage, decisionSupportHouses.length - 1);
    renderDecisionSupportPage(container);
}

function renderDecisionSupportPage(container) {
    const house = decisionSupportHouses[decisionSupportPage];

    if (!house) {
        container.innerHTML = `
            <div class="decision-support-empty">
                <p>No active recommendations at this time. All systems are operating normally.</p>
            </div>
        `;
        return;
    }

    const pens = house.pens.slice().sort((a, b) => formatPenName(a.pen_name, a.pen_id).localeCompare(
        formatPenName(b.pen_name, b.pen_id),
        undefined,
        { numeric: true },
    ));
    const houseKey = decisionSupportHouseKey(house);
    const currentPenPage = Math.max(0, Math.min(
        decisionSupportPenPages[houseKey] || 0,
        Math.max(pens.length - 1, 0),
    ));
    decisionSupportPenPages[houseKey] = currentPenPage;
    const activePen = pens[currentPenPage] || null;
    const severity = highestSeverity(pens.map((pen) => pen.severity));
    const categories = uniqueValues(pens.flatMap((pen) => (
        Array.isArray(pen.categories) && pen.categories.length ? pen.categories : ['overall']
    )));
    const findingsCount = pens.reduce((total, pen) => total + Number(pen.findings_count || 0), 0);
    const latestGeneratedTime = latestDate(pens.map((pen) => pen.generated_at));
    const timeAgo = latestGeneratedTime ? getTimeAgo(latestGeneratedTime) : 'just now';
    const houseLabel = formatHouseName(house.house_name);
    const totalPages = decisionSupportHouses.length;

    container.innerHTML = `
        <div class="decision-support-house-pager">
            <article class="decision-support-house-card ${severity}">
                <div class="decision-support-item-header">
                    <div>
                        <span class="decision-support-item-house">${escapeHtml(houseLabel)}</span>
                        <span class="decision-support-item-meta">
                            ${pens.length} pen${pens.length === 1 ? '' : 's'} - ${findingsCount || 1} finding${findingsCount === 1 ? '' : 's'} - ${timeAgo}
                        </span>
                    </div>
                    <span class="decision-support-severity ${severity}" title="Highest current severity">
                        ${severity}
                    </span>
                </div>

                <div class="decision-support-category-row">
                    ${categories.map((category) => `
                        <span class="decision-support-category">${escapeHtml(formatCategory(category))}</span>
                    `).join('')}
                </div>

                ${renderPenTabs(house, pens, currentPenPage)}
                <div class="decision-support-pen-list">
                    ${activePen ? renderPenRecommendationCard(activePen) : ''}
                </div>
                <div class="decision-support-item-time" title="${latestGeneratedTime ? latestGeneratedTime.toLocaleString() : ''}">
                    Generated ${timeAgo}
                </div>
            </article>

            ${totalPages > 1 ? `
                <div class="decision-support-pagination">
                    <button type="button" class="decision-support-page-btn" data-decision-page-prev ${decisionSupportPage === 0 ? 'disabled' : ''}>
                        Previous
                    </button>
                    <div class="decision-support-page-status">
                        <span>${decisionSupportPage + 1}</span> / ${totalPages}
                    </div>
                    <button type="button" class="decision-support-page-btn" data-decision-page-next ${decisionSupportPage >= totalPages - 1 ? 'disabled' : ''}>
                        Next
                    </button>
                </div>
                <div class="decision-support-page-dots">
                    ${decisionSupportHouses.map((pageHouse, index) => `
                        <button
                            type="button"
                            class="decision-support-page-dot ${index === decisionSupportPage ? 'active' : ''}"
                            data-decision-page="${index}"
                            aria-label="Show ${escapeHtml(formatHouseName(pageHouse.house_name))}"
                        ></button>
                    `).join('')}
                </div>
            ` : ''}
        </div>
    `;

    bindDecisionSupportPagination(container);
    bindDecisionSupportPenTabs(container);
}

function latestRecommendationPerPen(recommendations) {
    const groupedByPen = {};

    recommendations.forEach((rec) => {
        const penId = rec.pen_id || 'unknown';
        if (!groupedByPen[penId]) {
            groupedByPen[penId] = [];
        }
        groupedByPen[penId].push(rec);
    });

    return Object.values(groupedByPen).map((recs) => recs
        .slice()
        .sort((a, b) => new Date(b.generated_at) - new Date(a.generated_at))[0]);
}

function renderPenRecommendationCard(rec) {
    const generatedTime = new Date(rec.generated_at);
    const severity = normalizeSeverity(rec.severity);
    const categories = Array.isArray(rec.categories) && rec.categories.length
        ? rec.categories
        : ['overall'];
    const findingsCount = Number(rec.findings_count || 0);

    return `
        <article class="decision-support-pen-card ${severity}">
            <div class="decision-support-pen-header">
                <div>
                    <span class="decision-support-pen-title">${escapeHtml(formatPenName(rec.pen_name, rec.pen_id))}</span>
                    <span class="decision-support-pen-meta">
                        ${findingsCount || 1} finding${findingsCount === 1 ? '' : 's'} - ${getTimeAgo(generatedTime)}
                    </span>
                </div>
                <span class="decision-support-severity ${severity}" title="Pen severity">
                    ${severity}
                </span>
            </div>
            <div class="decision-support-category-row">
                ${categories.map((category) => `
                    <span class="decision-support-category">${escapeHtml(formatCategory(category))}</span>
                `).join('')}
            </div>
            <div class="decision-support-item-text">${formatRecommendationText(rec.text)}</div>
        </article>
    `;
}

function renderPenTabs(house, pens, currentPenPage) {
    if (pens.length <= 1) {
        return '';
    }

    const houseKey = decisionSupportHouseKey(house);

    return `
        <div class="decision-support-pen-tabs" data-decision-pen-house="${escapeHtml(houseKey)}" role="tablist" aria-label="Pens in this house">
            ${pens.map((pen, index) => `
                <button
                    type="button"
                    class="decision-support-pen-tab ${normalizeSeverity(pen.severity)} ${index === currentPenPage ? 'active' : ''}"
                    data-decision-pen-page="${index}"
                    role="tab"
                    aria-selected="${index === currentPenPage ? 'true' : 'false'}"
                    title="${escapeHtml(formatPenName(pen.pen_name, pen.pen_id))} - ${escapeHtml(formatSeverityLabel(pen.severity))}"
                >
                    <span class="decision-support-pen-tab-dot"></span>
                    <span>${escapeHtml(formatPenName(pen.pen_name, pen.pen_id))}</span>
                </button>
            `).join('')}
        </div>
    `;
}

function bindDecisionSupportPagination(container) {
    container.querySelector('[data-decision-page-prev]')?.addEventListener('click', () => {
        decisionSupportPage = Math.max(0, decisionSupportPage - 1);
        renderDecisionSupportPage(container);
    });

    container.querySelector('[data-decision-page-next]')?.addEventListener('click', () => {
        decisionSupportPage = Math.min(decisionSupportHouses.length - 1, decisionSupportPage + 1);
        renderDecisionSupportPage(container);
    });

    container.querySelectorAll('[data-decision-page]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextPage = Number(button.dataset.decisionPage);
            if (Number.isNaN(nextPage)) return;
            decisionSupportPage = Math.max(0, Math.min(decisionSupportHouses.length - 1, nextPage));
            renderDecisionSupportPage(container);
        });
    });
}

function bindDecisionSupportPenTabs(container) {
    const house = decisionSupportHouses[decisionSupportPage];
    if (!house) return;

    const houseKey = decisionSupportHouseKey(house);
    const pensCount = house.pens.length;

    container.querySelectorAll('[data-decision-pen-page]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextPage = Number(button.dataset.decisionPenPage);
            if (Number.isNaN(nextPage)) return;
            decisionSupportPenPages[houseKey] = Math.max(0, Math.min(pensCount - 1, nextPage));
            renderDecisionSupportPage(container);
        });
    });
}

function decisionSupportHouseKey(house) {
    return String(house?.house_id || house?.house_name || 'unknown');
}

function formatSeverityLabel(severity) {
    return `${normalizeSeverity(severity)} severity`;
}

function normalizeSeverity(severity) {
    const value = String(severity || '').toLowerCase();
    return ['critical', 'warning', 'normal'].includes(value) ? value : 'normal';
}

function formatHouseName(name) {
    const value = String(name || '').trim();
    if (!value) return 'Unknown House';
    return /^house\b/i.test(value) ? value : `House ${value}`;
}

function formatPenName(name, id = null) {
    const value = String(name || '').trim();
    if (value) return /^pen\b/i.test(value) ? value : `Pen ${value}`;
    return id ? `Pen ${id}` : 'Unknown Pen';
}

function highestSeverity(severities) {
    const rank = {
        normal: 1,
        warning: 2,
        critical: 3,
    };

    return severities
        .map(normalizeSeverity)
        .sort((a, b) => rank[b] - rank[a])[0] || 'normal';
}

function uniqueValues(values) {
    return [...new Set(values.filter(Boolean))];
}

function latestDate(values) {
    const dates = values
        .map((value) => new Date(value))
        .filter((date) => !Number.isNaN(date.getTime()))
        .sort((a, b) => b - a);

    return dates[0] || null;
}

function formatCategory(category) {
    return String(category || 'overall')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatRecommendationText(text) {
    const sections = parseRecommendationSections(text);

    if (!sections.length) {
        return '<p class="decision-support-text-empty">No recommendation text available.</p>';
    }

    return sections.map(renderRecommendationSection).join('');
}

function parseRecommendationSections(text) {
    const lines = String(text || '')
        .split(/\n+/)
        .map((line) => line.trim())
        .filter(Boolean);

    const sections = [];
    let currentSection = null;

    lines.forEach((line) => {
        const heading = parseRecommendationHeading(line);

        if (heading) {
            const existingSection = sections.find((section) => (
                section.type === heading.type && section.title === heading.title
            ));

            currentSection = existingSection || {
                title: heading.title,
                type: heading.type,
                rows: [],
            };

            if (!existingSection) {
                sections.push(currentSection);
            }
            return;
        }

        if (!currentSection) {
            currentSection = {
                title: 'Recommendation',
                type: 'general',
                rows: [],
            };
            sections.push(currentSection);
        }

        addRecommendationRow(currentSection, parseRecommendationRow(line));
    });

    return splitStatusSections(sections);
}

function splitStatusSections(sections) {
    const normalizedSections = [];

    sections.forEach((section) => {
        if (section.title !== 'Flock Status Analysis') {
            normalizedSections.push(section);
            return;
        }

        const flockRows = [];
        const temperatureRows = [];

        section.rows.forEach((row) => {
            if (row.kind === 'fact' && isTemperatureStatusLabel(row.label)) {
                temperatureRows.push(row);
                return;
            }

            flockRows.push(row);
        });

        const shouldShowFlockStatus = false;

        if (shouldShowFlockStatus && flockRows.length) {
            normalizedSections.push({
                ...section,
                rows: flockRows,
            });
        }

        if (temperatureRows.length) {
            mergeSection(normalizedSections, {
                title: 'Temperature Status Analysis',
                type: 'status',
                rows: temperatureRows,
            });
        }
    });

    return normalizedSections;
}

function isTemperatureStatusLabel(label) {
    const normalized = String(label || '').toLowerCase();
    return normalized === 'current temperature' || normalized === 'thermal condition';
}

function mergeSection(sections, nextSection) {
    const existingSection = sections.find((section) => (
        section.type === nextSection.type && section.title === nextSection.title
    ));

    if (!existingSection) {
        sections.push(nextSection);
        return;
    }

    nextSection.rows.forEach((row) => {
        addRecommendationRow(existingSection, row);
    });
}

function addRecommendationRow(section, row) {
    const nextKey = recommendationRowKey(row);
    const alreadyExists = section.rows.some((existingRow) => (
        recommendationRowKey(existingRow) === nextKey
    ));

    if (!alreadyExists) {
        section.rows.push(row);
    }
}

function recommendationRowKey(row) {
    if (row.kind === 'fact') {
        return `fact:${row.label.toLowerCase()}:${row.value.toLowerCase()}`;
    }

    return `text:${row.text.toLowerCase()}`;
}

function parseRecommendationHeading(line) {
    if (/^[-*]\s+/.test(line)) {
        return null;
    }

    const cleaned = stripMarkdownBold(line)
        .replace(/^\d+[\).\s-]+/, '')
        .replace(/:$/, '')
        .trim();
    const lower = cleaned.toLowerCase();

    if (lower.includes('emergency action required')) {
        return { title: 'Emergency Action Required', type: 'emergency' };
    }

    if (lower.includes('flock status analysis')) {
        return { title: 'Flock Status Analysis', type: 'status' };
    }

    if (lower.includes('temperature status analysis') || lower.includes('thermal status analysis')) {
        return { title: 'Temperature Status Analysis', type: 'status' };
    }

    if (lower.includes('standard management actions')) {
        return { title: 'Standard Management Actions', type: 'actions' };
    }

    if (lower.includes('critical warning') || lower.includes('observation note')) {
        return { title: 'Critical Warning / Observation Note', type: 'warning-note' };
    }

    if (lower.includes('ammonia')) {
        return { title: cleaned, type: 'status' };
    }

    return null;
}

function parseRecommendationRow(line) {
    const withoutBullet = line
        .replace(/^\d+[\).\s-]+/, '')
        .replace(/^[-*]\s+/, '')
        .trim();
    const labelMatch = stripMarkdownBold(withoutBullet).match(/^([^:]{2,44}):\s*(.+)$/);

    if (labelMatch) {
        return {
            kind: 'fact',
            label: labelMatch[1].trim(),
            value: labelMatch[2].trim(),
        };
    }

    return {
        kind: 'text',
        text: withoutBullet,
    };
}

function renderRecommendationSection(section) {
    const rowsHtml = section.rows.map((row) => renderRecommendationRow(row, section.type)).join('');
    const titleHtml = escapeHtml(section.title);
    const sectionClass = `${section.type} ${sectionTitleClass(section.title)}`.trim();

    return `
        <section class="decision-support-text-section ${sectionClass}">
            <h4>${titleHtml}</h4>
            <div class="decision-support-text-body">
                ${rowsHtml || '<p>No details provided.</p>'}
            </div>
        </section>
    `;
}

function sectionTitleClass(title) {
    return String(title || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function renderRecommendationRow(row, sectionType) {
    if (row.kind === 'fact') {
        return `
            <div class="decision-support-fact-row">
                <span>${escapeHtml(row.label)}</span>
                <strong>${formatInlineMarkdown(row.value)}</strong>
            </div>
        `;
    }

    if (sectionType === 'emergency' || sectionType === 'actions') {
        return `
            <div class="decision-support-action-row">
                <span class="decision-support-action-marker"></span>
                <span>${formatInlineMarkdown(row.text)}</span>
            </div>
        `;
    }

    return `<p>${formatInlineMarkdown(row.text)}</p>`;
}

function stripMarkdownBold(text) {
    return String(text || '').replace(/\*\*/g, '');
}

function formatInlineMarkdown(text) {
    return escapeHtml(text).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
}

function getTimeAgo(date) {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return String(text || '').replace(/[&<>"']/g, (m) => map[m]);
}

function setupDecisionSupportRefresh() {
    const refreshBtn = document.getElementById('refreshDecisionSupport');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            decisionSupportPage = 0;
            decisionSupportPenPages = {};
            loadDecisionSupport(true);
        });
    }

    decisionSupportAutoRefresh = setInterval(loadDecisionSupport, 30 * 60 * 1000);
}

function cleanupDecisionSupport() {
    if (decisionSupportAutoRefresh) {
        clearInterval(decisionSupportAutoRefresh);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDecisionSupport();
    setupDecisionSupportRefresh();
});

window.addEventListener('beforeunload', cleanupDecisionSupport);
