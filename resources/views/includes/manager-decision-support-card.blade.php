<div class="decision-support-card">
    <div class="decision-support-header">
        <h3 class="decision-support-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4"></path>
                <path d="M12 8h.01"></path>
            </svg>
            Decision Support
        </h3>
        <button class="decision-support-refresh" id="refreshDecisionSupport" title="Refresh">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36M20.49 15a9 9 0 0 1-14.85 3.36"></path>
            </svg>
        </button>
    </div>

    <div class="decision-support-content" id="decisionSupportContent">
        <div class="decision-support-loading">
            <div class="spinner"></div>
            <p>Loading recommendations...</p>
        </div>
    </div>
</div>
