/**
 * Decision Support Module
 * Loads AI-powered farm management recommendations
 */

const DEFAULT_DECISION_SUPPORT_API = '/api/manager/dashboard/decision-support';
const DECISION_SUPPORT_REFRESH_INTERVAL_MS = 60 * 60 * 1000;
const DECISION_SUPPORT_CRITICAL_CHECK_INTERVAL_MS = 60 * 1000;
const DECISION_SUPPORT_CRITICAL_REGEN_COOLDOWN_MS = 5 * 60 * 1000;
let decisionSupportAutoRefresh = null;
let decisionSupportCriticalCheckInterval = null;
let isDecisionSupportCriticalCheckRunning = false;
let lastDecisionSupportCriticalRefreshAt = 0;
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

        const apiUrl = getDecisionSupportApi(container);
        const url = forceRefresh ? `${apiUrl}?refresh=1` : apiUrl;
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

async function checkCriticalDecisionSupportReadings() {
    const container = document.getElementById('decisionSupportContent');
    if (!container || isDecisionSupportCriticalCheckRunning) {
        return;
    }

    isDecisionSupportCriticalCheckRunning = true;

    try {
        const response = await fetch(getDecisionSupportCriticalCheckApi(container));
        if (!response.ok) {
            throw new Error('Failed to check critical sensor readings');
        }

        const data = await response.json();
        if (data.has_critical) {
            const now = Date.now();
            if (lastDecisionSupportCriticalRefreshAt && now - lastDecisionSupportCriticalRefreshAt < DECISION_SUPPORT_CRITICAL_REGEN_COOLDOWN_MS) {
                return;
            }

            lastDecisionSupportCriticalRefreshAt = now;
            decisionSupportPage = 0;
            decisionSupportPenPages = {};
            await loadDecisionSupport(true);
            return;
        }

        lastDecisionSupportCriticalRefreshAt = 0;
    } catch (error) {
        console.error('Decision Support Critical Check Error:', error);
    } finally {
        isDecisionSupportCriticalCheckRunning = false;
    }
}

function getDecisionSupportApi(container) {
    return container?.dataset?.decisionSupportApi || DEFAULT_DECISION_SUPPORT_API;
}

function getDecisionSupportCriticalCheckApi(container) {
    return container?.dataset?.decisionSupportCriticalCheckApi || `${getDecisionSupportApi(container)}/critical-check`;
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
                    </div>
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
                    <div class="decision-support-page-dots" aria-label="Decision support pages">
                        ${decisionSupportHouses.map((pageHouse, index) => `
                            <button
                                type="button"
                                class="decision-support-page-dot ${index === decisionSupportPage ? 'active' : ''}"
                                data-decision-page="${index}"
                                aria-label="Show ${escapeHtml(formatHouseName(pageHouse.house_name))}"
                                aria-current="${index === decisionSupportPage ? 'page' : 'false'}"
                            ></button>
                        `).join('')}
                    </div>
                    <button type="button" class="decision-support-page-btn" data-decision-page-next ${decisionSupportPage >= totalPages - 1 ? 'disabled' : ''}>
                        Next
                    </button>
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
    const severity = normalizeSeverity(rec.severity);

    return `
        <article class="decision-support-pen-card ${severity}">
            <div class="decision-support-pen-header">
                <div>
                    <span class="decision-support-pen-title">${escapeHtml(formatPenName(rec.pen_name, rec.pen_id))}</span>
                </div>
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
    const lines = stripHiddenRecommendationSections(text)
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

    return splitStatusSections(sections).filter((section) => section.title !== 'Refill Alerts');
}

function stripHiddenRecommendationSections(text) {
    const hiddenHeadings = [
        'flock & equipment status',
        'flock and equipment status',
    ];
    const hiddenLinePatterns = [
        /^[-*]?\s*feeder trend\s*:/i,
        /^[-*]?\s*no abnormal feed-water consumption correlation was detected/i,
        /^[-*]?\s*current (feeder|drinker) level: .*configured (critical low|warning\/refill) threshold:/i,
    ];
    const visibleHeadings = [
        'refill alerts',
        'cross-environmental diagnostics',
        'immediate actions',
        'emergency action required',
        'flock status analysis',
        'temperature status analysis',
        'thermal status analysis',
        'standard management actions',
        'critical warning',
        'observation note',
        'ammonia',
    ];
    let skipping = false;

    return String(text || '')
        .split(/\n+/)
        .filter((line) => {
            if (hiddenLinePatterns.some((pattern) => pattern.test(stripMarkdownBold(line).trim()))) {
                return false;
            }

            const heading = stripMarkdownBold(line)
                .replace(/^\d+[\).\s-]+/, '')
                .replace(/:$/, '')
                .trim()
                .toLowerCase();

            if (hiddenHeadings.some((hiddenHeading) => heading.includes(hiddenHeading))) {
                skipping = true;
                return false;
            }

            if (skipping && visibleHeadings.some((visibleHeading) => heading.includes(visibleHeading))) {
                skipping = false;
            }

            return !skipping;
        })
        .join('\n');
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

    if (lower.includes('refill alerts')) {
        return { title: 'Refill Alerts', type: 'warning-note' };
    }

    if (lower.includes('cross-environmental diagnostics')) {
        return { title: 'Cross-Environmental Diagnostics', type: 'status' };
    }

    if (lower.includes('immediate actions')) {
        return { title: 'Immediate Actions', type: 'actions' };
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
    const temperatureRow = normalizeTemperatureRecommendationRow(withoutBullet);
    if (temperatureRow) {
        return temperatureRow;
    }

    const ammoniaRow = normalizeAmmoniaRecommendationRow(withoutBullet);
    if (ammoniaRow) {
        return ammoniaRow;
    }

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

function normalizeTemperatureRecommendationRow(line) {
    const normalized = stripMarkdownBold(line).trim();
    const match = normalized.match(/^Current Temperature:\s*([^;]+)(?:;\s*Thermal Condition:\s*(.+))?\.?$/i);
    if (!match) {
        return null;
    }

    const reading = match[1].trim();
    const condition = (match[2] || '').replace(/\.$/, '').trim();
    const numericReading = reading.match(/-?\d+(?:\.\d+)?/);

    return {
        kind: 'fact',
        label: 'Current Temperature',
        value: numericReading
            ? `${numericReading[0]}C${condition ? ` - ${condition}` : ''}`
            : 'No Reading',
    };
}

function normalizeAmmoniaRecommendationRow(line) {
    const normalized = stripMarkdownBold(line).trim();
    const match = normalized.match(/^Current Ammonia Level:\s*([^;]+)(?:;\s*Ammonia Condition:\s*(.+))?\.?$/i);
    if (!match) {
        return null;
    }

    const reading = match[1].trim();
    const condition = (match[2] || '').replace(/\.$/, '').trim();
    const numericReading = reading.match(/-?\d+(?:\.\d+)?/);

    return {
        kind: 'fact',
        label: 'Ammonia',
        value: numericReading
            ? `${numericReading[0]}ppm${condition ? ` - ${condition}` : ''}`
            : 'No Reading',
    };
}

function renderRecommendationSection(section) {
    if (section.title === 'Cross-Environmental Diagnostics') {
        return renderCrossEnvironmentalFindings(section);
    }

    const rows = section.type === 'actions'
        ? section.rows.filter((row) => !shouldHideImmediateAction(row))
        : section.rows;

    if (section.type === 'actions' && !rows.length) {
        return '';
    }

    const rowsHtml = rows.map((row) => renderRecommendationRow(row, section.type)).join('');
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

function shouldHideImmediateAction(row) {
    const text = stripMarkdownBold(row?.text || '')
        .replace(/^[-*]\s+/, '')
        .trim();

    return /^refill\s+(feeder|drinker)\s+\d+\s+immediately\b/i.test(text);
}

function renderCrossEnvironmentalFindings(section) {
    const paragraph = buildCrossEnvironmentalReport(section.rows);

    return `
        <section class="decision-support-text-section ${section.type} ${sectionTitleClass(section.title)}">
            <div class="decision-support-text-body">
                <p>${paragraph ? formatInlineMarkdown(paragraph) : 'No details provided.'}</p>
            </div>
        </section>
    `;
}

function buildCrossEnvironmentalReport(rows) {
    const facts = rows.filter((row) => row.kind === 'fact');
    const findings = rows
        .filter((row) => row.kind !== 'fact')
        .map((row) => supervisorFindingSentence(row.text))
        .filter(Boolean);
    const factSentence = supervisorFactSentence(facts);
    const findingSentence = findings.length
        ? `Based on these conditions, ${sentenceCase(findings.join(' '))}`
        : '';

    return [factSentence, findingSentence].filter(Boolean).join(' ');
}

function supervisorFactSentence(facts) {
    const temperature = facts.find((row) => String(row.label || '').toLowerCase().includes('temperature'));
    const ammonia = facts.find((row) => String(row.label || '').toLowerCase().includes('ammonia'));
    const parts = [];

    if (temperature) {
        parts.push(readingReportPart('temperature', temperature.value));
    }

    if (ammonia) {
        parts.push(readingReportPart('ammonia', ammonia.value));
    }

    const otherFacts = facts
        .filter((row) => row !== temperature && row !== ammonia)
        .map((row) => `${String(row.label || '').toLowerCase()} at ${cleanStatusValue(row.value)}`);

    parts.push(...otherFacts);

    if (!parts.length) {
        return '';
    }

    return `Current readings show ${joinReportParts(parts)}.`;
}

function readingReportPart(label, value) {
    const status = splitStatusValue(value);

    if (status.reading === 'no reading') {
        return `${label} with no active reading`;
    }

    return status.condition
        ? `${label} at ${status.reading}, which is reported as ${status.condition}`
        : `${label} at ${status.reading}`;
}

function cleanStatusValue(value) {
    return String(value || '')
        .trim()
        .replace(/\s+-\s+/g, ', ')
        .toLowerCase() === 'no reading'
        ? 'no reading'
        : String(value || '').trim().replace(/\s+-\s+/g, ', ');
}

function splitStatusValue(value) {
    const cleaned = cleanStatusValue(value);
    const [reading, ...conditionParts] = cleaned.split(',').map((part) => part.trim()).filter(Boolean);

    return {
        reading: reading || 'no reading',
        condition: conditionParts.join(', '),
    };
}

function supervisorFindingSentence(text) {
    const softened = String(text || '')
        .trim()
        .replace(/^(finding|evidence|action):\s*/i, '')
        .replace(/\bcan indicate\b/gi, 'may point to')
        .replace(/\bDecision Support needs\b/gi, 'the assessment still needs')
        .replace(/\bwas detected during this decision-support run\b/gi, 'was detected')
        .replace(/\bNo broiler thermal stress condition was detected\b/gi, 'no broiler thermal stress was detected')
        .replace(/\bThe current broiler temperature is\b/gi, 'the broiler temperature is')
        .replace(/\bPhysically inspect\b/gi, 'a physical check should cover')
        .replace(/\bCheck\b/gi, 'checking')
        .replace(/\bVerify\b/gi, 'verification of');

    if (!softened) {
        return '';
    }

    if (/[.!?]$/.test(stripMarkdownBold(softened))) {
        return softened;
    }

    return `${softened}.`;
}

function joinReportParts(parts) {
    if (parts.length <= 1) {
        return parts[0] || '';
    }

    return `${parts.slice(0, -1).join(', ')} and ${parts[parts.length - 1]}`;
}

function sentenceCase(text) {
    const trimmed = String(text || '').trim();
    return trimmed ? trimmed.charAt(0).toLowerCase() + trimmed.slice(1) : '';
}

function recommendationRowSentence(row) {
    const text = row.kind === 'fact'
        ? `${row.label}: ${row.value}`
        : row.text;
    const trimmed = String(text || '').trim();

    if (!trimmed || /[.!?]$/.test(stripMarkdownBold(trimmed))) {
        return trimmed;
    }

    return `${trimmed}.`;
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
                <span>${sectionType === 'actions' ? escapeHtml(stripMarkdownBold(row.text)) : formatInlineMarkdown(row.text)}</span>
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

    decisionSupportAutoRefresh = setInterval(() => {
        decisionSupportPage = 0;
        decisionSupportPenPages = {};
        loadDecisionSupport(true);
    }, DECISION_SUPPORT_REFRESH_INTERVAL_MS);

    decisionSupportCriticalCheckInterval = setInterval(
        checkCriticalDecisionSupportReadings,
        DECISION_SUPPORT_CRITICAL_CHECK_INTERVAL_MS,
    );
}

function cleanupDecisionSupport() {
    if (decisionSupportAutoRefresh) {
        clearInterval(decisionSupportAutoRefresh);
    }

    if (decisionSupportCriticalCheckInterval) {
        clearInterval(decisionSupportCriticalCheckInterval);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDecisionSupport();
    setupDecisionSupportRefresh();
});

window.addEventListener('beforeunload', cleanupDecisionSupport);
