# Class Diagram: Modelli Core (CRUD)

**Autore:** Stefano Lenzi  
**Licenza:** GPL-2.0+  
**Versione:** 1.1.0 (Open Source)

I seguenti diagrammi UML descrivono la struttura delle classi che si occupano di interagire con il database per le entità principali del plugin Free Bookly.

```mermaid
classDiagram
    class FB_Staff {
        +int id
        +string full_name
        +string email
        +string phone
        +int wp_user_id
        +__construct(array data)
        +bool save()
        +bool delete()
        +static FB_Staff get_by_id(int id)
        +static FB_Staff[] get_all()
        +int[] get_service_ids()
        +bool set_services(int[] service_ids)
        +static FB_Staff[] get_by_service_id(int service_id)
    }

    class FB_Service {
        +int id
        +string title
        +int duration
        +float price
        +int capacity
        +__construct(array data)
        +bool save()
        +bool delete()
        +static FB_Service get_by_id(int id)
        +static FB_Service[] get_all()
    }

    class FB_Customer {
        +int id
        +string full_name
        +string email
        +string phone
        +int wp_user_id
        +__construct(array data)
        +bool save()
        +static FB_Customer get_by_id(int id)
    }

    class FB_Appointment {
        +int id
        +int staff_id
        +int service_id
        +int customer_id
        +string start_date
        +string end_date
        +string status
        +__construct(array data)
        +bool save()
        +static FB_Appointment get_by_id(int id)
    }

    class Free_Bookly_Admin {
        -string plugin_name
        -string version
        +add_plugin_admin_menu()
        +handle_form_actions()
        +display_dashboard()
        +display_calendar()
        +display_staff()
        +display_services()
        +display_modules()
        +enqueue_styles(hook)
        +enqueue_scripts(hook)
    }

    class FB_Ajax {
        +get_services()
        +get_staff()
        +get_available_slots()
        +save_appointment()
    }

    class FB_Staff_Schedule {
        +static array get_day_names()
        +static array get_schedule(int staff_id)
        +static array get_day_schedule(int staff_id, int day_index)
        +static bool save_schedule(int staff_id, array days_data)
        +static void delete_by_staff(int staff_id)
    }

    FB_Appointment --> FB_Staff : "Assigned To"
    FB_Appointment --> FB_Service : "Books"
    FB_Appointment --> FB_Customer : "Made By"
    FB_Staff "1" -- "*" FB_Service : "Eroga (via wp_fb_staff_services)"
    FB_Staff "1" -- "7" FB_Staff_Schedule : "Configura orari e pause"
    Free_Bookly_Admin ..> FB_Staff : "CRUD"
    Free_Bookly_Admin ..> FB_Staff_Schedule : "CRUD"
    Free_Bookly_Admin ..> FB_Service : "CRUD"
    FB_Ajax ..> FB_Service : "Queries"
    FB_Ajax ..> FB_Staff : "Queries"
    FB_Ajax ..> FB_Staff_Schedule : "Queries"
```
