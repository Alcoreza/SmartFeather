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
                <div class="inventory-analysis-card">
                    <div class="inventory-analysis-card-header">
                        <div>
                            <h3 class="inventory-analysis-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                Feed Allocation Insight
                            </h3>
                            <div class="inventory-analysis-meta">
                                <span id="generateAnalysisHouse">Selected house</span>
                                <span id="generateAnalysisTime">Generated just now</span>
                            </div>
                        </div>
                    </div>

                    <section class="inventory-analysis-text-section">
                        <h4>Recommendation</h4>
                        <div class="inventory-analysis-insight" id="generateAnalysisInsight"></div>
                    </section>
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
            const analysisHouse = document.getElementById("generateAnalysisHouse");
            const analysisTime = document.getElementById("generateAnalysisTime");
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
                    insight.innerHTML = "";
                }

                if (analysisHouse) {
                    analysisHouse.textContent = "Selected house";
                }

                if (analysisTime) {
                    analysisTime.textContent = "Generated just now";
                }
            }

            function setMessage(text, type = "error") {
                if (!message) return;

                message.textContent = text || "";
                message.className = text ? `inventory-modal-message ${type}` : "inventory-modal-message";
            }

            function escapeHtml(value) {
                return String(value ?? "")
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function splitInsightSentences(text) {
                return String(text || "")
                    .split(/(?<=[.!?])\s+(?=[A-Z])/)
                    .map((sentence) => sentence.trim())
                    .filter(Boolean) || [];
            }

            function removePercentDetails(text) {
                return String(text || "")
                    .replace(/\s*\([^)]*%[^)]*\)/g, "")
                    .replace(/\b\d+(?:\.\d+)?%\s*(?:of\s+[^,.!?]+)?/gi, "")
                    .replace(/\s+([,.!?])/g, "$1")
                    .replace(/\s{2,}/g, " ")
                    .trim();
            }

            function formatAnalysisTime(value) {
                if (!value) return "Generated just now";

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return "Generated just now";

                return `Generated ${date.toLocaleString([], {
                    month: "short",
                    day: "numeric",
                    hour: "numeric",
                    minute: "2-digit",
                })}`;
            }

            function renderAnalysisResult(data) {
                const insightText = removePercentDetails(data.insight || "No insight generated.");
                const sentences = splitInsightSentences(insightText);

                if (insight) {
                    insight.innerHTML = sentences.length
                        ? sentences.map((sentence) => `
                            <div class="inventory-analysis-action-row">
                                <span class="inventory-analysis-action-marker"></span>
                                <span>${escapeHtml(sentence)}</span>
                            </div>
                        `).join("")
                        : `<p class="inventory-analysis-empty">${escapeHtml(insightText)}</p>`;
                }

                if (analysisHouse) {
                    analysisHouse.textContent = data.house_name || "Selected house";
                }

                if (analysisTime) {
                    analysisTime.textContent = formatAnalysisTime(data.generated_at);
                }
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

                    renderAnalysisResult(data);

                    if (result) {
                        result.hidden = false;
                    }

                    setMessage("Analysis generated.", "success");
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
