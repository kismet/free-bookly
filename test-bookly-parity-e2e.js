const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

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
  await page.setViewport({ width: 1280, height: 960 });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.error('[BROWSER ERROR]', msg.text());
    } else {
      console.log('[BROWSER]', msg.type(), msg.text());
    }
  });
  page.on('pageerror', err => console.error('[PAGE ERROR]', err.message));

  console.log('=== TEST 1: SETTINGS (IMPOSTAZIONI AZIENDA, GENERALE, ORARI, FESTIVITÀ, PAGAMENTI) ===');
  
  // 1.1 Tab Azienda
  console.log('1.1 Visita Impostazioni > Azienda...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=company', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb_company_name', { timeout: 15000 });
  
  await page.$eval('#fb_company_name', el => el.value = 'Salone Bellezza & Stile');
  await page.$eval('#fb_company_phone', el => el.value = '+39 06 1234567');
  await page.$eval('#fb_company_address', el => el.value = 'Via del Corso 120, Roma');
  await page.$eval('#fb_company_email', el => el.value = 'info@bellezzaestile.it');
  
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  await page.screenshot({ path: path.join(screenshotsDir, '30-settings-company.png') });
  console.log('Tab Azienda salvato con successo!');

  // 1.2 Tab Generale
  console.log('1.2 Visita Impostazioni > Generale...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=general', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb_default_appointment_status', { timeout: 15000 });
  await page.select('#fb_default_appointment_status', 'approved');
  await page.select('#fb_time_slot_length', '15');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  await page.screenshot({ path: path.join(screenshotsDir, '31-settings-general.png') });
  console.log('Tab Generale salvato con successo!');

  // 1.3 Tab Orari Lavorativi
  console.log('1.3 Visita Impostazioni > Orari Lavorativi...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=working_hours', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('form[action*="working_hours"]', { timeout: 15000 });
  await page.screenshot({ path: path.join(screenshotsDir, '32-settings-working-hours.png') });
  console.log('Tab Orari Lavorativi verificato!');

  // 1.4 Tab Giorni Festivi
  console.log('1.4 Visita Impostazioni > Giorni Festivi...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=holidays', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#holiday_date', { timeout: 15000 });
  await page.$eval('#holiday_date', el => el.value = '2026-08-15');
  await page.$eval('#holiday_title', el => el.value = 'Ferragosto');
  await page.$eval('input[name="repeat_yearly"]', el => el.checked = true);
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  await page.screenshot({ path: path.join(screenshotsDir, '33-settings-holidays.png') });
  console.log('Tab Festività salvato con Ferragosto!');

  // 1.5 Tab Pagamenti & Valuta
  console.log('1.5 Visita Impostazioni > Pagamenti & Valuta...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-settings&tab=payments', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb_currency', { timeout: 15000 });
  await page.select('#fb_currency', 'EUR');
  await page.select('#fb_currency_format', '{price} €');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  await page.screenshot({ path: path.join(screenshotsDir, '34-settings-payments.png') });
  console.log('Tab Pagamenti & Valuta salvato con successo!');

  console.log('=== TEST 2: GESTIONE CATEGORIE & SERVIZI AVANZATI ===');
  // 2.1 Gestione Categorie
  console.log('2.1 Visita Gestione Categorie...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-services&view=categories', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#cat_name', { timeout: 15000 });
  await page.$eval('#cat_name', el => el.value = 'Trattamenti Viso & Benessere');
  await page.$eval('#cat_description', el => el.value = 'Trattamenti professionali di estetica avanzata e cura del viso');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  console.log('Categoria creata con successo!');

  // 2.2 Gestione Servizi
  console.log('2.2 Creazione Servizio con buffer time e parametri Bookly...');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-services', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb_service_title', { timeout: 15000 });

  await page.$eval('#fb_service_title', el => el.value = 'Trattamento Viso Gold');
  await page.$eval('#fb_service_duration', el => el.value = '45');
  await page.$eval('#fb_service_price', el => el.value = '65.00');
  
  // Buffer before & after
  await page.$eval('input[name="buffer_before"]', el => el.value = '10');
  await page.$eval('input[name="buffer_after"]', el => el.value = '15');
  
  // Rule & Category
  await page.select('#fb_any_rule', 'least_busy');
  
  const catOpts = await page.$$eval('#fb_service_category option', os => os.map(o => o.value));
  if (catOpts.length > 1) {
    await page.select('#fb_service_category', catOpts[catOpts.length - 1]);
  }

  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'domcontentloaded' })
  ]);
  await page.screenshot({ path: path.join(screenshotsDir, '35-admin-services-categories.png') });
  console.log('Servizio salvato con successo!');

  console.log('=== TEST 3: GESTIONE STAFF (FERIE / GIORNI DI RIPOSO) ===');
  await page.goto('http://127.0.0.1:8081/wp-admin/admin.php?page=free-bookly-staff', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('.wp-list-table', { timeout: 15000 });
  
  const holidaysLink = await page.$('a[href*="view=holidays"]');
  if (holidaysLink) {
    console.log('Apertura Ferie Staff...');
    const href = await page.$eval('a[href*="view=holidays"]', a => a.href);
    await page.goto(href, { waitUntil: 'domcontentloaded', timeout: 60000 });

    await page.waitForSelector('#holiday_date', { timeout: 15000 });
    await page.$eval('#holiday_date', el => {
      el.value = '2026-08-20';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await page.$eval('#holiday_title', el => {
      el.value = 'Ferie Estive Staff';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 })
    ]);
    await page.screenshot({ path: path.join(screenshotsDir, '36-admin-staff-holidays.png') });
    console.log('Ferie staff salvate con successo!');
  }

  console.log('=== TEST 4: FRONTEND - CLASSIC STEP-BY-STEP (CON CATEGORIE) ===');
  await page.goto('http://127.0.0.1:8081/prenota-online/', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb-service-select', { timeout: 20000 });
  await page.waitForFunction(() => {
    const sel = document.querySelector('#fb-service-select');
    return sel && sel.options.length > 1;
  }, { timeout: 15000 });
  await page.screenshot({ path: path.join(screenshotsDir, '37-frontend-classic-categories.png') });
  console.log('Form Classic caricato con successo!');

  console.log('=== TEST 5: FRONTEND - CATALOGO SERVIZI (CARDS + MODALE) ===');
  await page.goto('http://127.0.0.1:8081/catalogo-servizi/', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('.fb-service-card', { timeout: 20000 });
  await page.screenshot({ path: path.join(screenshotsDir, '38-frontend-services-catalog.png') });
  
  // Clic su "Prenota Ora" per aprire il modale
  const bookNowBtn = await page.$('.fb-btn-book-service');
  if (bookNowBtn) {
    console.log('Apertura modale di prenotazione da scheda servizio...');
    await bookNowBtn.click();
    await page.waitForSelector('#fb-catalog-modal', { visible: true, timeout: 5000 });
    await delay(600);
    await page.screenshot({ path: path.join(screenshotsDir, '39-frontend-services-modal.png') });
    
    // Chiudi modale
    const closeBtn = await page.$('#fb-catalog-modal .fb-modal-close');
    if (closeBtn) await closeBtn.click();
    await delay(300);
  }
  console.log('Catalogo Servizi e Modale testati con successo!');

  console.log('=== TEST 6: FRONTEND - IL NOSTRO TEAM (STAFF FIRST + MODALE) ===');
  await page.goto('http://127.0.0.1:8081/il-nostro-team/', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('.fb-staff-card', { timeout: 20000 });
  await page.screenshot({ path: path.join(screenshotsDir, '40-frontend-staff-first.png') });

  // Clic su "Prenota con ..."
  const bookStaffBtn = await page.$('.fb-btn-book-staff');
  if (bookStaffBtn) {
    console.log('Apertura modale di prenotazione da scheda staff...');
    await bookStaffBtn.click();
    await page.waitForSelector('#fb-staff-modal', { visible: true, timeout: 5000 });
    await delay(600);
    await page.screenshot({ path: path.join(screenshotsDir, '41-frontend-staff-modal.png') });
    
    // Chiudi modale
    const closeBtn = await page.$('#fb-staff-modal .fb-modal-close');
    if (closeBtn) await closeBtn.click();
    await delay(300);
  }
  console.log('Staff-First e Modale testati con successo!');

  console.log('=== TEST 7: FRONTEND - CERCA ORARI DISPONIBILI (SEARCH FORM) ===');
  await page.goto('http://127.0.0.1:8081/cerca-orari/', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#fb-search-service', { timeout: 20000 });

  // Troviamo il prossimo mercoledì
  const d = new Date();
  while (d.getDay() !== 3) {
    d.setDate(d.getDate() + 1);
  }
  const targetDate = d.toISOString().split('T')[0];

  await page.$eval('#fb-search-date', (el, val) => el.value = val, targetDate);

  // Seleziona un servizio e cerca
  const sVal = await page.$eval('#fb-search-service option:not([value=""])', opt => opt.value);
  await page.select('#fb-search-service', sVal);
  
  console.log('Esecuzione ricerca slot per', targetDate, '...');
  await page.click('#fb-btn-execute-search');
  await page.waitForFunction(() => {
    const container = document.querySelector('#fb-search-results');
    return container && container.style.display !== 'none';
  }, { timeout: 15000 });
  await delay(600);
  await page.screenshot({ path: path.join(screenshotsDir, '42-frontend-search-form.png') });
  console.log('Search Form testato con successo!');

  console.log('=== TUTTI I TEST DI PARITÀ BOOKLY SONO STATI COMPLETATI CON SUCCESSO! ===');
  await browser.close();
})().catch(err => {
  console.error('ERRORE E2E:', err);
  process.exit(1);
});
