import assert from 'node:assert/strict';
import {createServer} from 'node:http';
import {readFile} from 'node:fs/promises';
import {chromium} from 'playwright';

const calendarScript=await readFile(new URL('../assets/calendar.js',import.meta.url));
const server=createServer((request,response)=>{
  const origin=`http://${request.headers.host}`;
  if(request.url==='/calendar.js'){response.writeHead(200,{'Content-Type':'text/javascript'});response.end(calendarScript);return;}
  if(request.url?.startsWith('/api/media')){const source=new URL(request.url,origin).searchParams.get('source');response.writeHead(200,{'Content-Type':'application/json'});response.end(JSON.stringify({url:source}));return;}
  const icons=JSON.stringify([
    {title:'First icon',description:'First description',dates:['17 September'],images:[`${origin}/icons/first-a.jpg`,`${origin}/icons/first-b.jpg`]},
    {title:'Second icon',description:'Second description',dates:['17 September'],images:[`${origin}/icons/second.jpg`]}
  ]);
  response.writeHead(200,{'Content-Type':'text/html'});response.end(`<!doctype html><body>
    <div class="orthocal" data-orthocal='{"endpoint":"${origin}/api/","mode":"today","ui":{}}'>
      <div hidden data-oc-day-icon-gallery='${icons}'></div>
      <a href="${origin}/icons/first-a.jpg" data-oc-icon data-oc-icon-index="0">Visible icon card</a>
      <p class="oc-status"></p>
    </div>
    <script>document.addEventListener('click',event=>{if(event.target.closest('[data-oc-icon]'))document.body.dataset.themeLightbox='opened';});</script>
    <script src="/calendar.js"></script>
  </body>`);
});
await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
const address=server.address();const origin=`http://127.0.0.1:${address.port}`;
const browser=await chromium.launch(process.env.PLAYWRIGHT_EXECUTABLE_PATH
  ? {headless:true,executablePath:process.env.PLAYWRIGHT_EXECUTABLE_PATH}
  : process.platform==='win32' ? {headless:true,channel:'msedge'} : {headless:true});
try {
  const page=await browser.newPage();await page.goto(origin);
  await page.getByText('Visible icon card').click();
  await page.locator('dialog[open]').waitFor();
  assert.equal(await page.locator('body').getAttribute('data-theme-lightbox'),null,'theme lightbox must not replace the plugin gallery');
  assert.equal(await page.locator('.oc-day-icon-choice').count(),2,'gallery must retain every icon even when one card is visible');
  await page.getByRole('button',{name:'→'}).click();
  assert.match(await page.locator('.oc-icon-counter').textContent(),/^2\s+из\s+3$/);
} finally { await browser.close();server.closeAllConnections?.();await new Promise(resolve=>server.close(resolve)); }
console.info('PASS icon gallery capture and complete gallery data');
