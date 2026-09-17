(() => {
  const el=wp.element.createElement,config=window.OrthocalEditor||{hasApiKey:true,settingsUrl:''};
  const titles={today:'Сегодня',upcoming:'Ближайшие праздники',month:'Календарь на месяц',year:'Календарь на год',day:'День календаря',readings:'Чтения дня',calendar:'Православный календарь',fasting:'Пост и трапеза',saints:'Памяти святых',feasts:'Праздники',memorial:'Поминальные дни',pascha:'Пасха',fasts:'Посты на год',date:'Дата по двум стилям',texts:'Богослужебные тексты',troparia:'Тропари',kontakia:'Кондаки',prayers:'Молитвы',magnifications:'Величания',horologion:'Часослов',akathists:'Акафисты',canons:'Каноны'};
  const descriptions={today:'Карточка текущего дня: пост, праздники, памяти и чтения.',upcoming:'Список ближайших главных, великих или поминальных дней.',month:'Сетка выбранного месяца с событиями, постом и старым стилем.',year:'Годовой календарь с православными датами.',day:'Полная карточка указанной даты.',readings:'Библейские чтения выбранного дня.',calendar:'Календарь с выбором даты и переходом к подробному дню.',fasting:'Правило поста и трапезы выбранного дня.',saints:'Памяти святых выбранного дня.',feasts:'Ближайшие православные праздники.',memorial:'Ближайшие дни особого поминовения усопших.',pascha:'Дата Пасхи и связанные сведения для выбранного года.',fasts:'Многодневные посты выбранного года.',date:'Гражданская дата и соответствующая дата старого стиля.',texts:'Справочник тропарей, кондаков, молитв и величаний.',troparia:'Справочник тропарей с фильтрами по гласу и дню седмицы.',kontakia:'Справочник кондаков с фильтрами по гласу и дню седмицы.',prayers:'Справочник молитв.',magnifications:'Справочник величаний.',horologion:'Полные разделы Часослова с выбором языка текста.',akathists:'Полные акафисты из библиотеки Bible Desktop.',canons:'Каноны с выбором языка текста.'};
  const missingKey=()=>el(wp.components.Notice,{status:'warning',isDismissible:false},'API-ключ календаря не задан. ',config.settingsUrl&&el('a',{href:config.settingsUrl},'Открыть подключение'));
  for(const [mode,title] of Object.entries(titles)) wp.blocks.registerBlockType('orthocal/'+mode,{
    title:'Православный календарь: '+title,description:descriptions[mode],icon:'calendar-alt',category:'widgets',keywords:['календарь','православный',title.toLowerCase()],
    edit({attributes,setAttributes}) {
      const control=(label,name,options)=>el(wp.components.SelectControl,{label,value:attributes[name]||'',options:[{label:'Настройка сайта',value:''},...options.map(([value,label])=>({value,label}))],onChange:value=>setAttributes({[name]:value})});
      const attrs=Object.fromEntries(Object.entries(attributes).filter(([,value])=>value!==''));
      const preview=config.hasApiKey?el(wp.serverSideRender,{block:'orthocal/'+mode,attributes:attrs}):missingKey();
      return el('div',wp.blockEditor.useBlockProps(),
        el(wp.blockEditor.InspectorControls,null,el(wp.components.PanelBody,{title:'Календарь'},
          el(wp.components.TextControl,{label:'Дата (ГГГГ-ММ-ДД), пусто — сегодня',value:attributes.date||'',onChange:date=>setAttributes({date})}),
          ...(['month','year','calendar','fasts','pascha'].includes(mode)?[el(wp.components.TextControl,{label:'Год',type:'number',value:attributes.year||'',onChange:year=>setAttributes({year})})]:[]),
          ...(['month','calendar'].includes(mode)?[el(wp.components.TextControl,{label:'Месяц (1–12)',type:'number',value:attributes.month||'',onChange:month=>setAttributes({month})})]:[]),
          ...(['upcoming','feasts','memorial'].includes(mode)?[el(wp.components.RangeControl,{label:'Количество',min:1,max:10,value:Number(attributes.limit||5),onChange:limit=>setAttributes({limit:String(limit)})}),control('События','filter',[['main','Главные'],['twelve','Пасха и двунадесятые'],['great','Великие'],['memorial','Поминальные'],['all','Все']])]:[]),
          control('Язык календаря','lang',[['ru','Русский'],['cu','Церковнославянский'],['de','Немецкий'],['uk','Украинский'],['pl','Польский']]),
          ...(['texts','troparia','kontakia','prayers','magnifications','horologion','akathists','canons'].includes(mode)?[control('Язык текста','text_language',[['ru','Русский'],['cu','Церковнославянский'],['cu-civil','Церковнославянский — гражданский шрифт'],['de','Deutsch'],['pl','Polski'],['uk','Українська']])]:[]),
          control('Профиль поста','profile',[['typikon-strict','Типикон'],['parish','Приходской']]),
          control('Оформление','theme',[['book','Книжное'],['modern','Современное'],['inherit','В стиле сайта']]),
          control('CSS карточки','css_mode',[['plugin','Оформление плагина'],['site','Полностью CSS сайта WordPress']]),
          control('Открытие дня','open',[['inline','Под блоком'],['modal','В окне'],['page','На странице']]),
          control('Открытие чтений','reading_open',[['inline','Под ссылкой'],['modal','В окне']]),
          control('Картинки','images',[['1','Показывать'],['0','Скрыть']]),
          el(wp.components.TextControl,{label:'Высота картинки поста, px',help:'Ширина определяется автоматически по пропорциям изображения.',type:'number',min:1,step:1,value:attributes.image_size||'',onChange:image_size=>setAttributes({image_size})}),
          control('Навигация по дням','show_nav',[['1','Показывать'],['0','Скрыть']]),
          control('Переход к дате','show_picker',[['1','Показывать'],['0','Скрыть']]),
          control('Ссылка на день','show_copy',[['1','Показывать'],['0','Скрыть']]),
          control('Поиск по памятям','show_search',[['1','Показывать'],['0','Скрыть']]),
          control('Заголовки секций','show_section_titles',[['1','Показывать'],['0','Скрыть']]),
          el(wp.components.TextControl,{label:'Уровни памятей (0,1,2,3,4)',value:attributes.event_levels||'0,1,2,3,4',onChange:event_levels=>setAttributes({event_levels})}),
          control('Заголовок','heading',[['1','Показывать'],['0','Скрыть']]),
          el(wp.components.TextControl,{label:'Разделы: fasting,saints,readings,texts,icons',value:attributes.sections||'',onChange:sections=>setAttributes({sections})}),
          ...(['texts','troparia','kontakia','prayers','magnifications'].includes(mode)?[
            control('Раздел справочника','scope',[['resurrection','Воскресные'],['weekday','Дни седмицы'],['common','Общие']]),
            el(wp.components.TextControl,{label:'Глас (1–8), необязательно',type:'number',value:attributes.tone||'',onChange:tone=>setAttributes({tone})}),
            el(wp.components.TextControl,{label:'День седмицы (0–6), необязательно',type:'number',value:attributes.weekday||'',onChange:weekday=>setAttributes({weekday})}),
            el(wp.components.TextControl,{label:'ID конкретного текста, необязательно',value:attributes.text_id||'',onChange:text_id=>setAttributes({text_id})})]:[]),
          control('Размер','compact',[['1','Компактный'],['0','Подробный']]),control('Старый стиль','oldstyle',[['1','Показывать'],['0','Скрыть']])
        )),preview);
    },save:()=>null
  });
})();
