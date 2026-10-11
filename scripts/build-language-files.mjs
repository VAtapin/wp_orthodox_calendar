import {readFileSync as read,writeFileSync as write} from 'node:fs';
import {readPo,compileMo} from './wordpress-po.mjs';
const root='languages',domain='georg-kloster-calendar-workshop';
for(const locale of ['ru_RU','de_DE','uk','pl_PL']) {
  const entries=readPo(`${root}/${domain}-${locale}.po`);
  const missing=entries.filter(e=>e.msgid&&!e.msgstr);if(missing.length)throw new Error('Missing translations '+locale);
  const keys=entries.map(e=>(e.msgctxt??'')+'\u0004'+e.msgid);if(new Set(keys).size!==keys.length)throw new Error('Duplicate PO entry '+locale);
  write(`${root}/${domain}-${locale}.mo`,compileMo(entries));
  const header=entries.find(e=>e.msgid==='').msgstr;
  for(const [handle,file] of [['orthocal','calendar.js'],['orthocal-admin','admin.js'],['orthocal-editor','editor.js']]) {
    const catalogue={'':{domain,lang:locale,plural_forms:header.match(/Plural-Forms: ([^\n]+)/)?.[1]}};
    for(const entry of entries.filter(e=>e.msgid&&e.comments.some(c=>c.startsWith('#:')&&c.includes('assets/'+file))))catalogue[(entry.msgctxt?entry.msgctxt+'\u0004':'')+entry.msgid]=[entry.msgstr];
    write(`${root}/${domain}-${locale}-${handle}.json`,JSON.stringify({domain,locale_data:{messages:catalogue}})+'\n');
  }
  console.log(`PASS ${locale}: ${entries.length-1} translated entries, MO and three WordPress script catalogues`);
}
