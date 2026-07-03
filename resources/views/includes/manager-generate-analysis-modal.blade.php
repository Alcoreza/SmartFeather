<div class="inventory-modal-overlay" id="generateAnalysisModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Generate Analysis</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="generateAnalysisForm">
            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="analysisHouseSelect">House</label>
                    <select id="analysisHouseSelect" required>
                        <option value="">Select house</option>
                        @foreach (($analysisHouseOptions ?? collect()) as $house)
                            <option value="{{ $house->id }}">
                                {{ $house->house_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <p class="inventory-modal-message" id="generateAnalysisMessage"></p>

            <div class="inventory-analysis-result" id="generateAnalysisResult" hidden>
                <div class="inventory-analysis-insight" id="generateAnalysisInsight"></div>
            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeGenerateAnalysisModal"
                >
                    Close
                </button>

                <button
                    type="submit"
                    class="inventory-save-btn"
                    id="submitGenerateAnalysis"
                >
                    Generate
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .inventory-analysis-result {
        display: grid;
        gap: 12px;
    }

    .inventory-analysis-result[hidden] {
        display: none;
    }

    .inventory-analysis-insight {
        padding: 12px 14px;
        border: 1px solid rgba(23, 100, 58, 0.16);
        border-radius: 14px;
        background: #ffffff;
        color: #1f2f26;
        font-size: 0.92rem;
        font-weight: 600;
        line-height: 1.45;
        white-space: pre-line;
    }

</style>

<script>
    (() => {
        function setupGenerateAnalysisModal() {
            const modal = document.getElementById("generateAnalysisModal");
            const openBtn = document.getElementById("openGenerateAnalysisModal");
            const closeBtn = document.getElementById("closeGenerateAnalysisModal");
            const form = document.getElementById("generateAnalysisForm");
            const houseSelect = document.getElementById("analysisHouseSelect");
            const message = document.getElementById("generateAnalysisMessage");
            const result = document.getElementById("generateAnalysisResult");
            const insight = document.getElementById("generateAnalysisInsight");
            const submitBtn = document.getElementById("submitGenerateAnalysis");

            if (!modal || !openBtn || openBtn.dataset.analysisModalReady === "true") {
                return;
            }

            openBtn.dataset.analysisModalReady = "true";

            function openModal() {
                if (houseSelect) {
                    houseSelect.value = "";
                }

                clearResult();
                modal.classList.add("show");
            }

            function closeModal() {
                modal.classList.remove("show");
            }

            function clearResult() {
                if (message) {
                    message.textContent = "";
                    message.className = "inventory-modal-message";
                }

                if (result) {
                    result.hidden = true;
                }

                if (insight) {
                    insight.textContent = "";
                }
            }

            function setMessage(text, type = "error") {
                if (!message) return;

                message.textContent = text || "";
                message.className = text ? `inventory-modal-message ${type}` : "inventory-modal-message";
            }

            openBtn.addEventListener("click", openModal);
            closeBtn?.addEventListener("click", closeModal);

            houseSelect?.addEventListener("change", clearResult);

            form?.addEventListener("submit", async (event) => {
                event.preventDefault();

                const houseId = houseSelect?.value || "";

                if (!houseId) {
                    setMessage("Please select a house first.", "error");
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = "Generating...";
                }

                setMessage("Generating analysis...", "success");

                try {
                    const response = await fetch("/api/manager/inventory/analysis", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                        },
                        body: JSON.stringify({ house_id: houseId }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        setMessage(data.message || "Unable to generate analysis.", "error");
                        return;
                    }

                    if (insight) {
                        insight.textContent = data.insight || "No insight generated.";
                    }

                    if (result) {
                        result.hidden = false;
                    }

                    setMessage(data.used_ai ? "AI insight generated." : "Analysis generated from system rules.", "success");
                } catch (error) {
                    console.error(error);
                    setMessage("Something went wrong while generating analysis.", "error");
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Generate";
                    }
                }
            });

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
