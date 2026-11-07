# Architettura "Free Bookly"

**Autore:** Stefano Lenzi  
**Licenza:** GPL-2.0+  
**Versione:** 1.4.0 (Open Source)

Questo documento descrive l'architettura tecnica del plugin, progettata secondo i principi di massima modularità, assenza di dipendenze pesanti, testabilità e parità funzionale completa con Bookly Pro.

---

## 1. Architettura Generale a Moduli (Core + Extensions)

Il plugin adotta un pattern MVC modulare integrato con WordPress Hooks:

```mermaid
classDiagram
    class FreeBookly {
        +run()
        -load_dependencies()
        -set_locale()
        -define_admin_hooks()
        -define_public_hooks()
    }
    
    class ModuleManager {
        -modules: array
        +register_module(module_name)
        +activate_module(module_name)
        +deactivate_module(module_name)
        +is_active(module_name)
    }

    class CoreModels {
        +FB_Category
        +FB_Service
        +FB_Staff
        +FB_Staff_Schedule
        +FB_Holiday
        +FB_Customer
        +FB_Appointment
        +FB_Settings
    }

    class AvailabilityEngine {
        +get_available_slots()
        +assign_staff_for_any()
        -check_collision()
        -is_holiday()
    }

    class PublicRouter {
        +render_classic_wizard()
        +render_services_catalog()
        +render_staff_first()
        +render_search_form()
    }

    FreeBookly --> ModuleManager : Inizializza
    FreeBookly --> CoreModels : Carica
    FreeBookly --> PublicRouter : Instrada Form
    PublicRouter --> AvailabilityEngine : Calcola Slot Real-Time
```

---

## 2. Schema del Database Relazionale (DB Version 1.4.0)

Tutte le entità sono gestite con 11 tabelle dedicate SQL, indicizzate per garantire velocità istantanea anche con grandi volumi di dati:

```mermaid
erDiagram
    WP_FB_CATEGORIES {
        bigint id PK
        varchar name
        text description
        varchar image_url
        int sort_order
    }

    WP_FB_SERVICES {
        bigint id PK
        bigint category_id FK
        varchar title
        int duration
        decimal price
        int capacity
        varchar image_url
        varchar color
        varchar visibility "public, private"
        text info
        int time_slot_length
        int buffer_before
        int buffer_after
        varchar any_provider_rule "random, first, least_busy, least_expensive"
    }

    WP_FB_SERVICE_EXTRAS {
        bigint id PK
        bigint service_id FK "0 = Tutti i servizi"
        varchar title
        decimal price
        int duration
        varchar status "active, inactive"
    }

    WP_FB_CUSTOM_FIELDS {
        bigint id PK
        bigint service_id FK "0 = Tutti i servizi"
        varchar type "text, textarea, select, radio, checkbox"
        varchar label
        text options "JSON o CSV per select/radio/checkbox"
        tinyint required "0 o 1"
        int sort_order
    }

    WP_FB_APPOINTMENT_EXTRAS {
        bigint id PK
        bigint appointment_id FK
        bigint extra_id FK
        varchar title
        decimal price
        int duration
    }

    WP_FB_EMAIL_LOG {
        bigint id PK
        varchar recipient
        varchar subject
        text message
        varchar status "sent, failed"
        varchar trigger_event
        datetime created_at
    }

    WP_FB_STAFF {
        bigint id PK
        varchar full_name
        varchar email
        varchar phone
        varchar avatar_url
        varchar color
        varchar visibility "public, private"
        text info
        varchar status "active, archived"
        bigint wp_user_id FK "Nullable"
    }

    WP_FB_STAFF_SERVICES {
        bigint staff_id FK
        bigint service_id FK
        decimal custom_price "Nullable"
    }

    WP_FB_STAFF_SCHEDULE {
        bigint id PK
        bigint staff_id FK
        tinyint day_of_week "1=Lun, 7=Dom"
        tinyint is_working "0 o 1"
        time start_time
        time end_time
        time break_start "Nullable"
        time break_end "Nullable"
    }

    WP_FB_HOLIDAYS {
        bigint id PK
        bigint staff_id FK "0 = Azienda intera"
        date holiday_date
        varchar title
        tinyint repeat_yearly "0 o 1"
    }

    WP_FB_CUSTOMERS {
        bigint id PK
        varchar full_name
        varchar email
        varchar phone
    }

    WP_FB_APPOINTMENTS {
        bigint id PK
        bigint staff_id FK
        bigint service_id FK
        bigint customer_id FK
        datetime start_date
        datetime end_date
        varchar status "pending, approved, cancelled"
        decimal extras_price "Prezzo sommato degli add-on"
        text custom_fields "JSON risposte campi personalizzati"
        text notes
    }

    WP_FB_CATEGORIES ||--o{ WP_FB_SERVICES : "raggruppa"
    WP_FB_SERVICES ||--o{ WP_FB_STAFF_SERVICES : "offerto da"
    WP_FB_SERVICES ||--o{ WP_FB_SERVICE_EXTRAS : "dispone di"
    WP_FB_SERVICES ||--o{ WP_FB_CUSTOM_FIELDS : "richiede"
    WP_FB_STAFF ||--o{ WP_FB_STAFF_SERVICES : "abilitato a"
    WP_FB_STAFF ||--o{ WP_FB_STAFF_SCHEDULE : "turni di"
    WP_FB_STAFF ||--o{ WP_FB_HOLIDAYS : "ferie di"
    WP_FB_APPOINTMENTS }|--|| WP_FB_STAFF : "eseguito da"
    WP_FB_APPOINTMENTS }|--|| WP_FB_SERVICES : "relativo a"
    WP_FB_APPOINTMENTS }|--|| WP_FB_CUSTOMERS : "prenotato da"
    WP_FB_APPOINTMENTS ||--o{ WP_FB_APPOINTMENT_EXTRAS : "comprende"
```

---

## 3. Motore di Disponibilità & Collision Engine

La classe [`FB_Availability`](file:///c:/Users/stefy/Progetti/free-bookly/includes/class-fb-availability.php) esegue il calcolo in tempo reale secondo il seguente flusso:
1. **Controllo Festività (Holidays):**
   * Se la data è registrata come festività aziendale (`staff_id = 0`) o giorno di ferie del collaboratore, la giornata è considerata chiusa e restituisce 0 slot.
   * Supporta ricorrenza annuale automatica (`repeat_yearly = 1`).
2. **Turno di Lavoro & Pause (Schedules & Breaks):**
   * Recupera gli orari del collaboratore da `wp_fb_staff_schedule`.
   * Se non impostati, eredita automaticamente gli orari aziendali predefiniti (`FB_Settings::get_company_working_hours()`).
3. **Calcolo Dinamico della Durata Effettiva con Add-ons:**
   * Durata slot richiesta = `base_service_duration + sum(extras_duration)`.
   * Il motore verifica la disponibilità per l'intero intervallo espanso, evitando sovrapposizioni su turni di chiusura o pause intermedie.
4. **Passo Temporale di Avanzamento (Time Slot Length):**
   * Determinato dal servizio (`time_slot_length`) oppure dall'impostazione globale (`fb_time_slot_length`, default 15 min).
5. **Verifica Collisioni con Buffer Times:**
   * Per ogni slot potenziale `[start, end]`:
     * Durata richiesta = `effettiva_durata + buffer_after`.
     * Margine iniziale = `start - buffer_before`.
     * Verifica che l'intervallo non si sovrapponga a pause di turno o appuntamenti già confermati/in attesa.
6. **Regole Assegnazione "Qualsiasi Collaboratore" (Any Provider):**
   * `random`: Selezione casuale uniforme tra tutti i collaboratori liberi.
   * `first`: Primo collaboratore in ordine di apparizione.
   * `least_busy`: Collaboratore con minor numero di appuntamenti nella giornata prescelta (bilanciamento del carico).
   * `least_expensive`: Collaboratore che offre la tariffa più conveniente per il servizio.

---

## 4. Frontend Multi-Form & Vanilla JS Engine

Tutti i form sono serviti dal file [`public/js/free-bookly-wizard.js`](file:///c:/Users/stefy/Progetti/free-bookly/public/js/free-bookly-wizard.js), scritto in puro Vanilla JavaScript:
* **Classic Wizard:** Navigazione a schede progressiva con pre-filtro categorie, selezione extra opzionali in Step 1, selezione slot in Step 2, compilazione campi personalizzati con validazione obbligatorietà in Step 3 e riepilogo dettagliato in Step 4.
* **Services Catalog:** Griglia responsive a schede con filtraggio pillole e modale dinamico integrato con supporto add-ons.
* **Staff-First:** Vetrina del personale con avatar, colori distintivi e apertura modale con collaboratore pre-assegnato.
* **Search Form:** Ricerca per data e fascia oraria (*Mattina*, *Pomeriggio*, *Sera*) con restituzione immediata degli slot.

---

## 5. Modulo 1: Notifiche Email & Calendario (.ics) RFC 5545

Il modulo [`FB_Email_Notifications`](file:///c:/Users/stefy/Progetti/free-bookly/includes/modules/class-fb-email-notifications.php) gestisce il ciclo completo delle comunicazioni transazionali:
* **Trigger Automatici:**
  * Creazione appuntamento (notifica cliente + notifica staff/admin).
  * Approvazione appuntamento (`status = 'approved'`).
  * Cancellazione appuntamento (`status = 'cancelled'`).
* **Generatore ICS Nativo ([`FB_ICS`](file:///c:/Users/stefy/Progetti/free-bookly/includes/class-fb-ics.php)):**
  * Conforme alle specifiche RFC 5545 (line folding a 75 ottetti, caratteri di escape `\,`, `\;`, `\n`, timestamp UTC `Z`, UID univoco, allarme promemoria -15m).
  * Genera file `.ics` temporaneo allegato all'email per l'aggiunta istantanea su Google Calendar, Apple Calendar e Outlook.
* **Template & Segnaposto Dinamici:**
  * `{customer_name}`, `{service_name}`, `{service_extras}`, `{custom_fields}`, `{staff_name}`, `{appointment_date}`, `{appointment_time}`, `{duration}`, `{total_price}`, `{company_name}`, `{company_phone}`.
* **Logging & Tracciabilità:**
  * Ogni email inviata viene archiviata nella tabella `wp_fb_email_log`, consultabile dall'amministratore con stato (*sent*/*failed*) e data/ora.

---

## 6. Modulo 4: Servizi Extra & Campi Personalizzati

### 6.1 Servizi Extra (Add-ons)
* Gestiti dalla classe [`FB_Service_Extra`](file:///c:/Users/stefy/Progetti/free-bookly/includes/class-fb-service-extra.php).
* Supportano prezzo addizionale (€) e durata aggiuntiva (minuti).
* Possono essere globali (tutti i servizi) o dedicati a un servizio specifico.
* Nel frontend vengono renderizzati in tempo reale come checkbox reattivi che aggiornano sia il prezzo totale che la disponibilità oraria del calendario.

### 6.2 Campi Personalizzati (Custom Fields)
* Gestiti dalla classe [`FB_Custom_Field`](file:///c:/Users/stefy/Progetti/free-bookly/includes/class-fb-custom-field.php).
* Tipologie supportate: `text`, `textarea`, `select`, `radio`, `checkbox`.
* Supporto flag obbligatorio (`required`) con validazione client-side e server-side.
* I valori compilati vengono serializzati in formato JSON nella colonna `wp_fb_appointments.custom_fields` e visualizzati nel calendario admin e nelle notifiche email.
