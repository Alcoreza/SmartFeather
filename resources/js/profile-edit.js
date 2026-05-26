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
