# Guida Utente e Manuale di Installazione: Free Bookly

**Autore:** Stefano Lenzi  
**Licenza:** GPL-2.0+  
**Versione:** 1.1.0 (Open Source)

Benvenuto in **Free Bookly**, l'alternativa Open Source e modulare a Bookly Pro per la gestione professionale delle prenotazioni e degli appuntamenti su WordPress.

---

## 1. Requisiti di Sistema

Free Bookly è progettato per essere leggero, performante e compatibile con qualsiasi ambiente WordPress standard:

* **Versione WordPress:** 5.8 o superiore (completamente compatibile con WordPress 6.7+)
* **Versione PHP:** 7.4, 8.0, 8.1, 8.2, 8.3
* **Database:** MySQL 5.7+ o MariaDB 10.3+
* **Browser supportati:** Chrome, Firefox, Safari, Edge (desktop e mobile)
* **Dipendenze esterne:** Nessuna (il frontend è scritto in Vanilla JavaScript nativo per garantire massima velocità e zero conflitti).

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
* Vengono create 5 tabelle dedicate e indicizzate nel database (`wp_fb_services`, `wp_fb_staff`, `wp_fb_staff_services`, `wp_fb_customers`, `wp_fb_appointments`).
* Non viene modificata né appesantita la tabella nativa `wp_posts` o `wp_postmeta`.
* Nel menu principale di WordPress compare la nuova voce **Free Bookly** con tutte le sue sotto-sezioni.

---

## 3. Configurazione Guidata Passo-Passo

### Passo 1: Configurare i Servizi
1. Vai su **Free Bookly > Servizi**.
2. Nel modulo a sinistra ("Aggiungi Nuovo Servizio"), inserisci:
   * **Titolo del Servizio:** Nome del trattamento o della prestazione (es. *Taglio e Piega*).
   * **Durata (minuti):** Durata dello slot (es. 30, 45, 60 minuti).
   * **Prezzo (€):** Tariffa del servizio.
   * **Capacità Clienti:** Numero di clienti che possono usufruire contemporaneamente del servizio (default: 1).
3. Clicca su **Salva Servizio**.
4. Ripeti per tutti i servizi offerti. È sempre possibile modificare o eliminare un servizio dalla tabella a destra.

### Passo 2: Configurare i Membri dello Staff
1. Vai su **Free Bookly > Staff**.
2. Nel modulo a sinistra ("Aggiungi Nuovo Membro Staff"), compila:
   * **Nome Completo:** Nome dell'operatore o del professionista.
   * **Indirizzo Email & Telefono:** Contatti dell'operatore.
   * **Collega a Utente WordPress (opzionale):** Puoi collegare il profilo a un account utente già registrato su WordPress.
   * **Servizi Abilitati:** Seleziona con una spunta tutti i servizi che questo specifico collaboratore è in grado di eseguire.
3. Clicca su **Salva Membro Staff**.
4. *Nota:* Quando un cliente sul sito sceglie un servizio, il sistema mostrerà automaticamente solo i collaboratori abilitati a quel servizio.

### Passo 3: Configurare Orari di Lavoro e Pause (Staff Schedules & Breaks)
1. Nella tabella di **Free Bookly > Staff**, clicca sul pulsante **Orari & Pause** accanto al membro del team da configurare.
2. Per ciascun giorno della settimana (Lunedì &rarr; Domenica):
   * **Stato Lavorativo:** Seleziona se il collaboratore è *In Servizio* o in *Giorno di Riposo*.
   * **Orario di Turno:** Imposta l'orario di inizio e fine giornata (es. *09:00* - *18:00*).
   * **Pausa Pranzo / Intervallo:** Specifica la fascia oraria di pausa (es. dalle *13:00* alle *14:00*).
3. Clicca su **Salva Orari e Pause**.
4. Il motore di disponibilità frontend escluderà automaticamente gli orari di pausa e i giorni di riposo, riallineando gli slot disponibili non appena termina l'intervallo.

### Passo 4: Pubblicare il Form di Prenotazione sul Sito
1. Nel menu di WordPress, vai su **Pagine > Aggiungi nuova pagina**.
2. Assegna un titolo alla pagina (es. *Prenota Online* oppure *Prenotazioni*).
3. Inserisci lo shortcode:
   ```text
   [free_bookly]
   ```
4. Clicca su **Pubblica**.
5. Visita la pagina: il booking wizard a 4 step sarà immediatamente attivo, responsive e funzionante su desktop, tablet e smartphone.

---

## 4. Gestione Quotidiana degli Appuntamenti

### Dashboard e Statistiche Live
Accedendo a **Free Bookly > Dashboard**, puoi monitorare:
* Numero di appuntamenti previsti per la giornata odierna.
* Appuntamenti in attesa di approvazione.
* Totale dei membri del team attivi e dei servizi a catalogo.
* Tabella immediata degli ultimi appuntamenti ricevuti con stato di conferma.

### Gestione Appuntamenti (Calendario / Elenco)
Accedendo a **Free Bookly > Calendario**:
* Puoi filtrare la vista per **Stato** (*In Attesa*, *Confermati*, *Annullati*), per **Membro dello Staff** o per **Servizio**.
* **Approva:** Con un solo click, contrassegna la prenotazione come confermata.
* **Annulla:** Se un cliente disdice, puoi contrassegnare l'orario come annullato.
* **Elimina:** Rimuove definitivamente il record dal sistema.

---

## 5. Gestione dei Moduli Avanzati (Add-ons Gratuiti)

In Free Bookly tutte le funzionalità avanzate sono incluse gratuitamente nel codice sorgente:
1. Vai su **Free Bookly > Moduli**.
2. Troverai le schede per i vari moduli:
   * *Notifiche Email*
   * *Pagamenti Online*
   * *Campi Personalizzati*
   * *Servizi Extra & Add-ons*
   * *Coupon & Promozioni*
   * *Lista d'Attesa*
3. Attiva o disattiva i singoli interruttori in base alle reali necessità del tuo business per mantenere WordPress sempre leggero e reattivo.
4. Clicca su **Salva Configurazione Moduli**.

---

## 6. Disattivazione e Disinstallazione Sicura

Free Bookly gestisce il ciclo di vita del software nel pieno rispetto degli standard di sicurezza e pulizia di WordPress.

### Disattivazione Temporanea (I tuoi dati sono protetti)
Se disattivi il plugin da **Plugin > Plugin installati > Disattiva**:
* Il funzionamento sul frontend e il menu admin vengono temporaneamente sospesi.
* **Tutti i tuoi dati (appuntamenti, clienti, servizi, storico) rimangono intatti e al sicuro nel database**.
* Riattivando il plugin in qualunque momento, ritroverai esattamente tutte le impostazioni e le prenotazioni esistenti.

### Disinstallazione Completa (Eliminazione Definitiva)
Se decidi di rimuovere definitivamente il plugin cliccando su **Elimina**:
* WordPress esegue automaticamente lo script [`uninstall.php`](file:///c:/Users/stefy/Progetti/free-bookly/uninstall.php) incluso nel pacchetto.
* Tutte le tabelle dedicate (`wp_fb_*`) e le impostazioni registrate in `wp_options` vengono eliminate in modo pulito.
* Il database di WordPress torna esattamente allo stato precedente all'installazione, con **zero file orfani o residui di dati**.

---

## 7. Buone Pratiche per la Produzione

1. **Fuso Orario di WordPress:** Assicurati che in *Impostazioni > Generali* il fuso orario del sito corrisponda alla tua sede (es. *Roma*).
2. **Plugin di Caching (WP Rocket, LiteSpeed, ecc.):** Escludi la pagina contenente `[free_bookly]` dalla cache statica HTML, in modo che gli slot orari rimangano sempre sincronizzati in tempo reale.
3. **Protocollo HTTPS:** Utilizza sempre un certificato SSL (HTTPS) per proteggere i dati di contatto inseriti dagli utenti.
