<div class="manager-sensor-modal-backdrop" id="sensorViewModal">
    <div class="manager-sensor-modal-card">
        <div class="manager-sensor-modal-header center">
            <h2>View Sensor Configuration</h2>
            <div class="manager-sensor-header-line"></div>
        </div>

        <div class="manager-sensor-modal-body">
            <div class="manager-sensor-modal-field full">
                <label>Sensor Type</label>
                <input type="text" id="sensorViewType" readonly>
            </div>

            <div class="manager-sensor-modal-field full">
                <label>Sensor Name</label>
                <input type="text" id="sensorViewName" readonly>
            </div>

            <div class="manager-sensor-modal-grid">
                <div class="manager-sensor-modal-field">
                    <label>House Name</label>
                    <input type="text" id="sensorViewHouse" readonly>
                </div>

                <div class="manager-sensor-modal-field">
                    <label>Pen Number</label>
                    <input type="text" id="sensorViewPen" readonly>
                </div>
            </div>

            <div class="manager-sensor-modal-actions">
                <button type="button" class="manager-sensor-btn close"
                    data-close-sensor-modal="sensorViewModal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="manager-sensor-modal-backdrop" id="sensorThresholdModal">
    <div class="manager-sensor-modal-card">
        <div class="manager-sensor-modal-header center">
            <h2>Sensor Threshold</h2>
            <div class="manager-sensor-header-line"></div>
        </div>

        <div class="manager-sensor-modal-body">
            <div class="manager-sensor-modal-field full">
                <label>Sensor Type</label>
                <input type="text" id="sensorThresholdType" readonly>
            </div>

            <div class="manager-sensor-modal-grid">
                <div class="manager-sensor-modal-field">
                    <label>Lowest Threshold</label>
                    <input type="text" id="sensorThresholdLow" readonly>
                </div>

                <div class="manager-sensor-modal-field">
                    <label>Highest Threshold</label>
                    <input type="text" id="sensorThresholdHigh" readonly>
                </div>
            </div>

            <div class="manager-sensor-modal-actions">
                <button type="button" class="manager-sensor-btn close"
                    data-close-sensor-modal="sensorThresholdModal">Close</button>
            </div>
        </div>
    </div>
</div>