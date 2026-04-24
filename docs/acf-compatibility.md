# ACF Compatibility

This document describes how FieldForge maps to Advanced Custom Fields (ACF) field types and storage conventions.

---

## Storage format

FieldForge uses the same `wp_postmeta` storage layout as ACF:

| Meta key | Stores |
|---|---|
| `{field_name}` | The field value |
| `_{field_name}` | The field key (e.g. `field_abc123`) used for reverse lookup |

For Repeater fields:

| Meta key | Stores |
|---|---|
| `{repeater_name}` | Row count (integer) |
| `{repeater_name}_{i}_{sub_field_name}` | Value for row `i`, sub-field `sub_field_name` |
| `_{repeater_name}_{i}_{sub_field_name}` | Sub-field key reference |

This means **existing ACF postmeta is readable by FieldForge without any migration**, as long as the field group is re-created (or imported via JSON) in FieldForge.

---

## Field type mapping

### Fully supported (1:1)

| ACF type | FieldForge type | Notes |
|---|---|---|
| `text` | `text` | |
| `textarea` | `textarea` | |
| `number` | `number` | min/max/step supported |
| `range` | `number` | Rendered as number input |
| `email` | `email` | |
| `url` | `url` | |
| `password` | `password` | |
| `image` | `image` | Stores attachment ID; `return_format` respected |
| `file` | `file` | Stores attachment ID |
| `gallery` | `gallery` | Stores array of attachment IDs |
| `select` | `select` | Single and multiple; allow_null supported |
| `checkbox` | `checkbox` | |
| `radio` | `radio` | |
| `button_group` | `radio` | Rendered as radio buttons |
| `true_false` | `true_false` | |
| `link` | `link` | Stores `{url, title, target}` array |
| `post_object` | `post_object` | Single and multiple; post_type filter supported |
| `relationship` | `post_object` | Rendered as multi-select |
| `page_link` | `url` | Stored as URL string |
| `taxonomy` | `taxonomy` | checkbox / radio / select / multi_select |
| `user` | `user` | Single and multiple; role filter supported |
| `wysiwyg` | `wysiwyg` | Uses `wp_editor()`; toolbar and media_upload respected |
| `date_picker` | `date_picker` | Stores in `Ymd` format (ACF convention) |
| `date_time_picker` | `date_picker` | Time component ignored in v0.1 |
| `color_picker` | `color_picker` | Stores hex value |
| `message` | `message` | Display-only, no value stored |
| `accordion` | `message` | Rendered as message |
| `tab` | `message` | Rendered as message |
| `repeater` | `repeater` | Full ACF-compatible storage |
| `flexible_content` | `flexible_content` | Full support — layouts, sub-fields, drag-to-reorder |
| `group` | `repeater` | Converted to a single-row repeater |

### Partially supported

| ACF type | FieldForge type | Limitation |
|---|---|---|
| `google_map` | `text` | Stores raw value as text; no map UI |
| `time_picker` | `time_picker` | Full time picker support |
| `oembed` | `url` | Stores URL; no embed rendering |
| `clone` | `text` | Field key stored; no cloning logic |

---

## JSON import

FieldForge reads the standard ACF JSON export format (produced by **Custom Fields → Tools → Export Field Groups**).

### How to import

1. In your ACF site: go to **Custom Fields → Tools → Export Field Groups**, select the groups, click **Export**.
2. In FieldForge: go to **FieldForge → Import / Export**, paste the JSON, click **Import**.

### What is preserved

- Group title, menu order, position, active state
- All supported field types with their settings (choices, min/max, return_format, etc.)
- Location rules (param / operator / value)
- Repeater sub-fields (recursively)

### What is not preserved

- `clone` field references (converted to plain text)

---

## Template helper equivalents

| ACF function | FieldForge equivalent |
|---|---|
| `get_field( 'name' )` | `fieldforge_get( 'name' )` |
| `the_field( 'name' )` | `fieldforge_the( 'name' )` |
| `have_rows( 'name' )` | `fieldforge_have_rows( 'name' )` |
| `the_row()` | `fieldforge_the_row()` |
| `get_sub_field( 'name' )` | `fieldforge_sub_field( 'name' )` |
| `the_sub_field( 'name' )` | `fieldforge_the_sub_field( 'name' )` |
| `update_field( 'name', $value )` | `fieldforge_update_field( 'name', $value )` |
| `get_field( 'name', 'option' )` | `fieldforge_get_option( 'name' )` |
| `update_field( 'name', $value, 'option' )` | `fieldforge_update_option( 'name', $value )` |

---

## Known differences from ACF

1. **No nested repeaters** — repeaters inside repeaters are deferred to a future release.
2. **`date_time_picker` loses time** — stored as `Ymd` only; the time component is dropped on import.
3. **`clone` field** — converted to a plain text field; no cloning logic.
4. **Options pages** — `fieldforge_get_option()` / `fieldforge_update_option()` are available. ACF's `get_field('name','option')` syntax is not supported.
