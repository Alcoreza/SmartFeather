document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-profile-edit-modal]');

    if (!modal) {
        return;
    }

    const openButton = document.querySelector('[data-profile-edit-open]');
    const closeButton = document.querySelector('[data-profile-edit-close]');
    const cancelButton = document.querySelector('[data-profile-edit-cancel]');
    const phoneField = document.getElementById('profileEditPhoneNumber');
    const addressField = document.getElementById('profileEditAddress');
    const currentPhoneField = document.getElementById('profilePhone');
    const currentAddressField = document.getElementById('profileAddress');
    const currentAdminPhoneField = document.getElementById('adminProfilePhone');
    const currentAdminAddressField = document.getElementById('adminProfileAddress');
    const form = document.getElementById('profileEditForm');
    let profileSubmitConfirmed = false;

    const sanitizePhoneValue = (value) => String(value || '').replace(/\D/g, '').slice(0, 11);

    const phoneError = document.getElementById('profileEditPhoneError');

    const showPhoneError = (message) => {
        const field = phoneField?.closest('.profile-edit-field');
        field?.classList.add('has-error');
        if (phoneError) {
            phoneError.textContent = message;
            phoneError.classList.add('show');
        }
    };

    const clearPhoneError = () => {
        const field = phoneField?.closest('.profile-edit-field');
        field?.classList.remove('has-error');
        if (phoneError) {
            phoneError.textContent = '';
            phoneError.classList.remove('show');
        }
    };

    const validatePhoneNumber = () => {
        if (!phoneField) {
            return true;
        }

        phoneField.value = sanitizePhoneValue(phoneField.value);

        if (!/^09\d{9}$/.test(phoneField.value)) {
            showPhoneError('Invalid phone number.');
            phoneField.focus();
            return false;
        }

        clearPhoneError();
        return true;
    };

    const checkPhoneAvailability = async () => {
        if (!phoneField) {
            return true;
        }

        try {
            const response = await fetch('/api/profile/check-phone', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ PhoneNumber: phoneField.value }),
            });
            const result = await response.json().catch(() => ({}));

            if (response.status === 409 || result.available === false) {
                showPhoneError('Phone number already in use.');
                phoneField.focus();
                return false;
            }

            if (!response.ok) {
                const message = result.errors?.PhoneNumber?.[0] || result.message || 'Unable to verify phone number. Please try again.';
                showPhoneError(message);
                phoneField.focus();
                return false;
            }

            clearPhoneError();
            return true;
        } catch (error) {
            showPhoneError('Unable to verify phone number. Please try again.');
            phoneField.focus();
            return false;
        }
    };

    const showProfileConfirmModal = (message) => new Promise((resolve) => {
        document.getElementById('profileGenericConfirmModal')?.remove();

        const confirmModal = document.createElement('div');
        confirmModal.className = 'profile-edit-modal-backdrop confirm-modal-top show';
        confirmModal.id = 'profileGenericConfirmModal';
        confirmModal.innerHTML = `
            <div class="profile-edit-modal-card">
                <div class="profile-edit-modal-header">
                    <div>
                        <h2>Confirm Save</h2>
                        <p></p>
                    </div>
                </div>
                <div class="profile-edit-actions">
                    <button type="button" class="profile-edit-btn cancel-btn" data-confirm-cancel>Cancel</button>
                    <button type="button" class="profile-edit-btn save-btn" data-confirm-ok>Confirm</button>
                </div>
            </div>
        `;

        confirmModal.querySelector('p').textContent = message;

        const close = (confirmed) => {
            confirmModal.remove();
            resolve(confirmed);
        };

        confirmModal.querySelector('[data-confirm-cancel]').addEventListener('click', () => close(false));
        confirmModal.querySelector('[data-confirm-ok]').addEventListener('click', () => close(true));
        confirmModal.addEventListener('click', (event) => {
            if (event.target === confirmModal) close(false);
        });

        document.body.appendChild(confirmModal);
    });

    const closeModal = () => {
        clearPhoneError();
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    };

    const openModal = () => {
        const currentPhone = currentPhoneField?.value || currentAdminPhoneField?.value || '';
        const currentAddress = currentAddressField?.value || currentAdminAddressField?.value || '';

        if (phoneField) {
            phoneField.value = sanitizePhoneValue(currentPhone);
            clearPhoneError();
        }

        if (addressField) {
            addressField.value = currentAddress;
        }

        modal.classList.add('show');
        document.body.classList.add('modal-open');

        if (phoneField) {
            phoneField.focus();
        }
    };

    openButton?.addEventListener('click', openModal);
    closeButton?.addEventListener('click', closeModal);
    cancelButton?.addEventListener('click', closeModal);

    phoneField?.addEventListener('input', () => {
        const sanitized = sanitizePhoneValue(phoneField.value);
        if (phoneField.value !== sanitized) {
            phoneField.value = sanitized;
        }

        if (phoneField.value.length === 11 && /^09\d{9}$/.test(phoneField.value)) {
            clearPhoneError();
        }
    });

    form?.addEventListener('submit', async (event) => {
        if (profileSubmitConfirmed) {
            profileSubmitConfirmed = false;
            return;
        }

        event.preventDefault();

        if (!validatePhoneNumber()) {
            return;
        }

        if (!await checkPhoneAvailability()) {
            return;
        }

        const confirmed = await showProfileConfirmModal('Save changes to your profile?');

        if (confirmed) {
            profileSubmitConfirmed = true;
            form.requestSubmit();
        }
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
});
