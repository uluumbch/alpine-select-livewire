@props([
    'options' => [],
    'placeholder' => null,
    'no_results_text' => null,
    'search_placeholder' => null,
    'searchable' => false,
    'clearable' => false,
    'disabled' => false,
    'floating' => true,
    'class' => '',
    // Optional initial selection from server (array of ids)
    'selected' => [],
    // Enable drag-and-drop reordering of selected items
    'orderable' => false,
    'select_all_text' => null,
    'clear_all_text' => null,
])

@php
    $placeholder = $placeholder ?? __('alpine-select::alpine-select.placeholder');
    $no_results_text = $no_results_text ?? __('alpine-select::alpine-select.no_results');
    $search_placeholder = $search_placeholder ?? __('alpine-select::alpine-select.search_placeholder');
    $select_all_text = $select_all_text ?? __('alpine-select::alpine-select.select_all');
    $clear_all_text = $clear_all_text ?? __('alpine-select::alpine-select.clear_all');
@endphp

<div x-data="{
    open: false,
    search: '',
    searchable: {{ $searchable ? 'true' : 'false' }},
    clearable: {{ $clearable ? 'true' : 'false' }},
    disabled: {{ $disabled ? 'true' : 'false' }},
    floating: {{ $floating ? 'true' : 'false' }},
    orderable: {{ $orderable ? 'true' : 'false' }},
    // internal flags for sync control
    ready: false,
    lastSynced: '',
    // Drag state for orderable
    draggedIndex: null,
    dragOverIndex: null,
    // Name of the Livewire property bound via wire:model
    modelName: '{{ $attributes->wire('model')->value() ?? '' }}',
    // Array of selected values (ids) bound to Livewire
    selectedValues: @if (isset($__livewire)) @entangle($attributes->wire('model')).defer
        @else
            [] @endif,
    // Raw options from PHP (can be strings or { value, label })
    rawOptions: {{ json_encode($options) }},
    // Initial selected values from server-rendered attribute
    initialSelected: {{ json_encode($selected) }},
    placeholder: '{{ $placeholder }}',

    // Normalize options into a consistent shape: { value: string, label: string }
    get normalizedOptions() {
        try {
            const list = Array.isArray(this.rawOptions) ? this.rawOptions : [];
            return list
                .map(o => {
                    if (o && typeof o === 'object') {
                        const value = (o.value ?? o.id ?? o.key ?? '').toString();
                        const label = (o.label ?? o.nama ?? o.text ?? o.value ?? '').toString();
                        return value !== '' ? { value, label: label || value } : null;
                    }
                    const value = (o ?? '').toString();
                    return value !== '' ? { value, label: value } : null;
                })
                .filter(Boolean);
        } catch (e) {
            return [];
        }
    },

    // Always work with an array, even if entanglement momentarily sets a scalar
    get selectedArray() {
        const v = this.selectedValues;
        if (Array.isArray(v)) return v;
        if (v === null || typeof v === 'undefined') return [];
        return [v];
    },

    get filteredOptions() {
        const opts = this.normalizedOptions;
        if (!this.searchable) return opts;
        const q = (this.search || '').toLowerCase();
        return opts.filter(o => o.label.toLowerCase().includes(q));
    },

    get selectedChips() {
        // Preserve the order from selectedArray instead of normalizedOptions
        const optMap = new Map(this.normalizedOptions.map(o => [o.value.toString(), o]));
        return this.selectedArray
            .map(v => optMap.get(v?.toString()))
            .filter(Boolean);
    },

    toggleOption(value) {
        const val = value?.toString();
        const list = this.selectedArray.map(v => v?.toString());
        if (list.includes(val)) {
            this.selectedValues = list.filter(v => v !== val);
        } else {
            this.selectedValues = [...list, val];
        }
    },

    // Drag and drop handlers for orderable
    handleDragStart(index) {
        if (!this.orderable) return;
        this.draggedIndex = index;
    },

    handleDragOver(e, index) {
        if (!this.orderable) return;
        e.preventDefault();
        this.dragOverIndex = index;
    },

    handleDragEnd() {
        if (!this.orderable) return;
        this.draggedIndex = null;
        this.dragOverIndex = null;
    },

    handleDrop(index) {
        if (!this.orderable || this.draggedIndex === null || this.draggedIndex === index) {
            this.handleDragEnd();
            return;
        }
        const list = [...this.selectedArray.map(v => v?.toString())];
        const [draggedItem] = list.splice(this.draggedIndex, 1);
        list.splice(index, 0, draggedItem);
        this.selectedValues = list;
        this.handleDragEnd();
    },

    clearAll() {
        this.selectedValues = [];
        this.search = '';
    },

    selectAll() {
        this.selectedValues = this.normalizedOptions.map(o => o.value.toString());
    },

    // Ensure Livewire receives updates immediately, even if entangle is deferred
    syncToWire() {
        try {
            if (this.modelName && typeof $wire !== 'undefined') {
                const payload = (this.selectedArray || [])
                    .map(v => (v === null || typeof v === 'undefined') ? null : v.toString())
                    .filter(v => v !== null);
                if (typeof $wire.$set === 'function') {
                    $wire.$set(this.modelName, payload);
                } else if (typeof $wire.set === 'function') {
                    $wire.set(this.modelName, payload);
                }
            }
        } catch (e) { /* noop */ }
    },
    // Positioning data for the teleported dropdown
    position: { top: 0, left: 0, width: 0 },

    // Compute and set dropdown position based on trigger element
    updatePosition() {
        const el = this.$refs.trigger;
        if (!el) return;
        const r = el.getBoundingClientRect();
        // Use fixed positioning relative to viewport; add small gap (8px)
        this.position = { top: Math.round(r.bottom + 8), left: Math.round(r.left), width: Math.round(r.width) };
    }
}" x-init="if (!Array.isArray(selectedValues)) selectedValues = selectedValues ? [selectedValues] : [];
// If Livewire hasn't populated entangled value yet, adopt from server-provided initialSelected
$nextTick(() => {
    const empty = !Array.isArray(selectedValues) || selectedValues.length === 0;
    if (empty && Array.isArray(initialSelected) && initialSelected.length) {
        selectedValues = initialSelected.map(v => v === null ? null : v.toString()).filter(v => v !== null);
    }
    // Initialize sync snapshot and enable syncing
    lastSynced = JSON.stringify(selectedArray || []);
    ready = true;
});

// Watch for any checkbox/array changes and sync to Livewire (after ready)
$watch(() => JSON.stringify(selectedArray || []), (json) => {
    if (!ready) return;
    if (json !== lastSynced) {
        lastSynced = json;
        $nextTick(() => { try { syncToWire(); } catch (e) {} });
    }
})" wire:ignore class="relative">
    <!-- Trigger -->
    <button x-ref="trigger"
        class="flex justify-between gap-2 border border-zinc-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-700 cursor-pointer"
        :class="{
            'ring-2 ring-blue-900 dark:ring-blue-400 border-blue-900 dark:border-blue-400': open,
            'ring-0': !open,
            '{{ $class }}': true,
            '!cursor-not-allowed': disabled
        }"
        @click="open = !open; if (open) updatePosition()">
        <template x-if="selectedArray.length === 0">
            <span class="text-zinc-400 dark:text-zinc-500" x-text="placeholder"></span>
        </template>

        <div class="flex flex-wrap gap-2 flex-1">
            <template x-for="(chip, index) in selectedChips" :key="chip.value">
                <span
                    class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full flex items-center space-x-1 transition-all"
                    :class="{
                        'cursor-grab': orderable,
                        'opacity-50': draggedIndex === index,
                        'ring-2 ring-blue-500': dragOverIndex === index && draggedIndex !== null
                    }"
                    :draggable="orderable" @dragstart="handleDragStart(index)" @dragover="handleDragOver($event, index)"
                    @dragend="handleDragEnd()" @drop="handleDrop(index)">
                    <template x-if="orderable">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1 text-blue-600 dark:text-blue-300"
                            fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="14" cy="4" r="2" />
                            <circle cx="14" cy="12" r="2" />
                            <circle cx="14" cy="20" r="2" />
                            <circle cx="6" cy="4" r="2" />
                            <circle cx="6" cy="12" r="2" />
                            <circle cx="6" cy="20" r="2" />
                        </svg>
                    </template>
                    <span x-text="chip.label"></span>
                    <button type="button"
                        class="text-blue-600 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-400 ms-1"
                        @click.stop="toggleOption(chip.value)">
                        ✕
                    </button>
                </span>
            </template>
        </div>


        <div class="flex items-center space-x-2">
            <!-- Clear button -->
            <template x-if="clearable && selectedArray.length > 0">
                <button type="button" @click.stop="clearAll"
                    class="ml-auto text-zinc-400 dark:text-zinc-500 hover:text-red-500 dark:hover:text-red-400 focus:outline-none">
                    ✕
                </button>
            </template>

            <!-- Dropdown icon -->
            <template x-if="floating">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-500 dark:text-zinc-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </template>
        </div>
    </button>

    <div x-show="floating ? open : true" @click.outside="open = false" x-transition
        class="mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 z-[9999] w-full rounded-lg"
        :class="{
            'absolute left-0 dark:bg-zinc-900 dark:border-zinc-700 shadow-lg ': floating
        }">
        <!-- Search -->
        <template x-if="searchable">
            <div class="p-2 border-b border-zinc-200 dark:border-zinc-700">
                <input type="text" x-model="search" placeholder="{{ $search_placeholder }}"
                    class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-2 py-1 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-blue-900 dark:focus:ring-blue-400 focus:border-blue-900 dark:focus:border-blue-400" />
            </div>
        </template>

        <!-- Action buttons -->
        <div
            class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700 text-sm text-blue-600 dark:text-blue-300">
            <button type="button" @click.stop="selectAll()" class="hover:underline">{{ $select_all_text }}</button>
            <button type="button" @click.stop="clearAll()" class="hover:underline text-red-500 dark:text-red-400">{{ $clear_all_text }}</button>
        </div>

        <!-- Options -->
        <div class="max-h-48 overflow-y-auto p-2 space-y-1">
            <template x-for="(option, index) in filteredOptions" :key="option.value ?? index">
                <label
                    class="flex items-center space-x-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md p-1">
                    <input type="checkbox" :value="option.value" x-model="selectedValues"
                        class="rounded text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700" />
                    <span x-text="option.label" class="text-zinc-900 dark:text-zinc-100"></span>
                </label>
            </template>

            <template x-if="filteredOptions.length === 0">
                <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-2">{{ $no_results_text }}</p>
            </template>
        </div>
    </div>
</div>
