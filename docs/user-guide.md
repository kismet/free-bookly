# Guida Utente e Manuale di Installazione: Free Bookly

**Autore:** Stefano Lenzi  
**Licenza:** GPL-2.0+  
**Versione:** 1.4.0 (Open Source)

Benvenuto in **Free Bookly**, l'alternativa Open Source completa, modulare e ad alte prestazioni a Bookly Pro per la gestione professionale delle prenotazioni e degli appuntamenti su WordPress.

---

## 1. Requisiti di Sistema

Free Bookly è progettato per essere ultra-leggero, performante e compatibile con qualsiasi ambiente WordPress standard:

* **Versione WordPress:** 5.8 o superiore (completamente compatibile con WordPress 6.7+)
* **Versione PHP:** 7.4, 8.0, 8.1, 8.2, 8.3, 8.4+
* **Database:** MySQL 5.7+ o MariaDB 10.3+ (compatibile anche con SQLite via WP Playground)
* **Browser supportati:** Chrome, Firefox, Safari, Edge (desktop e mobile)
* **Dipendenze esterne:** Nessuna (il frontend è scritto al 100% in Vanilla JavaScript nativo per garantire massima velocità e zero conflitti con temi o page builder).

---

## 2. Installazione in un Ambiente WordPress Reale

L'installazione su un sito WordPress in produzione o staging richiede meno di un minuto:

1. Accedi al pannello di amministrazione di WordPress (`/wp-admin`).
2. Nel menu laterale, clicca su **Plugin > Aggiungi nuovo plugin**.
3. In alto, clicca sul pulsante **Carica plugin**.
4. Clicca su **Scegli file** e seleziona l'archivio `free-bookly.zip`.
5. Clicca su **Installa ora**.
6. Al termine del caricamento, clicca su **Attiva plugin**.

### Cosa accade automaticamente all'attivazione:
* Vengono create o migrate in modo trasparente le 11 tabelle dedicate e indicizzate nel database:
  * `wp_fb_categories` (Categorie di servizi)
  * `wp_fb_services` (Servizi con durata, buffer time, colore, regole fornitore e visibilità)
  * `wp_fb_service_extras` (Servizi extra e add-ons opzionali con prezzo e durata addizionali)
  * `wp_fb_custom_fields` (Campi personalizzati per raccogliere dati addizionali dal cliente)
  * `wp_fb_appointment_extras` (Extra associati a ciascuna prenotazione)
  * `wp_fb_staff` (Collaboratori con avatar, colore, visibilità e stato attivo/archiviato)
  * `wp_fb_staff_services` (Associazione servizi, prezzi personalizzati)
  * `wp_fb_staff_schedule` (Orari di turno settimanali e pause individuali)
  * `wp_fb_holidays` (Festività aziendali e giorni di ferie staff, con ricorrenza annuale)
  * `wp_fb_customers` (Anagrafica clienti)
  * `wp_fb_appointments` (Prenotazioni, extra, campi personalizzati e storico)
* Vengono inizializzate le impostazioni aziendali e la categoria predefinita.
* Nel menu principale di WordPress compare la voce **Free Bookly** con tutte le sue sezioni di controllo.

---

## 3. Configurazione Completa (Parità Bookly Pro)

### 3.1 Impostazioni Generali & Aziendali (Free Bookly > Impostazioni)
Il pannello impostazioni è organizzato in 7 schede specializzate:
1. **Azienda:** Configura Nome attività, Telefono, Logo, Indirizzo sede ed Email di notifica.
2. **Generale:**
   * **Dimensione Fascia Oraria Standard (Time Slot Length):** Intervallo di inizio prenotabile (es. 15, 30, 45, 60 minuti).
   * **Stato Predefinito:** Determina se i nuovi appuntamenti sono subito approvati (*Approved*) o in attesa di revisione (*Pending*).
   * **Caricamento Script:** Opzione per caricare gli asset solo sulle pagine con form per la massima velocità.
3. **Orari Lavorativi Aziendali:** Orari di apertura predefiniti per ogni giorno della settimana con inizio, fine e pausa pranzo predefinita. Ereditati automaticamente da tutti i collaboratori.
4. **Giorni Festivi Aziendali:** Aggiungi date di chiusura (es. Natale, Ferragosto, ponti) con opzione di ripetizione annuale. In queste date tutte le prenotazioni sono bloccate per l'intera azienda.
5. **Pagamenti & Valuta:** Selezione valuta (EUR, USD, GBP, CHF, CAD, AUD) e formato di visualizzazione del prezzo (`{price} €` o `€ {price}`).
6. **Notifiche Email (Modulo 1):** Configurazione mittente, abilitazione notifiche (cliente e staff per creazione, approvazione e cancellazione), modelli HTML responsive con segnaposto e allegato di calendario RFC 5545 `.ics`. Include pulsante di test e registro delle email inviate.
7. **Campi Personalizzati (Modulo 4):** Configura campi aggiuntivi nel form di contatto (testo, textarea, menu a tendina, radio button, caselle di spunta) con indicazione di obbligatorietà e collegamento a tutti i servizi o a un servizio specifico.

### 3.2 Categorie, Servizi & Servizi Extra (Free Bookly > Servizi)
* **Gestione Categorie:** Crea raggruppamenti (es. *Cura dei Capelli*, *Estetica Viso*, *Massaggi*).
* **Buffer Time (Margine Cuscinetto):** Imposta un tempo di preparazione prima (*Buffer Before*) o di igienizzazione/riordino dopo (*Buffer After*) l'appuntamento. Il motore di disponibilità blocca automaticamente gli orari sovrapposti.
* **Regola Collaboratore Casuale / "Qualsiasi":** Scegli come assegnare la prenotazione quando il cliente seleziona "Qualsiasi collaboratore" (*Casuale*, *Primo in elenco*, *Meno occupato*, *Più economico*).
* **Servizi Extra & Add-ons (Modulo 4):** Aggiungi trattamenti o opzioni supplementari che il cliente può selezionare nel wizard. Ogni extra incrementa il prezzo complessivo e/o la durata del servizio, ricalcolando automaticamente la disponibilità degli slot orari.

### 3.3 Gestione Collaboratori & Ferie Staff (Free Bookly > Staff)
* **Profilo Completo:** Nome, Email, Telefono, Avatar, Colore badge, Descrizione e collegamento facoltativo all'utente WordPress.
* **Archiviazione Staff:** I collaboratori che lasciano l'azienda possono essere contrassegnati come *Archiviati*. Rimangono nello storico degli appuntamenti passati ma vengono esclusi dai form di prenotazione pubblici.
* **Orari & Pause:** Configurazione oraria individuale per ogni giorno della settimana.
* **Giorni Liberi & Ferie:** Cliccando su *Ferie* è possibile impostare giornate di assenza per ferie o permessi individuali (con supporto alla ricorrenza annuale).

---

## 4. I 4 Form di Prenotazione Frontend

Free Bookly supporta nativamente 4 tipologie di interfaccia di prenotazione:

### Form 1: Classic Step-by-Step Wizard
Il classico flusso di prenotazione guidato in 4 step, arricchito con filtro categorie, selezione extra opzionali, campi personalizzati e riepilogo dettagliato:
```text
[free_bookly]
oppure
[free_bookly form="classic"]
```

### Form 2: Catalogo Servizi Moderno (Services Catalog)
Ideale per saloni, spa e cliniche: visualizza schede moderne dei servizi con immagine, durata, prezzo, descrizione e pulsante "Prenota Ora" che apre il wizard modale integrato con il servizio già preselezionato:
```text
[free_bookly form="services"]
oppure
[free_bookly_services]
```

### Form 3: Staff-First Team (Il Nostro Team)
Ideale per studi associati, consulenti e studi medici: mostra le card dei collaboratori con avatar, specializzazioni, servizi erogati e pulsante "Prenota con [Collaboratore]" che apre il modale con il professionista preselezionato:
```text
[free_bookly form="staff"]
oppure
[free_bookly_staff]
```

### Form 4: Ricerca per Data e Fascia Oraria (Search Form)
Permette al cliente di cercare rapidamente tutti gli orari disponibili per una data specifica e filtrare per fascia oraria (*Mattina*, *Pomeriggio*, *Sera*):
```text
[free_bookly form="search"]
oppure
[free_bookly_search]
```

---

## 5. Gestione Appuntamenti & Calendario

Accedendo a **Free Bookly > Calendario**:
* Filtro immediato per stato (*In Attesa*, *Confermati*, *Annullati*), collaboratore e servizio.
* Visualizzazione dettagliata degli extra selezionati con badge di prezzo e calcolo del totale complessivo.
* Visualizzazione dei campi personalizzati compilati dal cliente.
* Azioni rapide con un click: **Approva** (invia notifica email al cliente con file `.ics` aggiornato), **Annulla** (invia notifica di cancellazione con evento calendario annullato) o **Elimina**.

---

## 6. Disinstallazione Pulita e Sicura

Free Bookly implementa un ciclo di vita conforme agli standard WordPress:
* **Disattivazione temporanea:** Tutti i dati e le impostazioni rimangono preservati nel database.
* **Eliminazione definitiva:** Viene eseguito [`uninstall.php`](file:///c:/Users/stefy/Progetti/free-bookly/uninstall.php), che rimuove tutte le 11 tabelle `wp_fb_*` e tutte le opzioni registrate, lasciando l'installazione di WordPress completamente pulita.
