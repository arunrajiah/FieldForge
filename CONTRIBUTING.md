# Contributing to FieldForge

Thank you for taking the time to contribute! FieldForge is a community-governed GPL project and every contribution — bug reports, feature requests, code, docs, or tests — helps keep it healthy.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Reporting bugs](#reporting-bugs)
- [Requesting features](#requesting-features)
- [Development setup](#development-setup)
- [Making changes](#making-changes)
- [Pull request checklist](#pull-request-checklist)
- [Coding standards](#coding-standards)
- [Adding a new field type](#adding-a-new-field-type)
- [Tests](#tests)

---

## Code of Conduct

Please be respectful and constructive in all interactions. We follow the [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct.

---

## Reporting bugs

Before opening a new issue:

1. Search [existing issues](https://github.com/arunrajiah/fieldforge/issues) to avoid duplicates.
2. Reproduce the bug on a clean WordPress installation if possible.

A good bug report includes:

- WordPress version, PHP version, FieldForge version
- Step-by-step reproduction instructions
- Expected vs actual behaviour
- Error messages or stack traces (check your PHP error log and browser console)
- Whether the bug also occurs with ACF deactivated

---

## Requesting features

Open a [Feature Request issue](https://github.com/arunrajiah/fieldforge/issues/new) with:

- A clear use case ("As a developer I need…")
- Why it fits the project scope (GPL ACF alternative)
- Whether you are willing to implement it yourself

---

## Development setup

```bash
# Clone
git clone https://github.com/arunrajiah/fieldforge.git
cd fieldforge

# Install PHP dependencies
composer install

# Install WordPress test suite (one-time, requires a local MySQL instance)
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

---

## Making changes

### Branch naming

| Type | Pattern | Example |
|---|---|---|
| Feature | `feat/<short-description>` | `feat/clone-field` |
| Bug fix | `fix/<short-description>` | `fix/repeater-row-order` |
| Documentation | `docs/<short-description>` | `docs/template-reference` |
| Chore | `chore/<short-description>` | `chore/bump-phpunit` |

### Commit style

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add clone field type
fix: repeater sub-field blank in nested FC rows
docs: add options page example to README
chore: upgrade PHPUnit to 10.x
test: add gallery field sanitize coverage
```

### Workflow

1. Fork the repository.
2. Create a branch from `main`.
3. Make your changes — keep each commit focused on a single concern.
4. Run the full check locally (see below) before pushing.
5. Open a pull request against `main`.

---

## Pull request checklist

- [ ] `composer test` passes — no failures, no errors
- [ ] `composer lint` passes — zero PHPCS errors (warnings are OK)
- [ ] New functionality has tests in `tests/`
- [ ] `CHANGELOG.md` has an entry under `[Unreleased]`
- [ ] PR description explains the problem and the solution
- [ ] Breaking changes (if any) are called out explicitly

---

## Coding standards

FieldForge follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) (PHPCS rule set in `phpcs.xml.dist`).

```bash
# Check
composer lint

# Auto-fix
composer lint:fix
```

**PHP compatibility:** all code must run on PHP 7.4 through 8.3.

**Key rules:**

- Prefix all global functions, classes, and constants with `fieldforge_` / `FieldForge_` / `FIELDFORGE_`.
- Sanitise all inputs with the appropriate WordPress function.
- Escape all outputs with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`.
- Never write raw `$_POST`, `$_GET`, or `$_SERVER` values to the database.

---

## Adding a new field type

1. **Create the class file** at `includes/fields/class-field-{slug}.php`:

```php
<?php
/**
 * My custom field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FieldForge_Field_My_Type extends FieldForge_Field_Base {

    public function render( int $post_id ): void {
        $value = $this->load( $post_id );
        $html  = sprintf(
            '<input type="text" name="%s" value="%s">',
            esc_attr( $this->field['name'] ),
            esc_attr( (string) $value )
        );
        $this->render_wrapper( $html );
    }

    public function sanitize( $value ) {
        return sanitize_text_field( wp_unslash( (string) $value ) );
    }
}
```

2. **Require the file** in `fieldforge.php` (in the `// --- Field types ---` block).

3. **Register the type** in `includes/class-field-registry.php` inside `register_core_fields()`:

```php
'my_type' => 'FieldForge_Field_My_Type',
```

4. **Add a label** in `admin/class-field-group-editor.php` inside `get_type_labels()`:

```php
'my_type' => __( 'My Type', 'fieldforge' ),
```

5. **Write tests** in `tests/test-field-my-type.php` covering at minimum `sanitize()` and `save()`/`load()`.

---

## Tests

```bash
# Run all tests
composer test

# Run a single test file
./vendor/bin/phpunit tests/test-field-repeater.php

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

Tests live in `tests/` and follow the `WP_UnitTestCase` base class. Each field type has a dedicated test file.

The CI matrix runs on PHP 7.4, 8.0, 8.1, 8.2, and 8.3 against the latest WordPress on every push and pull request.

---

## Questions?

Open a [GitHub Discussion](https://github.com/arunrajiah/fieldforge/discussions) or file an issue. We are happy to help new contributors get started.
