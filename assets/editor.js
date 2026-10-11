/* Original, unminified source; served directly, without a compilation step.
 * https://github.com/VAtapin/wp_orthodox_calendar/tree/main/assets
 */
(() => {
  const el = wp.element.createElement;
  const titles = {
    today: wp.i18n.__( "Сегодня", 'georg-kloster-calendar-workshop' ),
    upcoming: wp.i18n.__( "Ближайшие праздники", 'georg-kloster-calendar-workshop' ),
    month: wp.i18n.__( "Календарь на месяц", 'georg-kloster-calendar-workshop' ),
    year: wp.i18n.__( "Календарь на год", 'georg-kloster-calendar-workshop' ),
    day: wp.i18n.__( "День календаря", 'georg-kloster-calendar-workshop' ),
    readings: wp.i18n.__( "Чтения дня", 'georg-kloster-calendar-workshop' ),
    calendar: wp.i18n.__( "Православный календарь", 'georg-kloster-calendar-workshop' ),
    fasting: wp.i18n.__( "Пост и трапеза", 'georg-kloster-calendar-workshop' ),
    saints: wp.i18n.__( "Памяти святых", 'georg-kloster-calendar-workshop' ),
    feasts: wp.i18n.__( "Праздники", 'georg-kloster-calendar-workshop' ),
    memorial: wp.i18n.__( "Поминальные дни", 'georg-kloster-calendar-workshop' ),
    pascha: wp.i18n.__( "Пасха", 'georg-kloster-calendar-workshop' ),
    fasts: wp.i18n.__( "Посты на год", 'georg-kloster-calendar-workshop' ),
    date: wp.i18n.__( "Дата по двум стилям", 'georg-kloster-calendar-workshop' ),
    texts: wp.i18n.__( "Богослужебные тексты", 'georg-kloster-calendar-workshop' ),
    troparia: wp.i18n.__( "Тропари", 'georg-kloster-calendar-workshop' ),
    kontakia: wp.i18n.__( "Кондаки", 'georg-kloster-calendar-workshop' ),
    prayers: wp.i18n.__( "Молитвы", 'georg-kloster-calendar-workshop' ),
    magnifications: wp.i18n.__( "Величания", 'georg-kloster-calendar-workshop' ),
    horologion: wp.i18n.__( "Часослов", 'georg-kloster-calendar-workshop' ),
    akathists: wp.i18n.__( "Акафисты", 'georg-kloster-calendar-workshop' ),
    canons: wp.i18n.__( "Каноны", 'georg-kloster-calendar-workshop' ),
  };
  const descriptions = {
    today: wp.i18n.__( "Карточка текущего дня: пост, праздники, памяти и чтения.", 'georg-kloster-calendar-workshop' ),
    upcoming: wp.i18n.__( "Список ближайших главных, великих или поминальных дней.", 'georg-kloster-calendar-workshop' ),
    month: wp.i18n.__( "Сетка выбранного месяца с событиями, постом и старым стилем.", 'georg-kloster-calendar-workshop' ),
    year: wp.i18n.__( "Годовой календарь с православными датами.", 'georg-kloster-calendar-workshop' ),
    day: wp.i18n.__( "Полная карточка указанной даты.", 'georg-kloster-calendar-workshop' ),
    readings: wp.i18n.__( "Библейские чтения выбранного дня.", 'georg-kloster-calendar-workshop' ),
    calendar: wp.i18n.__( "Календарь с выбором даты и переходом к подробному дню.", 'georg-kloster-calendar-workshop' ),
    fasting: wp.i18n.__( "Правило поста и трапезы выбранного дня.", 'georg-kloster-calendar-workshop' ),
    saints: wp.i18n.__( "Памяти святых выбранного дня.", 'georg-kloster-calendar-workshop' ),
    feasts: wp.i18n.__( "Ближайшие православные праздники.", 'georg-kloster-calendar-workshop' ),
    memorial: wp.i18n.__( "Ближайшие дни особого поминовения усопших.", 'georg-kloster-calendar-workshop' ),
    pascha: wp.i18n.__( "Дата Пасхи и связанные сведения для выбранного года.", 'georg-kloster-calendar-workshop' ),
    fasts: wp.i18n.__( "Многодневные посты выбранного года.", 'georg-kloster-calendar-workshop' ),
    date: wp.i18n.__( "Гражданская дата и соответствующая дата старого стиля.", 'georg-kloster-calendar-workshop' ),
    texts: wp.i18n.__( "Справочник тропарей, кондаков, молитв и величаний.", 'georg-kloster-calendar-workshop' ),
    troparia: wp.i18n.__( "Справочник тропарей с фильтрами по гласу и дню седмицы.", 'georg-kloster-calendar-workshop' ),
    kontakia: wp.i18n.__( "Справочник кондаков с фильтрами по гласу и дню седмицы.", 'georg-kloster-calendar-workshop' ),
    prayers: wp.i18n.__( "Справочник молитв.", 'georg-kloster-calendar-workshop' ),
    magnifications: wp.i18n.__( "Справочник величаний.", 'georg-kloster-calendar-workshop' ),
    horologion: wp.i18n.__( "Полные разделы Часослова с выбором языка текста.", 'georg-kloster-calendar-workshop' ),
    akathists: wp.i18n.__( "Полные акафисты из библиотеки Bible Desktop.", 'georg-kloster-calendar-workshop' ),
    canons: wp.i18n.__( "Каноны с выбором языка текста.", 'georg-kloster-calendar-workshop' ),
  };
  for (const [mode, title] of Object.entries(titles))
    wp.blocks.registerBlockType("orthocal/" + mode, {
      title: wp.i18n.__( "Православный календарь: ", 'georg-kloster-calendar-workshop' ) + title,
      description: descriptions[mode],
      icon: "calendar-alt",
      category: "widgets",
      keywords: [wp.i18n.__( "календарь", 'georg-kloster-calendar-workshop' ), wp.i18n.__( "православный", 'georg-kloster-calendar-workshop' ), title.toLowerCase()],
      edit({ attributes, setAttributes }) {
        const control = (label, name, options) =>
          el(wp.components.SelectControl, {
            label,
            value: attributes[name] || "",
            options: [
              { label: wp.i18n.__( "Настройка сайта", 'georg-kloster-calendar-workshop' ), value: "" },
              ...options.map(([value, label]) => ({ value, label })),
            ],
            onChange: (value) => setAttributes({ [name]: value }),
          });
        const attrs = Object.fromEntries(
          Object.entries(attributes).filter(([, value]) => value !== ""),
        );
        const preview = el(wp.serverSideRender, {
          block: "orthocal/" + mode,
          attributes: attrs,
        });
        return el(
          "div",
          wp.blockEditor.useBlockProps(),
          el(
            wp.blockEditor.InspectorControls,
            null,
            el(
              wp.components.PanelBody,
              { title: wp.i18n.__( "Календарь", 'georg-kloster-calendar-workshop' ) },
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Дата (ГГГГ-ММ-ДД), пусто — сегодня", 'georg-kloster-calendar-workshop' ),
                value: attributes.date || "",
                onChange: (date) => setAttributes({ date }),
              }),
              ...(["month", "year", "calendar", "fasts", "pascha"].includes(
                mode,
              )
                ? [
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Год", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.year || "",
                      onChange: (year) => setAttributes({ year }),
                    }),
                  ]
                : []),
              ...(["month", "calendar"].includes(mode)
                ? [
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Месяц (1–12)", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.month || "",
                      onChange: (month) => setAttributes({ month }),
                    }),
                  ]
                : []),
              ...(["upcoming", "feasts", "memorial"].includes(mode)
                ? [
                    el(wp.components.RangeControl, {
                      label: wp.i18n.__( "Количество", 'georg-kloster-calendar-workshop' ),
                      min: 1,
                      max: 10,
                      value: Number(attributes.limit || 5),
                      onChange: (limit) =>
                        setAttributes({ limit: String(limit) }),
                    }),
                    control(wp.i18n.__( "События", 'georg-kloster-calendar-workshop' ), "filter", [
                      ["main", wp.i18n.__( "Главные", 'georg-kloster-calendar-workshop' )],
                      ["twelve", wp.i18n.__( "Пасха и двунадесятые", 'georg-kloster-calendar-workshop' )],
                      ["great", wp.i18n.__( "Великие", 'georg-kloster-calendar-workshop' )],
                      ["memorial", wp.i18n.__( "Поминальные", 'georg-kloster-calendar-workshop' )],
                      ["all", wp.i18n.__( "Все", 'georg-kloster-calendar-workshop' )],
                    ]),
                  ]
                : []),
              control(wp.i18n.__( "Язык календаря", 'georg-kloster-calendar-workshop' ), "lang", [
                ["ru", wp.i18n.__( "Русский", 'georg-kloster-calendar-workshop' )],
                ["cu", wp.i18n.__( "Церковнославянский", 'georg-kloster-calendar-workshop' )],
                ["de", wp.i18n.__( "Немецкий", 'georg-kloster-calendar-workshop' )],
                ["uk", wp.i18n.__( "Украинский", 'georg-kloster-calendar-workshop' )],
                ["pl", wp.i18n.__( "Польский", 'georg-kloster-calendar-workshop' )],
              ]),
              ...([
                "texts",
                "troparia",
                "kontakia",
                "prayers",
                "magnifications",
                "horologion",
                "akathists",
                "canons",
              ].includes(mode)
                ? [
                    control(wp.i18n.__( "Язык текста", 'georg-kloster-calendar-workshop' ), "text_language", [
                      ["ru", wp.i18n.__( "Русский", 'georg-kloster-calendar-workshop' )],
                      ["cu", wp.i18n.__( "Церковнославянский", 'georg-kloster-calendar-workshop' )],
                      ["cu-civil", wp.i18n.__( "Церковнославянский — гражданский шрифт", 'georg-kloster-calendar-workshop' )],
                      ["de", "Deutsch"],
                      ["pl", "Polski"],
                      ["uk", wp.i18n.__( "Українська", 'georg-kloster-calendar-workshop' )],
                    ]),
                  ]
                : []),
              control(wp.i18n.__( "Профиль поста", 'georg-kloster-calendar-workshop' ), "profile", [
                ["typikon-strict", wp.i18n.__( "Типикон", 'georg-kloster-calendar-workshop' )],
                ["parish", wp.i18n.__( "Приходской", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Оформление", 'georg-kloster-calendar-workshop' ), "theme", [
                ["book", wp.i18n.__( "Книжное", 'georg-kloster-calendar-workshop' )],
                ["modern", wp.i18n.__( "Современное", 'georg-kloster-calendar-workshop' )],
                ["inherit", wp.i18n.__( "В стиле сайта", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "CSS карточки", 'georg-kloster-calendar-workshop' ), "css_mode", [
                ["plugin", wp.i18n.__( "Оформление плагина", 'georg-kloster-calendar-workshop' )],
                ["site", wp.i18n.__( "Полностью CSS сайта WordPress", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Открытие дня", 'georg-kloster-calendar-workshop' ), "open", [
                ["inline", wp.i18n.__( "Под блоком", 'georg-kloster-calendar-workshop' )],
                ["modal", wp.i18n.__( "В окне", 'georg-kloster-calendar-workshop' )],
                ["page", wp.i18n.__( "На странице", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Открытие чтений", 'georg-kloster-calendar-workshop' ), "reading_open", [
                ["inline", wp.i18n.__( "Под ссылкой", 'georg-kloster-calendar-workshop' )],
                ["modal", wp.i18n.__( "В окне", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Картинки", 'georg-kloster-calendar-workshop' ), "images", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Высота картинки поста, px", 'georg-kloster-calendar-workshop' ),
                help: wp.i18n.__( "Ширина определяется автоматически по пропорциям изображения.", 'georg-kloster-calendar-workshop' ),
                type: "number",
                min: 1,
                step: 1,
                value: attributes.image_size || "",
                onChange: (image_size) => setAttributes({ image_size }),
              }),
              control(wp.i18n.__( "Навигация по дням", 'georg-kloster-calendar-workshop' ), "show_nav", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Переход к дате", 'georg-kloster-calendar-workshop' ), "show_picker", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Ссылка на день", 'georg-kloster-calendar-workshop' ), "show_copy", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Поиск по памятям", 'georg-kloster-calendar-workshop' ), "show_search", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Заголовки секций", 'georg-kloster-calendar-workshop' ), "show_section_titles", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Уровни памятей (0,1,2,3,4)", 'georg-kloster-calendar-workshop' ),
                value: attributes.event_levels || "0,1,2,3,4",
                onChange: (event_levels) => setAttributes({ event_levels }),
              }),
              control(wp.i18n.__( "Заголовок", 'georg-kloster-calendar-workshop' ), "heading", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Разделы: fasting,saints,readings,texts,icons", 'georg-kloster-calendar-workshop' ),
                value: attributes.sections || "",
                onChange: (sections) => setAttributes({ sections }),
              }),
              ...([
                "texts",
                "troparia",
                "kontakia",
                "prayers",
                "magnifications",
              ].includes(mode)
                ? [
                    control(wp.i18n.__( "Раздел справочника", 'georg-kloster-calendar-workshop' ), "scope", [
                      ["resurrection", wp.i18n.__( "Воскресные", 'georg-kloster-calendar-workshop' )],
                      ["weekday", wp.i18n.__( "Дни седмицы", 'georg-kloster-calendar-workshop' )],
                      ["common", wp.i18n.__( "Общие", 'georg-kloster-calendar-workshop' )],
                    ]),
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Глас (1–8), необязательно", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.tone || "",
                      onChange: (tone) => setAttributes({ tone }),
                    }),
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "День седмицы (0–6), необязательно", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.weekday || "",
                      onChange: (weekday) => setAttributes({ weekday }),
                    }),
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "ID конкретного текста, необязательно", 'georg-kloster-calendar-workshop' ),
                      value: attributes.text_id || "",
                      onChange: (text_id) => setAttributes({ text_id }),
                    }),
                  ]
                : []),
              control(wp.i18n.__( "Размер", 'georg-kloster-calendar-workshop' ), "compact", [
                ["1", wp.i18n.__( "Компактный", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Подробный", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Старый стиль", 'georg-kloster-calendar-workshop' ), "oldstyle", [
                ["1", wp.i18n.__( "Показывать", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Скрыть", 'georg-kloster-calendar-workshop' )],
              ]),
            ),
          ),
          preview,
        );
      },
      save: () => null,
    });
})();
