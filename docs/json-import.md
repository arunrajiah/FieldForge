# JSON Import & Export

FieldForge can import and export field group definitions as JSON. This lets you move field groups between sites, commit them to version control, and restore configurations without manual recreation.

---

## Exporting a field group

1. Go to **FieldForge → Field Groups** and open any field group.
2. Click the **Export JSON** button in the sidebar.
3. Save the downloaded `.json` file.

Alternatively, if Local JSON sync is enabled (see Settings), field groups are written to JSON files automatically on every save.

---

## Importing a field group

1. Go to **FieldForge → Import / Export**.
2. Paste the JSON into the text area and click **Import**.
3. FieldForge creates a new field group for each object in the JSON.

If a field group with the same title already exists it will be **skipped** — delete the existing group first if you want to replace it.

---

## JSON field type mapping

The importer maps all common field type names to FieldForge equivalents:

| JSON type | FieldForge type | Notes |
|---|---|---|
| `text` | `text` | |
| `textarea` | `textarea` | |
| `number` | `number` | |
| `range` | `number` | Min/max/step preserved; range slider UI not yet available |
| `email` | `email` | |
| `url` | `url` | |
| `password` | `password` | |
| `image` | `image` | |
| `file` | `file` | |
| `wysiwyg` | `wysiwyg` | |
| `oembed` | `url` | Converted to plain URL field |
| `gallery` | `gallery` | |
| `select` | `select` | |
| `checkbox` | `checkbox` | |
| `radio` | `radio` | |
| `button_group` | `radio` | Rendered as radio buttons |
| `true_false` | `true_false` | |
| `link` | `link` | |
| `post_object` | `post_object` | |
| `page_link` | `url` | Converted to plain URL field |
| `relationship` | `post_object` | Multiple selection preserved |
| `taxonomy` | `taxonomy` | |
| `user` | `user` | |
| `google_map` | `text` | Stores address string only |
| `date_picker` | `date_picker` | Stores in `Ymd` format |
| `date_time_picker` | `date_picker` | Time component dropped |
| `time_picker` | `time_picker` | |
| `color_picker` | `color_picker` | |
| `message` | `message` | |
| `accordion` | `accordion` | |
| `tab` | `tab` | |
| `group` | `repeater` | Converted to single-row repeater |
| `repeater` | `repeater` | Full sub-field support |
| `flexible_content` | `flexible_content` | All layouts and sub-fields preserved |
| `clone` | `text` | Clone logic not supported |

> **Lossy conversions** — `range`, `oembed`, `page_link`, `relationship`, `button_group`, `date_time_picker`, `google_map`, `group`, and `clone` are converted with data loss. A warning is logged to the debug log (if enabled in Settings) for each affected field.

---

## Unsupported field types

Unknown field type names not listed above are **skipped** during import. A debug log entry is written for each skipped field.

---

## Location rules

Location rule groups and conditions are imported as-is. Supported parameters:

- `post_type`
- `post_status`
- `user_role`
- `page_parent`
- `page_template`
- `taxonomy`
- `post_format`
- `attachment`
- `comment`
- `nav_menu`
- `options_page`

Unknown parameters are preserved as-is and will simply never match.

---

## Conditional logic

Conditional logic rules are imported per-field. Each rule's `field`, `operator`, and `value` are preserved. Supported operators: `==`, `!=`.

---

## Local JSON sync

When a JSON save path is configured in **FieldForge → Settings**, every field group save writes a JSON file named `group_{ID}.json` to that directory. This is the equivalent of storing field configurations in version control alongside your theme.

To load JSON files on a new site:

1. Copy the JSON files into the configured load path.
2. Go to **FieldForge → Settings** and click **Sync from JSON**.

---

## Storage format

FieldForge stores field values in standard `wp_postmeta`:

| Meta key | Value |
|---|---|
| `{field_name}` | The field value |
| `_{field_name}` | The field key (for type lookup) |

**Repeater rows:**

| Meta key | Value |
|---|---|
| `{name}` | Row count (integer) |
| `{name}_{i}_{sub_field_name}` | Sub-field value for row `i` |

**Flexible Content blocks:**

| Meta key | Value |
|---|---|
| `{name}` | Block count (integer) |
| `{name}_{i}_acf_fc_layout` | Layout name for block `i` |
| `{name}_{i}_{sub_field_name}` | Sub-field value for block `i` |

This storage layout is compatible with other plugins that follow the same `wp_postmeta` conventions.
