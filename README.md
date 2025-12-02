# Alpine Select Livewire

Powerful searchable select components with multi-select and drag-ordering support for Laravel Livewire & Alpine.js.

## Features

- **Single & Multi-select variants** - Choose between single selection or multiple selections with chips  
- **Searchable/filterable options** - Built-in search functionality for large option lists  
- **Drag & drop reordering** - Reorder selected items in multi-select mode  
- **Dark mode support** - Full support for dark mode styling  
- **Full Livewire integration** - Seamless wire:model binding with defer support  
- **Zero JavaScript compilation** - Uses Livewire's bundled Alpine.js  
- **TailwindCSS styled** - Fully customizable with TailwindCSS  
- **Flexible options format** - Supports arrays, objects, or mixed formats  

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Livewire ^3.0
- TailwindCSS ^3.0 or ^4.0

## Installation

### Step 1: Install via Composer

```bash
composer require uluumbch/alpine-select-livewire
```

### Step 2: Configure TailwindCSS

Add the package views to your TailwindCSS content configuration.

**For TailwindCSS v3** (tailwind.config.js):

```javascript
export default {
  content: [
    './resources/**/*.blade.php',
    './vendor/uluumbch/alpine-select-livewire/resources/**/*.blade.php',
  ],
  // ... rest of config
}
```

**For TailwindCSS v4** (resources/css/app.css):

```css
@import 'tailwindcss';

@source '../views';
@source '../../vendor/uluumbch/alpine-select-livewire/resources';
```

### Step 3: Rebuild Assets

```bash
npm run build
```

The service provider will be automatically registered via Laravel's package discovery.

## Usage

### Single Select

Basic single-select dropdown:

```blade
<x-alpine-select::default
    wire:model="selectedOption"
    :options="['Option 1', 'Option 2', 'Option 3']"
    placeholder="Choose an option..."
/>
```

With object options (value/label):

```blade
<x-alpine-select::default
    wire:model="religion"
    :options="[
        ['value' => 'islam', 'label' => 'Islam'],
        ['value' => 'christian', 'label' => 'Christian'],
        ['value' => 'catholic', 'label' => 'Catholic'],
    ]"
    placeholder="Select religion..."
    searchable
    clearable
/>
```

### Multi Select

Basic multi-select with chips:

```blade
<x-alpine-select::multiple
    wire:model="selectedItems"
    :options="[
        ['value' => 1, 'label' => 'Option 1'],
        ['value' => 2, 'label' => 'Option 2'],
        ['value' => 3, 'label' => 'Option 3'],
    ]"
    placeholder="Select multiple..."
/>
```

With drag-and-drop ordering:

```blade
<x-alpine-select::multiple
    wire:model="stages"
    :options="$stageOptions"
    :selected="$stages"
    placeholder="Select stages..."
    searchable
    clearable
    orderable
/>
```

## Component API

### Single Select (`<x-alpine-select::default>`)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `options` | array | `[]` | Array of options (strings or objects) |
| `placeholder` | string | `'Pilih opsi...'` | Placeholder text when no selection |
| `no_results_text` | string | `'Tidak ada hasil'` | Text shown when search returns no results |
| `searchable` | boolean | `false` | Enable search functionality |
| `clearable` | boolean | `false` | Show clear button to reset selection |
| `disabled` | boolean | `false` | Disable the select input |
| `floating` | boolean | `true` | Use floating dropdown (false for inline) |
| `selected` | string/int | `null` | Initial selected value |
| `class` | string | `''` | Additional CSS classes for trigger |

### Multi Select (`<x-alpine-select::multiple>`)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `options` | array | `[]` | Array of options (strings or objects) |
| `placeholder` | string | `'Pilih opsi...'` | Placeholder text when no selection |
| `no_results_text` | string | `'Tidak ada hasil'` | Text shown when search returns no results |
| `searchable` | boolean | `false` | Enable search functionality |
| `clearable` | boolean | `false` | Show clear button to reset all selections |
| `disabled` | boolean | `false` | Disable the select input |
| `floating` | boolean | `true` | Use floating dropdown (false for inline) |
| `selected` | array | `[]` | Initial selected values (array of IDs) |
| `orderable` | boolean | `false` | Enable drag-and-drop reordering of chips |
| `select_all_text` | string | `'Pilih Semua'` | Text for "Select All" button |
| `clear_all_text` | string | `'Hapus Semua'` | Text for "Clear All" button |
| `class` | string | `''` | Additional CSS classes for trigger |

## Options Format

The package intelligently normalizes options into a consistent format. You can pass options in various formats:

### Simple Array
```php
$options = ['Option 1', 'Option 2', 'Option 3'];
```

### Value/Label Objects
```php
$options = [
    ['value' => 1, 'label' => 'First Option'],
    ['value' => 2, 'label' => 'Second Option'],
];
```

### Alternative Object Keys
The package also recognizes these alternative keys:
- `id`, `key` (as value)
- `nama`, `text` (as label)

```php
$options = [
    ['id' => 1, 'nama' => 'Jakarta'],
    ['id' => 2, 'nama' => 'Surabaya'],
];
```

## Livewire Integration

### Wire Model Binding

The component supports all Livewire wire:model modifiers:

```blade
{{-- Immediate sync --}}
<x-alpine-select::default wire:model="field" :options="$options" />

{{-- Lazy sync (on blur/change) --}}
<x-alpine-select::default wire:model.lazy="field" :options="$options" />

{{-- Deferred sync (on form submit) --}}
<x-alpine-select::default wire:model.defer="field" :options="$options" />
```

### Custom Events

Listen to the `model-updated` event for custom handling:

```blade
<div x-data @model-updated="console.log('Selection changed:', $event.detail.value)">
    <x-alpine-select::default wire:model="field" :options="$options" />
</div>
```

### Dynamic Options

Update options dynamically from your Livewire component:

```php
class MyComponent extends Component
{
    public $selectedCity;
    public $cities = [];

    public function mount()
    {
        $this->cities = City::pluck('name', 'id')->toArray();
    }

    public function render()
    {
        return view('livewire.my-component');
    }
}
```

```blade
<x-alpine-select::default
    wire:model="selectedCity"
    :options="$cities"
/>
```

## Publishing Assets

### Publish Configuration

```bash
php artisan vendor:publish --tag=alpine-select-config
```

This publishes `config/alpine-select.php` for custom configuration.

### Publish Views

```bash
php artisan vendor:publish --tag=alpine-select-views
```

This publishes views to `resources/views/vendor/alpine-select` for customization.

### Publish Translations

```bash
php artisan vendor:publish --tag=alpine-select-lang
```

This publishes language files to `lang/vendor/alpine-select` for translation.

### Publish All

```bash
php artisan vendor:publish --provider="Uluumbch\AlpineSelectLivewire\AlpineSelectLivewireServiceProvider"
```

## Translations

The package supports multiple languages. Default texts are in English.

### Available Translation Keys

- `placeholder` - "Select an option..."
- `no_results` - "No results found"
- `search_placeholder` - "Search..."
- `select_all` - "Select All"
- `clear_all` - "Clear All"

### Creating Custom Translations

1. Publish the language files:
```bash
php artisan vendor:publish --tag=alpine-select-lang
```

2. Create your language file (e.g., `lang/vendor/alpine-select/id/alpine-select.php`):

```php
<?php

return [
    'placeholder' => 'Pilih opsi...',
    'no_results' => 'Tidak ada hasil',
    'search_placeholder' => 'Cari...',
    'select_all' => 'Pilih Semua',
    'clear_all' => 'Hapus Semua',
];
```

### Overriding Translations Per Component

You can override translations for individual components:

```blade
<x-alpine-select::default
    wire:model="selectedOption"
    :options="$options"
    placeholder="Custom placeholder text"
    no_results_text="Custom no results message"
    search_placeholder="Custom search placeholder"
/>
```

## Styling Customization

### Option 1: TailwindCSS Theme Extension

Customize colors by extending your TailwindCSS theme:

```javascript
// tailwind.config.js
export default {
  theme: {
    extend: {
      colors: {
        // Override zinc colors
        zinc: {
          // ... your custom shades
        },
        // Override blue accent colors
        blue: {
          // ... your custom shades
        }
      }
    }
  }
}
```

### Option 2: Publish and Modify Views

Publish the views to your application for full control:

```bash
php artisan vendor:publish --tag=alpine-select-views
```

Views will be published to `resources/views/vendor/alpine-select/components/`.

Then modify the Blade files directly. Example color scheme change:

```bash
# Change from zinc/blue to slate/indigo
cd resources/views/vendor/alpine-select/components
sed -i 's/zinc-/slate-/g' *.blade.php
sed -i 's/blue-/indigo-/g' *.blade.php
```

### Option 3: Custom CSS Overrides

Add custom CSS targeting component classes:

```css
/* resources/css/app.css */
.alpine-select-trigger {
  @apply border-2 border-purple-300 rounded-xl;
}

.alpine-select-option:hover {
  @apply bg-purple-100;
}
```

### Common Customizations

**Change dropdown max height:**

Find `max-h-48` in the component and change to your preferred height class.

**Customize icons:**

Replace the SVG elements in the published views with your own icons or icon library.

**Adjust border radius:**

Change `rounded-lg` to `rounded-xl`, `rounded-2xl`, etc.

## Troubleshooting

### Options not updating

Make sure options are passed as a PHP array or collection, not a JSON string:

```blade
{{-- ✅ Correct --}}
:options="$options"

{{-- ❌ Wrong --}}
options="{{ json_encode($options) }}"
```

### Selected value not persisting

Ensure your Livewire property is public and properly initialized:

```php
class MyComponent extends Component
{
    public $selectedOption = ''; // Initialize with empty string or default value
}
```

### Dropdown positioning issues in modals

For dropdowns inside modals or scrollable containers, use `floating="false"`:

```blade
<x-alpine-select::default
    wire:model="field"
    :options="$options"
    :floating="false"
/>
```

### Multi-select drag conflicts with Livewire

The multi-select component includes `wire:ignore` to prevent conflicts. If you still experience issues, ensure you're using Livewire 3.x and the latest package version.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- **uluumbch** - Package author
- Inspired by modern select libraries and Laravel Livewire ecosystem

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues or have questions, please open an issue on GitHub.
