<div class="bio-modal-overlay" id="editBioModal">
    <div class="bio-modal bio-modal-wide">
        <div class="bio-modal-header">
            <h2 id="editBioModalTitle">Edit</h2>
            <div class="bio-modal-line"></div>
        </div>

        <form id="editBioForm" class="bio-modal-form">
            <input type="hidden" id="editLogId" name="id">
            <input type="hidden" id="editLogType" name="type">

            <div id="editBioModalFields"></div>

            <div class="bio-modal-actions">
                <button type="button" class="bio-btn bio-btn-cancel" id="closeBioModal">Cancel</button>
                <button type="submit" class="bio-btn bio-btn-save">Save</button>
            </div>
        </form>
    </div>
</div>