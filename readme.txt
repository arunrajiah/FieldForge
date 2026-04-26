=== FieldForge ===
Contributors: arunrajiah
Donate link: https://github.com/sponsors/arunrajiah
Tags: custom fields, meta box, repeater, flexible content, field groups
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.1
License: MIT
License URI: https://opensource.org/licenses/MIT

A powerful, open-source custom fields plugin for WordPress — with a native Repeater field, Flexible Content, Options Pages, conditional logic, and a polished field group editor.

== Description ==

**FieldForge** gives agencies and developers a community-governed, feature-rich custom fields plugin for WordPress.

Every feature is built and maintained in the open. No freemium gates, no SaaS backend, no phone-home. Just a solid custom-fields plugin that runs entirely inside WordPress.

= Key Features =

* **26 field types** — text, textarea, number, select, checkbox, radio, true/false, date picker, time picker, color picker, URL, email, password, file, image, gallery, post object, taxonomy, user, link, WYSIWYG, message, tab, accordion, **Repeater**, and **Flexible Content**.
* **Native Repeater field** — supports drag-to-reorder rows, configurable sub-fields, and min/max row limits.
* **Flexible Content field** — multiple layouts each with their own sub-fields, drag-to-reorder blocks.
* **Options Pages** — register custom admin pages and store global settings outside of post meta.
* **JSON import / export** — export field groups as JSON and import them on any site. Field types, choices, location rules, Repeater sub-fields, and Flexible Content layouts are all mapped automatically.
* **Standard postmeta storage** — values are stored in standard `wp_postmeta` using established key conventions, compatible with any plugin that reads WordPress post meta.
* **Template helpers** — `fieldforge_get()`, `fieldforge_the()`, `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`, `fieldforge_update_field()`, `fieldforge_get_option()`, `fieldforge_update_option()`.
* **Location rules** — show field groups by post type, post status, user role, page parent, or page template. OR groups and AND rules.
* **Conditional logic** — show or hide individual fields based on the values of other fields.
* **REST API** — field values exposed via the WordPress REST API with location-rule filtering.
* **Local JSON sync** — field groups saved as JSON files alongside your theme for version control.
* **100% GPL, no SaaS** — runs entirely inside WordPress. No external services.

= Basic Usage =

1. Go to **FieldForge → Add New** to create a field group.
2. Click **+ Add Field**, choose a field type, give it a name and label.
3. Set a location rule (e.g. "Post Type is equal to Post").
4. Publish the field group.
5. Edit any matching post — your fields appear as a meta box.

In your theme templates:

`
<?php echo esc_html( fieldforge_get( 'subtitle' ) ); ?>
`

Or simply:

`
<?php fieldforge_the( 'subtitle' ); ?>
`

= Repeater Usage =

`
<?php while ( fieldforge_have_rows( 'team_members' ) ) : fieldforge_the_row(); ?>
    <h3><?php fieldforge_the_sub_field( 'name' ); ?></h3>
    <p><?php fieldforge_the_sub_field( 'role' ); ?></p>
<?php endwhile; ?>
`

= JSON Import =

Go to **FieldForge → Import / Export**, paste your field group JSON, and click Import. All supported field types and location rules are mapped automatically.

See `docs/json-import.md` in the plugin folder for the full import guide.

= Roadmap =

* v0.2 — Gutenberg / block editor field meta boxes
* v0.3 — Clone field
* v0.4 — Improved conditional logic UI builder

== Installation ==

= From the WordPress plugin directory =

1. Go to **Plugins → Add New** and search for "FieldForge".
2. Click **Install Now**, then **Activate**.

= From a ZIP file =

1. Download the latest `fieldforge.zip` from the [GitHub Releases page](https://github.com/arunrajiah/fieldforge/releases).
2. Go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip and click **Install Now**, then **Activate**.

= From source =

1. Clone the repository into `wp-content/plugins/fieldforge`.
2. Run `composer install` to install dev dependencies.
3. Activate the plugin from **Plugins → Installed Plugins**.

== Frequently Asked Questions ==

= What field types and features are included? =

FieldForge includes 26 field types (text, textarea, number, email, URL, password, select, checkbox, radio, true/false, date picker, time picker, color picker, image, file, gallery, post object, taxonomy, user, link, WYSIWYG, message, tab, accordion, Repeater, and Flexible Content), plus Options Pages, conditional logic, a REST API, a JSON importer, and local JSON sync. The Clone field and Gutenberg block fields are on the roadmap.

= Can I import existing field group configurations? =

Yes — go to **FieldForge → Import / Export** and paste a field group JSON export. FieldForge maps all supported field types, choices, location rules, sub-fields, and Flexible Content layouts automatically.

= Is it safe to use on a production site? =

v0.1 is an early but stable release. The core save/load path is covered by a PHPUnit test suite running on PHP 7.4 through 8.3. We recommend testing on a staging site before deploying to production, and watching the [GitHub releases](https://github.com/arunrajiah/fieldforge/releases) for patch updates.

= How do I display field values in my theme templates? =

Use `fieldforge_get( 'field_name' )` to return a value, or `fieldforge_the( 'field_name' )` to echo it safely escaped. For Repeater fields, use the `fieldforge_have_rows()` / `fieldforge_the_row()` / `fieldforge_sub_field()` loop helpers.

= Does FieldForge conflict with other custom field plugins? =

FieldForge registers its own function names, CPT slugs, and meta keys and does not interfere with other custom field plugins. You can run multiple custom field plugins simultaneously.

== Screenshots ==

1. **Field Group Editor — fields overview.** Six fields in the "Article Details" group, each row showing a colour-coded type icon, field name slug, and category badge (Text, Textarea, Number, True/False, Image, Post Object). Drag handles let you reorder by dragging. Action icons on the right duplicate, expand, or delete a field.
2. **Field Group Editor — field expanded.** The "Subtitle" Text field is open, showing the two-column settings grid: label, slug, type picker, instructions, required toggle, default value, and placeholder.
3. **Field Group Editor — empty state.** A fresh group before any fields are added — dashed placeholder card prompts you to click + Add Field.
4. **Field Groups list.** The standard WordPress list table showing all published field groups at a glance.
5. **Post edit screen — custom meta box.** FieldForge renders its fields as a standard WordPress meta box on the post editor, respecting location rules.
6. **Import / Export page.** Paste any JSON field-group export and click Import to bring fields across from any site.
7. **Settings page.** Configure local JSON sync paths, enable debug logging, and sponsor the project — all in one place.

== Changelog ==

= 0.1.1 =
* Fixed: sub-fields inside Repeater and Flexible Content rows rendering blank.
* Fixed: required True/False field unsaveable when set to "No".
* Fixed: message field always rendering empty (key mismatch).
* Fixed: Flexible Content missing from field type selector in the group editor.
* Fixed: JSON importer not instantiated from plugin bootstrap.
* Fixed: Settings page options (local JSON path, debug log) not consumed by subsystems.
* Fixed: error_log() firing unconditionally in production.
* Fixed: REST API exposing field groups with no location rules.
* Fixed: duplicate conditional-logic script emission.

= 0.1.0 =
* Initial public release.
* 26 field types: text, textarea, number, email, URL, password, select, checkbox, radio, true/false, date picker, time picker, color picker, file, image, gallery, post object, taxonomy, user, link, WYSIWYG, message, tab, accordion, Repeater, and Flexible Content.
* Flexible Content field with multiple configurable layouts, drag-to-reorder blocks, and min/max limits.
* Options Pages — register global settings pages outside of post meta.
* Repeater field with drag-to-reorder rows and configurable sub-fields.
* JSON importer — maps field types, conditional logic rules, Flexible Content layouts, and Repeater sub-fields.
* JSON export and import for field groups.
* Template helpers: `fieldforge_get()`, `fieldforge_the()`, `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`, `fieldforge_update_field()`, `fieldforge_get_option()`, `fieldforge_update_option()`.
* Location rules: post type, post status, user role, page parent, page template, taxonomy, format — with OR groups and AND rules.
* Conditional logic: show/hide fields based on other field values.
* REST API: field values for posts exposed via `/fieldforge/v1/fields/{id}` with location-rule filtering.
* Field validation: number enforces min/max, email validates format, URL validates format, text enforces maxlength.
* Local JSON sync — field groups saved as JSON files in the theme for version control.
* Field Group editor with drag-to-reorder fields, live field name auto-generation, type-specific settings panels, Repeater/Flexible Content sub-field editors, and dynamic location rule value dropdowns.
* PHPUnit test suite covering all field type sanitize/save/load cycles and JSON importer logic.
* GitHub Actions CI matrix on PHP 7.4, 8.0, 8.1, 8.2, 8.3.

== Upgrade Notice ==

= 0.1.0 =
Initial release — no upgrade path needed.
