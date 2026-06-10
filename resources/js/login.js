const BASE_URL = '/api/login';

const loginErrorModal = document.getElementById('loginErrorModal');
const loginErrorMessage = document.getElementById('loginErrorMessage');
const closeLoginErrorModal = document.getElementById('closeLoginErrorModal');
const loginSubmitBtn = document.getElementById('loginSubmitBtn');
const loginSubmitLabel = loginSubmitBtn?.querySelector('.go-btn-label');

function setLoginLoading(isLoading) {
    if (!loginSubmitBtn || !loginSubmitLabel) return;

    loginSubmitBtn.disabled = isLoading;
    loginSubmitBtn.classList.toggle('is-loading', isLoading);
    loginSubmitLabel.textContent = isLoading ? 'Logging in' : 'Go';
}

function showLoginError(message = 'Wrong username or password.') {
    if (!loginErrorModal || !loginErrorMessage) {
        alert(message);
        return;
    }

    loginErrorMessage.textContent = message;
    loginErrorModal.classList.add('show');
    loginErrorModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    closeLoginErrorModal?.focus();
}

function hideLoginError() {
    if (!loginErrorModal) return;

    loginErrorModal.classList.remove('show');
    loginErrorModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

closeLoginErrorModal?.addEventListener('click', hideLoginError);

loginErrorModal?.addEventListener('click', (event) => {
    if (event.target === loginErrorModal) {
        hideLoginError();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && loginErrorModal?.classList.contains('show')) {
        hideLoginError();
    }
});

document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    try {
        setLoginLoading(true);

        const res = await fetch(BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                username,
                password
            })
        });

        const data = await res.json();

        if (!res.ok) {
            showLoginError(data.message || 'Wrong username or password.');
            setLoginLoading(false);
            return;
        }

        if (data.user.Role === 'Admin') {
            // Use replace() instead of href to replace history entry
            window.location.replace('/admin/dashboard');
        } else if (data.user.Role === 'Manager') {
            // Use replace() instead of href to replace history entry
            window.location.replace('/manager/dashboard');
        } else {
            showLoginError('Unauthorized role.');
            setLoginLoading(false);
        }

    } catch (err) {
        console.error(err);
        showLoginError('Network error. Please try again.');
        setLoginLoading(false);
    }
});
