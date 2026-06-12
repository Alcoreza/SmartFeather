<div class="admin-sensor-modal-backdrop" id="adminSensorViewModal">
    <div class="admin-sensor-modal-card">
        <div class="admin-sensor-modal-header center">
            <h2>View Sensor Configuration</h2>
            <div class="admin-sensor-header-line"></div>
        </div>

        <div class="admin-sensor-modal-body">
            <div class="admin-sensor-modal-field full">
                <label>Sensor Type</label>
                <input type="text" id="adminSensorViewType" readonly>
            </div>

            <div class="admin-sensor-modal-field full">
                <label>Sensor Name</label>
                <input type="text" id="adminSensorViewName" readonly>
            </div>

            <div class="admin-sensor-modal-grid">
                <div class="admin-sensor-modal-field">
                    <label>House Number</label>
                    <input type="text" id="adminSensorViewHouse" readonly>
                </div>

                <div class="admin-sensor-modal-field">
                    <label>Pen Number</label>
                    <input type="text" id="adminSensorViewPen" readonly>
                </div>
            </div>

            <div class="admin-sensor-modal-actions">
                <button type="button" class="admin-sensor-btn close"
                    data-close-admin-sensor-modal="adminSensorViewModal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="admin-sensor-modal-backdrop" id="adminSensorEditModal">
    <div class="admin-sensor-modal-card admin-sensor-form-modal">
        <div class="admin-sensor-modal-header center">
            <h2>Edit Sensor Configuration</h2>
            <div class="admin-sensor-header-line"></div>
        </div>

        <form class="admin-sensor-form-body" id="adminSensorEditForm">
            <div class="admin-sensor-form-error" id="adminSensorEditFormError" role="alert" aria-live="polite"></div>

            <div class="admin-sensor-form-field full">
                <label for="adminSensorEditType">Sensor Type</label>
                <select id="adminSensorEditType" name="sensor_type" class="admin-sensor-select-placeholder"></select>
            </div>

            <div class="admin-sensor-form-field full">
                <label for="adminSensorEditName">Sensor Name</label>
                <input type="text" id="adminSensorEditName" name="sensor_name">
            </div>

            <div class="admin-sensor-form-grid">
                <div class="admin-sensor-form-field">
                    <label for="adminSensorEditHouse">House Number</label>
                    <select id="adminSensorEditHouse" name="house_houseid"
                        class="admin-sensor-select-placeholder"></select>
                </div>

                <div class="admin-sensor-form-field">
                    <label for="adminSensorEditPen">Pen Number</label>
                    <select id="adminSensorEditPen" name="pen_penid" class="admin-sensor-select-placeholder"></select>
                </div>
            </div>

            <div class="admin-sensor-form-grid">
                <div class="admin-sensor-form-field">
                    <label for="adminSensorEditFeederNumber">Feeder Number</label>
                    <input type="number" id="adminSensorEditFeederNumber" name="feeder_number" min="1" step="1">
                </div>

                <div class="admin-sensor-form-field">
                    <label for="adminSensorEditDrinkerNumber">Drinker Number</label>
                    <input type="number" id="adminSensorEditDrinkerNumber" name="drinker_number" min="1" step="1">
                </div>
            </div>

            <input type="hidden" id="adminSensorEditId" name="sensor_id">
            <div class="admin-sensor-modal-actions">
                <button type="button" class="admin-sensor-btn close"
                    data-close-admin-sensor-modal="adminSensorEditModal">Close</button>
                <button type="submit" class="admin-sensor-btn save">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-sensor-modal-backdrop" id="adminSensorAddModal">
    <div class="admin-sensor-modal-card admin-sensor-form-modal">
        <div class="admin-sensor-modal-header center">
            <h2>Add Sensor Configuration</h2>
            <div class="admin-sensor-header-line"></div>
        </div>

        <form class="admin-sensor-form-body" id="adminSensorAddForm">
            <div class="admin-sensor-form-error" id="adminSensorAddFormError" role="alert" aria-live="polite"></div>

            <div class="admin-sensor-form-field full">
                <label for="adminSensorAddType">Sensor Type*</label>
                <select id="adminSensorAddType" name="sensor_type" class="admin-sensor-select-placeholder"></select>
            </div>

            <div class="admin-sensor-form-field full">
                <label for="adminSensorAddName">Sensor Name*</label>
                <input type="text" id="adminSensorAddName" name="sensor_name">
            </div>

            <div class="admin-sensor-form-grid">
                <div class="admin-sensor-form-field">
                    <label for="adminSensorAddHouse">House Number*</label>
                    <select id="adminSensorAddHouse" name="house_houseid"
                        class="admin-sensor-select-placeholder"></select>
                </div>

                <div class="admin-sensor-form-field">
                    <label for="adminSensorAddPen">Pen Number*</label>
                    <select id="adminSensorAddPen" name="pen_penid" class="admin-sensor-select-placeholder"></select>
                </div>
            </div>

            <div class="admin-sensor-form-grid">
                <div class="admin-sensor-form-field">
                    <label for="adminSensorAddFeederNumber">Feeder Number</label>
                    <input type="number" id="adminSensorAddFeederNumber" name="feeder_number" min="1" step="1">
                </div>

                <div class="admin-sensor-form-field">
                    <label for="adminSensorAddDrinkerNumber">Drinker Number</label>
                    <input type="number" id="adminSensorAddDrinkerNumber" name="drinker_number" min="1" step="1">
                </div>
            </div>

            <div class="admin-sensor-modal-actions">
                <button type="button" class="admin-sensor-btn close"
                    data-close-admin-sensor-modal="adminSensorAddModal">Close</button>
                <button type="submit" class="admin-sensor-btn save">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-sensor-modal-backdrop" id="adminSensorThresholdModal">
    <div class="admin-sensor-modal-card">
        <div class="admin-sensor-modal-header center">
            <h2>Sensor Threshold</h2>
            <div class="admin-sensor-header-line"></div>
        </div>

        <div class="admin-sensor-modal-body">
            <input type="hidden" id="adminSensorThresholdType" readonly>
            <div class="admin-sensor-modal-field full">
                <label>Sensor Type</label>
                <input type="text" id="adminSensorThresholdTypeDisplay" readonly>
            </div>

            <div class="admin-sensor-modal-grid">
                <div class="admin-sensor-modal-field">
                    <label>Lowest Threshold</label>
                    <input type="number" id="adminSensorThresholdLow" step="any">
                </div>

                <div class="admin-sensor-modal-field">
                    <label>Highest Threshold</label>
                    <input type="number" id="adminSensorThresholdHigh" step="any">
                </div>
            </div>

            <div class="admin-sensor-modal-actions">
                <button type="button" class="admin-sensor-btn close"
                    data-close-admin-sensor-modal="adminSensorThresholdModal">Close</button>
                <button type="button" class="admin-sensor-btn save" id="adminSensorThresholdSave">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="admin-sensor-modal-backdrop" id="adminSensorDeleteModal">
    <div class="admin-sensor-modal-card admin-sensor-delete-card">
        <div class="admin-sensor-modal-header center">
            <h2>Delete Sensor</h2>
            <div class="admin-sensor-header-line"></div>
        </div>

        <div class="admin-sensor-modal-body">
            <p class="admin-sensor-delete-text">This placeholder will later connect to backend delete confirmation.</p>

            <input type="hidden" id="adminSensorDeleteId">
            <div class="admin-sensor-modal-actions">
                <button type="button" class="admin-sensor-btn close"
                    data-close-admin-sensor-modal="adminSensorDeleteModal">Close</button>
                <button type="button" class="admin-sensor-btn delete">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="admin-sensor-modal-backdrop" id="adminSensorStatusModal">
    <div class="admin-sensor-modal-card admin-sensor-delete-card">
        <div class="admin-sensor-modal-header center">
            <h2>Update Sensor Status</h2>
            <div class="admin-sensor-header-line"></div>
        </div>

        <div class="admin-sensor-modal-body">
            <p class="admin-sensor-delete-text" id="adminSensorStatusText">
                Are you sure you want to update this sensor status?
            </p>

            <input type="hidden" id="adminSensorStatusId">
            <input type="hidden" id="adminSensorStatusValue">

            <div class="admin-sensor-modal-actions">
                <button type="button" class="admin-sensor-btn close"
                    data-close-admin-sensor-modal="adminSensorStatusModal">
                    Cancel
                </button>
                <button type="button" class="admin-sensor-btn save" id="adminSensorStatusConfirm">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
