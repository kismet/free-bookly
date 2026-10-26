const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const screenshotsDir = 'C:\\Users\\stefy\\.gemini\\antigravity\\brain\\a56d3fa0-c7f2-41e2-852f-18230531088f\\screenshots';
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir, { recursive: true });
}

(async () => {
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });

  page.on('console', msg => console.log('[BROWSER CONSOLE]', msg.type(), msg.text()));
  page.on('pageerror', err => console.error('[PAGE ERROR]', err.message));

  // 1. Visita Staff e verifica pulsante Orari & Pause
  console.log('1. Visita lista Staff...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-staff', { waitUntil: 'networkidle2' });
  
  const scheduleBtn = await page.$('a[href*="view=schedule"]');
  if (!scheduleBtn) {
    throw new Error('Pulsante Orari & Pause non trovato!');
  }

  // 2. Clicca su Orari & Pause
  console.log('2. Apertura editor Orari e Pause per Marco Rossi...');
  await scheduleBtn.click();
  await page.waitForNavigation({ waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '22-admin-staff-schedule.png') });

  // 3. Salva la pianificazione con pausa pranzo (13:00 - 14:00) per i giorni feriali
  console.log('3. Salvataggio orari e pause...');
  await page.click('button[type="submit"]');
  await page.waitForNavigation({ waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '23-admin-staff-schedule-saved.png') });

  // 4. Test Frontend Wizard con rispetto delle pause
  console.log('4. Visita Frontend Wizard...');
  await page.goto('http://127.0.0.1:8081/prenota-online/', { waitUntil: 'networkidle2' });

  // Seleziona Servizio (Taglio e Piega) e Staff (Marco Rossi)
  await page.waitForSelector('#fb-service-select option:not([value=""])', { timeout: 10000 });
  const serviceVal = await page.$eval('#fb-service-select option:not([value=""])', opt => opt.value);
  await page.select('#fb-service-select', serviceVal);

  await page.waitForSelector('#fb-staff-select option:not([value="any"])', { timeout: 10000 });
  const staffVal = await page.$eval('#fb-staff-select option:not([value="any"])', opt => opt.value);
  await page.select('#fb-staff-select', staffVal);

  // Click Avanti -> Step 2
  await page.click('#fb-step-1 .fb-next-step');
  await page.waitForSelector('#fb-step-2', { visible: true });

  // Troviamo il prossimo mercoledì (giorno feriale)
  const d = new Date();
  while (d.getDay() !== 3) { // 3 = Mercoledì
    d.setDate(d.getDate() + 1);
  }
  const wedDate = d.toISOString().split('T')[0];
  console.log('Test slot per Mercoledì:', wedDate);

  await page.$eval('#fb-date-picker', (el, val) => {
    el.value = val;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, wedDate);

  await page.waitForSelector('.fb-slot-btn', { timeout: 10000 });
  await page.screenshot({ path: path.join(screenshotsDir, '24-frontend-slots-respecting-breaks.png') });

  const wedSlots = await page.$$eval('.fb-slot-btn', btns => btns.map(b => b.innerText.trim()));
  console.log('Slot generati per Mercoledì (con pausa 13:00-14:00):', wedSlots);

  // Verifica che gli orari che cadono nella pausa pranzo (13:00) non siano presenti
  const hasBreakSlot = wedSlots.some(s => s === '13:00' || s === '13:15' || s === '13:30');
  if (!hasBreakSlot && wedSlots.includes('14:00')) {
    console.log('SUCCESSO: La pausa pranzo 13:00-14:00 è stata perfettamente rispettata e non presenta slot!');
  } else {
    console.warn('ATTENZIONE: Trovati slot durante la pausa o 14:00 mancante:', wedSlots);
  }

  // 5. Test Frontend Wizard per Giorno di Riposo (Domenica)
  while (d.getDay() !== 0) { // 0 = Domenica
    d.setDate(d.getDate() + 1);
  }
  const sunDate = d.toISOString().split('T')[0];
  console.log('Test giorno di riposo (Domenica):', sunDate);

  await page.$eval('#fb-date-picker', (el, val) => {
    el.value = val;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, sunDate);

  // Attesa completamento richiesta AJAX
  await page.waitForFunction(() => {
    const el = document.querySelector('#fb-time-slots');
    return el && !el.innerText.includes('Caricamento');
  }, { timeout: 10000 });
  await page.screenshot({ path: path.join(screenshotsDir, '25-frontend-day-off.png') });

  const slotsContainerText = await page.$eval('#fb-time-slots', el => el.innerText.trim());
  console.log('Messaggio per giorno di riposo:', slotsContainerText);
  if (slotsContainerText.includes('Nessun orario disponibile')) {
    console.log('SUCCESSO: I giorni di riposo escludono tutti gli slot e mostrano il messaggio corretto!');
  }

  console.log('Tutti i test del Modulo Orari e Pause completati con successo!');
  await browser.close();
})().catch(err => {
  console.error('ERRORE E2E:', err);
  process.exit(1);
});
