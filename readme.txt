=== FieldForge ===
Contributors: arunrajiah
Donate link: https://github.com/sponsors/arunrajiah
Tags: custom fields, meta box, acf alternative, repeater, advanced custom fields
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Open-source, GPL alternative to Advanced Custom Fields (ACF) Pro — with a native Repeater field and full ACF JSON import compatibility.

== Description ==

**FieldForge** gives agencies and developers a community-governed, feature-rich alternative to ACF Pro — without the paywall.

Every feature is built and maintained in the open. No freemium gates, no SaaS backend, no phone-home. Just a solid custom-fields plugin that runs entirely inside WordPress.

= Key Features =

* **22 field types** — text, textarea, number, select, checkbox, radio, true/false, date picker, color picker, URL, email, password, file, image, gallery, post object, taxonomy, user, link, WYSIWYG, message, and **Repeater**.
* **Native Repeater field** — the most-requested ACF Pro feature, free for everyone. Supports drag-to-reorder rows, configurable sub-fields, and min/max row limits.
* **ACF JSON import** — export your existing ACF field groups as JSON and import them into FieldForge. Field types, choices, location rules, and Repeater sub-fields are all mapped automatically.
* **ACF-compatible data storage** — values are stored in standard `wp_postmeta` using the same key conventions as ACF. If you already have ACF data in the database, FieldForge can read it without any migration.
* **Template helpers** — `fieldforge_get()`, `fieldforge_the()`, `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`.
* **Location rules** — show field groups by post type, post status, user role, page parent, or page template. OR groups and AND rules, just like ACF.
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

* v0.2 — Flexible Content field
* v0.3 — Gutenberg block fields
* v0.4 — Options pages
* v0.5 — Conditional logic UI, REST API integration, Clone field

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

= Is this a drop-in replacement for ACF Free and ACF Pro? =

FieldForge covers all of ACF Free's field types plus the Repeater field from ACF Pro — without a licence fee. v0.1 does not yet include Flexible Content, Options Pages, or the Clone field, which are on the roadmap. For teams whose primary blocker is the Repeater paywall, FieldForge is a solid drop-in today.

= Can I migrate my existing ACF data? =

Yes — for most cases, no migration is needed at all. FieldForge uses the same `wp_postmeta` storage format as ACF (including the `_{field_name}` key reference convention). Import your field group JSON into FieldForge, and your existing post data will be read correctly without touching the database.

= Is it safe to use on a production site? =

v0.1 is an early but stable release. The core save/load path is covered by a PHPUnit test suite running on PHP 7.4 through 8.3. We recommend testing on a staging site before deploying to production, and watching the [GitHub releases](https://github.com/arunrajiah/fieldforge/releases) for patch updates.

= How do I display field values in my theme templates? =

Use `fieldforge_get( 'field_name' )` to return a value, or `fieldforge_the( 'field_name' )` to echo it safely escaped. For Repeater fields, use the `fieldforge_have_rows()` / `fieldforge_the_row()` / `fieldforge_sub_field()` loop helpers — they are intentionally API-compatible with ACF's `have_rows()` / `the_row()` / `get_sub_field()`.

= Does FieldForge conflict with ACF if both are active? =

The two plugins do not conflict — they register different function names, CPT slugs, and meta keys. You can run both simultaneously during a migration. Once you have imported your field groups and verified everything works in FieldForge, deactivate ACF.

== Screenshots ==

1. Field Group editor — add and configure fields with a clean drag-and-drop UI.
2. Meta box on a post edit screen showing multiple field types.
3. Repeater field with multiple rows and drag-to-reorder handles.
4. Import / Export tools page — paste ACF JSON to import in one click.

== Changelog ==

= 0.1.0 =
* Initial public release.
* 22 field types: text, textarea, number, email, URL, password, select, checkbox, radio, true/false, date picker, color picker, file, image, gallery, post object, taxonomy, user, link, WYSIWYG, message, and Repeater.
* Repeater field with ACF-compatible storage format (`{name}` = row count, `{name}_{i}_{sub}` = sub-field values) and drag-to-reorder rows.
* ACF JSON importer — maps all common ACF field types to FieldForge equivalents.
* FieldForge JSON export and import.
* Template helpers: `fieldforge_get()`, `fieldforge_the()`, `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`.
* Location rules: post type, post status, user role, page parent, page template — with OR groups and AND rules.
* Field Group editor with drag-to-reorder fields and live field name auto-generation.
* PHPUnit test suite covering all field type sanitize/save/load cycles and ACF importer logic.
* GitHub Actions CI matrix on PHP 7.4, 8.0, 8.1, 8.2, 8.3.

== Upgrade Notice ==

= 0.1.0 =
Initial release — no upgrade path needed.
