(() => {
  const root=document.querySelector('[data-oc-admin]');if(!root)return;
  const endpoint=action=>{const url=new URL(root.dataset.endpoint);if(url.searchParams.has('rest_route'))url.searchParams.set('rest_route',url.searchParams.get('rest_route').replace(/\/$/,'')+'/'+action);else url.pathname+=action;return url;};
  const panel=name=>{
    root.querySelectorAll('[data-oc-panel]').forEach(item=>item.hidden=item.dataset.ocPanel!==name);
    root.querySelectorAll('[data-oc-tab]').forEach(link=>link.classList.toggle('nav-tab-active',link.dataset.ocTab===name));
  };
  panel(root.dataset.activeTab);
  root.querySelectorAll('[data-oc-tab]').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();panel(link.dataset.ocTab);history.replaceState(null,'',link.href);}));
  async function copy(text,status){try{await navigator.clipboard.writeText(text);status.textContent='Шорткод скопирован.';}catch{status.textContent='Выделите строку и скопируйте вручную.';}}
  const fields=[...root.querySelectorAll('[data-oc-build]')], code=root.querySelector('#oc-generated-code'), status=root.querySelector('[data-oc-generator-status]'), preview=root.querySelector('[data-oc-preview-slot]');
  const mode=()=>root.querySelector('[data-oc-build="mode"]')?.value||'today';
  const values=()=>{
    const value={};
    for(const input of fields){if(input.closest('[data-oc-build-wrap]')?.hidden)continue;if(input.type==='checkbox')value[input.dataset.ocBuild]=input.checked?'1':'0';else if(input.value!=='')value[input.dataset.ocBuild]=input.value;}
    const sections=[...root.querySelectorAll('[data-oc-build-section]')].filter(input=>input.checked).map(input=>input.dataset.ocBuildSection);
    value.sections=sections.join(',');
    const levels=[...root.querySelectorAll('[data-oc-event-level]')].filter(input=>input.checked).map(input=>input.dataset.ocEventLevel);
    value.event_levels=levels.join(',');return value;
  };
  function visibility(){
    const selected=mode(), texts=['texts','troparia','kontakia','prayers','magnifications'].includes(selected), day=['today','day','calendar','month','year','readings','fasting','saints','date'].includes(selected);
    const enabled={date:!texts,year:['month','year','calendar','fasts','pascha'].includes(selected),month:['month','calendar'].includes(selected),limit:['upcoming','feasts','memorial'].includes(selected),filter:['upcoming','feasts'].includes(selected),image_pack:['today','day','fasting','calendar'].includes(selected),image_size:day,event_levels:['today','day','calendar','fasting','saints','date'].includes(selected),show_nav:['today','day','calendar'].includes(selected),show_picker:['today','day','calendar'].includes(selected),show_copy:['today','day','calendar'].includes(selected),show_search:['today','day','calendar','saints'].includes(selected),show_section_titles:day,show_font_size:['today','day','readings','calendar','month','year'].includes(selected),office:selected==='horologion',sections:day,images:day,icons:day,icon_limit:day,reading_open:['today','day','readings','calendar','month','year'].includes(selected),open:['upcoming','feasts','memorial','pascha','month','year','calendar'].includes(selected),day_page:['upcoming','feasts','memorial','pascha','month','year','calendar'].includes(selected),translation:['today','day','readings','calendar','month','year'].includes(selected),scope:['texts','troparia','kontakia'].includes(selected),tone:['texts','troparia','kontakia'].includes(selected),weekday:['texts','troparia','kontakia'].includes(selected),text_id:['texts','troparia','kontakia','magnifications'].includes(selected),profile:!texts,oldstyle:!texts};
    root.querySelectorAll('[data-oc-build-wrap]').forEach(wrap=>wrap.hidden=enabled[wrap.dataset.ocBuildWrap]===false);
  }
  function generate(){
    if(!code)return;visibility();const attrs=values();delete attrs.mode;const booleanAttrs=new Set(['compact','oldstyle','show_nav','show_picker','show_copy','show_search','show_section_titles','show_font_size','images','icons','heading']);code.value='[orthocal_'+mode()+Object.entries(attrs).filter(([name,value])=>value!==''&&(value!=='0'||booleanAttrs.has(name))).map(([name,value])=>' '+name+'="'+String(value).replace(/["<>\[\]]/g,'')+'"').join('')+']';
  }
  let previewTimer, previewVersion=0;
  async function updatePreview(){
    if(!preview)return;const version=++previewVersion,attrs=values();attrs.mode=mode();status.textContent='Обновляю предпросмотр…';
    try{const url=endpoint('render');for(const [name,value] of Object.entries(attrs))url.searchParams.set(name,value);const response=await fetch(url,{headers:{Accept:'application/json'}});const data=await response.json();if(version!==previewVersion)return;if(!response.ok)throw new Error(data.message||'Не удалось получить блок.');const doc=new DOMParser().parseFromString(data.html,'text/html'),block=doc.querySelector('.orthocal');if(!block)throw new Error('Блок не получен.');preview.replaceChildren(block);window.OrthocalInit?.(preview);status.textContent='Предпросмотр обновлён.';}catch(error){if(version!==previewVersion)return;preview.replaceChildren();status.textContent=error.message;}
  }
  function changed(){previewVersion++;generate();clearTimeout(previewTimer);previewTimer=setTimeout(updatePreview,350);}
  fields.forEach(input=>input.addEventListener(input.type==='checkbox'||input.tagName==='SELECT'?'change':'input',changed));root.querySelectorAll('[data-oc-build-section]').forEach(input=>input.addEventListener('change',changed));
  root.querySelectorAll('[data-oc-event-level]').forEach(input=>input.addEventListener('change',changed));
  root.querySelector('[data-oc-copy-code]')?.addEventListener('click',()=>copy(code.value,status));root.querySelector('[data-oc-preview]')?.addEventListener('click',()=>{clearTimeout(previewTimer);updatePreview();});
  generate();if(root.dataset.activeTab==='shortcodes')void updatePreview();
  root.querySelectorAll('[data-oc-tab="shortcodes"]').forEach(link=>link.addEventListener('click',()=>{if(!preview?.children.length)void updatePreview();}));
  root.querySelector('[data-oc-load-translations]')?.addEventListener('click',async()=>{
    const info=root.querySelector('[data-oc-admin-status]'), button=root.querySelector('[data-oc-load-translations]');info.textContent='Загрузка каталога…';button.disabled=true;
    try{const response=await fetch(endpoint('bible')+'?path=translations',{headers:{Accept:'application/json'}}),data=await response.json();if(!response.ok)throw new Error(data.message||'BibleDesktop вернул ошибку.');if(!Array.isArray(data))throw new Error('BibleDesktop вернул неожиданный формат каталога.');const select=root.querySelector('[data-oc-admin-translations]');select.replaceChildren(new Option('Выберите перевод',''));for(const item of data)if(typeof item.code==='string'&&item.language?.code)select.add(new Option(item.language.code+' · '+item.name+' ('+item.code+')',item.code));select.hidden=false;info.textContent=data.length?'Каталог загружен: '+data.length+' переводов.':'В BibleDesktop пока нет активных переводов.';}catch(error){info.textContent=error.message;}finally{button.disabled=false;}
  });
  root.querySelector('[data-oc-admin-translations]')?.addEventListener('change',event=>root.querySelector('[name="orthocal_options[translation]"]').value=event.target.value);
})();
