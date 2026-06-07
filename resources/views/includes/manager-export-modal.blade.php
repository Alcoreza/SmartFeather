<div class="modal-overlay" id="exportModal">
    <div class="modal-card export-modal">
        <div class="modal-header">
            <h2>Export Report</h2>
            <button type="button" class="modal-close" id="closeExportModal">&times;</button>
        </div>

        <div class="modal-body">
            <p class="export-modal-label">Choose export format:</p>
            <div class="export-format-options">
                <button type="button" class="export-format-btn" id="exportPdfBtn" data-format="pdf">
                    <span class="export-format-icon">📄</span>
                    <span class="export-format-text">PDF</span>
                </button>
                <button type="button" class="export-format-btn" id="exportCsvBtn" data-format="csv">
                    <span class="export-format-icon">📊</span>
                    <span class="export-format-text">CSV</span>
                </button>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" class="cancel-btn" id="cancelExportModal">Cancel</button>
        </div>
    </div>
</div>
