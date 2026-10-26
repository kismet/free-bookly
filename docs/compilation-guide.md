# Guida alla Compilazione, Build e Distribuzione: Free Bookly

**Autore:** Stefano Lenzi  
**Licenza:** GPL-2.0+  
**Versione Base:** 1.1.0 (Open Source)  
**Repository:** https://github.com/stefanolenzi/free-bookly  

---

## 1. Introduzione & Filosofia di Rilascio

**Free Bookly** è progettato secondo i principi del software libero (licenza GPL-2.0+), garantendo massima trasparenza, modularità e assenza di licenze proprietarie a pagamento.

Per garantire la massima efficienza e stabilità quando il plugin viene installato su un sito WordPress di produzione, il repository di sviluppo adotta una **pipeline di compilazione e packaging automatizzata** basata su Node.js:
* Il codice sorgente di sviluppo contiene test di unità, test E2E Puppeteer, generatori di documentazione e file di configurazione.
* Il pacchetto di distribuzione (`free-bookly.zip`) contiene esclusivamente i file PHP, CSS, JS e i manuali PDF necessari al funzionamento su WordPress, con un risparmio del 95% sul peso complessivo e zero file spazzatura.

---

## 2. Requisiti di Sistema e Prerequisiti

Per compilare il progetto e generare il file di distribuzione `free-bookly.zip`, l'ambiente di sviluppo deve disporre di:

| Strumento | Versione Minima Raccomandata | Scopo |
| :--- | :--- | :--- |
| **Node.js** | v18.x o superiore (supporta v20, v22, v24, v26) | Esecuzione degli script di build, automazione e Puppeteer. |
| **npm** | v9.x o superiore | Gestione dei pacchetti e script di automazione. |
| **Git** | Qualsiasi versione recente | Tracciamento del versionamento ed estrazione dell'ID di commit per la build. |
| **PHP** | v7.4 - v8.3+ | Esecuzione dei test unitari (PHPUnit) e sintassi PHP. |
| **Chromium / Chrome** | Installato automaticamente da Puppeteer | Generazione headless dei file PDF della documentazione. |

---

## 3. Sistema di Versionamento Dinamico & Git Commit ID

In conformità con le buone pratiche di Continuous Integration e Semantic Versioning (SemVer), Free Bookly adotta un sistema di **versionamento dinamico ancorato al commit Git**:

### Come funziona l'algoritmo di versione:
1. **Versione di Iterazione (Base Version):** Definita in `package.json` (es. `1.1.0`). Viene incrementata ad ogni nuova iterazione o rilascio di modulo.
2. **Git Commit Hash (Build Version):** Lo script estrae dinamicamente l'hash univoco dell'ultimo commit Git tramite il comando nativo:
   ```bash
   git rev-parse --short HEAD
   ```
   (es. `766e21a`).
3. **Versione Completa di Produzione (Full Version):**
   ```text
   1.1.0-build.766e21a
   ```
4. **Iniezione Automatica:** Lo script [`scripts/version.js`](file:///c:/Users/stefy/Progetti/free-bookly/scripts/version.js) inietta questi valori direttamente nel file principale `free-bookly.php`:
   * Header WordPress:
     ```php
     * Version:           1.1.0-build.766e21a
     * Author:            Stefano Lenzi
     * Author URI:        https://github.com/stefanolenzi
     ```
   * Costanti PHP disponibili a runtime:
     ```php
     define( 'FREE_BOOKLY_VERSION', '1.1.0' );
     define( 'FREE_BOOKLY_BUILD', '766e21a' );
     define( 'FREE_BOOKLY_VERSION_FULL', '1.1.0-build.766e21a' );
     define( 'FREE_BOOKLY_AUTHOR', 'Stefano Lenzi' );
     ```
5. **Visibilità nell'Admin:** Il numero di versione e il commit hash vengono mostrati nel footer della dashboard di Free Bookly e nella schermata standard dei plugin di WordPress, permettendo l'identificazione istantanea della build in esecuzione.

---

## 4. Architettura degli Script di Build

Il file [`package.json`](file:///c:/Users/stefy/Progetti/free-bookly/package.json) coordina la pipeline attraverso specifici comandi npm:

```json
"scripts": {
  "test": "phpunit",
  "version:stamp": "node scripts/version.js",
  "docs": "node generate-pdf.js",
  "build:zip": "node build.js",
  "build": "npm run version:stamp && npm run docs && npm run build:zip"
}
```

### Dettaglio dei componenti:
* **`scripts/version.js`**: Legge la versione da `package.json`, interroga Git per il commit ID corrente e aggiorna i metadati di `free-bookly.php`.
* **`generate-pdf.js`**: Avvia un'istanza headless di Chromium tramite Puppeteer e genera in formato tipografico A4:
  * `docs/user-guide.pdf`: Manuale Utente per l'amministratore.
  * `docs/compilation-guide.pdf`: Questa guida tecnica di compilazione e build.
  * `docs/test-report.pdf`: Report di certificazione dei test E2E con screenshot.
* **`build.js`**: Utilizza la libreria `archiver` con compressione zlib livello 9 per creare l'archivio `free-bookly.zip`.

---

## 5. Guida Passo-Passo per Compilare e Generare il Pacchetto

### Passo 1: Clonare il repository o aprire la cartella
Apri un terminale (Bash, PowerShell o Prompt dei comandi) nella directory principale del progetto:
```bash
cd c:\Users\stefy\Progetti\free-bookly
```

### Passo 2: Installare le dipendenze di build
Esegui l'installazione dei moduli Node.js (necessario solo la prima volta o in caso di aggiornamento dipendenze):
```bash
npm install
```

### Passo 3: (Opzionale) Eseguire i test di validazione
Prima di creare la build di distribuzione, è consigliato verificare che tutti i test passino:
```bash
# Esecuzione test di unità PHP
npm test

# Esecuzione test E2E con browser headless
node test-e2e.js
node test-schedule-e2e.js
```

### Passo 4: Generare la documentazione PDF
Se desideri ricompilare esclusivamente i manuali PDF:
```bash
npm run docs
```
I file PDF verranno generati all'interno della cartella `docs/`.

### Passo 5: Compilare e produrre la build completa
Per eseguire l'intera pipeline di rilascio (timbro versione Git + compilazione PDF + pacchettizzazione zip):
```bash
npm run build
```
Al termine dell'esecuzione, apparirà l'output di conferma:
```text
[Version Stamp] Free Bookly v1.1.0 (build: 766e21a)
[Version Stamp] Full Version: 1.1.0-build.766e21a
[Version Stamp] Author: Stefano Lenzi

Avvio generazione PDF con Puppeteer...
Generazione test-report.pdf...
-> test-report.pdf salvato in docs/ e artifacts!
Generazione user-guide.pdf...
-> user-guide.pdf salvato in docs/ e artifacts!
Generazione compilation-guide.pdf...
-> compilation-guide.pdf salvato in docs/ e artifacts!
Tutti i PDF generati con successo!

Build completata! file ~135000 bytes totali.
Il plugin è stato pacchettizzato con successo in free-bookly.zip e può essere installato in WordPress.
```

---

## 6. Struttura del Pacchetto Distribuibile (`free-bookly.zip`)

L'archivio `free-bookly.zip` generato rispetta scrupolosamente le linee guida ufficiali di WordPress.org per la struttura dei plugin.

### File inclusi nel pacchetto:
```text
free-bookly/
├── free-bookly.php              # File principale con header, versione Git e bootstrap
├── uninstall.php                # Disinstallatore sicuro (rimozione pulita tabelle SQL)
├── README.md                    # Presentazione del plugin
├── admin/                       # Controller, viste e stili del pannello WordPress
│   ├── class-free-bookly-admin.php
│   ├── css/free-bookly-admin.css
│   └── partials/*.php
├── includes/                    # Modelli CRUD, Availability Engine e Module Manager
│   ├── class-free-bookly.php
│   ├── class-free-bookly-activator.php
│   ├── class-module-manager.php
│   ├── class-fb-service.php
│   ├── class-fb-staff.php
│   ├── class-fb-staff-schedule.php
│   ├── class-fb-availability.php
│   └── class-fb-ajax.php
├── public/                      # Wizard frontend Vanilla JS e stili responsive
│   ├── css/free-bookly-public.css
│   ├── js/free-bookly-wizard.js
│   └── partials/free-bookly-public-display.php
└── docs/                        # Manuali ufficiali in PDF distribuiti con il plugin
    ├── user-guide.pdf           # Manuale d'uso completo
    └── compilation-guide.pdf    # Guida tecnica di compilazione
```

### File rigorosamente esclusi per la produzione:
* Cartella `node_modules/` (risparmio di oltre 150 MB).
* File e cartelle Git (`.git/`, `.gitignore`).
* Script di compilazione e test (`build.js`, `generate-pdf.js`, `scripts/`, `test-e2e.js`, `tests/`, `phpunit.xml.dist`).
* File Markdown grezzi (`docs/*.md`).
* Report di test interno (`docs/test-report.pdf`).
* File `package.json` e `package-lock.json`.

---

## 7. Installazione e Verifica del Rilascio su WordPress

Per verificare la build prodotta:
1. Accedi alla bacheca di un sito WordPress (`/wp-admin`).
2. Vai su **Plugin > Aggiungi nuovo plugin > Carica plugin**.
3. Seleziona il file `free-bookly.zip` e clicca su **Installa ora**.
4. Clicca su **Attiva plugin**.
5. Nella lista dei plugin installati, verifica la riga di Free Bookly:
   * **Nome:** Free Bookly
   * **Descrizione:** *L'alternativa Open Source a Bookly per la gestione delle prenotazioni. Completamente modulare e gratuita.*
   * **Versione:** `1.1.0-build.766e21a`
   * **Autore:** `Stefano Lenzi`
6. Accedi a **Free Bookly > Dashboard** e verifica nel piè di pagina il badge con versione, commit Git e licenza GPL-2.0+.

---

## 8. Troubleshooting e FAQ

### Q1: Su Windows PowerShell ottengo l'errore `&& non è consentito`
In PowerShell nativo (specialmente su versioni precedenti a PowerShell 7), l'operatore `&&` non è supportato. Utilizza il punto e virgola `;`:
```powershell
npm run version:stamp; npm run docs; npm run build:zip
```
In alternativa, esegui semplicemente `npm run build`, che invoca internamente la shell corretta.

### Q2: Puppeteer genera un errore `Failed to launch the browser process`
Assicurati che non vi siano processi Chromium rimasti bloccati in background. Gli script utilizzano gli argomenti `--no-sandbox` e `--disable-setuid-sandbox` per garantire la massima compatibilità anche in ambienti containerizzati e CI/CD.

### Q3: Come aggiornare il numero di versione per una nuova iterazione?
È sufficiente modificare il campo `"version"` all'interno di `package.json` (es. impostando `"1.2.0"` per il Modulo Notifiche Email) e lanciare `npm run build`. Lo script inietterà automaticamente il nuovo numero di versione combinato con l'hash Git attuale.
