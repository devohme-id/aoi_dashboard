class SearchableSelect {
    constructor(selector, placeholder, onChangeCallback = null) {
        this.originalSelect = document.querySelector(selector);
        this.placeholder = placeholder;
        this.onChangeCallback = onChangeCallback;
        this.isOpen = false;

        this.originalSelect.classList.add("hidden");
        this.renderUI();
        this.bindEvents();
        this.syncFromOriginal();
    }

    renderUI() {
        this.wrapper = document.createElement("div");
        this.wrapper.className = "relative w-full";
        this.originalSelect.insertAdjacentElement("afterend", this.wrapper);

        this.trigger = document.createElement("div");
        this.trigger.className = "w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 flex justify-between items-center cursor-pointer hover:border-indigo-500 transition-all group select-none";
        this.trigger.innerHTML = `<span class="text-slate-400 text-sm font-medium truncate">${this.placeholder}</span>
        <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>`;

        this.dropdown = document.createElement("div");
        this.dropdown.className = "absolute top-full left-0 w-full mt-2 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-50 hidden overflow-hidden ring-1 ring-white/5 origin-top scale-95 opacity-0 transition-all duration-200";
        this.dropdown.innerHTML = `
        <div class="p-2 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 outline-none placeholder-slate-600" placeholder="Search...">
            </div>
        </div>
        <div class="options-list max-h-60 overflow-y-auto custom-scrollbar p-1"></div>`;

        this.wrapper.append(this.trigger, this.dropdown);
        this.optionsList = this.dropdown.querySelector(".options-list");
        this.searchInput = this.dropdown.querySelector("input");
    }

    bindEvents() {
        this.trigger.addEventListener("click", (e) => {
            if (this.originalSelect.disabled) return;
            e.stopPropagation();
            this.toggle();
        });

        this.searchInput.addEventListener("input", (e) => {
            const term = e.target.value.toLowerCase();
            this.optionsList.querySelectorAll(".custom-option").forEach((opt) => {
                opt.style.display = opt.textContent.toLowerCase().includes(term) ? "" : "none";
            });
        });

        this.optionsList.addEventListener("click", (e) => {
            if (!e.target.classList.contains("custom-option")) return;
            const value = e.target.dataset.value;
            this.originalSelect.value = value;
            this.updateTriggerText(e.target.textContent);
            this.close();
            if (this.onChangeCallback) this.onChangeCallback(value);
        });

        document.addEventListener("click", (e) => {
            if (!this.wrapper.contains(e.target)) this.close();
        });
    }

    syncFromOriginal() {
        this.optionsList.innerHTML = "";
        const options = this.originalSelect.options;

        if (options.length <= 1) {
            this.optionsList.innerHTML = `<div class="p-4 text-center text-xs text-slate-500 italic">No data available</div>`;
        } else {
            Array.from(options).forEach((opt) => {
                if (!opt.value) return;
                const isSelected = opt.selected;
                const div = document.createElement("div");
                div.className = `custom-option px-3 py-2 rounded-lg cursor-pointer text-sm mb-1 ${isSelected ? "bg-indigo-600 text-white" : "text-slate-300 hover:bg-slate-800"} transition-colors`;
                div.dataset.value = opt.value;
                div.textContent = opt.text;
                this.optionsList.appendChild(div);
                if (isSelected) this.updateTriggerText(opt.text);
            });
        }
        this.wrapper.classList.toggle("opacity-50", this.originalSelect.disabled);
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.dropdown.classList.remove("hidden");
        requestAnimationFrame(() => {
            this.dropdown.classList.add("scale-100", "opacity-100");
            this.trigger.querySelector("svg").classList.add("rotate-180");
            this.searchInput.focus();
        });
    }

    close() {
        this.isOpen = false;
        this.dropdown.classList.remove("scale-100", "opacity-100");
        this.trigger.querySelector("svg").classList.remove("rotate-180");
        setTimeout(() => this.dropdown.classList.add("hidden"), 200);
        this.searchInput.value = "";
        this.optionsList.querySelectorAll(".custom-option").forEach((opt) => (opt.style.display = ""));
    }

    updateTriggerText(text) {
        const span = this.trigger.querySelector("span");
        span.textContent = text;
        span.classList.remove("text-slate-400");
        span.classList.add("text-white");
    }

    setLoading(isLoading) {
        if (isLoading) {
            this.trigger.querySelector("span").innerHTML = '<div class="flex items-center gap-2"><div class="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div> Loading...</div>';
            this.originalSelect.disabled = true;
            this.wrapper.classList.add("opacity-75");
        } else {
            this.originalSelect.disabled = false;
            this.wrapper.classList.remove("opacity-75");
        }
    }

    reset() {
        this.originalSelect.value = "";
        this.trigger.querySelector("span").textContent = this.placeholder;
        this.syncFromOriginal();
    }
}

class TuningApp {
    constructor() {
        this.el = {
            line: document.querySelector("#line_id"),
            assembly: document.querySelector("#assembly_name"),
            preview: {
                container: document.querySelector("#preview_content"),
                placeholder: document.querySelector("#preview_placeholder"),
                badge: document.querySelector("#preview_status_badge"),
            },
        };

        this.state = {
            currentLineData: null,
            currentHistoryData: [],
        };

        this.init();
    }

    init() {
        this.lineSelectUI = new SearchableSelect("#line_id", "-- Select Production Line --", (id) => this.handleLineChange(id));
        this.assemblySelectUI = new SearchableSelect("#assembly_name", "-- Choose Line First --", (name) => this.handleAssemblyChange(name));
    }

    handleLineChange(lineId) {
        this.assemblySelectUI.reset();
        this.assemblySelectUI.setLoading(true);
        this.state.currentHistoryData = [];

        this.el.preview.container.classList.add("hidden");
        this.el.preview.placeholder.classList.remove("hidden");
        this.el.preview.placeholder.innerHTML = `<div class="animate-pulse flex flex-col items-center"><div class="w-10 h-10 border-4 border-slate-700 border-t-indigo-500 rounded-full animate-spin mb-3"></div><span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Loading Data...</span></div>`;

        if (!lineId) {
            this.assemblySelectUI.setLoading(false);
            return;
        }

        fetch("api/get_assemblies.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ line_id: lineId }),
        })
            .then((res) => res.json())
            .then((response) => {
                const opts = response.all_assemblies || [];
                this.el.assembly.innerHTML =
                    `<option value="">-- Select Assembly --</option>` + opts.map((a) => `<option value="${a}" ${a === response.current_assembly ? "selected" : ""}>${a}</option>`).join("");
                this.assemblySelectUI.setLoading(false);
                this.assemblySelectUI.syncFromOriginal();
                if (this.el.assembly.value) this.handleAssemblyChange(this.el.assembly.value);
            });

        this.fetchPreviewData(lineId);
    }

    handleAssemblyChange(selected) {
        if (selected && this.state.currentLineData) this.fetchTuningHistory(this.el.line.value, selected);
    }

    async fetchPreviewData(lineId) {
        try {
            const res = await fetch(`api/get_dashboard_data.php?t=${Date.now()}`);
            const data = await res.json();
            this.state.currentLineData = data.lines?.[`line_${lineId}`] || null;
            if (this.state.currentLineData) {
                 // Render Logic Placeholder
            }
        } catch (e) { console.error(e) }
    }

    fetchTuningHistory(lineId, assembly) {
        fetch("api/get_report_data.php", {
            method: "POST",
            body: new URLSearchParams({
                line_filter: lineId,
                "search[value]": assembly,
                start: 0,
                length: 50,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                const map = new Map();
                data.data.forEach((row) => {
                    if (!map.has(row.TuningCycleID)) {
                        map.set(row.TuningCycleID, {
                            version: row.TuningCycleID,
                            date: row.EndTime,
                            user: row.DebuggerFullName,
                            notes: row.Notes,
                        });
                    }
                });
                this.state.currentHistoryData = [...map.values()].sort((a, b) => b.version - a.version);
            });
    }
}

document.addEventListener("DOMContentLoaded", () => new TuningApp());