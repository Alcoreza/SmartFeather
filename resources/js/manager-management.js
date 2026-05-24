const profileModal = document.getElementById("profileModal");
const openProfileModalBtn = document.getElementById("openProfileModal");
const closeProfileModalBtn = document.getElementById("closeProfileModal");

async function populateProfileModal() {
    try {
        const response = await fetch("/api/user");

        if (!response.ok) {
            throw new Error("Failed to fetch user info");
        }

        const user = await response.json();

        document.getElementById("profileFirstName").value = user.FirstName || "";
        document.getElementById("profileMiddleName").value = user.MiddleName || "";
        document.getElementById("profileLastName").value = user.LastName || "";
        document.getElementById("profileSuffix").value = user.Suffix || "";
        document.getElementById("profileRole").value = user.Role || "";
        document.getElementById("profilePhone").value = user.PhoneNumber || "";
        document.getElementById("profileId").value = user.EmployeeId || "";
        document.getElementById("profileBirthday").value = user.Birthday || "";
        document.getElementById("profileGender").value = user.Gender || "";
        document.getElementById("profileAddress").value = user.Address || "";
    } catch (error) {
        console.error("Failed to load profile details.", error);
    }
}

if (openProfileModalBtn && profileModal) {
    openProfileModalBtn.addEventListener("click", async () => {
        await populateProfileModal();
        profileModal.classList.add("show");
        document.body.style.overflow = "hidden";
    });
}

if (closeProfileModalBtn && profileModal) {
    closeProfileModalBtn.addEventListener("click", () => {
        profileModal.classList.remove("show");
        document.body.style.overflow = "";
    });
}

if (profileModal) {
    profileModal.addEventListener("click", (event) => {
        if (event.target === profileModal) {
            profileModal.classList.remove("show");
            document.body.style.overflow = "";
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            profileModal.classList.remove("show");
            document.body.style.overflow = "";
        }
    });
}
