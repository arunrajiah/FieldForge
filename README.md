# FieldForge

**Open-source, GPL alternative to Advanced Custom Fields (ACF) Pro** — native Repeater, Flexible Content, Options Pages, conditional logic, and full ACF JSON import. Runs entirely inside WordPress. No SaaS, no phone-home, no lock-in.

[![CI](https://github.com/arunrajiah/fieldforge/actions/workflows/ci.yml/badge.svg)](https://github.com/arunrajiah/fieldforge/actions/workflows/ci.yml)
[![Version](https://img.shields.io/badge/version-0.1.1-blue)](https://github.com/arunrajiah/fieldforge/releases)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue)](https://wordpress.org)

---

## Why FieldForge?

Most teams are stuck on ACF Pro because of two features: **Repeater** and **Flexible Content**. FieldForge ships both — plus Options Pages, conditional logic, REST API, and ACF import — as a community-governed, 100% GPL plugin.

- **Zero lock-in.** All values stored in standard `wp_postmeta`, byte-for-byte compatible with ACF.
- **No SaaS. No phone-home.** Pure PHP + MySQL. Works in air-gapped environments.
- **Import your ACF field groups** in 30 seconds — paste the JSON export and it maps everything automatically.
- **Open governance.** Feature requests, roadmap, and releases happen in public on GitHub.

---

## Features

### Core Fields (26 field types)

`text` · `textarea` · `number` · `email` · `url` · `password` · `select` · `checkbox` · `radio` · `true_false` · `date_picker` · `time_picker` · `color_picker` · `image` · `file` · `gallery` · `post_object` · `taxonomy` · `user` · `link` · `wysiwyg` · `message` · `tab` · `accordion` · **`repeater`** · **`flexible_content`**

### Advanced

- **Repeater field** — Unlimited rows, configurable sub-fields, drag-to-reorder, min/max row limits.
- **Flexible Content field** — Multiple named layouts each with their own sub-fields, drag-to-reorder blocks.
- **Options Pages** — Register global admin pages, store settings outside of post meta.
- **Conditional Logic** — Show or hide individual fields based on the value of other fields in the same group.

### Developer Experience

- **ACF JSON import** — Export from ACF, import into FieldForge. All field types, rules, sub-fields, and layouts map automatically.
- **Local JSON sync** — Field groups saved as JSON files alongside your theme (equivalent to ACF's `acf-json` folder). Commit them to version control.
- **REST API** — Field values exposed at `/fieldforge/v1/fields/{post_id}` with location-rule filtering.
- **Template helpers** — ACF-compatible function API for themes and plugins.
- **Field validation** — Required fields, number min/max, email/URL format checks, text maxlength.
- **PHPUnit test suite** — CI matrix on PHP 7.4 → 8.3.
- **Custom field types** — Register your own via a single action hook.

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.2 |
| PHP | 7.4 |
| MySQL | 5.7 |
| MariaDB | 10.3 |
| Browser (admin) | Any modern browser |

---

## Installation

### Option 1 — GitHub Release ZIP (recommended until WP.org listing)

1. Download `fieldforge.zip` from the [Releases](https://github.com/arunrajiah/fieldforge/releases) page.
2. In WordPress: go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip and click **Install Now**, then **Activate**.

### Option 2 — Git clone

```bash
cd wp-content/plugins
git clone https://github.com/arunrajiah/fieldforge.git
```

Activate from **Plugins → Installed Plugins**.

### Option 3 — Composer

```bash
composer require wpackagist-plugin/fieldforge
```

> **Note:** WPackagist availability is pending WordPress.org directory approval.

---

## Quick Start

### 1. Create a Field Group

Go to **FieldForge → Add New**. Add fields, configure their settings, then set a **Location Rule** (e.g. "Post Type is equal to `post`"). Publish the group.

### 2. Edit a Post

Open any post matching your location rule — your fields appear as a meta box below the editor.

### 3. Display Values in Templates

```php
// Return a value
$title = fieldforge_get( 'hero_title' );

// Echo a value (auto-escaped)
fieldforge_the( 'hero_title' );

// Repeater loop
while ( fieldforge_have_rows( 'team_members' ) ) {
    fieldforge_the_row();
    echo '<h3>' . esc_html( fieldforge_sub_field( 'name' ) ) . '</h3>';
    echo '<p>'  . esc_html( fieldforge_sub_field( 'role' ) ) . '</p>';
}

// Options page value
$site_logo = fieldforge_get_option( 'site_logo' );
```

---

## Field Types Reference

| Slug | Label | Stored as | Notes |
|---|---|---|---|
| `text` | Text | `string` | Supports `maxlength`, `placeholder`, `prepend`, `append` |
| `textarea` | Textarea | `string` | Supports `rows`, `new_lines` |
| `number` | Number | `string` | Supports `min`, `max`, `step` |
| `email` | Email | `string` | Format-validated on save |
| `url` | URL | `string` | Format-validated on save |
| `password` | Password | `string` | Stored as plain text in postmeta |
| `select` | Select | `string` or `array` | Supports `multiple`, `allow_null`, `choices` |
| `checkbox` | Checkbox | `array` | `choices` key → value pairs |
| `radio` | Radio | `string` | `choices` key → value pairs |
| `true_false` | True / False | `int` (0 or 1) | Renders as a toggle switch |
| `date_picker` | Date Picker | `string` (YYYYMMDD) | Returns `Y-m-d` via `format_value()` |
| `time_picker` | Time Picker | `string` (HH:MM:SS) | |
| `color_picker` | Color Picker | `string` (hex) | |
| `image` | Image | `int` (attachment ID) | Returns URL array via `format_value()` |
| `file` | File | `int` (attachment ID) | Returns URL/title array via `format_value()` |
| `gallery` | Gallery | `array` of attachment IDs | Returns array of URL arrays |
| `post_object` | Post Object | `int` or `array` | Supports `multiple`, `post_type` filter |
| `taxonomy` | Taxonomy | `int` or `array` (term IDs) | Supports `taxonomy`, `field_type` |
| `user` | User | `int` or `array` (user IDs) | Supports `multiple`, `role` filter |
| `link` | Link | `array` (`url`, `title`, `target`) | |
| `wysiwyg` | WYSIWYG Editor | `string` (HTML) | Uses `wp_editor()`, sanitised with `wp_kses_post` |
| `message` | Message | — | Display-only. Supports `wpautop` / `br` / `none` |
| `tab` | Tab | — | UI-only, no stored value |
| `accordion` | Accordion | — | UI-only, no stored value |
| `repeater` | Repeater | `int` (row count) + row meta | Sub-fields stored as `{name}_{row}_{subfield}` |
| `flexible_content` | Flexible Content | `int` (block count) + block meta | Layout stored in `acf_fc_layout` sub-key |

---

## Template Reference

### Reading values

| Function | Returns | Description |
|---|---|---|
| `fieldforge_get( $name, $post_id )` | `mixed` | Returns the formatted field value. `$post_id` defaults to current post in the loop. Pass `'option'` for options pages. |
| `fieldforge_the( $name, $post_id )` | `void` | Echoes the value, escaped by field type (`esc_html`, `wp_kses_post`, or `<a>` tag for links). |
| `fieldforge_get_option( $name, $slug )` | `mixed` | Returns a field value from an options page. `$slug` defaults to `'option'`. |

### Repeater & Flexible Content loops

| Function | Returns | Description |
|---|---|---|
| `fieldforge_have_rows( $name, $post_id )` | `bool` | Advances the row pointer. Use as a `while` condition. |
| `fieldforge_the_row()` | `void` | No-op; provided for ACF API compatibility. |
| `fieldforge_sub_field( $name )` | `mixed` | Returns a sub-field value within the current row. |
| `fieldforge_the_sub_field( $name )` | `void` | Echoes a sub-field value, escaped as plain text. |
| `fieldforge_get_row_layout()` | `string` | Returns the layout name for the current Flexible Content row. |

### Writing values programmatically

| Function | Returns | Description |
|---|---|---|
| `fieldforge_update_field( $name, $value, $post_id )` | `bool` | Sanitises and saves a field value to postmeta. |
| `fieldforge_update_option( $name, $value, $slug )` | `bool` | Saves a field value to an options page. |

### Registering a custom field type

```php
add_action( 'fieldforge_register_fields', function( FieldForge_Field_Registry $registry ) {
    $registry->register( 'my_type', 'My_Field_Class' );
} );
```

Your class must extend `FieldForge_Field_Base` and implement at minimum:
- `render( int $post_id ): void`
- `sanitize( $value ): mixed`

---

## Location Rules

Location rules control which post edit screens a field group appears on. Rules are grouped as `OR` groups of `AND` conditions.

**Supported rule types:**

| Rule type | Example values |
|---|---|
| Post Type | `post`, `page`, `product` |
| Post Status | `publish`, `draft`, `pending` |
| User Role | `administrator`, `editor` |
| Page Parent | Post ID of the parent page |
| Page Template | Template filename, e.g. `templates/home.php` |
| Taxonomy | `category`, `post_tag`, `product_cat` |
| Post Format | `aside`, `gallery`, `video` |

**Operators:** `==` (is equal to), `!=` (is not equal to)

---

## Conditional Logic

Conditional logic hides or shows individual fields based on the value of a sibling field in the same group.

**Supported operators:**

| Operator | Meaning |
|---|---|
| `==` | is equal to |
| `!=` | is not equal to |
| `>` | is greater than (numeric) |
| `<` | is less than (numeric) |
| `>=` | is greater than or equal to |
| `<=` | is less than or equal to |
| `==empty` | has no value |
| `!=empty` | has any value |
| `==contains` | contains the string |
| `!=contains` | does not contain the string |

Rules are evaluated client-side on the post edit screen via the bundled `assets/js/admin.js`. The condition data is injected via `wp_localize_script`.

---

## Repeater & Flexible Content

### Repeater

```php
// In your template:
while ( fieldforge_have_rows( 'gallery_slides' ) ) {
    fieldforge_the_row();
    $img = fieldforge_sub_field( 'slide_image' );
    ?>
    <div class="slide">
        <img src="<?php echo esc_url( $img['url'] ); ?>"
             alt="<?php echo esc_attr( $img['alt'] ); ?>">
        <p><?php fieldforge_the_sub_field( 'caption' ); ?></p>
    </div>
    <?php
}
```

### Flexible Content

```php
while ( fieldforge_have_rows( 'page_builder' ) ) {
    fieldforge_the_row();
    $layout = fieldforge_get_row_layout();

    if ( 'hero_block' === $layout ) {
        echo '<h1>' . esc_html( fieldforge_sub_field( 'heading' ) ) . '</h1>';
    } elseif ( 'text_block' === $layout ) {
        echo wp_kses_post( fieldforge_sub_field( 'content' ) );
    } elseif ( 'image_block' === $layout ) {
        $img = fieldforge_sub_field( 'image' );
        echo '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $img['alt'] ) . '">';
    }
}
```

---

## Options Pages

Register an options page programmatically:

```php
add_action( 'init', function() {
    if ( function_exists( 'fieldforge_register_options_page' ) ) {
        fieldforge_register_options_page( array(
            'page_title'  => 'Site Settings',
            'menu_title'  => 'Site Settings',
            'menu_slug'   => 'site-settings',
            'capability'  => 'manage_options',
            'position'    => 80,
        ) );
    }
} );
```

Then attach a field group to it by setting the location rule:

> **Options Page** is equal to `site-settings`

Read values anywhere in your theme:

```php
$phone = fieldforge_get_option( 'contact_phone', 'site-settings' );
// or shorthand (defaults to 'option'):
$phone = fieldforge_get( 'contact_phone', 'option' );
```

---

## ACF Migration

### Importing from ACF

1. In ACF: go to **Custom Fields → Tools → Export Field Groups** and export as JSON.
2. In FieldForge: go to **FieldForge → Import / Export**, paste the JSON, click **Import**.

All supported field types, location rules, sub-fields, Flexible Content layouts, and conditional logic rules are imported automatically.

### Data compatibility

FieldForge uses the same `wp_postmeta` storage format as ACF. After importing the field group JSON, existing post meta written by ACF is read correctly — no database migration required for most field types.

### Unsupported ACF types

| ACF type | FieldForge behaviour |
|---|---|
| `clone` | Silently skipped — sub-fields are not inlined |
| `group` | Silently skipped |
| `range` | Falls back to `number` |
| `button_group` | Falls back to `radio` |
| `google_map` | Silently skipped |
| `oembed` | Falls back to `url` |

---

## Security

| Area | Implementation |
|---|---|
| Nonce verification | Every meta-box save is gated by `wp_verify_nonce()` per field group |
| Capability check | `current_user_can( 'edit_post', $post_id )` before any write |
| Input sanitisation | Each field type has a typed `sanitize()` method; raw `$_POST` is never written directly |
| Output escaping | `fieldforge_the()` uses `esc_html()`, `wp_kses_post()`, or `esc_url()` per field type |
| AJAX actions | All admin-ajax handlers verify a nonce and capability |
| REST API | Read-only endpoint; respects location rules so admin-only groups are not exposed |
| Options writes | `fieldforge_update_option()` sanitises through the field class before calling `update_option()` |
| Error logging | `error_log()` is gated behind the **Debug Log** setting; never fires in production by default |

---

## Architecture

```
fieldforge/
├── fieldforge.php              # Plugin bootstrap — requires all classes
├── includes/
│   ├── class-fieldforge.php    # Main singleton — instantiates all subsystems
│   ├── class-field-registry.php   # Maps type slugs → class names
│   ├── class-field-group.php      # Field group CPT & location rule engine
│   ├── class-meta-handler.php     # save_post hook — sanitise, validate, save
│   ├── class-field-renderer.php   # Enqueues assets, provides render helpers
│   ├── class-template-helpers.php # Public API functions (fieldforge_get etc.)
│   ├── class-options-page.php     # Options page registration & storage
│   ├── class-local-json.php       # JSON export/import (acf-json equivalent)
│   ├── class-rest-api.php         # /fieldforge/v1/fields/{id} endpoint
│   ├── class-conditional-logic.php# Location & conditional rule evaluator
│   ├── class-acf-importer.php     # ACF JSON → FieldForge group converter
│   └── fields/
│       ├── class-field-base.php   # Abstract base — load/save/render/sanitize
│       ├── class-field-text.php
│       ├── class-field-repeater.php
│       ├── class-field-flexible-content.php
│       └── ... (26 field type classes total)
├── admin/
│   ├── class-field-group-editor.php  # Field group edit screen UI
│   ├── class-meta-box-renderer.php   # Renders field meta boxes on post screen
│   └── class-settings-page.php       # FieldForge → Settings admin page
├── assets/
│   ├── js/admin.js             # Field group editor + conditional logic evaluator
│   └── css/admin.css           # Admin styles
├── tests/                      # PHPUnit test suite
└── .github/workflows/
    ├── ci.yml                  # Lint + test matrix (PHP 7.4–8.3)
    └── release.yml             # Builds fieldforge.zip and attaches to GitHub Release
```

**Post-save data flow:**

```
Browser POST
  └─▶ FieldForge_Meta_Handler::save_post()
        ├─ Verify nonce (per group)
        ├─ Check capability
        ├─ For each field:
        │    ├─ FieldForge_Conditional_Logic::field_is_visible()
        │    ├─ Field::sanitize( $_POST[$name] )
        │    ├─ Field::validate( $value )
        │    └─ Field::save( $post_id, $value )  →  update_post_meta()
        └─ Set transient if validation errors
```

---

## Development

### Setup

```bash
git clone https://github.com/arunrajiah/fieldforge.git
cd fieldforge
composer install
```

### Commands

| Command | Description |
|---|---|
| `composer test` | Run PHPUnit test suite |
| `composer lint` | Run PHPCS (WordPress Coding Standards) |
| `composer lint:fix` | Auto-fix PHPCS errors with PHPCBF |
| `bash bin/install-wp-tests.sh wordpress_test root '' localhost latest` | Install WP test suite (one-time) |

### CI matrix

Tests run on every push and pull request across:

| PHP | WordPress |
|---|---|
| 7.4 | latest |
| 8.0 | latest |
| 8.1 | latest |
| 8.2 | latest |
| 8.3 | latest |

### Adding a new field type

1. Create `includes/fields/class-field-{slug}.php` extending `FieldForge_Field_Base`.
2. Implement `render( int $post_id ): void` and `sanitize( $value ): mixed`.
3. Optionally override `load()`, `save()`, `validate()`, `format_value()`, `get_empty_value()`.
4. Require the file in `fieldforge.php`.
5. Register via `register_core_fields()` in `class-field-registry.php`, or use the `fieldforge_register_fields` action hook from a theme/plugin.

---

## Troubleshooting

### Fields are not showing on the post edit screen

- Check the **Location Rules** on the field group. The rule must match the current post type, status, and template.
- Make sure the field group is published (not draft).
- If using a block/Gutenberg editor, FieldForge renders meta boxes — ensure meta boxes are enabled in the editor settings (⋮ → Preferences → Panels → Custom Fields or your field group name).

### Field values are not saving

- Open browser dev tools → Network tab → look for the POST to `post.php`. Confirm `fieldforge_nonce_*` is present.
- Check for PHP errors in your server log or enable **FieldForge → Settings → Debug Log**.
- Confirm `current_user_can( 'edit_post', $post_id )` is true for the current user.

### Repeater rows rendering blank sub-fields

This is usually a `prefilled_value` issue with nested fields. Ensure you are using FieldForge ≥ 0.1.1, which fixes sub-field rendering inside Repeater and Flexible Content rows.

### ACF import produces empty fields

- Some ACF field types (`clone`, `group`, `google_map`, `oembed`) are not supported and are silently skipped. Check the **Import / Export** screen for any warnings.
- If your ACF export uses field keys (`field_abc123`) for conditional logic references, these are mapped by position — verify the resulting rules after import.

### Conditional logic not evaluating

- Open the browser console. Conditional-logic data is injected as `fieldforgeData.conditionalLogic` via `wp_localize_script`. Confirm the object is present and populated.
- On new-post screens (no `?post=` in the URL), the evaluator uses `$post_id = 0` for location checks — groups with location rules that require a saved post ID may not match.

### Debug mode

Enable detailed logging via **FieldForge → Settings → Debug Log**. Log entries are written to the PHP error log with the prefix `[FieldForge]`.

```php
// Manually log from your own code:
FieldForge_Settings_Page::debug_log( 'My debug message' );
```

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for full contribution guidelines.

**Quick summary:**

1. Fork the repository and create a branch: `feat/my-feature` or `fix/my-bug`.
2. Write or update tests for your change.
3. Run `composer test` and `composer lint` — both must pass.
4. Update `CHANGELOG.md` under `[Unreleased]`.
5. Open a pull request with a clear description of the problem and solution.

Issues, feature requests, and roadmap discussion happen in [GitHub Issues](https://github.com/arunrajiah/fieldforge/issues).

---

## Changelog

### 0.1.1
- Fixed: sub-fields inside Repeater and Flexible Content rows rendering blank (prefilled\_value not honoured in leaf field classes).
- Fixed: required True/False field unsaveable when set to "No" (false positive required validation).
- Fixed: message field always rendering empty (message\_content key mismatch).
- Fixed: Flexible Content type missing from field type selector in the group editor.
- Fixed: ACF Importer never instantiated from plugin bootstrap.
- Fixed: Settings page options (local JSON path, debug log) not wired into subsystems.
- Fixed: `error_log()` firing unconditionally in production; now gated behind Debug Log setting.
- Fixed: REST API exposing fields for groups with no location rules (should be hidden like admin).
- Fixed: duplicate conditional-logic data emission (both inline script and `wp_localize_script`).

### 0.1.0
- Initial public release.
- 26 field types including Repeater and Flexible Content.
- Options Pages, conditional logic, REST API, ACF JSON import, local JSON sync.
- PHPUnit test suite with CI matrix on PHP 7.4–8.3.

---

## Releases

FieldForge is distributed as a GitHub Release ZIP until the plugin is listed in the WordPress.org directory.

| Version | Date | Download | Notes |
|---|---|---|---|
| [0.1.1](https://github.com/arunrajiah/fieldforge/releases/tag/v0.1.1) | 2025 | [fieldforge.zip](https://github.com/arunrajiah/fieldforge/releases/download/v0.1.1/fieldforge.zip) | Bug-fix release — 9 sub-system fixes |
| [0.1.0](https://github.com/arunrajiah/fieldforge/releases/tag/v0.1.0) | 2025 | [fieldforge.zip](https://github.com/arunrajiah/fieldforge/releases/download/v0.1.0/fieldforge.zip) | Initial public release |

### Installing a release ZIP

1. Go to the [Releases](https://github.com/arunrajiah/fieldforge/releases) page and download `fieldforge.zip` for the version you want.
2. In WordPress: **Plugins → Add New → Upload Plugin → Choose File**.
3. Select the downloaded zip and click **Install Now**, then **Activate**.

### Versioning

FieldForge follows [Semantic Versioning](https://semver.org/):

- **PATCH** (`0.1.x`) — backwards-compatible bug fixes.
- **MINOR** (`0.x.0`) — new features, backwards-compatible.
- **MAJOR** (`x.0.0`) — breaking changes (none yet planned).

Release ZIPs are built automatically by [`.github/workflows/release.yml`](.github/workflows/release.yml) when a version tag (`v*.*.*`) is pushed to `main`. The ZIP includes all plugin files wrapped in a `fieldforge/` directory, ready for WordPress upload.

---

## License

FieldForge is released under the **MIT License**.



```
MIT License

Copyright (c) 2025 FieldForge Contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
```

Full license text: [LICENSE](LICENSE) · [opensource.org/licenses/MIT](https://opensource.org/licenses/MIT)

This means you are free to use, modify, and redistribute FieldForge in any project — including commercial and closed-source projects — with no copyleft requirements. No CLA required to contribute.

---

Built and maintained by [FieldForge Contributors](https://github.com/arunrajiah/fieldforge/graphs/contributors).  
Sponsor the project: [❤ GitHub Sponsors](https://github.com/sponsors/arunrajiah)
