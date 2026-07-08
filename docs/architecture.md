# Architettura "Free Bookly"

Questo documento descrive l'architettura base del plugin, progettata per essere modulare, performante e testabile.

## 1. Architettura a Moduli (Core + Extensions)

Il sistema adotta un design pattern basato su un gestore centrale di moduli (Module Manager).
Il `Core` fornisce API, interfacce e le strutture dati (Custom Tables). I `Moduli` si agganciano a queste API.

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

    class Core {
        +StaffManager
        +ServiceManager
        +AppointmentManager
    }

    class PaymentModule {
        +process_payment()
    }

    class NotificationModule {
        +send_email()
    }

    FreeBookly --> ModuleManager : Inizializza
    FreeBookly --> Core : Carica
    ModuleManager --> PaymentModule : Registra/Attiva
    ModuleManager --> NotificationModule : Registra/Attiva
```

## 2. Schema del Database

Invece di usare `wp_posts` e `wp_postmeta`, usiamo tabelle custom per le entità principali per garantire query veloci e join ottimali per la ricerca della disponibilità.

```mermaid
erDiagram
    WP_FB_STAFF {
        bigint id PK
        varchar full_name
        varchar email
        varchar phone
        text wp_user_id "Nullable, per login"
    }
    
    WP_FB_SERVICES {
        bigint id PK
        varchar title
        int duration "In minutes"
        decimal price
        int capacity "Default 1"
    }

    WP_FB_STAFF_SERVICES {
        bigint staff_id FK
        bigint service_id FK
        decimal custom_price "Optional"
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
    }

    WP_FB_STAFF ||--o{ WP_FB_STAFF_SERVICES : "offre"
    WP_FB_SERVICES ||--o{ WP_FB_STAFF_SERVICES : "fornito da"
    
    WP_FB_APPOINTMENTS }|--|| WP_FB_STAFF : "eseguito da"
    WP_FB_APPOINTMENTS }|--|| WP_FB_SERVICES : "richiede"
    WP_FB_APPOINTMENTS }|--|| WP_FB_CUSTOMERS : "prenotato da"

```

## 3. Gestione della Disponibilità (Anti-Overbooking)

L'algoritmo di disponibilità esegue i seguenti step:
1. Trova gli orari di lavoro dello staff richiesto per il giorno specifico (escludendo le pause).
2. Estrae da `WP_FB_APPOINTMENTS` tutti gli appuntamenti nello stato `approved` o `pending` per quel giorno.
3. Se integrato, estrae gli eventi da Google Calendar.
4. Genera gli slot temporali liberi dividendo l'orario di lavoro per intervalli regolari (es. ogni 15 minuti) e scarta gli slot che si sovrappongono con gli impegni (Step 2 e 3).
