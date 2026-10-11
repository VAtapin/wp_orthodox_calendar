# Project status

- Version 1.3.73; Georg-Kloster Calendar Workshop, text domain georg-kloster-calendar-workshop. Bible Desktop remains the sole calendar/text/media provider; retired Kalendar credentials are never forwarded.
- Readable JS/CSS, complete fasting-period validation and orthocal transient prefixes are retained. Public dates and client IP addresses now use unslashing, sanitization and format validation; malformed arrays are rejected. Read-only navigation, DONOTCACHEPAGE and fresh cache metadata enumeration have narrow documented Plugin Check exceptions. Translation files remain bundled by the owner's decision.
- Official Plugin Check: 0 errors, 0 warnings. PHP lint, JS syntax, full WordPress/SQLite + Edge (22 blocks), invalid date/IP regression, ZIP verification and Kalendar production build passed.
- WordPress.org approved 1.3.73 and the owner authorized publication on 2026-10-11. Annotated Git tag 1.3.73 targets 33bd5404d67320786936d077ad4e10ff082642a4. GitHub Actions run 38147442465 completed release and SVN deployment successfully.
- Public SVN trunk and tags/1.3.73 contain the correct version; the WordPress.org downloadable ZIP contains 1.3.73 and the directory page returns HTTP 200 with the correct plugin name. No owner server deployment was performed. Future releases use checked version tags; main pushes alone do not publish.
- Last related commit: Record first WordPress.org publication (current status commit); release implementation: 33bd540.
