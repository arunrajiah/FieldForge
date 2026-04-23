# Release Process

How to ship a new version of FieldForge to GitHub and wordpress.org.

---

## 1. Pre-release checklist

- [ ] All tests pass: `composer test`
- [ ] Zero phpcs errors: `composer lint`
- [ ] `CHANGELOG.md` — move items from `[Unreleased]` to the new version section with today's date.
- [ ] `readme.txt` — update `Stable tag` and `Changelog` section.
- [ ] `fieldforge.php` — bump the `Version:` header and `FIELDFORGE_VERSION` constant.
- [ ] Verify the plugin activates cleanly on a fresh WordPress install.

## 2. Tag and push

```bash
git add -A
git commit -m "chore: release vX.Y.Z"
git tag vX.Y.Z
git push origin main --tags
```

The `release.yml` GitHub Action fires on tag push. It:
1. Installs production Composer dependencies.
2. Builds `fieldforge-vX.Y.Z.zip` excluding dev files.
3. Creates a GitHub Release and attaches the zip.

Wait for the action to complete, then verify the zip at **Releases → vX.Y.Z** on GitHub.

## 3. Publish to wordpress.org

### First-time submission

1. Go to [https://wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/).
2. Fill in the form — use the content from `readme.txt`.
3. Upload the release zip from step 2.
4. Wait for the review email (typically 1–5 business days).

Once approved, WordPress.org assigns an SVN repository:
```
https://plugins.svn.wordpress.org/fieldforge/
```

### Subsequent releases via SVN

```bash
# Check out the SVN repo (one-time)
svn co https://plugins.svn.wordpress.org/fieldforge/ fieldforge-svn
cd fieldforge-svn

# Copy plugin files into trunk/
rsync -av --delete \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='phpcs.xml.dist' \
  --exclude='phpunit.xml.dist' \
  --exclude='.editorconfig' \
  --exclude='.gitignore' \
  "/path/to/fieldforge/" trunk/

# Tag the release in SVN
svn cp trunk tags/X.Y.Z

# Upload .wordpress-org assets if changed
svn add assets/* --force

# Commit
svn ci -m "Release X.Y.Z"
```

### Updating .wordpress-org assets

Place the following files in `.wordpress-org/` in the plugin source, then copy to `assets/` in the SVN repo before committing:

| File | Dimensions | Format |
|---|---|---|
| `banner-1544x500.png` | 1544 × 500 px | PNG or JPG |
| `banner-772x250.png` | 772 × 250 px | PNG or JPG (retina fallback) |
| `icon-256x256.png` | 256 × 256 px | PNG |
| `icon-128x128.png` | 128 × 128 px | PNG |
| `screenshot-1.png` | Any | PNG (matches Screenshot 1 in readme.txt) |
| `screenshot-2.png` | Any | PNG |
| `screenshot-3.png` | Any | PNG |
| `screenshot-4.png` | Any | PNG |

## 4. Post-release

- [ ] Announce in GitHub Discussions → Announcements.
- [ ] Update the GitHub repo's **About** description and tags.
- [ ] Open a `[Unreleased]` section in `CHANGELOG.md` for the next cycle.
- [ ] Close the milestone in GitHub Issues if one was used.
