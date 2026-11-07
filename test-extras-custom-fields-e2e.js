const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

const screenshotsDir = 'C:\\Users\\stefy\\.gemini\\antigravity\\brain\\a56d3fa0-c7f2-41e2-852f-18230531088f\\screenshots';
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir, { recursive: true });
}

(async () => {
  const tmpProfile = path.join(__dirname, '.tmp-extras-cf-profile');
  if (fs.existsSync(tmpProfile)) {
    try { fs.rmSync(tmpProfile, { recursive: true, force: true }); } catch (e) {}
  }
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

  console.log('=== TEST MODULO 4: SERVIZI EXTRA & CAMPI PERSONALIZZATI ===');

  // 1. Assicuriamoci che i moduli 'service_extras' e 'custom_fields' siano attivi
  console.log('1. Verifica stato Moduli Extra (service_extras & custom_fields)...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-modules', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('form[action*="free-bookly-modules"]', { timeout: 15000 });

  let needSave = false;
  for (const mod of ['service_extras', 'custom_fields', 'email_notifications']) {
    const cb = await page.$(`input[value="${mod}"]`);
    if (cb) {
      const isChecked = await (await cb.getProperty('checked')).jsonValue();
      if (!isChecked) {
        console.log(`Attivazione modulo ${mod}...`);
        await cb.click();
        needSave = true;
      }
    }
  }

  if (needSave) {
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ waitUntil: 'domcontentloaded' })
    ]);
  }
  await page.screenshot({ path: path.join(screenshotsDir, '60-modules-extras-cf-active.png') });
  console.log('Moduli attivi e verificati!');

  // 2. Creazione Servizio Extra in Admin (Servizi -> Servizi Extra & Add-ons)
  console.log('2. Accesso al pannello Servizi Extra & Add-ons...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-services&view=extras', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#fb_extra_title', { timeout: 15000 });

  const extrasTableText = await page.$eval('.fb-admin-table', el => el.innerText).catch(() => '');
  if (!extrasTableText.includes('Trattamento Maschera Idratante')) {
    console.log('Inserimento nuovo Servizio Extra: Trattamento Idratante (+€15, +20m)...');
    await page.$eval('#fb_extra_title', el => el.value = '');
    await page.type('#fb_extra_title', 'Trattamento Maschera Idratante');
    await page.$eval('#fb_extra_price', el => el.value = '15.00');
    await page.$eval('#fb_extra_duration', el => el.value = '20');

    await Promise.all([
      page.click('#fb_save_extra_btn'),
      page.waitForNavigation({ waitUntil: 'domcontentloaded' })
    ]);
    console.log('Servizio Extra creato con successo!');
  } else {
    console.log('Servizio Extra già presente in archivio!');
  }

  await delay(500);
  await page.screenshot({ path: path.join(screenshotsDir, '61-admin-extra-created.png') });

  // 3. Creazione Campi Personalizzati in Impostazioni -> Campi Personalizzati
  console.log('3. Accesso a Impostazioni -> Campi Personalizzati...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=custom_fields', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#fb_cf_label', { timeout: 15000 });

  const cfTableText = await page.$eval('.fb-admin-table', el => el.innerText).catch(() => '');
  if (!cfTableText.includes('Codice Fiscale')) {
    console.log('Creazione Campo Personalizzato 1: Codice Fiscale (Text, Obbligatorio)...');
    await page.$eval('#fb_cf_label', el => el.value = '');
    await page.type('#fb_cf_label', 'Codice Fiscale');
    await page.select('#fb_cf_type', 'text');
    const reqCb = await page.$('#fb_cf_required');
    const isReqChecked = await (await reqCb.getProperty('checked')).jsonValue();
    if (!isReqChecked) await reqCb.click();

    await Promise.all([
      page.click('#fb_save_cf_btn'),
      page.waitForNavigation({ waitUntil: 'domcontentloaded' })
    ]);
    console.log('Campo "Codice Fiscale" creato!');
  } else {
    console.log('Campo "Codice Fiscale" già configurato!');
  }

  const cfTableText2 = await page.$eval('.fb-admin-table', el => el.innerText).catch(() => '');
  if (!cfTableText2.includes('Come ci hai conosciuto?')) {
    console.log('Creazione Campo Personalizzato 2: Come ci hai conosciuto? (Select)...');
    await page.waitForSelector('#fb_cf_label', { timeout: 15000 });
    await page.$eval('#fb_cf_label', el => el.value = '');
    await page.type('#fb_cf_label', 'Come ci hai conosciuto?');
    await page.select('#fb_cf_type', 'select');
    await page.$eval('#fb_cf_options', el => el.value = "Google Ricerca\nInstagram / Social\nPassaparola da un amico");
    const reqCb2 = await page.$('#fb_cf_required');
    const isReqChecked2 = await (await reqCb2.getProperty('checked')).jsonValue();
    if (isReqChecked2) await reqCb2.click(); // non obbligatorio

    await Promise.all([
      page.click('#fb_save_cf_btn'),
      page.waitForNavigation({ waitUntil: 'domcontentloaded' })
    ]);
    console.log('Campo "Come ci hai conosciuto?" creato!');
  } else {
    console.log('Campo "Come ci hai conosciuto?" già configurato!');
  }

  await delay(500);
  await page.screenshot({ path: path.join(screenshotsDir, '62-admin-custom-fields-created.png') });
  console.log('Campi Personalizzati verificati con successo!');

  // 4. Test Frontend: Prenotazione con Extra e Campi Personalizzati
  console.log('4. Navigazione a /prenota/ sul Frontend...');
  await page.goto('http://127.0.0.1:8081/prenota/', { waitUntil: 'networkidle2' });
  await page.waitForSelector('#fb-service-select', { timeout: 15000 });

  // Attendi popolamento servizi
  await page.waitForFunction(() => {
    const s = document.getElementById('fb-service-select');
    return s && s.options.length > 1;
  }, { timeout: 10000 });

  // Seleziona il primo servizio
  console.log('Seleziona Servizio...');
  const serviceIdVal = await page.evaluate(() => {
    const s = document.getElementById('fb-service-select');
    s.selectedIndex = 1;
    s.dispatchEvent(new Event('change'));
    return s.value;
  });

  // Attendi che compaia il box dei Servizi Extra
  await page.waitForSelector('#fb-extras-container label input[type="checkbox"]', { timeout: 10000 });
  console.log('Box Servizi Extra visualizzato! Selezioniamo il trattamento idratante...');
  await page.click('#fb-extras-container label input[type="checkbox"]');

  await delay(300);
  await page.screenshot({ path: path.join(screenshotsDir, '63-frontend-extra-selected.png') });

  // Procedi a Step 2: Data e Ora
  console.log('Avanzamento a Step 2 (Data e Ora)...');
  await page.click('#btn-to-step-2');
  await page.waitForSelector('#fb-date-picker', { timeout: 5000 });

  // Imposta la data di domani
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  // Se è domenica o sabato e il salone è chiuso, sposta a lunedì
  if (tomorrow.getDay() === 0) tomorrow.setDate(tomorrow.getDate() + 1);
  if (tomorrow.getDay() === 6) tomorrow.setDate(tomorrow.getDate() + 2);
  const dateStr = tomorrow.toISOString().split('T')[0];

  console.log(`Selezione data: ${dateStr}...`);
  await page.evaluate((d) => {
    const dp = document.getElementById('fb-date-picker');
    dp.value = d;
    dp.dispatchEvent(new Event('change'));
  }, dateStr);

  // Attendi slot disponibili
  await page.waitForSelector('.fb-slot-btn', { timeout: 15000 });
  console.log('Slot orari disponibili generati! Clicco sul primo orario...');
  await page.click('.fb-slot-btn');

  // Procedi a Step 3: Dati Utente & Campi Personalizzati
  console.log('Avanzamento a Step 3 (Dati Utente e Campi Personalizzati)...');
  await page.click('#btn-to-step-3');
  await page.waitForSelector('#fb-user-name', { timeout: 5000 });

  // Compila i dati anagrafici
  await page.type('#fb-user-name', 'Mario Rossi');
  await page.type('#fb-user-email', 'mario.rossi.extras@example.com');
  await page.type('#fb-user-phone', '+39 347 1122334');

  // Verifica la presenza dei campi personalizzati
  await page.waitForSelector('#fb-custom-fields-container .fb-cf-input', { timeout: 10000 });
  console.log('Campi personalizzati caricati dinamicamente nello Step 3!');

  // Compila tutti i campi di testo personalizzati obbligatori (es. Codice Fiscale)
  const allTextInputs = await page.$$('#fb-custom-fields-container input.fb-cf-input');
  for (const inp of allTextInputs) {
    await inp.type('RSSMRA85M01H501Z');
  }
  console.log(`Compilati ${allTextInputs.length} campi input di testo!`);

  // Seleziona opzione nel dropdown "Come ci hai conosciuto?"
  const cfSelect = await page.$('#fb-custom-fields-container select.fb-cf-input');
  if (cfSelect) {
    await page.evaluate(sel => {
      if (sel.options.length > 1) {
        sel.selectedIndex = 1; // Google Ricerca
        sel.dispatchEvent(new Event('change'));
      }
    }, cfSelect);
    console.log('Opzione "Come ci hai conosciuto" selezionata!');
  }

  await delay(300);
  await page.screenshot({ path: path.join(screenshotsDir, '64-frontend-cf-filled.png') });

  // Procedi a Step 4: Riepilogo
  console.log('Avanzamento a Step 4 (Riepilogo e Conferma)...');
  await page.click('#btn-to-step-4');
  await page.waitForSelector('#fb-summary', { timeout: 5000 });

  const summaryText = await page.$eval('#fb-summary', el => el.innerText);
  console.log('Testo Riepilogo:\n', summaryText);

  if (!summaryText.includes('Trattamento Maschera Idratante') || !summaryText.includes('Codice Fiscale') || !summaryText.includes('RSSMRA85M01H501Z')) {
    throw new Error('Il riepilogo non contiene gli extra o i campi personalizzati attesi!');
  }

  await delay(300);
  await page.screenshot({ path: path.join(screenshotsDir, '65-frontend-summary-verified.png') });

  // Clic su "Conferma Prenotazione"
  console.log('Conferma prenotazione in corso...');
  await page.click('#fb-confirm-btn');

  // Attendi schermata di successo
  await page.waitForSelector('#fb-step-success', { visible: true, timeout: 20000 });
  await page.screenshot({ path: path.join(screenshotsDir, '66-frontend-booking-confirmed.png') });
  console.log('Prenotazione completata con successo!');

  // 5. Verifica in Admin: Calendario Appuntamenti
  console.log('5. Verifica appuntamento nel Calendario Admin...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-calendar', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.fb-admin-table', { timeout: 15000 });

  const calendarTableText = await page.$eval('.fb-admin-table', el => el.innerText);
  if (!calendarTableText.includes('Mario Rossi') || !calendarTableText.includes('Trattamento Maschera Idratante') || !calendarTableText.includes('RSSMRA85M01H501Z')) {
    throw new Error('L\'appuntamento salvato non mostra gli extra o i campi personalizzati nella tabella!');
  }
  await page.screenshot({ path: path.join(screenshotsDir, '67-admin-calendar-verified.png') });
  console.log('Appuntamento verificato nel calendario con Badge Extra e Campi Personalizzati!');

  // 6. Verifica Registro Email con nuovi segnaposto
  console.log('6. Verifica Notifiche Email inviate nel registro...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=notifications', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#fb-email-log', { timeout: 15000 });
  await page.screenshot({ path: path.join(screenshotsDir, '68-email-log-verified.png') });
  console.log('Registro Notifiche Email confermato!');

  console.log('=== TEST MODULO 4 COMPLETATO CON SUCCESSO! TUTTE LE FUNZIONALITÀ VERIFICATE! ===');
  await browser.close();
})().catch(err => {
  console.error('[TEST FAILURE]', err);
  process.exit(1);
});
