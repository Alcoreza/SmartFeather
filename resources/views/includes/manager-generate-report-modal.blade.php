<div class="reports-modal-overlay" id="generateReportModal">
    <div class="reports-modal">
        <div class="reports-modal-header">
            <h2>Generate Report</h2>
            <div class="reports-modal-line"></div>
        </div>

        <form class="reports-modal-form">
            <div class="reports-modal-row">
                <div class="reports-field">
                    <label for="reportPeriod">Select Time Period</label>
                    <input type="text" id="reportPeriod" name="report_period">
                </div>
            </div>

            <div class="reports-modal-row">
                <div class="reports-field">
                    <label for="reportFileType">File Type</label>
                    <select id="reportFileType" name="file_type">
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
            </div>

            <div class="reports-modal-actions">
                <button type="button" class="reports-btn reports-btn-cancel" id="closeGenerateReportModal">Cancel</button>
                <button type="submit" class="reports-btn reports-btn-download">Download</button>
            </div>
        </form>
    </div>
</div>