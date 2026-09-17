import {mkdir, readdir, readFile, writeFile} from 'node:fs/promises';
import {dirname, join} from 'node:path';

const languages=join(import.meta.dirname,'..','languages');
const textDomain='orthodox-calendar-workshop';
const value=line=>JSON.parse(line.slice(line.indexOf('"')));

function entries(source) {
  const result=[]; let current=null; let field=null;
  const finish=()=>{if(current&&current.id!==null)result.push(current);current=null;field=null;};
  for(const line of source.replaceAll('\r\n','\n').split('\n')) {
    if(!line.trim()) { finish(); continue; }
    if(line.startsWith('#')) continue;
    if(line.startsWith('msgid ')) { finish(); current={id:value(line),translation:''}; field='id'; continue; }
    if(line.startsWith('msgstr ')) { if(current){current.translation=value(line);field='translation';} continue; }
    if(line.startsWith('"')&&current&&field) current[field]+=value(line);
  }
  finish(); return result;
}

function moFile(source) {
  const records=entries(source).sort((left,right)=>left.id.localeCompare(right.id));
  const originals=records.map(record=>Buffer.from(record.id));
  const translations=records.map(record=>Buffer.from(record.translation));
  const count=records.length, originalsOffset=28, translationsOffset=originalsOffset+count*8;
  let offset=translationsOffset+count*8;
  const originalRows=[];
  for(const text of originals){originalRows.push([text.length,offset]);offset+=text.length+1;}
  const translationRows=[];
  for(const text of translations){translationRows.push([text.length,offset]);offset+=text.length+1;}
  const output=Buffer.alloc(offset); output.writeUInt32LE(0x950412de,0); output.writeUInt32LE(0,4); output.writeUInt32LE(count,8);
  output.writeUInt32LE(originalsOffset,12); output.writeUInt32LE(translationsOffset,16); output.writeUInt32LE(0,20); output.writeUInt32LE(translationsOffset+count*8,24);
  originalRows.forEach(([length,start],index)=>{output.writeUInt32LE(length,originalsOffset+index*8);output.writeUInt32LE(start,originalsOffset+index*8+4);originals[index].copy(output,start);});
  translationRows.forEach(([length,start],index)=>{output.writeUInt32LE(length,translationsOffset+index*8);output.writeUInt32LE(start,translationsOffset+index*8+4);translations[index].copy(output,start);});
  return output;
}

await mkdir(languages,{recursive:true});
for(const file of await readdir(languages)) {
  if(!file.startsWith(textDomain+'-') || !/^[a-z]{2}_[A-Z]{2}\.po$/.test(file.slice(textDomain.length+1))) continue;
  await writeFile(join(languages,file.replace(/\.po$/,'.mo')),moFile(await readFile(join(languages,file),'utf8')));
}
