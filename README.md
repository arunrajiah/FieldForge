# FieldForge

**Open-source, GPL alternative to Advanced Custom Fields (ACF) Pro** — with native Repeater field and full ACF JSON import compatibility.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net)
[![Sponsor](https://img.shields.io/badge/Sponsor-%E2%9D%A4-pink)](https://github.com/sponsors/arunrajiah)

---

## Why FieldForge?

Most teams can't migrate away from ACF Pro because of two features: **Repeater** and **Flexible Content** fields. FieldForge ships the Repeater in v0.1 and targets Flexible Content in v0.2, giving agencies and developers a community-governed, 100% GPL path forward.

- **Zero lock-in.** All data is stored in standard `wp_postmeta`, ACF-compatible.
- **No SaaS. No phone-home.** Pure PHP + MySQL, runs entirely inside WordPress.
- **Import from ACF.** Paste your ACF field group JSON export and it just works.

---

## Features (v0.1)

| Feature | Status |
|---|---|
| Field Group CPT with location rules | ✅ |
| 22 core field types | ✅ |
| **Repeater field** | ✅ |
| Classic editor meta boxes | ✅ |
| Template helpers (`fieldforge_get`, `fieldforge_the`) | ✅ |
| ACF JSON import | ✅ |
| FieldForge JSON export/import | ✅ |
| PHPUnit test suite | ✅ |

### Field Types

`text` · `textarea` · `number` · `select` · `checkbox` · `radio` · `true_false` · `date_picker` · `color_picker` · `url` · `email` · `password` · `file` · `image` · `gallery` · `post_object` · `taxonomy` · `user` · `link` · `wysiwyg` · `message` · **`repeater`**

---

## Requirements

- **WordPress** 6.2 or later
- **PHP** 7.4 or later
- MySQL 5.7+ / MariaDB 10.3+

---

## Installation

### From the WordPress admin

1. Download the latest `.zip` from the [Releases](https://github.com/arunrajiah/fieldforge/releases) page.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Upload and activate.

### With Composer / WPackagist

```bash
composer require wpackagist-plugin/fieldforge
```

### Manual

```bash
cd wp-content/plugins
git clone https://github.com/arunrajiah/fieldforge.git
```

Activate the plugin in **Plugins → Installed Plugins**.

---

## Quick Start

### 1. Create a Field Group

Go to **FieldForge → Add New** in the admin. Add fields, set location rules (e.g. "Post Type is equal to `post`"), and publish.

### 2. Edit a Post

Open any post that matches your location rules — your fields appear as meta boxes.

### 3. Display in Templates

```php
// Single value
$hero_title = fieldforge_get( 'hero_title' );
echo esc_html( $hero_title );

// Echo directly (auto-escaped)
fieldforge_the( 'hero_title' );

// Repeater loop
while ( fieldforge_have_rows( 'team_members' ) ) {
    echo '<h3>' . esc_html( fieldforge_sub_field( 'name' ) ) . '</h3>';
    echo '<p>'  . esc_html( fieldforge_sub_field( 'role' ) ) . '</p>';
}
```

---

## Importing from ACF

1. In ACF: go to **Custom Fields → Tools → Export Field Groups**, export as JSON.
2. In FieldForge: go to **FieldForge → Import / Export**, paste the JSON, click **Import**.

All supported field types are imported with zero data loss. Existing post meta written by ACF is fully compatible (FieldForge uses the same `wp_postmeta` storage format).

---

## Template Reference

| Function | Description |
|---|---|
| `fieldforge_get( 'field_name', $post_id )` | Returns the field value. |
| `fieldforge_the( 'field_name', $post_id )` | Echoes the field value (escaped). |
| `fieldforge_have_rows( 'repeater_name', $post_id )` | Iterates repeater rows. |
| `fieldforge_sub_field( 'sub_field_name' )` | Returns a sub-field value inside a repeater loop. |
| `fieldforge_the_sub_field( 'sub_field_name' )` | Echoes a sub-field value (escaped). |

---

## Developer Hooks

### Register a custom field type

```php
add_action( 'fieldforge_register_fields', function( $registry ) {
    $registry->register( 'my_type', 'My_Custom_Field_Class' );
} );
```

Your class must extend `FieldForge_Field_Base` and implement `render()` and `sanitize()`.

---

## Running Tests

```bash
# Install WP test suite (once)
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run PHPUnit
composer install
./vendor/bin/phpunit
```

---

## Contributing

We welcome all contributions. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a PR.

**Roadmap for v0.2:**
- Flexible Content field
- Gutenberg block fields
- Options pages
- Conditional logic (show/hide)
- REST API integration

---

## Sponsoring

FieldForge is free and open-source. If it saves you time or money, please consider sponsoring its development:

**[❤ Sponsor FieldForge on GitHub](https://github.com/sponsors/arunrajiah)**

Every contribution helps keep the project maintained, documented, and growing.

---

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
