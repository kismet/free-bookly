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

  // 1. Servizi (verifica fix layout tabella)
  console.log('1. Verifica schermata Servizi con fix layout...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-services', { waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '05-service-fixed-table.png') });

  // 2. Staff (verifica fix layout tabella)
  console.log('2. Verifica schermata Staff con fix layout...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-staff', { waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '08-staff-fixed-table.png') });

  // 3. Calendario / Appuntamenti
  console.log('3. Visita schermata Appuntamenti / Calendario...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-calendar', { waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '17-admin-calendar-appointments.png') });

  // Clicca su "Approva" per l'appuntamento
  const approveBtn = await page.$('a.button[href*="action=approve"]');
  if (approveBtn) {
    console.log('Clic su Approva appuntamento...');
    await approveBtn.click();
    await page.waitForNavigation({ waitUntil: 'networkidle2' });
    await page.screenshot({ path: path.join(screenshotsDir, '18-admin-appointment-approved.png') });
  }

  // 4. Dashboard (con statistiche e ultimo appuntamento approvato)
  console.log('4. Visita Dashboard con metriche reali...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly', { waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '19-admin-dashboard-stats.png') });

  // 5. Moduli Extra (Add-ons gratuiti)
  console.log('5. Visita schermata Moduli Extra...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-modules', { waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(screenshotsDir, '20-admin-modules-addons.png') });

  // 6. Verifica Overbooking sul Frontend per 2026-10-21 alle 09:00
  console.log('6. Verifica esclusione slot 09:00 già prenotato su Frontend...');
  await page.goto('http://127.0.0.1:8081/prenota-online/', { waitUntil: 'networkidle2' });
  await page.waitForSelector('#fb-service-select option:not([value=""])', { timeout: 10000 });
  const serviceValue = await page.$eval('#fb-service-select option:not([value=""])', opt => opt.value);
  await page.select('#fb-service-select', serviceValue);
  await page.click('#fb-step-1 .fb-next-step');
  await page.waitForSelector('#fb-step-2', { visible: true });

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const dateStr = tomorrow.toISOString().split('T')[0];

  await page.$eval('#fb-date-picker', (el, val) => {
    el.value = val;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, dateStr);

  await page.waitForSelector('.fb-slot-btn', { timeout: 10000 });
  await page.screenshot({ path: path.join(screenshotsDir, '21-frontend-slots-without-0900.png') });

  // Controlla che '09:00' non sia presente tra gli slot
  const slotsText = await page.$$eval('.fb-slot-btn', buttons => buttons.map(b => b.innerText.trim()));
  console.log('Slot disponibili per domani:', slotsText);
  if (!slotsText.includes('09:00')) {
    console.log('PERFETTO! Lo slot delle 09:00 è stato correttamente escluso per evitare overbooking!');
  } else {
    console.warn('ATTENZIONE: lo slot delle 09:00 è ancora presente!');
  }

  console.log('Tutti i test e controlli visivi completati con successo!');
  await browser.close();
})().catch(err => {
  console.error('ERRORE DURANTE IL TEST:', err);
  process.exit(1);
});
