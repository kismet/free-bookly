# Architettura "Free Bookly"

**Autore:** Stefano Lenzi  
**Licenza:** GPL-2.0+  
**Versione:** 1.2.0 (Open Source)

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

## 2. Schema del Database Relazionale (DB Version 1.2.0)

Tutte le entità sono gestite con tabelle dedicate SQL, indicizzate per garantire velocità istantanea anche con centinaia di migliaia di appuntamenti:

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
        text notes
    }

    WP_FB_CATEGORIES ||--o{ WP_FB_SERVICES : "raggruppa"
    WP_FB_SERVICES ||--o{ WP_FB_STAFF_SERVICES : "offerto da"
    WP_FB_STAFF ||--o{ WP_FB_STAFF_SERVICES : "abilitato a"
    WP_FB_STAFF ||--o{ WP_FB_STAFF_SCHEDULE : "turni di"
    WP_FB_STAFF ||--o{ WP_FB_HOLIDAYS : "ferie di"
    WP_FB_APPOINTMENTS }|--|| WP_FB_STAFF : "eseguito da"
    WP_FB_APPOINTMENTS }|--|| WP_FB_SERVICES : "relativo a"
    WP_FB_APPOINTMENTS }|--|| WP_FB_CUSTOMERS : "prenotato da"
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
3. **Passo Temporale di Avanzamento (Time Slot Length):**
   * Determinato dal servizio (`time_slot_length`) oppure dall'impostazione globale (`fb_time_slot_length`, default 15 min).
4. **Verifica Collisioni con Buffer Times:**
   * Per ogni slot potenziale `[start, end]`:
     * Durata richiesta = `duration + buffer_after`.
     * Margine iniziale = `start - buffer_before`.
     * Verifica che l'intervallo non si sovrapponga a pause di turno o appuntamenti già confermati/in attesa.
5. **Regole Assegnazione "Qualsiasi Collaboratore" (Any Provider):**
   * `random`: Selezione casuale uniforme tra tutti i collaboratori liberi.
   * `first`: Primo collaboratore in ordine di apparizione.
   * `least_busy`: Collaboratore con minor numero di appuntamenti nella giornata prescelta (bilanciamento del carico).
   * `least_expensive`: Collaboratore che offre la tariffa più conveniente per il servizio.

---

## 4. Frontend Multi-Form & Vanilla JS Engine

Tutti i form sono serviti dal file [`public/js/free-bookly-wizard.js`](file:///c:/Users/stefy/Progetti/free-bookly/public/js/free-bookly-wizard.js), scritto in puro Vanilla JavaScript:
* **Classic Wizard:** Navigazione a schede progressiva con pre-filtro categorie e validazione input.
* **Services Catalog:** Griglia responsive a schede con filtraggio pillole e modale dinamico.
* **Staff-First:** Vetrina del personale con avatar, colori distintivi e apertura modale con collaboratore pre-assegnato.
* **Search Form:** Ricerca per data e fascia oraria (*Mattina*, *Pomeriggio*, *Sera*) con restituzione immediata degli slot.
