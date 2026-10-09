# Georg-Kloster Calendar Workshop

Public source for the WordPress plugin. Version 1.3.73, GPL-2.0-or-later.

## Original sources

`assets/admin.js`, `assets/calendar.js`, `assets/editor.js`, `assets/admin.css`
and `assets/calendar.css` are the original, unminified source files. The same
readable files are shipped in the installation ZIP and served directly by
WordPress. There is no bundler, transpiler, minifier or generated JS/CSS.
Gutenberg dependencies are provided by WordPress; no developer-library copies
are bundled. Edit the asset files directly; no npm build or dependency install
is required. Optional formatting uses Prettier 3.6.2.

```sh
node --check assets/admin.js
node --check assets/calendar.js
node --check assets/editor.js
php -l orthocal.php
node scripts/build-language-files.mjs
```

## Packaging

The `.github/workflows/deploy-wordpress.yml` workflow copies runtime files
using `.distignore` and puts them inside `georg-kloster-calendar-workshop/`.
It does not compile or modify JavaScript/CSS. A version tag must match the PHP
header. No tag or WordPress.org deployment is created merely by pushing main.

To create the installation ZIP manually on Linux with zip installed, run from
this repository root:

```sh
package=$(mktemp -d) && mkdir "$package/georg-kloster-calendar-workshop" && cp -R assets blocks includes languages orthocal.php readme.txt "$package/georg-kloster-calendar-workshop/" && output="$(pwd)/orthocal-1.3.72.zip" && (cd "$package" && zip -qr "$output" georg-kloster-calendar-workshop)
```

## Compatibility and review

Existing orthocal blocks, shortcodes, options and REST routes are preserved.
All response/cooldown/rate transients use the established orthocal prefix.
Malformed fasting-period dates are skipped without PHP warnings; valid periods
continue to render. Human-readable asset sources and external service policies
are documented in readme.txt. WordPress.org review is still pending.
