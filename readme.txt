=== Orthodox Calendar – Calendar Workshop ===
Contributors: atapin
Tags: calendar, orthodox, bible
Requires at least: 6.3
Requires PHP: 8.0
Stable tag: 1.3.62
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Orthodox calendar for WordPress: feasts, fasting, commemorations, readings, liturgical texts and Gutenberg blocks.

== Description ==

Adds 22 dynamic Gutenberg blocks and shortcodes. Calendar and Bible data come from the external services listed below; no calendar database is included in the plugin.

== Installation ==
1. WordPress → Plugins → Add New → Upload Plugin: orthocal-1.3.62.zip.
2. Активируйте плагин.
3. Календарь уже работает. При необходимости откройте «Православный календарь», добавьте дополнительный API-ключ и проверьте каталог BibleDesktop.
4. Добавьте блоки категории «Виджеты» или шорткоды на страницу.

Ключ также можно задать константой ORTHOCAL_API_KEY в wp-config.php. Константа имеет приоритет над настройками. Ключ не выводится в форму, браузер и REST-ответы.

== Шорткоды ==
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

open="inline|modal|page" — открытие дня; reading_open="inline|modal" — чтения. Для отдельной страницы разместите на ней [orthocal_day] и выберите её в настройках. sections="fasting,saints,readings,texts,icons", images="0|1", heading="0|1" управляют содержимым. Конструктор в админке показывает параметры и результат.
image_size="120" задаёт высоту картинки поста в пикселях; ширина сохраняет пропорции исходного изображения. Ранее созданные шорткоды small, medium и large продолжают работать с высотой 28, 44 и 72 px.
Справочник содержит начальный корпус из 98 церковнославянских текстов с источниками; ожидает богослужебной редакционной проверки. Автоматического назначения службе дня нет. Акафисты запланированы в BibleDesktop и пока не подключены.

Общие параметры: lang="ru|cu|de|uk|pl", profile="typikon-strict|parish", theme="book|modern|inherit", compact="0|1", oldstyle="0|1". Часослов использует защищённый маршрут службы и показывает вставки шестого часа. Пустая дата — текущая дата в часовом поясе WordPress. Годы 1900–2200.
Фильтры ближайших событий: main (главные и поминальные), twelve (Пасха и двунадесятые), great (великие), memorial (поминальные), all. Поиск включает исходную дату и следующие 366 дней, максимум 10 событий.

== External Services ==
The plugin works without registration or an API key for normal public use. When it requests calendar data, the WordPress server sends date/period, language, profile and the public `X-Calendar-Client: orthocal-wordpress` identifier to https://kalender.georg-kloster.ru/api/v1/calendar/. This identifier is not a secret. The API enforces an endpoint and parameter allowlist, the 1900–2200 date range, server cache and per-server-IP limits. An optional API key may be configured for extended or authorized access.
При открытии чтений сервер получает каталог переводов, книг и главы из https://bible-desktop.com/api/. Ключ календаря в BibleDesktop не отправляется. Тексты показываются по выбранному переводу. Плагин не удостоверяет соответствие нумерации стихов календарному источнику.
При запросах данных сервисы видят IP сервера WordPress, а не посетителя. Знаки Типикона, значки поста и церковнославянский шрифт сохраняются сервером WordPress и выдаются посетителю локально.
Полные тексты, иконы и жития в ZIP не входят. Политики и условия услуг уточняйте у владельцев https://kalender.georg-kloster.ru/ и https://bible-desktop.com/.
Без ключа официальный плагин получает день, месяц, год, Пасху и ближайшие праздники в публичном режиме. Персональный ключ остаётся необязательной возможностью для повышенных лимитов или авторизованного доступа.

== Cache and access ==
Требуется обновлённый календарный API: upcoming, view=summary и заголовок X-Calendar-Application-Cache-TTL.
Успешные календарные ответы хранятся до 300 секунд только по разрешению API; отмена доступа и правки базы проявляются не позднее истечения этого срока. Старые данные при ошибках не используются. Ответы без разрешающего заголовка не сохраняются между запросами.
Библейские ответы сохраняются на выбранные 0/1/6/24 часа (24 по умолчанию); no-store без отдельного разрешения API отключает сохранение. Браузер объединяет одновременно выполняющиеся запросы.
Локальные картинки и шрифт хранятся в uploads/orthocal-cache. Условная проверка ETag/Last-Modified через WP-Cron выполняется раз в 6/12/24/168 часов (24 по умолчанию). При изменении файла создаётся новый URL с хэшем, при сбое остаётся последняя рабочая копия. Предел 64 МБ; старые версии можно очистить в админке. Файлы медиабиблиотеки не затрагиваются.
Публичные REST-маршруты ограничены фиксированными источниками и перечнем параметров. Счётчики WordPress ограничивают обычный трафик; для жёсткой защиты квоты от распределённой нагрузки дополнительно настройте ограничение частоты на веб-сервере.
Если на сайте включён полностраничный кэш, исключите страницы с календарём или задайте TTL не более 300 секунд и очистку в полночь по времени сайта. Иначе HTML может пережить срок жизни API-данных.
Сохранение настроек инвалидирует кэш плагина. Деактивация сохраняет настройки.

== Features ==
22 динамических блоков Gutenberg и шорткодов, отдельное меню плагина, генератор с живым предпросмотром и копированием готового шорткода, встроенная помощь, серверный HTML, переключение дат без перезагрузки, всплывающие окна, поиск памятей дня, постоянные ссылки ?orthocal_date=YYYY-MM-DD, независимые блоки, три оформления, мобильная адаптация, печатные стили, клавиатурное управление.
Карточка дня и читатель поддерживают русские и немецкие подписи; язык календарных данных и язык Библии выбираются отдельно. Переводы календаря могут быть неполными. Встроенные богослужебные тексты церковнославянские, немецкие издания доступны по ссылкам. Изображения икон и жития не подставляются вместо отсутствующих данных.

== Changelog ==

= 1.3.62 =
* Corrected the Plugin URI for WordPress.org submission; it now differs from the author URL.

= 1.3.61 =
* The configured number of icons limits only cards in the block; every daily icon and every image remains available in its gallery.
* Prevented theme lightboxes from replacing the plugin's icon gallery.
* Added Russian and German plugin-header translations.
