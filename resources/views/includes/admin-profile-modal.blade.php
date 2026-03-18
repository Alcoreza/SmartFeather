<!-- ADD MODAL -->
<div id="addModal">
    <input id="id" placeholder="ID">
    <input id="first_name" placeholder="First Name">
    <input id="middle_name" placeholder="Middle Name">
    <input id="last_name" placeholder="Last Name">
    <input id="suffix" placeholder="Suffix">
    <input id="role" placeholder="Role">
    <input id="phone_number" placeholder="Phone">
    <input id="birthday" placeholder="Birthday">
    <input id="gender" placeholder="Gender">
    <input id="address" placeholder="Address">
    <button onclick="addEmployee()">Save</button>
</div>

<!-- EDIT MODAL -->
<div id="editModal" style="display:none;">
    <input id="edit_id" hidden>
    <input id="edit_first_name">
    <input id="edit_middle_name">
    <input id="edit_last_name">
    <input id="edit_suffix">
    <input id="edit_role">
    <input id="edit_phone_number">
    <input id="edit_birthday">
    <input id="edit_gender">
    <input id="edit_address">
    <button onclick="updateEmployee()">Update</button>
</div>