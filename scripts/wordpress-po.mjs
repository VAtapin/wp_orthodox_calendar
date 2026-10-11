import {readFileSync} from 'node:fs';

// PO syntax emitted by WordPress/GlotPress, including multiline fields and contexts.
export function readPo(path) {
  const entries=[];let entry=null,field=null,comments=[];
  const finish=()=>{if(entry)entries.push({...entry,comments});entry=null;field=null;comments=[];};
  for(const line of readFileSync(path,'utf8').replace(/^\uFEFF/,'').replaceAll('\r\n','\n').split('\n')) {
    if(!line.trim()){finish();continue;}
    if(line.startsWith('#')){comments.push(line);continue;}
    const match=line.match(/^(msgctxt|msgid|msgstr) (".*")$/);
    if(match){entry??={msgid:'',msgstr:''};field=match[1];entry[field]=JSON.parse(match[2]);}
    else if(line.startsWith('"')&&entry&&field)entry[field]+=JSON.parse(line);
    else throw new Error('Unsupported PO line: '+line);
  }
  finish();return entries;
}

export function compileMo(entries) {
  const rows=entries.map(e=>({id:(e.msgctxt?e.msgctxt+'\u0004':'')+e.msgid,value:e.msgstr})).sort((a,b)=>Buffer.compare(Buffer.from(a.id),Buffer.from(b.id)));
  const count=rows.length,originalTable=28,translationTable=28+count*8;
  let offset=28+count*16;const chunks=[],out=Buffer.alloc(offset);
  [0x950412de,0,count,originalTable,translationTable,0,offset].forEach((v,i)=>out.writeUInt32LE(v,i*4));
  for(const [field,table] of [['id',originalTable],['value',translationTable]])for(let i=0;i<count;i++) {
    const bytes=Buffer.from(rows[i][field]);out.writeUInt32LE(bytes.length,table+i*8);out.writeUInt32LE(offset,table+i*8+4);chunks.push(bytes,Buffer.from([0]));offset+=bytes.length+1;
  }
  return Buffer.concat([out,...chunks]);
}
