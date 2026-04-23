# Contributing to FieldForge

Thank you for helping make FieldForge better!

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## Reporting issues

- Search existing issues before opening a new one.
- Use the appropriate issue template (bug report or feature request).
- Provide as much context as possible: WP version, PHP version, plugin version, steps to reproduce.

## Submitting a pull request

### Branch naming

| Type | Pattern |
|------|---------|
| Feature | `feat/short-description` |
| Bug fix | `fix/short-description` |
| Docs | `docs/short-description` |

### Commit style

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add gallery field type
fix: repeater row ordering after deletion
docs: update readme install steps
chore: bump composer dependencies
```

### Before you open a PR

1. Run tests and make sure they pass:
   ```sh
   composer test
   ```
2. Run the linter and fix any errors:
   ```sh
   composer lint
   # auto-fix:
   composer lint:fix
   ```
3. Update `CHANGELOG.md` under `[Unreleased]`.
4. Fill in the pull request template completely.

### PR checklist

- [ ] Tests added or updated
- [ ] `composer test` passes
- [ ] `composer lint` passes (zero errors)
- [ ] `CHANGELOG.md` updated

## Running tests locally

Install dependencies:
```sh
composer install
```

Set up the WordPress test suite (one-time):
```sh
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Then run:
```sh
composer test
```

## Coding standards

We follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).
All PHP must be compatible with PHP 7.4+.
