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
    'selected' => null,
])

@php
    $placeholder = $placeholder ?? __('alpine-select::alpine-select.placeholder');
    $no_results_text = $no_results_text ?? __('alpine-select::alpine-select.no_results');
    $search_placeholder = $search_placeholder ?? __('alpine-select::alpine-select.search_placeholder');
@endphp

<div {{ $attributes->except(['wire:model', 'class']) }} x-data="{
    open: false,
    search: '',
    searchable: {{ $searchable ? 'true' : 'false' }},
    clearable: {{ $clearable ? 'true' : 'false' }},
    disabled: {{ $disabled ? 'true' : 'false' }},
    floating: {{ $floating ? 'true' : 'false' }},
    position: { top: 0, left: 0, width: 0 },
    // Name of the Livewire property bound via wire:model
    modelName: '{{ $attributes->wire('model')->value() ?? '' }}',
    // Livewire-bound selected value (stores the option value/id, not the label)
    selectedValue: @if (isset($__livewire)) @entangle($attributes->wire('model')).defer @else '{{ $selected ?? '' }}' @endif,
    // Raw options from PHP (can be strings or { value, label } objects)
    rawOptions: {{ json_encode($options) }},
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
                    // primitives (string/number)
                    const value = (o ?? '').toString();
                    return value !== '' ? { value, label: value } : null;
                })
                .filter(Boolean);
        } catch (e) {
            return [];
        }
    },

    get filteredOptions() {
        const opts = this.normalizedOptions;
        if (!this.searchable) return opts;
        const q = (this.search || '').toLowerCase();
        return opts.filter(o => o.label.toLowerCase().includes(q));
    },

    displayText() {
        if (!this.selectedValue && this.selectedValue !== 0) return this.placeholder;
        const found = this.normalizedOptions.find(o => o.value.toString() === (this.selectedValue ?? '').toString());
        return found ? found.label : this.placeholder;
    },

    choose(option) {
        this.selectedValue = option.value;
        this.open = false;
        this.syncToWire();
        // Bubble custom event after state settles to avoid race with x-model
        this.$nextTick(() => {
            this.$el.dispatchEvent(new CustomEvent('model-updated', { detail: { value: this.selectedValue }, bubbles: true }));
        });
    },

    clearSelection() {
        this.selectedValue = '';
        this.search = '';
        this.syncToWire();
        this.$nextTick(() => {
            this.$el.dispatchEvent(new CustomEvent('model-updated', { detail: { value: this.selectedValue }, bubbles: true }));
        });
    },

    updatePosition() {
        const el = this.$refs.trigger;
        if (!el) return;
        const r = el.getBoundingClientRect();
        // Use fixed positioning relative to viewport; add small gap (8px)
        this.position = { top: r.bottom + 8, left: r.left, width: r.width };
    },

    // Ensure Livewire receives updates immediately, even if entangle is deferred
    syncToWire() {
        try {
            // Update hidden input and dispatch input/change for Livewire
            const el = this.$refs.bridge;
            if (el) {
                el.value = this.selectedValue ?? '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
            // Also set directly on the Livewire component (v3: $set, v2: set)
            if (this.modelName && typeof $wire !== 'undefined') {
                if (typeof $wire.$set === 'function') {
                    $wire.$set(this.modelName, this.selectedValue ?? '');
                } else if (typeof $wire.set === 'function') {
                    $wire.set(this.modelName, this.selectedValue ?? '');
                }
            }
        } catch (e) { /* noop */ }
    }
}" x-modelable="selectedValue"
    x-init="// Avoid initial sync that can overwrite prefilled edit values.
    // Also, if Livewire rendered an initial value into the hidden bridge, adopt it for display.
    $nextTick(() => {
        const v = $refs.bridge ? $refs.bridge.value : null;
        if ((selectedValue === '' || selectedValue === null || typeof selectedValue === 'undefined') && v !== null && v !== '') {
            selectedValue = v.toString();
        }
    });

    // Coerce any future entangled updates (numbers/null) into a stable string for comparison/display
    $watch('selectedValue', (val) => {
        if (typeof val === 'undefined') return; // ignore truly undefined
        selectedValue = (val === null) ? '' : val.toString();
    });

    // Watch Livewire property for external updates
    // this is needed so when Livewire updates the property from outside (eg. other field changes),
    // the select reflects the new value
    if (modelName && typeof $wire !== 'undefined') {
        $watch(() => $wire.get(modelName), (newValue) => {
            if (newValue !== undefined && newValue !== selectedValue) {
                selectedValue = (newValue === null) ? '' : newValue.toString();
            }
        });
    }
    " class="relative">
    <!-- Bridge Alpine state to Livewire with the same wire:model (no Alpine binding so server value persists) -->
    <input type="hidden" x-ref="bridge" {{ $attributes->whereStartsWith('wire:model') }} />

    <!-- Trigger -->
    <button x-ref="trigger" type="button"
        class="w-full border border-zinc-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-700 flex items-center justify-between gap-2 cursor-pointer"
        :class="{
            'ring-2 ring-blue-900 dark:ring-blue-400 border-blue-900 dark:border-blue-400': open,
            'ring-0': !open,
            '{{ $class }}': true,
        }"
        @click="if(!disabled){ open = !open; if (open) updatePosition() }">
        <div class="flex-1 text-left truncate"
            x-text="displayText()"
            :class="(!selectedValue || selectedValue === '') ? 'text-zinc-400 dark:text-zinc-500' :
            'text-zinc-900 dark:text-zinc-100'"></div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Clear button -->
            <template x-if="clearable && selectedValue">
                <button type="button" @click.stop="clearSelection" tabindex="0"
                    class="text-zinc-400 dark:text-zinc-500 hover:text-red-500 dark:hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 rounded">
                    ✕
                </button>
            </template>

            <!-- Dropdown icon -->
            <template x-if="floating">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-500 dark:text-zinc-400 flex-shrink-0" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </template>
        </div>
    </button>

    <div x-show="floating ? open : true" @click.outside="open = false" @keydown.escape.window="open = false" x-transition
        class="mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 z-[9999] w-full rounded-lg"
        :class="{
            'absolute left-0 dark:bg-zinc-900 dark:border-zinc-700 shadow-lg': floating
        }">
        <!-- Search -->
        <template x-if="searchable">
            <div class="p-2 border-b border-zinc-200 dark:border-zinc-700">
                <input type="text" x-model="search" placeholder="{{ $search_placeholder }}"
                    class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-md px-2 py-1 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-blue-900 dark:focus:ring-blue-400 focus:border-blue-900 dark:focus:border-blue-400" />
            </div>
        </template>

        <!-- Options -->
        <div class="max-h-48 overflow-y-auto p-2 space-y-1">
            <template x-for="(option, index) in filteredOptions" :key="option.value ?? index">
                <div @click="choose(option)" 
                    @keydown.enter="choose(option)"
                    @keydown.space.prevent="choose(option)"
                    tabindex="0"
                    role="option"
                    :aria-selected="selectedValue == option.value"
                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    :class="{
                        'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-medium': selectedValue ==
                            option.value
                    }">
                    <span x-text="option.label"></span>
                </div>
            </template>

            <template x-if="filteredOptions.length === 0">
                <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-2">{{ $no_results_text }}</p>
            </template>
        </div>
    </div>
</div>
