const state = {
    selectedCategory: 'Personnel Biosecurity Logs',
    logs: {},
    houses: [],
    workers: [],
    pens: [],
    allWorkers: [],
};

let visitorCameraStream = null;
let visitorPhotoDataUrl = null;

const TABLE_CONFIG = {
    'Personnel Biosecurity Logs': {
        title: 'Personnel Biosecurity Logs',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'role', label: 'Role' },
            { key: 'date', label: 'Date' },
            { key: 'time', label: 'Time' },
            { key: 'status', label: 'Status' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'name', label: 'Name', type: 'text' },
                    { key: 'role', label: 'Role', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'date', label: 'Date', type: 'date' },
                    { key: 'time', label: 'Time', type: 'time' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    {
                        key: 'status',
                        label: 'Status',
                        type: 'select',
                        options: ['IN', 'OUT'],
                        full: true,
                    },
                ],
            },
        ],
    },

    'Visitors': {
        title: 'Visitors',
        columns: [
            { key: 'date', label: 'Date' },
            { key: 'time_in', label: 'Time In' },
            { key: 'time_out', label: 'Time Out' },
            { key: 'name', label: 'Name' },
            { key: 'photo_url', label: 'Photo' },
            { key: 'purpose', label: 'Purpose' },
            { key: 'foot_bath', label: 'Foot Bath' },
            { key: 'sanitation', label: 'Sanitation' },
            { key: 'ppe', label: 'PPE' },
            { key: 'monitored_by', label: 'Monitored<br>By:' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'date', label: 'Date', type: 'date' },
                    { key: 'name', label: 'Name', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'time_in', label: 'Time In', type: 'time' },
                    { key: 'time_out', label: 'Time Out', type: 'time' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'purpose', label: 'Purpose', type: 'text', full: true },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    {
                        key: 'foot_bath',
                        label: 'Foot Bath',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                    {
                        key: 'sanitation',
                        label: 'Sanitation',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    {
                        key: 'ppe',
                        label: 'PPE',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                    {
                        key: 'monitored_by',
                        label: 'Monitored By',
                        type: 'select',
                        options: 'allWorkers',
                    },
                ],
            },
        ],
    },
};

const tableHead = document.getElementById('bioTableHead');
const tableBody = document.getElementById('bioLogsTableBody');
const filterSelect = document.getElementById('bioCategoryFilter');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setOverview(overview) {
    const violations = document.getElementById('bioViolations');
    const visitors = document.getElementById('bioVisitors');
    const mortalities = document.getElementById('bioMortalities');
    const disinfectionDate = document.getElementById('bioDisinfectionDate');
    const disinfectionTime = document.getElementById('bioDisinfectionTime');

    if (violations) violations.textContent = overview?.violations ?? 0;
    if (visitors) visitors.textContent = overview?.visitors ?? 0;
    if (mortalities) mortalities.textContent = overview?.mortalities ?? 0;
    if (disinfectionDate) disinfectionDate.textContent = overview?.last_disinfection?.date ?? '--';
    if (disinfectionTime) disinfectionTime.textContent = overview?.last_disinfection?.time ?? '--';
}

function renderTableHead(type) {
    const config = TABLE_CONFIG[type];
    if (!config || !tableHead) return;

    const headers = config.columns.map((col) => `<th>${col.label}</th>`).join('');

    tableHead.innerHTML = `
        <tr>
            ${headers}
            <th></th>
        </tr>
    `;
}

function renderTableRows(type, rows) {
    const config = TABLE_CONFIG[type];
    if (!config || !tableBody) return;

    if (!rows.length) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="${config.columns.length + 1}" class="bio-empty">
                    No logs found for this category.
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = rows.map((row) => {
        const cells = config.columns.map((col) => {
            if (col.key === 'photo_url') {
                return `
                    <td>
                        ${row.photo_url ? `<img src="${escapeHtml(row.photo_url)}" alt="Visitor photo" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:8px;">` : 'No photo'}
                    </td>
                `;
            }
            return `
                <td>${escapeHtml(row[col.key])}</td>
            `;
        }).join('');

        const encodedRow = encodeURIComponent(JSON.stringify(row));

        return `
            <tr>
                ${cells}
                <td>
                    <button
                        type="button"
                        class="bio-action-btn edit-btn"
                        data-mode="${escapeHtml(type)}"
                        data-log="${encodedRow}"
                        aria-label="Edit log"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-4-4L4 16v4z"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderCurrentTable() {
    const type = state.selectedCategory;
    const rows = state.logs[type] || [];

    renderTableHead(type);
    renderTableRows(type, rows);
}

function createFieldHtml(prefix, field, value = '') {
    const fieldId = `${prefix}_${field.key}`;
    const wrapperClass = field.full ? 'bio-field full' : 'bio-field';

    if (field.type === 'select') {
        let options = field.options || [];
        
        // Handle dynamic options from state
        if (field.options === 'houses' && state.houses) {
            options = state.houses.map(h => ({ value: h.id, label: h.number }));
        } else if (field.options === 'workers' && state.workers) {
            options = state.workers.map(w => ({ value: w.id, label: w.name }));
        } else if (field.options === 'allWorkers' && state.allWorkers) {
            options = state.allWorkers.map(w => ({ value: w.id, label: w.name }));
        } else if (field.options === 'pens' && state.pens) {
            options = state.pens.map(p => ({ value: p.number, label: p.label }));
        }

        const optionsHtml = options.map((option) => {
            const optValue = option.value ?? option;
            const optLabel = option.label ?? option;
            return `<option value="${escapeHtml(optValue)}" ${String(value) === String(optValue) ? 'selected' : ''}>
                ${escapeHtml(optLabel)}
            </option>`;
        }).join('');

        return `
            <div class="${wrapperClass}">
                <label for="${fieldId}">${field.label}</label>
                <select id="${fieldId}" name="${field.key}" class="bio-select-placeholder">
                    <option value="">---</option>
                    ${optionsHtml}
                </select>
            </div>
        `;
    }

    // For date/time fields, set max to today
    let extraAttrs = '';
    if (field.type === 'date') {
        extraAttrs = `max="${new Date().toISOString().split('T')[0]}"`;
    }

    return `
        <div class="${wrapperClass}">
            <label for="${fieldId}">${field.label}</label>
            <input
                type="${field.type || 'text'}"
                id="${fieldId}"
                name="${field.key}"
                value="${escapeHtml(value)}"
                ${extraAttrs}
            >
        </div>
    `;
}

function createVisitorPhotoSection(prefix) {
    return `
        <div class="bio-modal-row">
            <div class="bio-field full">
                <label>Visitor Photo</label>
                <div class="visitor-photo-section" style="display:flex; flex-direction:column; gap:10px;">
                    <button type="button" class="bio-btn bio-btn-save" id="${prefix}_open_camera_btn">Use Camera</button>
                    <img id="${prefix}_photo_preview" src="" alt="Visitor photo preview" style="display:none; width:100%; max-height:180px; object-fit:cover; border-radius:12px; border:1px solid #d1d1d1;" />
                    <div id="${prefix}_photo_status" style="font-size:0.9rem; color:#444;"></div>
                </div>
            </div>
        </div>
    `;
}

function buildModalFields(prefix, type, values = {}) {
    const config = TABLE_CONFIG[type];
    if (!config) return '';

    const fieldsHtml = config.form.map((row) => {
        const rowFieldsHtml = row.fields
            .map((field) => createFieldHtml(prefix, field, values[field.key] ?? ''))
            .join('');

        return `<div class="${row.rowClass}">${rowFieldsHtml}</div>`;
    }).join('');

    if (type === 'Visitors' && prefix === 'add') {
        return fieldsHtml + createVisitorPhotoSection(prefix);
    }

    return fieldsHtml;
}

function decodeRowData(encodedData) {
    if (!encodedData) return {};

    try {
        return JSON.parse(decodeURIComponent(encodedData));
    } catch (error) {
        console.error('Invalid row data:', error);
        return {};
    }
}

function updateVisitorPhotoPreview(photoUrl, statusText = '') {
    const previewImg = document.getElementById('add_photo_preview');
    const status = document.getElementById('add_photo_status');
    const hiddenInput = document.getElementById('add_photo_url');

    if (!previewImg || !hiddenInput) return;

    if (photoUrl) {
        previewImg.src = photoUrl;
        previewImg.style.display = 'block';
        hiddenInput.value = photoUrl.replace(/^https?:\/\/(.*?\/storage\/v1\/object\/public\/[^/]+\/)/, '');
        if (status) {
            status.textContent = statusText || 'Photo ready to save.';
        }
    } else {
        previewImg.style.display = 'none';
        hiddenInput.value = '';
        if (status) {
            status.textContent = statusText;
        }
    }
}

function closeVisitorCameraModal() {
    const cameraModal = document.getElementById('visitorCameraModal');
    const video = document.getElementById('visitorCameraVideo');
    const snapshot = document.getElementById('visitorCameraSnapshot');

    if (cameraModal) {
        cameraModal.classList.remove('show');
    }

    if (video && video.srcObject) {
        const tracks = video.srcObject.getTracks();
        tracks.forEach((track) => track.stop());
        video.srcObject = null;
    }

    if (snapshot) {
        snapshot.style.display = 'none';
    }

    visitorPhotoDataUrl = null;
    visitorCameraStream = null;
}

async function openVisitorCameraModal() {
    const cameraModal = document.getElementById('visitorCameraModal');
    const video = document.getElementById('visitorCameraVideo');
    const snapshot = document.getElementById('visitorCameraSnapshot');
    const useButton = document.getElementById('useVisitorPhotoBtn');

    if (!cameraModal || !video || !snapshot || !useButton) return;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not available in this browser.');
        return;
    }

    try {
        visitorCameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = visitorCameraStream;
        video.play();
        snapshot.style.display = 'none';
        useButton.disabled = true;
        cameraModal.classList.add('show');
    } catch (error) {
        console.error('Camera error:', error);
        alert('Could not access the camera. Please allow camera access or use a supported browser.');
    }
}

function captureVisitorPhoto() {
    const video = document.getElementById('visitorCameraVideo');
    const canvas = document.getElementById('visitorCameraCanvas');
    const snapshot = document.getElementById('visitorCameraSnapshot');
    const useButton = document.getElementById('useVisitorPhotoBtn');

    if (!video || !canvas || !snapshot || !useButton) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    if (!context) return;

    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    visitorPhotoDataUrl = canvas.toDataURL('image/jpeg', 0.9);
    snapshot.src = visitorPhotoDataUrl;
    snapshot.style.display = 'block';
    useButton.disabled = false;
}

async function uploadVisitorPhoto() {
    const status = document.getElementById('add_photo_status');
    const useButton = document.getElementById('useVisitorPhotoBtn');
    const openCameraButton = document.getElementById('add_open_camera_btn');

    if (!visitorPhotoDataUrl) {
        alert('Please capture a photo first.');
        return;
    }

    if (status) {
        status.textContent = 'Uploading photo...';
    }
    if (useButton) {
        useButton.disabled = true;
    }
    if (openCameraButton) {
        openCameraButton.disabled = true;
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || "";

        const response = await fetch('/api/manager/biosecurity-logs/visitor-photo', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                photo_data: visitorPhotoDataUrl,
                mime_type: 'image/jpeg',
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => null);
            console.error('Photo upload error:', errorData);
            throw new Error('Photo upload failed');
        }

        const data = await response.json();
        updateVisitorPhotoPreview(data.photo_url, 'Photo attached.');
        closeVisitorCameraModal();
    } catch (error) {
        console.error('Error uploading visitor photo:', error);
        alert('Failed to upload photo. Please try again.');
        if (status) {
            status.textContent = 'Upload failed. Try again.';
        }
    } finally {
        if (useButton) {
            useButton.disabled = false;
        }
        if (openCameraButton) {
            openCameraButton.disabled = false;
        }
    }
}

function attachVisitorPhoto() {
    // store the DataURL in a hidden input so it will be uploaded with the form on Save
    const hiddenInput = document.getElementById('add_photo_data');
    const preview = document.getElementById('add_photo_preview');
    const status = document.getElementById('add_photo_status');

    if (!visitorPhotoDataUrl) {
        alert('Please capture a photo first.');
        return;
    }

    if (hiddenInput) hiddenInput.value = visitorPhotoDataUrl;
    if (preview) {
        preview.src = visitorPhotoDataUrl;
        preview.style.display = 'block';
    }
    if (status) status.textContent = '';

    // close camera modal but keep photo data for save
    closeVisitorCameraModal();
}

function setupEditModal() {
    const modal = document.getElementById('editBioModal');
    const closeBtn = document.getElementById('closeBioModal');
    const title = document.getElementById('editBioModalTitle');
    const fieldsWrap = document.getElementById('editBioModalFields');
    const logIdInput = document.getElementById('editLogId');
    const logTypeInput = document.getElementById('editLogType');

    if (!modal || !closeBtn || !title || !fieldsWrap) return;

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('.edit-btn');
        if (!btn) return;

        const type = btn.dataset.mode || 'Personnel Biosecurity Logs';
        const rowData = decodeRowData(btn.dataset.log);

        // Load form options if not already loaded
        if (!state.houses || !state.workers) {
            await loadBioFormOptions();
        }

        title.textContent = `Edit ${type}`;
        fieldsWrap.innerHTML = buildModalFields('edit', type, rowData);

        if (logIdInput) logIdInput.value = rowData.id ?? '';
        if (logTypeInput) logTypeInput.value = type;

        // Set up house change event to load pens for edit modal
        const houseSelect = document.getElementById('edit_house');
        const penSelect = document.getElementById('edit_pen');
        
        if (houseSelect) {
            houseSelect.addEventListener('change', async () => {
                const houseId = houseSelect.value;
                if (houseId && penSelect) {
                    await loadBioPensForHouse(houseId, 'edit_pen');
                }
            });

            // If there's a house value, load pens for it
            if (rowData.house) {
                await loadBioPensForHouse(rowData.house, 'edit_pen');
            }
        }

        modal.classList.add('show');
    });

    // Handle form submission for edit
    const form = document.getElementById('editBioForm');
    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const logId = logIdInput?.value;

            if (!logId) {
                alert('Invalid log ID');
                return;
            }

            try {
                const response = await fetch(`/api/manager/biosecurity-logs/${logId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Server error:', errorData);
                    throw new Error(`Failed to update: ${response.status}`);
                }

                const result = await response.json();
                console.log('Log updated:', result);

                modal.classList.remove('show');

                // Reload logs to show the updated entry
                loadBiosecurityLogs();
            } catch (error) {
                console.error('Error updating log:', error);
                alert('Failed to update log. Please try again.');
            }
        });
    }

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('show');
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
}

function setupAddModal() {
    const modal = document.getElementById('addBioModal');
    const openBtn = document.getElementById('openAddBioModal');
    const closeBtn = document.getElementById('closeAddBioModal');
    const title = document.getElementById('addBioModalTitle');
    const fieldsWrap = document.getElementById('addBioModalFields');
    const logTypeInput = document.getElementById('addLogType');
    const formError = document.getElementById('addBioFormError');

    if (!modal || !openBtn || !closeBtn || !title || !fieldsWrap) return;

    openBtn.addEventListener('click', async () => {
        const type = state.selectedCategory;

        // Load form options for available categories
        if (type === 'Personnel Biosecurity Logs' || type === 'Visitors') {
            await loadBioFormOptions();
        }

        // Also load all workers for Visitors modal
        if (type === 'Visitors' && (!state.allWorkers || state.allWorkers.length === 0)) {
            await loadAllWorkers();
        } else if (!state.houses || !state.workers) {
            await loadBioFormOptions();
        }

        title.textContent = `Add ${type}`;
        fieldsWrap.innerHTML = buildModalFields('add', type, {});
        clearAddBioFormError();

        const photoInput = document.getElementById('add_photo_url');
        const photoPreview = document.getElementById('add_photo_preview');
        const photoStatus = document.getElementById('add_photo_status');

        if (photoInput) {
            photoInput.value = '';
        }
        if (photoPreview) {
            photoPreview.style.display = 'none';
            photoPreview.src = '';
        }
        if (photoStatus) {
            photoStatus.textContent = '';
        }

        visitorPhotoDataUrl = null;

        if (logTypeInput) logTypeInput.value = type;

        // Set up house change event to load pens (when present)
        const houseSelect = document.getElementById('add_house');
        const penSelect = document.getElementById('add_pen');
        
        if (houseSelect) {
            houseSelect.addEventListener('change', async () => {
                const houseId = houseSelect.value;
                if (houseId && penSelect) {
                    await loadBioPensForHouse(houseId, 'add_pen');
                }
            });
            
            // If a house is already selected, load pens for it
            const initialHouseId = houseSelect.value;
            if (initialHouseId) {
                await loadBioPensForHouse(initialHouseId, 'add_pen');
            }
        }

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('#add_open_camera_btn')) {
            openVisitorCameraModal();
        }

        if (event.target.closest('#captureVisitorPhotoBtn')) {
            captureVisitorPhoto();
        }

        if (event.target.closest('#useVisitorPhotoBtn')) {
            // Attach photo locally; actual upload occurs on Save
            attachVisitorPhoto();
        }

        if (event.target.closest('#closeVisitorCameraModal')) {
            closeVisitorCameraModal();
        }
    });

    // Handle form submission
    const form = document.getElementById('addBioForm');
    if (form) {
        form.addEventListener('input', (event) => {
            if (event.target.matches('input, select')) {
                clearBioFieldError(event.target);
            }
        });

        form.addEventListener('change', (event) => {
            if (event.target.matches('input, select')) {
                clearBioFieldError(event.target);
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            const missingFields = validateAddBioRequiredFields(form, payload);

            if (missingFields.length) {
                showAddBioFormError(formError, missingFields);
                return;
            }

            try {
                const response = await fetch('/api/manager/biosecurity-logs', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Server error:', errorData);
                    throw new Error(`Failed to save: ${response.status}`);
                }

                const result = await response.json();
                console.log('Log saved:', result);

                modal.classList.remove('show');
                document.body.style.overflow = '';

                // Reload logs to show the new entry
                loadBiosecurityLogs();
            } catch (error) {
                console.error('Error saving log:', error);
                showAddBioFormError(formError, 'Failed to save log. Please try again.');
            }
        });
    }

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        clearAddBioFormError();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            clearAddBioFormError();
        }
    });
}

function validateAddBioRequiredFields(form, payload) {
    const requiredFieldsByType = {
        'Personnel Biosecurity Logs': [
            { name: 'name', label: 'Name' },
            { name: 'role', label: 'Role' },
            { name: 'date', label: 'Date' },
            { name: 'time', label: 'Time' },
            { name: 'status', label: 'Status' },
        ],
        'Visitors': [
            { name: 'date', label: 'Date' },
            { name: 'name', label: 'Name' },
            { name: 'time_in', label: 'Time In' },
            { name: 'time_out', label: 'Time Out' },
            { name: 'purpose', label: 'Purpose' },
            { name: 'foot_bath', label: 'Foot Bath' },
            { name: 'sanitation', label: 'Sanitation' },
            { name: 'ppe', label: 'PPE' },
            { name: 'monitored_by', label: 'Monitored By' },
            { name: 'photo_data', label: 'Visitor Photo' },
        ],
    };

    const requiredFields = requiredFieldsByType[payload.type] || [];
    const missingFields = requiredFields.filter(({ name }) => {
        return !String(payload[name] ?? '').trim();
    });

    form.querySelectorAll('.bio-field.has-error').forEach((field) => {
        field.classList.remove('has-error');
    });

    missingFields.forEach(({ name }) => {
        const field = form.elements[name];
        const fieldWrapper = field?.closest('.bio-field')
            || document.getElementById('add_photo_preview')?.closest('.bio-field');

        fieldWrapper?.classList.add('has-error');
    });

    if (missingFields.length) {
        if (missingFields[0].name === 'photo_data') {
            document.getElementById('add_open_camera_btn')?.focus();
        } else {
            form.elements[missingFields[0].name]?.focus();
        }
    }

    return missingFields.map(({ label }) => label);
}

function showAddBioFormError(formError, messageOrFields) {
    if (!formError) return;

    if (Array.isArray(messageOrFields)) {
        formError.textContent = messageOrFields.length === 1
            ? `${messageOrFields[0]} is required.`
            : `Please complete all required fields: ${messageOrFields.join(', ')}.`;
    } else {
        formError.textContent = messageOrFields;
    }

    formError.classList.add('show');
    formError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearAddBioFormError() {
    const form = document.getElementById('addBioForm');
    const formError = document.getElementById('addBioFormError');

    formError?.classList.remove('show');
    if (formError) {
        formError.textContent = '';
    }

    form?.querySelectorAll('.bio-field.has-error').forEach((field) => {
        field.classList.remove('has-error');
    });
}

function clearBioFieldError(field) {
    field.closest('.bio-field')?.classList.remove('has-error');

    const form = document.getElementById('addBioForm');
    const hasErrors = form?.querySelector('.bio-field.has-error');
    if (!hasErrors) {
        clearAddBioFormError();
    }
}

function bindEvents() {
    if (!filterSelect) return;

    filterSelect.addEventListener('change', (event) => {
        state.selectedCategory = event.target.value;
        renderCurrentTable();
    });
}

async function loadBiosecurityLogs() {
    try {
        const response = await fetch('/api/manager/biosecurity-logs', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();

        setOverview(data.overview || {});
        state.logs = data.logs || {};

        renderCurrentTable();
    } catch (error) {
        console.error('Failed to load biosecurity logs:', error);

        if (tableHead) {
            tableHead.innerHTML = `
                <tr>
                    <th>Logs</th>
                </tr>
            `;
        }

        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td class="bio-empty">Unable to load biosecurity logs.</td>
                </tr>
            `;
        }
    }
}

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

function setupProfileModal() {
    const modal = document.getElementById('profileModal');
    const openBtn = document.getElementById('openProfileModal');
    const closeBtn = document.getElementById('closeProfileModal');

    if (!modal || !openBtn) return;

    openBtn.addEventListener('click', async () => {
        await populateProfileModal();
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
}

async function loadBioFormOptions() {
    try {
        const response = await fetch("/api/manager/tasks/form-options");
        const data = await response.json();

        // Store for reuse
        state.houses = data.houses || [];
        state.workers = data.workers || [];
        state.pens = [];

        return { houses: state.houses, workers: state.workers };
    } catch (error) {
        console.error("Failed to load form options:", error);
        return { houses: [], workers: [], pens: [] };
    }
}

async function loadAllWorkers() {
    try {
        const response = await fetch("/api/manager/tasks/all-workers");
        const data = await response.json();

        // Store all workers for Visitors modal
        state.allWorkers = data.workers || [];

        return state.allWorkers;
    } catch (error) {
        console.error("Failed to load all workers:", error);
        return [];
    }
}

async function loadBioPensForHouse(houseId, selectId) {
    const penSelect = document.getElementById(selectId);
    if (!penSelect) return;

    if (!houseId) {
        penSelect.innerHTML = '<option value="">---</option>';
        penSelect.disabled = true;
        return;
    }

    try {
        const response = await fetch(`/api/manager/tasks/houses/${houseId}/pens`);
        const data = await response.json();

        state.pens = data.pens || [];

        penSelect.innerHTML = '<option value="">---</option>' +
            state.pens.map(p => `<option value="${p.number}">${p.label}</option>`).join('');
        
        penSelect.disabled = false;
    } catch (error) {
        console.error("Failed to load pens:", error);
        penSelect.innerHTML = '<option value="">Error</option>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    bindEvents();
    loadBiosecurityLogs();
    setupEditModal();
    setupAddModal();
    setupProfileModal();
});
