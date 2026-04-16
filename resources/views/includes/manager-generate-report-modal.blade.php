<div class="reports-modal-overlay" id="generateReportModal">
    <div class="reports-modal">
        <div class="reports-modal-header">
            <h2>Generate Report</h2>
            <div class="reports-modal-line"></div>
        </div>

        <form class="reports-modal-form" id="generateReportForm">
            <div class="reports-modal-row">
                <div class="reports-field">
                    <label for="generateReportType">Report Type</label>
                    <input type="text" id="generateReportType" readonly>
                </div>
            </div>

            <div class="reports-modal-row">
                <div class="reports-field">
                    <label for="reportMonth">Month</label>
                    <select id="reportMonth" name="month" required>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
            </div>

            <div class="reports-modal-row">
                <div class="reports-field">
                    <label for="reportYear">Year</label>
                    <input type="number" id="reportYear" name="year" min="2020" max="2100" value="{{ now()->year }}" required>
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