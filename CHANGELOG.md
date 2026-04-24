# Changelog

All notable changes to FieldForge are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] — 2026-04-24

### Added

**Core plugin**
- Main plugin singleton (`FieldForge`) bootstrapped via `plugins_loaded`.
- `fieldforge_group` custom post type for storing field group definitions.
- Admin menu: **FieldForge → Field Groups** and **FieldForge → Import / Export**.
- Activation hook stores `fieldforge_version` option and flushes rewrite rules.

**Field types (22 total)**
- `text` — single-line text input with placeholder, prepend/append, maxlength.
- `textarea` — multi-line text with configurable rows.
- `number` — numeric input with min, max, step validation.
- `email` — validated email address field.
- `url` — validated URL field.
- `password` — masked text input.
- `select` — dropdown with single or multiple selection and allow-null option.
- `checkbox` — multiple selection from a choice list.
- `radio` — single selection from a choice list.
- `true_false` — toggle checkbox with hidden-input pattern so unchecked posts 0.
- `date_picker` — HTML5 date input; stores in `Ymd` format for ACF compatibility.
- `color_picker` — native `<input type="color">` with hex sanitization.
- `message` — display-only field, no value stored.
- `image` — WordPress media library picker; stores attachment ID.
- `file` — WordPress media library picker for any file type; stores attachment ID.
- `gallery` — multi-image media picker; stores array of attachment IDs with sortable UI.
- `post_object` — searchable post picker (single or multiple); supports post_type filter.
- `taxonomy` — term picker rendered as checkbox list, radio list, select, or multi-select.
- `user` — user picker (single or multiple) with optional role filter.
- `link` — structured link storing `{url, title, target}`.
- `wysiwyg` — full WordPress TinyMCE/Quicktags editor; configurable toolbar and media upload.
- `repeater` — multiple rows of any sub-field combination; ACF-compatible storage format.

**Repeater field**
- ACF-compatible storage: `{name}` stores row count; `{name}_{i}_{sub}` stores each cell value.
- Add Row, Remove Row, and drag-to-reorder (jQuery UI Sortable).
- Min/max row limits enforced in JS.
- Template helpers: `fieldforge_have_rows()`, `fieldforge_the_row()`, `fieldforge_sub_field()`, `fieldforge_the_sub_field()`.
- Stale rows beyond current count are cleaned up from postmeta on save.

**Template helpers**
- `fieldforge_get( $name, $post_id )` — returns field value with type-aware formatting.
- `fieldforge_the( $name, $post_id )` — echoes value, escaped as plain text.
- `fieldforge_have_rows( $name, $post_id )` — repeater loop check and pointer advance.
- `fieldforge_the_row()` — ACF-compatible row step function (no-op; pointer advanced by `have_rows`).
- `fieldforge_sub_field( $name )` — returns current row's sub-field value.
- `fieldforge_the_sub_field( $name )` — echoes sub-field value, escaped.

**ACF importer**
- Accepts ACF JSON export format (single group object or array of groups).
- Maps all common ACF field types to FieldForge equivalents.
- Preserves location rules, field choices, Repeater sub-fields, and group settings.
- Maps all ACF field types including Flexible Content, conditional_logic rules, and layout definitions.
- See `docs/acf-compatibility.md` for the full mapping table.

**FieldForge JSON export/import**
- Export button on each Field Group edit screen.
- Bulk export from **Import / Export** tools page.
- Import FieldForge JSON to create or update field groups.

**Flexible Content field**
- Multiple configurable layouts, each with their own sub-fields.
- Drag-to-reorder blocks; min/max block limits.
- Layout picker dropdown to add new blocks by layout name.
- ACF-compatible storage format.

**Options Pages**
- Register custom admin menu pages via `FieldForge_Options_Page`.
- Store and retrieve global settings outside of post meta.
- `fieldforge_get_option()` / `fieldforge_update_option()` template helpers.

**Conditional Logic**
- Show or hide individual fields based on the value of other fields in the same group.
- Rules editor in the field group admin: field, operator, value.
- Front-end JS evaluates rules on post edit screen in real time.

**REST API**
- `GET /fieldforge/v1/fields/{post_id}` returns all applicable field values.
- `PUT /fieldforge/v1/fields/{post_id}` updates field values (editor capability required).
- Location rules respected: only fields from matching groups are returned.

**Field validation**
- `number`: enforces min/max constraints.
- `email`: validates address format via `is_email()`.
- `url`: validates URL format via `FILTER_VALIDATE_URL`.
- `text`: enforces optional `maxlength` constraint.

**Additional template helpers**
- `fieldforge_update_field( $name, $value, $post_id )` — programmatic field update.
- `fieldforge_get_option( $name, $page_slug )` — read an options-page field.
- `fieldforge_update_option( $name, $value, $page_slug )` — write an options-page field.
- `fieldforge_the()` now branches by field type: WYSIWYG/message use `wp_kses_post()`,
  link renders an `<a>` tag, all others use `esc_html()`.

**Local JSON sync**
- Field groups automatically saved as JSON files (acf-json equivalent).
- Changes on disk reloaded on next admin page load.

**Field Group editor UI**
- Drag-to-reorder fields within a group.
- Live field name auto-generation from label (with manual override).
- Type-specific settings panels rendered inline for every field type.
- Repeater sub-field editor: add/remove/reorder sub-fields without saving first.
- Flexible Content layout editor: add/remove/reorder layouts and their sub-fields.
- Location rules editor: OR groups of AND rules; value widget adapts to param type
  (post-type select, status select, role select, template select, page-parent select).
- Supported location params: post type, post status, user role, page parent, page template,
  taxonomy, post format, attachment, options page.
- Group position setting: Normal, Side, After Title.
- Conditional logic builder in each field row.

**Admin UI / CSS**
- Tab field: coloured section separator.
- Accordion field: collapsible section with animated arrow toggle.
- Responsive layout: type picker grid, field settings grid, and repeater rows
  all adapt at 782 px and 480 px breakpoints.

**CI / deployment**
- GitHub Actions deploy workflow pushes to WordPress.org SVN on version tags.
- `.distignore` excludes dev-only files from the SVN build.
- `.wordpress-org/` directory for plugin listing banner and icon assets.

**Testing & tooling**
- PHPUnit test suite covering all 22 field type `sanitize()` methods and save/load cycles.
- ACF importer tests: single group, array of groups, repeater sub-fields,
  Flexible Content layouts, conditional logic, location rules, invalid JSON.
- GitHub Actions CI matrix on PHP 7.4, 8.0, 8.1, 8.2, 8.3.
- `composer lint` (WPCS) and `composer test` (PHPUnit) scripts.
