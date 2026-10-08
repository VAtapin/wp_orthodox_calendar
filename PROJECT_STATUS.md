# Project status

- Version 1.3.71: all calendar data, Bible readings, liturgical texts and icons use the single configured Bible Desktop origin (default https://bible-desktop.com).
- The separate Kalendar API origin override is removed. Legacy Kalendar keys remain stored but are never sent to the replacement provider. Public Gutenberg previews no longer require a legacy key.
- Existing shortcodes, 22 blocks, display settings and the complete per-icon gallery remain available. All 16 decorative styles, shared small assets, Typikon SVG and licensed Monomakh ship inside the plugin; raster derivatives are at most 384 px. External icon cache/security/compression behavior remains intact.
- Bible Desktop API dependency: f3903f00 Expose local calendar API, followed by 42b03949 Add reviewed Sergius calendar link. Production API source has been updated by the owner; verified icon links are populated by the existing audit command.
- Checks: PHP syntax, JS syntax, actual WordPress/SQLite server rendering, persistent cache, media security/revalidation/compression, frontend navigation/readings, all 16 style previews and eight liturgical modes. The full WordPress/SQLite + Edge browser suite passed, including both public Gutenberg previews without a key.
- WordPress.org publishing, version tags and GitHub releases were not performed. Install the new ZIP after updating API and Kalendar; old installable archives have been removed from public Kalendar downloads and retained in its local backup.
- Last related commit: the current Use Bible Desktop as the sole calendar provider commit; previous source HEAD 37a8bf4.
