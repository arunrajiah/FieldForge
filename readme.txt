=== FieldForge ===
Contributors: arunrajiah
Donate link: https://github.com/sponsors/arunrajiah
Tags: custom fields, meta box, repeater, flexible content, field groups
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Open-source, GPL custom fields plugin for WordPress — with a native Repeater field, Flexible Content, Options Pages, and full ACF JSON import compatibility.

== Description ==

**FieldForge** gives agencies and developers a community-governed, feature-rich custom fields plugin for WordPress.

Every feature is built and maintained in the open. No freemium gates, no SaaS backend, no phone-home. Just a solid custom-fields plugin that runs entirely inside WordPress.

= Key Features =

* **26 field types** — text, textarea, number, select, checkbox, radio, true/false, date picker, time picker, color picker, URL, email, password, file, image, gallery, post object, taxonomy, user, link, WYSIWYG, message, tab, accordion, **Repeater**, and **Flexible Content**.
* **Native Repeater field** — supports drag-to-reorder rows, configurable sub-fields, and min/max row limits.
* **Flexible Content field** — multiple layouts each with their own sub-fields, drag-to-reorder blocks.
* **Options Pages** — register custom admin pages and store global settings outside of post meta.
* **ACF JSON import** — export your existing ACF field groups as JSON and import them into FieldForge. Field types, choices, location rules, Repeater sub-fields, and Flexible Content layouts are all mapped automatically.
* **ACF-compatible data storage** — values are stored in standard `wp_postmeta` using the same key conventions. Existing ACF data can be read without any migration.
* **Template helpers** — `fieldforge_get()`, `fieldforge_the()`, `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`, `fieldforge_update_field()`, `fieldforge_get_option()`, `fieldforge_update_option()`.
* **Location rules** — show field groups by post type, post status, user role, page parent, or page template. OR groups and AND rules.
* **Conditional logic** — show or hide individual fields based on the values of other fields.
* **REST API** — field values exposed via the WordPress REST API with location-rule filtering.
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

= Import from ACF =

Export your ACF field groups (**Custom Fields → Tools → Export Field Groups**), then go to **FieldForge → Import / Export** and paste the JSON. All supported field types and location rules are mapped automatically.

See `docs/acf-compatibility.md` in the plugin folder for a full field type mapping table.

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

FieldForge includes 26 field types (text, textarea, number, email, URL, password, select, checkbox, radio, true/false, date picker, time picker, color picker, image, file, gallery, post object, taxonomy, user, link, WYSIWYG, message, tab, accordion, Repeater, and Flexible Content), plus Options Pages, conditional logic, a REST API, and an ACF JSON importer. The Clone field and Gutenberg block fields are on the roadmap.

= Can I migrate my existing ACF data? =

Yes — for most cases, no migration is needed at all. FieldForge uses the same `wp_postmeta` storage format as ACF (including the `_{field_name}` key reference convention). Import your field group JSON into FieldForge, and your existing post data will be read correctly without touching the database.

= Is it safe to use on a production site? =

v0.1 is an early but stable release. The core save/load path is covered by a PHPUnit test suite running on PHP 7.4 through 8.3. We recommend testing on a staging site before deploying to production, and watching the [GitHub releases](https://github.com/arunrajiah/fieldforge/releases) for patch updates.

= How do I display field values in my theme templates? =

Use `fieldforge_get( 'field_name' )` to return a value, or `fieldforge_the( 'field_name' )` to echo it safely escaped. For Repeater fields, use the `fieldforge_have_rows()` / `fieldforge_the_row()` / `fieldforge_sub_field()` loop helpers — they are intentionally API-compatible with ACF's `have_rows()` / `the_row()` / `get_sub_field()`.

= Does FieldForge conflict with ACF if both are active? =

The two plugins do not conflict — they register different function names, CPT slugs, and meta keys. You can run both simultaneously during a migration. Once you have imported your field groups and verified everything works in FieldForge, deactivate ACF.

== Changelog ==

= 0.1.1 =
* Fixed: sub-fields inside Repeater and Flexible Content rows rendering blank.
* Fixed: required True/False field unsaveable when set to "No".
* Fixed: message field always rendering empty (key mismatch).
* Fixed: Flexible Content missing from field type selector in the group editor.
* Fixed: ACF Importer not instantiated from plugin bootstrap.
* Fixed: Settings page options (local JSON path, debug log) not consumed by subsystems.
* Fixed: error_log() firing unconditionally in production.
* Fixed: REST API exposing field groups with no location rules.
* Fixed: duplicate conditional-logic script emission.

= 0.1.0 =
* Initial public release.
* 22 field types: text, textarea, number, email, URL, password, select, checkbox, radio, true/false, date picker, color picker, file, image, gallery, post object, taxonomy, user, link, WYSIWYG, message, time picker, tab, accordion, and Repeater.
* Flexible Content field with multiple configurable layouts, drag-to-reorder blocks, and min/max limits.
* Options Pages — register global settings pages outside of post meta.
* Repeater field with ACF-compatible storage format and drag-to-reorder rows.
* ACF JSON importer — maps all common ACF field types, conditional logic rules, Flexible Content layouts, and Repeater sub-fields.
* FieldForge JSON export and import.
* Template helpers: `fieldforge_get()`, `fieldforge_the()`, `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`, `fieldforge_update_field()`, `fieldforge_get_option()`, `fieldforge_update_option()`.
* `fieldforge_the()` branches by field type: WYSIWYG/message use `wp_kses_post()`, link renders an `<a>` tag, others use `esc_html()`.
* Location rules: post type, post status, user role, page parent, page template, taxonomy, format — with OR groups and AND rules.
* Conditional logic: show/hide fields based on other field values.
* REST API: field values for posts exposed via `/fieldforge/v1/fields/{id}` with location-rule filtering.
* Field validation: number enforces min/max, email validates format, URL validates format, text enforces maxlength.
* Programmatic helpers: `fieldforge_update_field()`, `fieldforge_get_option()`, `fieldforge_update_option()`.
* Local JSON sync (acf-json equivalent) — field groups saved as JSON files in the theme.
* Field Group editor with drag-to-reorder fields, live field name auto-generation, type-specific settings panels, Repeater/Flexible Content sub-field editors, and dynamic location rule value dropdowns.
* PHPUnit test suite covering all field type sanitize/save/load cycles and ACF importer logic.
* GitHub Actions CI matrix on PHP 7.4, 8.0, 8.1, 8.2, 8.3.

== Upgrade Notice ==

= 0.1.0 =
Initial release — no upgrade path needed.
