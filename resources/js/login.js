const BASE_URL = '/api/login';

document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const user_id = document.getElementById('user_id').value;
    const password = document.getElementById('password').value;

    try {
        const res = await fetch(BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                user_id,
                password
            })
        });

        const data = await res.json();

        if (!res.ok) {
            alert(data.message || 'Login failed');
            return;
        }

        // ✅ Redirect based on role
        if (data.user.Role === 'Admin') {
            window.location.href = '/admin/dashboard';
        } else if (data.user.Role === 'Manager') {
            window.location.href = '/manager/dashboard';
        } else {
            alert('Unauthorized role');
        }

    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});
