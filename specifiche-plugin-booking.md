# Specifiche Plugin WordPress "Free Bookly"

## 1. Introduzione
Il plugin "Free Bookly" è un sistema completo di prenotazione appuntamenti per WordPress, progettato per piccole e medie imprese come saloni, centri benessere, cliniche, consulenti, ecc.

## 2. Funzionalità Lato Frontend (Cliente)
*   **Form di Prenotazione Multi-Step**: Inseribile tramite shortcode (es. `[free_bookly_form]`), completamente responsive.
    *   *Step 1*: Selezione Categoria, Servizio, e Staff (opzionale).
    *   *Step 2*: Selezione di Data e Ora, basato sulla disponibilità in tempo reale dello staff.
    *   *Step 3*: Inserimento dettagli utente (Nome, Email, Telefono, Note).
    *   *Step 4*: Riepilogo e Conferma (o Pagamento, se abilitato).
*   **Pagamenti Online**: Integrazione opzionale per pagare durante la prenotazione.
*   **Pannello Cliente (Customer Cabinet)**: Shortcode per permettere ai clienti loggati di vedere, riprogrammare o cancellare i propri appuntamenti.

## 3. Funzionalità Lato Backend (Admin & Staff)
*   **Calendario Admin**: Visualizzazione giornaliera, settimanale e mensile di tutti gli appuntamenti.
*   **Gestione Staff**: 
    *   Creazione profili staff con orari di lavoro personalizzati, pause e giorni liberi.
    *   Associazione dei membri dello staff a specifici servizi.
*   **Gestione Servizi**:
    *   Definizione di servizi con durata, prezzo, e capacità (per prenotazioni di gruppo).
    *   Possibilità di raggruppare i servizi in categorie.
*   **Gestione Clienti**: Database clienti centralizzato con storico degli appuntamenti.
*   **Gestione Appuntamenti**: Possibilità di creare, modificare, cancellare o approvare manualmente gli appuntamenti.
*   **Impostazioni Generali**: Configurazione del fuso orario, formato ora, valuta, regole di prenotazione (es. preavviso minimo).

## 4. Automazione e Notifiche
*   **Notifiche Email**: Invio automatico di email per conferma prenotazione, promemoria, cancellazioni, ecc., sia al cliente che allo staff.
*   **Integrazione Google Calendar (2-way sync)**: Sincronizzazione automatica bidirezionale tra il calendario dello staff sul plugin e Google Calendar.

## 5. Requisiti Tecnici
*   **Architettura**: Pattern MVC o strutturato a moduli, utilizzando classi PHP moderne e namespace.
*   **Database**: Utilizzo di tabelle custom (es. `wp_fb_services`, `wp_fb_staff`, `wp_fb_appointments`, `wp_fb_customers`) per ottimizzare le performance, invece di basarsi solo sui Custom Post Types.
*   **Sicurezza**: Sanitizzazione e validazione di tutti gli input utente tramite le funzioni native di WordPress (`sanitize_text_field`, `wp_verify_nonce`, ecc.). Protezione delle API REST o admin-ajax.
*   **Test**: Presenza di test funzionali scritti con WP_UnitTestCase / PHPUnit per validare le logiche principali (creazione appuntamento, calcolo disponibilità, sovrapposizioni).

## 6. Funzionalità Avanzate (Equivalenti agli Add-on Premium)
Il plugin dovrà offrire nativamente tutte le seguenti funzionalità estese, replicando la user experience di Bookly e dei suoi add-on:

### 6.1 Pagamenti e Finanza
*   **Gateway di Pagamento Multipli**: Supporto integrato per Stripe, PayPal (Standard e Checkout), Authorize.Net, Mollie, 2Checkout, Payson, PayU, PayUbiz.
*   **Pagamenti con Deposito (Acconti)**: Possibilità di far pagare al cliente solo una percentuale o una quota fissa al momento della prenotazione.
*   **Fatturazione Automatica**: Generazione automatica di fatture/ricevute in PDF al momento del pagamento o dell'approvazione.
*   **Calcolo Automatizzato delle Tasse**: Applicazione di tasse/IVA configurabili in base al servizio o alla location.
*   **Sconti Condizionali e Coupon**: Codici sconto promozionali e sconti automatici basati su logiche specifiche (es. numero di appuntamenti).
*   **Pacchetti (Package Deals)**: Possibilità di vendere pacchetti di appuntamenti multipli prepagati.
*   **Prezzi Flessibili**: Il prezzo del servizio può variare in base all'orario, al giorno della settimana o al membro dello staff scelto.

### 6.2 Prenotazioni ed Esperienza Cliente
*   **Appuntamenti Ricorrenti**: I clienti possono prenotare una serie di appuntamenti ripetuti nel tempo (es. ogni martedì).
*   **Prenotazioni di Gruppo e Eventi**: 
    *   Gestione capacità per permettere a più persone di prenotare lo stesso slot.
    *   Supporto per "Eventi" specifici con una data/ora fissa e numero di posti.
*   **Appuntamenti Multipli, a Catena (Chain) e Carrello**:
    *   I clienti possono prenotare più servizi diversi in un unico processo ("Carrello").
    *   Servizi a catena: prenotare sequenzialmente più servizi con staff/tempi diversi in un solo colpo.
*   **Servizi Extra (Upselling)**: Possibilità di selezionare componenti aggiuntivi opzionali prima del check-out (es. "Olio speciale per massaggio").
*   **Lista d'Attesa (Waiting List)**: I clienti possono mettersi in lista d'attesa per slot attualmente occupati.
*   **Campi e Dati Personalizzati**: Aggiunta di campi di testo, menu a tendina, checkbox e caricamento file (Attachment) nel form di prenotazione. Memorizzazione di dati estesi nel profilo del cliente.
*   **Servizi a Orario Flessibile**: Il cliente può scegliere la durata o l'orario d'inizio in modo libero entro certi limiti.
*   **Limite Disponibilità**: Restrizione sul numero di prenotazioni effettuabili da un cliente o per un servizio in un dato periodo.

### 6.3 Gestione Staff, Sedi e Risorse
*   **Sedi Multiple (Service Locations)**: Gestione degli appuntamenti dislocati in diverse sedi fisiche. Integrazione con Google Maps Autocomplete per l'inserimento degli indirizzi.
*   **Condivisione Risorse (Rooms/Equipment)**: Gestione delle collisioni basata su stanze o macchinari limitati, indipendentemente dalla disponibilità dello staff.
*   **Pannello Personale Staff (Staff Cabinet)**: I membri dello staff loggati possono gestire le proprie prenotazioni, i propri orari e i giorni liberi direttamente dal frontend o da un pannello limitato.
*   **Orari Avanzati e Giorni Speciali**: Pianificazione avanzata dei turni dello staff con "Giorni Speciali" (orari diversi in giorni specifici).
*   **Valutazioni Staff (Ratings)**: I clienti possono recensire e votare il membro dello staff dopo l'appuntamento.

### 6.4 Integrazioni e Varie
*   **Sincronizzazione Calendari Completa**: Outlook Calendar Sync oltre al Google Calendar Sync (bidirezionale).
*   **Mailchimp Integration**: Sincronizzazione automatica dei clienti con liste di newsletter Mailchimp.
*   **Gruppi di Clienti**: Categorizzazione dei clienti in gruppi per segmentazione marketing e sconti.
*   **Stati Appuntamento Personalizzati**: Creazione di flussi di lavoro customizzati tramite nuovi "Status" per le prenotazioni (es. "Arrivato", "In corso", "Da saldare").
*   **Lista Tasks Illimitata**: Gestione delle cose da fare e delle azioni backend legate alle prenotazioni.
