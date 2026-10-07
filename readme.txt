=== Georg-Kloster Calendar Workshop ===
Contributors: atapin
Tags: calendar, orthodox, bible
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.3.70
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Orthodox calendar for WordPress: feasts, fasting, commemorations, readings, liturgical texts and Gutenberg blocks.

== Description ==

Adds 22 dynamic Gutenberg blocks and shortcodes. Calendar and Bible data come from the external services listed below; no calendar database is included in the plugin.

== Installation ==
1. WordPress â†’ Plugins â†’ Add New â†’ Upload Plugin: orthocal-1.3.70.zip.
2. Activate the plugin.
3. The calendar works immediately. Open Georg-Kloster Calendar Workshop â†’ Connection to change the public API address or add an optional API key.
4. Add blocks from the Widgets category or shortcodes to a page.

The key can also be set with the ORTHOCAL_API_KEY constant in wp-config.php. The constant has priority over settings. The key is not exposed in forms, browsers, or REST responses.
The public texts, translations, and icons API is configured in Georg-Kloster Calendar Workshop â†’ Connection. Its current default address is https://bible-desktop.com and no file access is required.

== Shortcodes ==
[orthocal_today]
[orthocal_upcoming limit="5" filter="main"]
[orthocal_month year="2027" month="5"]
[orthocal_year year="2027"]
[orthocal_day date="2027-05-02"]
[orthocal_readings date="2027-05-02"]
[orthocal_calendar open="modal"]
[orthocal_fasting]
[orthocal_saints]
[orthocal_feasts limit="5"]
[orthocal_memorial]
[orthocal_pascha year="2027"]
[orthocal_fasts year="2027"]
[orthocal_date]
[orthocal_texts]
[orthocal_troparia scope="resurrection" tone="1"]
[orthocal_kontakia scope="weekday" weekday="1"]
[orthocal_prayers]
[orthocal_magnifications]
[orthocal_horologion]

open="inline|modal|page" controls how the day opens; reading_open="inline|modal" controls readings. For a separate page, place [orthocal_day] on it and select that page in the settings. sections="fasting,saints,readings,texts,icons", images="0|1", and heading="0|1" control the content. The admin generator previews parameters and output.
image_size="120" sets a fasting-image height in pixels; its width retains the source image ratio. Existing small, medium, and large shortcodes continue to use heights of 28, 44, and 72 px.
The reference library contains an initial corpus of 98 Church Slavonic texts with sources and awaits liturgical editorial review. It does not automatically assign a text to a daily service. Akathists are planned in BibleDesktop and are not yet connected.

General parameters: lang="ru|cu|de|uk|pl", profile="typikon-strict|parish", theme="book|modern|inherit", compact="0|1", and oldstyle="0|1". The Horologion uses a protected service route and includes Sixth Hour inserts. An empty date means the current date in the WordPress timezone. Supported years are 1900â€“2200.
Upcoming-event filters: main (major and memorial), twelve (Pascha and the Twelve Great Feasts), great, memorial, and all. The search covers the supplied date and the next 366 days, with a maximum of 10 events.

== External Services ==
Calendar API: https://kalender.georg-kloster.ru/api/v1/calendar/
Used to calculate dates, feasts, fasting, commemorations and reading references. Loading a block, its admin preview or changing the date sends the date/period, language, calendar profile and display/filter parameters from the WordPress server. Public requests include X-Calendar-Client: orthocal-wordpress. An optional configured API key is sent only to the calendar service for extended access. Normal public use needs no registration or key.
Terms of Service: https://kalender.georg-kloster.ru/agb
Privacy Policy: https://kalender.georg-kloster.ru/datenschutz

Bible Desktop API: https://bible-desktop.com/api/
Used for Bible translations, books, chapters, liturgical libraries, hagiographies and icon catalogues/images. Opening readings or a library sends the selected translation, book/chapter, language, work identifier or pagination/filter parameters. Loading icons sends the calendar month/day or material/image identifier. On cache misses, WordPress downloads images and fonts; scheduled cache revalidation sends conditional HTTP headers. The calendar service may also request this source while composing a day. The calendar API key is never sent to Bible Desktop.
Terms of Service: https://bible-desktop.com/pages/api-terms
Privacy Policy: https://bible-desktop.com/pages/api-privacy

Both services receive the requesting server IP address, request time, requested resource and technical HTTP headers. The plugin does not send visitor IP addresses, names, email addresses, WordPress passwords or post contents. Remote media are cached and served locally. Requests may be skipped while a valid cache entry is available. Full texts, icons and hagiographies are not bundled in the ZIP; their respective rights remain applicable.
The public texts API origin can be changed in the Connection settings. These links describe the default providers; administrators choosing a different provider must review and disclose that provider's terms and privacy policy to their users.

== Cache and access ==
The updated calendar API is required: upcoming, view=summary, and the X-Calendar-Application-Cache-TTL header.
Successful calendar responses are retained for up to 300 seconds only when allowed by the API; access revocation and database changes appear no later than this expiry. Stale data is not used after errors. Responses without the allowing header are not retained between requests.
Bible responses are retained for the selected 0/1/6/24 hours (24 by default); no-store disables retention unless separately permitted by the API. The browser deduplicates simultaneous requests.
Local images and fonts are stored in uploads/orthocal-cache. Remote photos are resized to a maximum edge of 1200 pixels and compressed to no more than 100 KB before they are saved. Conditional ETag/Last-Modified checks through WP-Cron run every 6/12/24/168 hours (24 by default). A changed file receives a new hashed URL, while the last working copy remains available after a failure. The limit is 200 MB; old versions can be cleared in the admin area. Media Library files are not affected.
Public REST routes are limited to fixed sources and an allowlist of parameters. WordPress counters limit ordinary traffic; configure web-server rate limiting as well for strict protection against distributed quota exhaustion.
If the site has full-page caching, exclude calendar pages or use a TTL of no more than 300 seconds and clear it at midnight in the site timezone. Otherwise, HTML can outlive the API data.
Saving settings invalidates the plugin cache. Deactivation preserves settings.

== Features ==
22 dynamic Gutenberg blocks and shortcodes, a dedicated plugin menu, a live-preview generator with shortcode copying, built-in help, server-rendered HTML, date switching without reloads, modal dialogs, daily-commemoration search, ?orthocal_date=YYYY-MM-DD permalinks, independent blocks, three themes, responsive layouts, print styles, and keyboard navigation.
Day cards and the reader support Russian and German labels; the calendar-data language and Bible language are chosen separately. Calendar translations can be incomplete. Built-in liturgical texts are Church Slavonic, with German editions available through links. Icon images and hagiographies are never substituted for missing data.

== Changelog ==

= 1.3.70 =
* Rename the plugin to Georg-Kloster Calendar Workshop and use the matching text domain.
* Document external API requests and link to provider terms and privacy policies.
* Preserve existing blocks, shortcodes and settings.

= 1.3.69 =
* Compress remote icon photos to no more than 100 KB before saving them in WordPress.
* Clear full-size files from older plugin versions once after updating.

= 1.3.68 =
* Restored https://bible-desktop.com as the current default public API address.

= 1.3.67 =
* Fixed icon loading when the configured public API returns image files from a separate media domain.

= 1.3.66 =
* Added an editable public API address in the plugin settings.

= 1.3.65 =
* Centralized the calendar API origin.

= 1.3.64 =
* Increased the persistent local media cache limit from 64 MB to 200 MB.
* Renamed the plugin to Orthodox Calendar – Workshop and aligned the text domain with its WordPress.org slug.
* Updated the tested WordPress version to 7.1.

= 1.3.63 =
* Align the WordPress localization text domain with the approved plugin slug and add the tested WordPress version.

= 1.3.62 =
* Corrected the Plugin URI for WordPress.org submission; it now differs from the author URL.

= 1.3.61 =
* The configured number of icons limits only cards in the block; every daily icon and every image remains available in its gallery.
* Prevented theme lightboxes from replacing the plugin's icon gallery.
* Added Russian and German plugin-header translations.
