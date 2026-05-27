<div class="bio-modal-overlay" id="addBioModal">
    <div class="bio-modal bio-modal-wide">
        <div class="bio-modal-header">
            <h2 id="addBioModalTitle">Add</h2>
            <div class="bio-modal-line"></div>
        </div>

        <form id="addBioForm" class="bio-modal-form">
            @csrf
            <input type="hidden" id="addLogType" name="type">
            <input type="hidden" id="add_photo_url" name="photo_url">

            <div id="addBioModalFields"></div>

            <div class="bio-modal-actions">
                <button type="button" class="bio-btn bio-btn-cancel" id="closeAddBioModal">Cancel</button>
                <button type="submit" class="bio-btn bio-btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="bio-modal-overlay" id="visitorCameraModal">
    <div class="bio-modal bio-modal-wide">
        <div class="bio-modal-header">
            <h2>Capture Visitor Photo</h2>
            <div class="bio-modal-line"></div>
        </div>

        <div class="bio-modal-form">
            <div class="bio-modal-row">
                <video id="visitorCameraVideo" autoplay playsinline style="width:100%; border-radius:18px; background:#000;"></video>
                <canvas id="visitorCameraCanvas" style="display:none;"></canvas>
                <img id="visitorCameraSnapshot" alt="Captured visitor photo" style="display:none; width:100%; border-radius:18px; margin-top:12px; object-fit:cover;">
            </div>

            <div class="bio-modal-actions" style="flex-wrap:wrap; justify-content:center; gap:10px;">
                <button type="button" class="bio-btn bio-btn-cancel" id="closeVisitorCameraModal">Cancel</button>
                <button type="button" class="bio-btn bio-btn-save" id="captureVisitorPhotoBtn">Capture</button>
                <button type="button" class="bio-btn bio-btn-save" id="useVisitorPhotoBtn" disabled>Use Photo</button>
            </div>
        </div>
    </div>
</div>