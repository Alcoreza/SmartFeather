const BASE_URL = '/api/login';

const loginErrorModal = document.getElementById('loginErrorModal');
const loginErrorTitle = document.getElementById('loginErrorTitle');
const loginErrorMessage = document.getElementById('loginErrorMessage');
const closeLoginErrorModal = document.getElementById('closeLoginErrorModal');
const loginSubmitBtn = document.getElementById('loginSubmitBtn');
const loginSubmitLabel = loginSubmitBtn?.querySelector('.go-btn-label');

function setLoginLoading(isLoading) {
    if (!loginSubmitBtn || !loginSubmitLabel) return;

    loginSubmitBtn.disabled = isLoading;
    loginSubmitBtn.classList.toggle('is-loading', isLoading);
    loginSubmitLabel.textContent = isLoading ? 'Logging in' : 'Login';
}

function showLoginError(message = 'Wrong username or password.', title = 'Login Failed') {
    if (!loginErrorModal || !loginErrorMessage) {
        alert(message);
        return;
    }

    if (loginErrorTitle) {
        loginErrorTitle.textContent = title;
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

    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const username = usernameInput?.value.trim() || '';
    const password = passwordInput?.value || '';

    if (!username && !password) {
        showLoginError('Please enter your username and password before logging in.', 'Credentials Required');
        usernameInput?.focus();
        return;
    }

    if (!username) {
        showLoginError('Please enter your username before logging in.', 'Username Required');
        usernameInput?.focus();
        return;
    }

    if (!password) {
        showLoginError('Please enter your password before logging in.', 'Password Required');
        passwordInput?.focus();
        return;
    }

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

        sessionStorage.removeItem('smartfeather:logged-out');

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
