<div class="inventory-modal-overlay" id="generateAnalysisModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Generate Analysis</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <div class="inventory-modal-form">
            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="analysisHouseSelect">House</label>
                    <select id="analysisHouseSelect">
                        <option value="">Select house</option>
                        @foreach (($analysisHouseOptions ?? collect()) as $house)
                            <option value="{{ $house->id }}">
                                {{ $house->house_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeGenerateAnalysisModal"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        function setupGenerateAnalysisModal() {
            const modal = document.getElementById("generateAnalysisModal");
            const openBtn = document.getElementById("openGenerateAnalysisModal");
            const closeBtn = document.getElementById("closeGenerateAnalysisModal");
            const houseSelect = document.getElementById("analysisHouseSelect");

            if (!modal || !openBtn || openBtn.dataset.analysisModalReady === "true") {
                return;
            }

            openBtn.dataset.analysisModalReady = "true";

            function openModal() {
                if (houseSelect) {
                    houseSelect.value = "";
                }

                modal.classList.add("show");
            }

            function closeModal() {
                modal.classList.remove("show");
            }

            openBtn.addEventListener("click", openModal);
            closeBtn?.addEventListener("click", closeModal);

            modal.addEventListener("click", (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && modal.classList.contains("show")) {
                    closeModal();
                }
            });
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", setupGenerateAnalysisModal);
            return;
        }

        setupGenerateAnalysisModal();
    })();
</script>
