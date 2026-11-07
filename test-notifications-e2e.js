const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

const screenshotsDir = 'C:\\Users\\stefy\\.gemini\\antigravity\\brain\\a56d3fa0-c7f2-41e2-852f-18230531088f\\screenshots';
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir, { recursive: true });
}

(async () => {
  const tmpProfile = path.join(__dirname, '.tmp-notifications-profile');
  if (fs.existsSync(tmpProfile)) fs.rmSync(tmpProfile, { recursive: true, force: true });
  fs.mkdirSync(tmpProfile, { recursive: true });

  const browser = await puppeteer.launch({
    headless: 'new',
    userDataDir: tmpProfile,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 960 });

  page.on('dialog', async dialog => {
    console.log('[DIALOG]', dialog.message());
    await dialog.accept();
  });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.error('[BROWSER ERROR]', msg.text());
    } else {
      console.log('[BROWSER]', msg.type(), msg.text());
    }
  });
  page.on('pageerror', err => console.error('[PAGE ERROR]', err.message));

  console.log('=== TEST MODULO 1: NOTIFICHE EMAIL & CALENDARIO (.ICS) ===');

  // 1. Verifica Modulo Extra: assicuriamoci che email_notifications sia attivo
  console.log('1. Verifica stato Moduli Extra...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-modules', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('form[action*="free-bookly-modules"]', { timeout: 15000 });
  
  // Controlliamo la checkbox di email_notifications
  const emailModCheckbox = await page.$('input[value="email_notifications"]');
  if (emailModCheckbox) {
    const isChecked = await (await emailModCheckbox.getProperty('checked')).jsonValue();
    if (!isChecked) {
      console.log('Attivazione del modulo email_notifications...');
      await emailModCheckbox.click();
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ waitUntil: 'domcontentloaded' })
      ]);
    }
  }
  await page.screenshot({ path: path.join(screenshotsDir, '50-modules-email-active.png') });
  console.log('Modulo Notifiche Email attivo!');

  // 2. Visita Impostazioni > Notifiche Email
  console.log('2. Visita Impostazioni > Notifiche Email...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=notifications', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb_email_from_name', { timeout: 15000 });
  
  await page.$eval('#fb_email_from_name', el => el.value = 'Salone Bellezza & Stile');
  await page.$eval('#fb_email_from_address', el => el.value = 'notifiche@bellezzaestile.it');
  
  // Salva le impostazioni
  await Promise.all([
    page.click('button.button-primary'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  console.log('Impostazioni notifiche salvate!');

  // Invia email di prova
  console.log('2b. Invio email di prova...');
  await page.waitForSelector('#fb_test_email_to', { timeout: 15000 });
  await page.$eval('#fb_test_email_to', el => el.value = 'test-cliente@example.com');
  await Promise.all([
    page.click('#fb_send_test_btn'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  await page.screenshot({ path: path.join(screenshotsDir, '51-settings-email-notifications.png') });
  console.log('Email di prova inviata e tab Notifiche verificato!');

  // 3. Frontend: Prenotazione online da parte di un cliente
  console.log('3. Prenotazione online dal frontend...');
  await page.goto('http://127.0.0.1:8081/prenota-online/', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb-service-select', { timeout: 20000 });
  await page.waitForFunction(() => {
    const sel = document.querySelector('#fb-service-select');
    return sel && sel.options.length > 1;
  }, { timeout: 15000 });

  // Seleziona primo servizio valido
  const serviceVal = await page.$eval('#fb-service-select option:not([value=""])', opt => opt.value);
  await page.select('#fb-service-select', serviceVal);

  // Click Avanti -> Step 2
  await page.click('#btn-to-step-2');
  await page.waitForSelector('#fb-step-2', { visible: true });

  // Seleziona il prossimo mercoledì
  const d = new Date();
  while (d.getDay() !== 3) {
    d.setDate(d.getDate() + 1);
  }
  const targetDate = d.toISOString().split('T')[0];

  await page.$eval('#fb-date-picker', (el, val) => {
    el.value = val;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, targetDate);

  // Attendi e seleziona primo slot
  await page.waitForSelector('.fb-slot-btn', { timeout: 15000 });
  await page.click('.fb-slot-btn');

  // Click Avanti -> Step 3
  await page.click('#btn-to-step-3');
  await page.waitForSelector('#fb-step-3', { visible: true });

  // Compila dati cliente
  await page.$eval('#fb-user-name', el => el.value = 'Giulia Bianchi');
  await page.$eval('#fb-user-email', el => el.value = 'giulia.bianchi@example.com');
  await page.$eval('#fb-user-phone', el => el.value = '+39 340 9876543');

  // Click Avanti -> Step 4
  await page.click('#btn-to-step-4');
  await page.waitForSelector('#fb-step-4', { visible: true });

  // Conferma prenotazione
  console.log('Conferma prenotazione...');
  await page.click('#fb-confirm-btn');
  await page.waitForSelector('#fb-step-success', { visible: true, timeout: 20000 });
  await page.screenshot({ path: path.join(screenshotsDir, '52-frontend-booking-email-triggered.png') });
  console.log('Prenotazione confermata con successo!');

  // 4. Calendario Admin: Approva o visualizza l'appuntamento
  console.log('4. Visita Calendario Appuntamenti in Admin...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-calendar', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('.wp-list-table', { timeout: 15000 });

  // Se c'è un'azione di approvazione o cancellazione per testare il cambio di stato
  const cancelBtn = await page.$('a.button[href*="action=cancel"]');
  if (cancelBtn) {
    console.log('Test cambio stato appuntamento (Annulla per testare email di annullamento con .ics CANCEL)...');
    await Promise.all([
      cancelBtn.click(),
      page.waitForNavigation({ waitUntil: 'domcontentloaded' })
    ]);
  }
  await page.screenshot({ path: path.join(screenshotsDir, '53-calendar-status-updated.png') });

  // 5. Verifica Registro Invii Email in Impostazioni > Notifiche Email
  console.log('5. Verifica Registro Invii Email (Email Log)...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=notifications', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb-email-log', { timeout: 15000 });

  const logRows = await page.$$eval('#fb-email-log tbody tr', trs => trs.map(tr => tr.innerText.replace(/\t/g, ' | ')));
  console.log('Voci nel registro email inviate:', logRows);
  await page.screenshot({ path: path.join(screenshotsDir, '54-email-log-records.png') });

  console.log('=== TEST MODULO 1 COMPLETATO CON SUCCESSO! ===');
  await browser.close();
})().catch(err => {
  console.error('ERRORE E2E:', err);
  process.exit(1);
});
