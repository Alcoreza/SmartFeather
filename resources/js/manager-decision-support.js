/**
 * Decision Support Module
 * Loads AI-powered farm management recommendations
 */

const DECISION_SUPPORT_API = '/api/manager/dashboard/decision-support';
let decisionSupportAutoRefresh = null;

/**
 * Load decision support recommendations
 */
async function loadDecisionSupport() {
    const container = document.getElementById('decisionSupportContent');
    if (!container) return;

    try {
        // Show loading
        container.innerHTML = `
            <div class="decision-support-loading">
                <div class="spinner"></div>
                <p>Loading recommendations...</p>
            </div>
        `;

        const response = await fetch(DECISION_SUPPORT_API);
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
    }
}

/**
 * Render decision support recommendations
 */
function renderDecisionSupport(container, recommendations) {
    if (!recommendations || recommendations.length === 0) {
        container.innerHTML = `
            <div class="decision-support-empty">
                <p>No active recommendations at this time. All systems are operating normally.</p>
            </div>
        `;
        return;
    }

    // Group by house
    const groupedByHouse = {};
    recommendations.forEach(rec => {
        const houseId = rec.house_id || 'unknown';
        if (!groupedByHouse[houseId]) {
            groupedByHouse[houseId] = [];
        }
        groupedByHouse[houseId].push(rec);
    });

    // Render each house's recommendations
    container.innerHTML = Object.entries(groupedByHouse).map(([houseId, recs]) => {
        const latestRec = recs[0]; // Get most recent
        const generatedTime = new Date(latestRec.generated_at);
        const timeAgo = getTimeAgo(generatedTime);

        return `
            <div class="decision-support-item">
                <div class="decision-support-item-header">
                    <span class="decision-support-item-house">${latestRec.house_name}</span>
                    <span class="decision-support-item-time" title="${generatedTime.toLocaleString()}">
                        ${timeAgo}
                    </span>
                </div>
                <div class="decision-support-item-text">${escapeHtml(latestRec.text)}</div>
            </div>
        `;
    }).join('');
}

/**
 * Get human-readable time difference
 */
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

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Setup auto-refresh
 */
function setupDecisionSupportRefresh() {
    const refreshBtn = document.getElementById('refreshDecisionSupport');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            loadDecisionSupport();
        });
    }

    // Auto-refresh every 30 minutes
    decisionSupportAutoRefresh = setInterval(loadDecisionSupport, 30 * 60 * 1000);
}

/**
 * Cleanup
 */
function cleanupDecisionSupport() {
    if (decisionSupportAutoRefresh) {
        clearInterval(decisionSupportAutoRefresh);
    }
}

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    loadDecisionSupport();
    setupDecisionSupportRefresh();
});

// Cleanup on page unload
window.addEventListener('beforeunload', cleanupDecisionSupport);
