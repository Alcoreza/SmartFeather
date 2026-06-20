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
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    };

    const openModal = () => {
        const currentPhone = currentPhoneField?.value || currentAdminPhoneField?.value || '';
        const currentAddress = currentAddressField?.value || currentAdminAddressField?.value || '';

        if (phoneField) {
            phoneField.value = currentPhone;
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

    form?.addEventListener('submit', async (event) => {
        if (profileSubmitConfirmed) {
            profileSubmitConfirmed = false;
            return;
        }

        event.preventDefault();
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
