function formatBirthday(birthday) {
    if (!birthday) {
        return '';
    }

    const parsedDate = new Date(birthday);

    if (Number.isNaN(parsedDate.getTime())) {
        return birthday;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(parsedDate);
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('/api/user');
        if (!response.ok) {
            throw new Error('Failed to fetch user info');
        }

        const user = await response.json();

        document.getElementById('profileFirstName').value = user.FirstName || '';
        document.getElementById('profileMiddleName').value = user.MiddleName || '';
        document.getElementById('profileLastName').value = user.LastName || '';
        document.getElementById('profileSuffix').value = user.Suffix || '';
        document.getElementById('profileRole').value = user.Role || '';
        document.getElementById('profilePhone').value = user.PhoneNumber || '';
        document.getElementById('profileId').value = user.Username || '';
        document.getElementById('profileBirthday').value = formatBirthday(user.Birthday);
        document.getElementById('profileGender').value = user.Gender || '';
        document.getElementById('profileAddress').value = user.Address || '';
    } catch (error) {
        console.error(error);
    }
});
