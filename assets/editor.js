/* Original, unminified source; served directly, without a compilation step.
 * https://github.com/VAtapin/wp_orthodox_calendar/tree/main/assets
 */
(() => {
  const el = wp.element.createElement;
  const titles = {
    today: wp.i18n.__( "Today", 'georg-kloster-calendar-workshop' ),
    upcoming: wp.i18n.__( "Upcoming feasts", 'georg-kloster-calendar-workshop' ),
    month: wp.i18n.__( "Monthly calendar", 'georg-kloster-calendar-workshop' ),
    year: wp.i18n.__( "Yearly calendar", 'georg-kloster-calendar-workshop' ),
    day: wp.i18n.__( "Calendar day", 'georg-kloster-calendar-workshop' ),
    readings: wp.i18n.__( "Daily readings", 'georg-kloster-calendar-workshop' ),
    calendar: wp.i18n.__( "Orthodox calendar", 'georg-kloster-calendar-workshop' ),
    fasting: wp.i18n.__( "Fasting and meals", 'georg-kloster-calendar-workshop' ),
    saints: wp.i18n.__( "Saints commemorations", 'georg-kloster-calendar-workshop' ),
    feasts: wp.i18n.__( "Feasts", 'georg-kloster-calendar-workshop' ),
    memorial: wp.i18n.__( "Memorial days", 'georg-kloster-calendar-workshop' ),
    pascha: wp.i18n.__( "Pascha", 'georg-kloster-calendar-workshop' ),
    fasts: wp.i18n.__( "Yearly fasts", 'georg-kloster-calendar-workshop' ),
    date: wp.i18n.__( "New and old style date", 'georg-kloster-calendar-workshop' ),
    texts: wp.i18n.__( "Liturgical texts", 'georg-kloster-calendar-workshop' ),
    troparia: wp.i18n.__( "Troparia", 'georg-kloster-calendar-workshop' ),
    kontakia: wp.i18n.__( "Kontakia", 'georg-kloster-calendar-workshop' ),
    prayers: wp.i18n.__( "Prayers", 'georg-kloster-calendar-workshop' ),
    magnifications: wp.i18n.__( "Magnifications", 'georg-kloster-calendar-workshop' ),
    horologion: wp.i18n.__( "Horologion", 'georg-kloster-calendar-workshop' ),
    akathists: wp.i18n.__( "Akathists", 'georg-kloster-calendar-workshop' ),
    canons: wp.i18n.__( "Canons", 'georg-kloster-calendar-workshop' ),
  };
  const descriptions = {
    today: wp.i18n.__( "Current day card: fasting, feasts, commemorations and readings.", 'georg-kloster-calendar-workshop' ),
    upcoming: wp.i18n.__( "List of upcoming main, great or memorial days.", 'georg-kloster-calendar-workshop' ),
    month: wp.i18n.__( "Monthly grid with events, fasting rules and old style dates.", 'georg-kloster-calendar-workshop' ),
    year: wp.i18n.__( "Yearly calendar with Orthodox dates.", 'georg-kloster-calendar-workshop' ),
    day: wp.i18n.__( "Full card for the specified date.", 'georg-kloster-calendar-workshop' ),
    readings: wp.i18n.__( "Bible readings for the selected day.", 'georg-kloster-calendar-workshop' ),
    calendar: wp.i18n.__( "Calendar with date selection and navigation to a detailed day card.", 'georg-kloster-calendar-workshop' ),
    fasting: wp.i18n.__( "Fasting and meal rule for the selected day.", 'georg-kloster-calendar-workshop' ),
    saints: wp.i18n.__( "Saints commemorations for the selected day.", 'georg-kloster-calendar-workshop' ),
    feasts: wp.i18n.__( "Upcoming Orthodox feasts.", 'georg-kloster-calendar-workshop' ),
    memorial: wp.i18n.__( "Upcoming days of special commemoration of the departed.", 'georg-kloster-calendar-workshop' ),
    pascha: wp.i18n.__( "Pascha date and related information for the selected year.", 'georg-kloster-calendar-workshop' ),
    fasts: wp.i18n.__( "Multi-day fasts for the selected year.", 'georg-kloster-calendar-workshop' ),
    date: wp.i18n.__( "Civil date and corresponding old style date.", 'georg-kloster-calendar-workshop' ),
    texts: wp.i18n.__( "Reference library of troparia, kontakia, prayers and magnifications.", 'georg-kloster-calendar-workshop' ),
    troparia: wp.i18n.__( "Troparia reference library with tone and weekday filters.", 'georg-kloster-calendar-workshop' ),
    kontakia: wp.i18n.__( "Kontakia reference library with tone and weekday filters.", 'georg-kloster-calendar-workshop' ),
    prayers: wp.i18n.__( "Prayers reference library.", 'georg-kloster-calendar-workshop' ),
    magnifications: wp.i18n.__( "Magnifications reference library.", 'georg-kloster-calendar-workshop' ),
    horologion: wp.i18n.__( "Full Horologion sections with text language selection.", 'georg-kloster-calendar-workshop' ),
    akathists: wp.i18n.__( "Full akathists from the Bible Desktop library.", 'georg-kloster-calendar-workshop' ),
    canons: wp.i18n.__( "Canons with text language selection.", 'georg-kloster-calendar-workshop' ),
  };
  for (const [mode, title] of Object.entries(titles))
    wp.blocks.registerBlockType("orthocal/" + mode, {
      title: wp.i18n.__( "Orthodox calendar: ", 'georg-kloster-calendar-workshop' ) + title,
      description: descriptions[mode],
      icon: "calendar-alt",
      category: "widgets",
      keywords: [wp.i18n.__( "calendar", 'georg-kloster-calendar-workshop' ), wp.i18n.__( "orthodox", 'georg-kloster-calendar-workshop' ), title.toLowerCase()],
      edit({ attributes, setAttributes }) {
        const control = (label, name, options) =>
          el(wp.components.SelectControl, {
            label,
            value: attributes[name] || "",
            options: [
              { label: wp.i18n.__( "Site setting", 'georg-kloster-calendar-workshop' ), value: "" },
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
              { title: wp.i18n.__( "Calendar", 'georg-kloster-calendar-workshop' ) },
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Date (YYYY-MM-DD), empty for today", 'georg-kloster-calendar-workshop' ),
                value: attributes.date || "",
                onChange: (date) => setAttributes({ date }),
              }),
              ...(["month", "year", "calendar", "fasts", "pascha"].includes(
                mode,
              )
                ? [
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Year", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.year || "",
                      onChange: (year) => setAttributes({ year }),
                    }),
                  ]
                : []),
              ...(["month", "calendar"].includes(mode)
                ? [
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Month (1–12)", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.month || "",
                      onChange: (month) => setAttributes({ month }),
                    }),
                  ]
                : []),
              ...(["upcoming", "feasts", "memorial"].includes(mode)
                ? [
                    el(wp.components.RangeControl, {
                      label: wp.i18n.__( "Count", 'georg-kloster-calendar-workshop' ),
                      min: 1,
                      max: 10,
                      value: Number(attributes.limit || 5),
                      onChange: (limit) =>
                        setAttributes({ limit: String(limit) }),
                    }),
                    control(wp.i18n.__( "Events", 'georg-kloster-calendar-workshop' ), "filter", [
                      ["main", wp.i18n.__( "Main", 'georg-kloster-calendar-workshop' )],
                      ["twelve", wp.i18n.__( "Pascha and the Twelve Great Feasts", 'georg-kloster-calendar-workshop' )],
                      ["great", wp.i18n.__( "Great", 'georg-kloster-calendar-workshop' )],
                      ["memorial", wp.i18n.__( "Memorial", 'georg-kloster-calendar-workshop' )],
                      ["all", wp.i18n.__( "All", 'georg-kloster-calendar-workshop' )],
                    ]),
                  ]
                : []),
              control(wp.i18n.__( "Calendar language", 'georg-kloster-calendar-workshop' ), "lang", [
                ["ru", wp.i18n.__( "Russian", 'georg-kloster-calendar-workshop' )],
                ["cu", wp.i18n.__( "Church Slavonic", 'georg-kloster-calendar-workshop' )],
                ["de", wp.i18n.__( "German", 'georg-kloster-calendar-workshop' )],
                ["uk", wp.i18n.__( "Ukrainian", 'georg-kloster-calendar-workshop' )],
                ["pl", wp.i18n.__( "Polish", 'georg-kloster-calendar-workshop' )],
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
                    control(wp.i18n.__( "Text language", 'georg-kloster-calendar-workshop' ), "text_language", [
                      ["ru", wp.i18n.__( "Russian", 'georg-kloster-calendar-workshop' )],
                      ["cu", wp.i18n.__( "Church Slavonic", 'georg-kloster-calendar-workshop' )],
                      ["cu-civil", wp.i18n.__( "Church Slavonic — civil font", 'georg-kloster-calendar-workshop' )],
                      ["de", wp.i18n.__( "German", 'georg-kloster-calendar-workshop' )],
                      ["pl", wp.i18n.__( "Polish", 'georg-kloster-calendar-workshop' )],
                      ["uk", wp.i18n._x( "Ukrainian", "native language name", 'georg-kloster-calendar-workshop' )],
                    ]),
                  ]
                : []),
              control(wp.i18n.__( "Fasting profile", 'georg-kloster-calendar-workshop' ), "profile", [
                ["typikon-strict", wp.i18n.__( "Typikon", 'georg-kloster-calendar-workshop' )],
                ["parish", wp.i18n.__( "Parish", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Appearance", 'georg-kloster-calendar-workshop' ), "theme", [
                ["book", wp.i18n.__( "Book", 'georg-kloster-calendar-workshop' )],
                ["modern", wp.i18n.__( "Modern", 'georg-kloster-calendar-workshop' )],
                ["inherit", wp.i18n.__( "Site style", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Card CSS", 'georg-kloster-calendar-workshop' ), "css_mode", [
                ["plugin", wp.i18n.__( "Plugin styling", 'georg-kloster-calendar-workshop' )],
                ["site", wp.i18n.__( "Use only the WordPress site CSS", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Opening a day", 'georg-kloster-calendar-workshop' ), "open", [
                ["inline", wp.i18n.__( "Below the block", 'georg-kloster-calendar-workshop' )],
                ["modal", wp.i18n.__( "In a popup window", 'georg-kloster-calendar-workshop' )],
                ["page", wp.i18n.__( "On a page", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Opening readings", 'georg-kloster-calendar-workshop' ), "reading_open", [
                ["inline", wp.i18n.__( "Below the link", 'georg-kloster-calendar-workshop' )],
                ["modal", wp.i18n.__( "In a popup window", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Images", 'georg-kloster-calendar-workshop' ), "images", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Fasting image height, px", 'georg-kloster-calendar-workshop' ),
                help: wp.i18n.__( "The width is calculated automatically from the image proportions.", 'georg-kloster-calendar-workshop' ),
                type: "number",
                min: 1,
                step: 1,
                value: attributes.image_size || "",
                onChange: (image_size) => setAttributes({ image_size }),
              }),
              control(wp.i18n.__( "Day navigation", 'georg-kloster-calendar-workshop' ), "show_nav", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Go to date", 'georg-kloster-calendar-workshop' ), "show_picker", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Day link", 'georg-kloster-calendar-workshop' ), "show_copy", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Commemoration search", 'georg-kloster-calendar-workshop' ), "show_search", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Section headings", 'georg-kloster-calendar-workshop' ), "show_section_titles", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Commemoration ranks (0,1,2,3,4)", 'georg-kloster-calendar-workshop' ),
                value: attributes.event_levels || "0,1,2,3,4",
                onChange: (event_levels) => setAttributes({ event_levels }),
              }),
              control(wp.i18n.__( "Heading", 'georg-kloster-calendar-workshop' ), "heading", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
              el(wp.components.TextControl, {
                label: wp.i18n.__( "Sections: fasting,saints,readings,texts,icons", 'georg-kloster-calendar-workshop' ),
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
                    control(wp.i18n.__( "Reference section", 'georg-kloster-calendar-workshop' ), "scope", [
                      ["resurrection", wp.i18n.__( "Resurrection", 'georg-kloster-calendar-workshop' )],
                      ["weekday", wp.i18n.__( "Days of the week", 'georg-kloster-calendar-workshop' )],
                      ["common", wp.i18n.__( "General", 'georg-kloster-calendar-workshop' )],
                    ]),
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Tone (1–8), optional", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.tone || "",
                      onChange: (tone) => setAttributes({ tone }),
                    }),
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Day of the week (0–6), optional", 'georg-kloster-calendar-workshop' ),
                      type: "number",
                      value: attributes.weekday || "",
                      onChange: (weekday) => setAttributes({ weekday }),
                    }),
                    el(wp.components.TextControl, {
                      label: wp.i18n.__( "Specific text ID, optional", 'georg-kloster-calendar-workshop' ),
                      value: attributes.text_id || "",
                      onChange: (text_id) => setAttributes({ text_id }),
                    }),
                  ]
                : []),
              control(wp.i18n.__( "Size", 'georg-kloster-calendar-workshop' ), "compact", [
                ["1", wp.i18n.__( "Compact", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Detailed", 'georg-kloster-calendar-workshop' )],
              ]),
              control(wp.i18n.__( "Old style", 'georg-kloster-calendar-workshop' ), "oldstyle", [
                ["1", wp.i18n.__( "Show", 'georg-kloster-calendar-workshop' )],
                ["0", wp.i18n.__( "Hide", 'georg-kloster-calendar-workshop' )],
              ]),
            ),
          ),
          preview,
        );
      },
      save: () => null,
    });
})();
