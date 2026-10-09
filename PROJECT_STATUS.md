# Project status

- Version 1.3.72, Georg-Kloster Calendar Workshop; WordPress.org review now uses slug georg-kloster-calendar-workshop.
- All JS/CSS are readable original sources, included directly in the package and linked from readme.txt. README documents syntax checks, translation generation and packaging; no JS/CSS compilation or minification is used.
- Fasting-period rendering validates complete real dates and skips malformed or reversed periods without warnings. Existing settings, shortcodes, blocks and REST routes remain compatible.
- Response, cooldown and rate-limit transients now use the established orthocal prefix; short shared oc_ storage keys are no longer created.
- PHP lint, JS syntax, full WordPress/SQLite + Edge with WP_DEBUG (including all 22 Gutenberg blocks), malformed-period/rate-counter regression and ZIP verification passed. Plugin Check: 0 errors and 17 existing warnings. Next: upload the 1.3.72 ZIP to the existing WordPress.org submission and reply in the original email thread. Do not create a duplicate submission.
- Last related commit: Address WordPress.org source and validation review (current commit containing this status).

- Дополнительная проверка: полный WordPress/SQLite + Edge сценарий прошёл на новой пустой базе с WP_DEBUG; прежние тестовые базы сохранены.

- Published provider migration 74d5e18 was integrated without force-push. Its runtime baseline matches Kalendar 1.3.71; the tested 1.3.72 runtime and installation ZIP are unchanged. Provider dependencies retained: f3903f00 Expose local calendar API, followed by 42b03949 Add reviewed Sergius calendar link. Legacy Kalendar keys are stored but never forwarded.
- Last integration commit: Integrate published provider migration into review release.
