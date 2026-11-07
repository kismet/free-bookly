<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'company';
$tabs = array(
	'company'       => array( 'title' => __( 'Azienda', 'free-bookly' ), 'icon' => 'dashicons-building' ),
	'general'       => array( 'title' => __( 'Generale', 'free-bookly' ), 'icon' => 'dashicons-admin-settings' ),
	'working_hours' => array( 'title' => __( 'Orari Lavorativi', 'free-bookly' ), 'icon' => 'dashicons-clock' ),
	'holidays'      => array( 'title' => __( 'Giorni Festivi', 'free-bookly' ), 'icon' => 'dashicons-calendar-alt' ),
	'payments'      => array( 'title' => __( 'Pagamenti & Valuta', 'free-bookly' ), 'icon' => 'dashicons-money-alt' ),
	'notifications' => array( 'title' => __( 'Notifiche Email', 'free-bookly' ), 'icon' => 'dashicons-email-alt' ),
	'custom_fields' => array( 'title' => __( 'Campi Personalizzati', 'free-bookly' ), 'icon' => 'dashicons-forms' ),
);

$day_names = FB_Staff_Schedule::get_day_names();
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">Free Bookly - <?php esc_html_e( 'Impostazioni', 'free-bookly' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible" style="margin-top: 15px;">
			<p><strong><?php esc_html_e( 'Impostazioni salvate con successo!', 'free-bookly' ); ?></strong></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible" style="margin-top: 15px;">
			<p><strong><?php esc_html_e( 'Elemento eliminato con successo!', 'free-bookly' ); ?></strong></p>
		</div>
	<?php endif; ?>

	<!-- Navigazione a Schede (Tabs) -->
	<h2 class="nav-tab-wrapper" style="margin-top: 20px; margin-bottom: 24px;">
		<?php foreach ( $tabs as $tab_key => $tab ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=' . $tab_key ) ); ?>" 
			   class="nav-tab <?php echo ( $active_tab === $tab_key ) ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" style="font-size: 17px; margin-right: 4px; line-height: 1.4;"></span>
				<?php echo esc_html( $tab['title'] ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<!-- TAB 1: AZIENDA -->
	<?php if ( 'company' === $active_tab ) : ?>
		<div class="fb-card" style="max-width: 800px;">
			<h2><?php esc_html_e( 'Dati Aziendali', 'free-bookly' ); ?></h2>
			<p style="color: #64748b; margin-bottom: 20px;">
				<?php esc_html_e( 'Queste informazioni identificano la tua attività nelle notifiche email inviate ai clienti e nell\'intestazione dei documenti.', 'free-bookly' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=company' ) ); ?>">
				<input type="hidden" name="action" value="fb_save_settings_company">
				<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>

				<div class="fb-form-group">
					<label for="fb_company_name"><?php esc_html_e( 'Nome dell\'Azienda / Attività', 'free-bookly' ); ?></label>
					<input type="text" id="fb_company_name" name="fb_company_name" value="<?php echo esc_attr( FB_Settings::get( 'company_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" required>
				</div>

				<div class="fb-form-group">
					<label for="fb_company_logo"><?php esc_html_e( 'URL Logo Aziendale', 'free-bookly' ); ?></label>
					<input type="url" id="fb_company_logo" name="fb_company_logo" value="<?php echo esc_url( FB_Settings::get( 'company_logo', '' ) ); ?>" class="regular-text" placeholder="https://tuosito.com/wp-content/uploads/logo.png">
					<small style="color: #64748b;"><?php esc_html_e( 'Inserisci l\'URL del logo della tua azienda per le email e i form di prenotazione.', 'free-bookly' ); ?></small>
				</div>

				<div class="fb-form-group">
					<label for="fb_company_address"><?php esc_html_e( 'Indirizzo Sede', 'free-bookly' ); ?></label>
					<textarea id="fb_company_address" name="fb_company_address" rows="2" class="large-text"><?php echo esc_textarea( FB_Settings::get( 'company_address', '' ) ); ?></textarea>
				</div>

				<div class="fb-form-group">
					<label for="fb_company_phone"><?php esc_html_e( 'Telefono Aziendale', 'free-bookly' ); ?></label>
					<input type="text" id="fb_company_phone" name="fb_company_phone" value="<?php echo esc_attr( FB_Settings::get( 'company_phone', '' ) ); ?>" class="regular-text" placeholder="+39 06 1234567">
				</div>

				<div class="fb-form-group">
					<label for="fb_company_email"><?php esc_html_e( 'Email Notifiche & Ricezione', 'free-bookly' ); ?></label>
					<input type="email" id="fb_company_email" name="fb_company_email" value="<?php echo esc_attr( FB_Settings::get( 'company_email', get_bloginfo( 'admin_email' ) ) ); ?>" class="regular-text" required>
					<small style="color: #64748b;"><?php esc_html_e( 'Indirizzo email utilizzato per inviare e ricevere le conferme di prenotazione.', 'free-bookly' ); ?></small>
				</div>

				<p class="submit" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Salva Dati Aziendali', 'free-bookly' ); ?></button>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<!-- TAB 2: GENERALE -->
	<?php if ( 'general' === $active_tab ) : ?>
		<div class="fb-card" style="max-width: 800px;">
			<h2><?php esc_html_e( 'Impostazioni Generali di Prenotazione', 'free-bookly' ); ?></h2>
			<p style="color: #64748b; margin-bottom: 20px;">
				<?php esc_html_e( 'Configura il comportamento standard dell\'algoritmo di disponibilità e degli script.', 'free-bookly' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=general' ) ); ?>">
				<input type="hidden" name="action" value="fb_save_settings_general">
				<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>

				<div class="fb-form-group">
					<label for="fb_time_slot_length"><?php esc_html_e( 'Dimensione Fascia Oraria Standard (Time slot length)', 'free-bookly' ); ?></label>
					<?php $current_slot = FB_Settings::get_time_slot_length(); ?>
					<select id="fb_time_slot_length" name="fb_time_slot_length" style="min-width: 220px;">
						<option value="5" <?php selected( $current_slot, 5 ); ?>>5 <?php esc_html_e( 'minuti', 'free-bookly' ); ?></option>
						<option value="10" <?php selected( $current_slot, 10 ); ?>>10 <?php esc_html_e( 'minuti', 'free-bookly' ); ?></option>
						<option value="15" <?php selected( $current_slot, 15 ); ?>>15 <?php esc_html_e( 'minuti (Consigliato)', 'free-bookly' ); ?></option>
						<option value="20" <?php selected( $current_slot, 20 ); ?>>20 <?php esc_html_e( 'minuti', 'free-bookly' ); ?></option>
						<option value="30" <?php selected( $current_slot, 30 ); ?>>30 <?php esc_html_e( 'minuti', 'free-bookly' ); ?></option>
						<option value="45" <?php selected( $current_slot, 45 ); ?>>45 <?php esc_html_e( 'minuti', 'free-bookly' ); ?></option>
						<option value="60" <?php selected( $current_slot, 60 ); ?>>1 <?php esc_html_e( 'ora (60 minuti)', 'free-bookly' ); ?></option>
					</select>
					<small style="display: block; color: #64748b; margin-top: 4px;">
						<?php esc_html_e( 'Questo valore determina l\'intervallo tra gli slot di inizio prenotabili (es. 10:00, 10:15, 10:30 anche per servizi di 1 ora). Può essere sovrascritto per singoli servizi.', 'free-bookly' ); ?>
					</small>
				</div>

				<div class="fb-form-group">
					<label for="fb_default_appointment_status"><?php esc_html_e( 'Stato Predefinito del Nuovo Appuntamento', 'free-bookly' ); ?></label>
					<?php $status_default = FB_Settings::get_default_appointment_status(); ?>
					<select id="fb_default_appointment_status" name="fb_default_appointment_status" style="min-width: 220px;">
						<option value="approved" <?php selected( $status_default, 'approved' ); ?>><?php esc_html_e( 'Confermato (Approved)', 'free-bookly' ); ?></option>
						<option value="pending" <?php selected( $status_default, 'pending' ); ?>><?php esc_html_e( 'In attesa di approvazione (Pending)', 'free-bookly' ); ?></option>
					</select>
					<small style="display: block; color: #64748b; margin-top: 4px;">
						<?php esc_html_e( 'Determina se gli appuntamenti creati dai clienti sul sito sono subito approvati o richiedono revisione dall\'amministratore.', 'free-bookly' ); ?>
					</small>
				</div>

				<div class="fb-form-group">
					<label for="fb_script_loading"><?php esc_html_e( 'Metodo di Caricamento Script e Stili CSS', 'free-bookly' ); ?></label>
					<?php $script_loading = FB_Settings::get_script_loading(); ?>
					<select id="fb_script_loading" name="fb_script_loading" style="min-width: 220px;">
						<option value="all_pages" <?php selected( $script_loading, 'all_pages' ); ?>><?php esc_html_e( 'Tutte le pagine (Massima compatibilità con temi/builder)', 'free-bookly' ); ?></option>
						<option value="booking_pages" <?php selected( $script_loading, 'booking_pages' ); ?>><?php esc_html_e( 'Solo pagine con form Free Bookly (Massima velocità frontend)', 'free-bookly' ); ?></option>
					</select>
				</div>

				<p class="submit" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Salva Impostazioni Generali', 'free-bookly' ); ?></button>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<!-- TAB 3: ORARI LAVORATIVI AZIENDALI -->
	<?php if ( 'working_hours' === $active_tab ) : ?>
		<?php $company_hours = FB_Settings::get_company_working_hours(); ?>
		<div class="fb-card">
			<h2><?php esc_html_e( 'Orari di Lavoro Aziendali Predefiniti', 'free-bookly' ); ?></h2>
			<p style="color: #64748b; margin-bottom: 20px;">
				<?php esc_html_e( 'Questi orari definiscono i giorni e le fasce di apertura dell\'attività e vengono ereditati automaticamente da tutti i nuovi membri dello staff creati.', 'free-bookly' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=working_hours' ) ); ?>">
				<input type="hidden" name="action" value="fb_save_settings_working_hours">
				<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>

				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th style="width: 130px; font-weight: 600;"><?php esc_html_e( 'Giorno', 'free-bookly' ); ?></th>
							<th style="width: 140px; font-weight: 600;"><?php esc_html_e( 'Stato', 'free-bookly' ); ?></th>
							<th style="font-weight: 600;"><?php esc_html_e( 'Orario Lavorativo (Inizio - Fine)', 'free-bookly' ); ?></th>
							<th style="font-weight: 600;"><?php esc_html_e( 'Pausa Pranzo / Intervallo Predefinito', 'free-bookly' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php for ( $day = 1; $day <= 7; $day++ ) : 
							$dh = isset( $company_hours[ $day ] ) ? $company_hours[ $day ] : array();
							$is_work = ! empty( $dh['is_working'] );
							$st = ! empty( $dh['start_time'] ) ? $dh['start_time'] : '09:00';
							$et = ! empty( $dh['end_time'] ) ? $dh['end_time'] : '18:00';
							$bst = ! empty( $dh['break_start'] ) ? $dh['break_start'] : '';
							$bet = ! empty( $dh['break_end'] ) ? $dh['break_end'] : '';
						?>
							<tr>
								<td><strong><?php echo esc_html( $day_names[ $day ] ); ?></strong></td>
								<td>
									<label>
										<input type="checkbox" name="days[<?php echo esc_attr( $day ); ?>][is_working]" value="1" <?php checked( $is_work ); ?>>
										<?php esc_html_e( 'Aperto', 'free-bookly' ); ?>
									</label>
								</td>
								<td>
									<div style="display: flex; align-items: center; gap: 8px;">
										<input type="time" name="days[<?php echo esc_attr( $day ); ?>][start_time]" value="<?php echo esc_attr( $st ); ?>">
										<span>&rarr;</span>
										<input type="time" name="days[<?php echo esc_attr( $day ); ?>][end_time]" value="<?php echo esc_attr( $et ); ?>">
									</div>
								</td>
								<td>
									<div style="display: flex; align-items: center; gap: 8px;">
										<input type="time" name="days[<?php echo esc_attr( $day ); ?>][break_start]" value="<?php echo esc_attr( $bst ); ?>" placeholder="13:00">
										<span>&rarr;</span>
										<input type="time" name="days[<?php echo esc_attr( $day ); ?>][break_end]" value="<?php echo esc_attr( $bet ); ?>" placeholder="14:00">
										<small style="color: #64748b;"><?php esc_html_e( '(opzionale)', 'free-bookly' ); ?></small>
									</div>
								</td>
							</tr>
						<?php endfor; ?>
					</tbody>
				</table>

				<p class="submit" style="margin-top: 24px;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Salva Orari Aziendali', 'free-bookly' ); ?></button>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<!-- TAB 4: GIORNI FESTIVI -->
	<?php if ( 'holidays' === $active_tab ) : ?>
		<?php $holidays = FB_Holiday::get_company_holidays(); ?>
		<div style="display: flex; gap: 24px; flex-wrap: wrap;">
			<!-- Modulo aggiunta festività -->
			<div class="fb-card" style="flex: 1; min-width: 320px; max-width: 400px; height: fit-content;">
				<h2><?php esc_html_e( 'Aggiungi Chiusura / Festività', 'free-bookly' ); ?></h2>
				<p style="color: #64748b; font-size: 13px;">
					<?php esc_html_e( 'Nelle date indicate l\'attività risulterà chiusa per tutti i collaboratori e nessuna prenotazione sarà consentita.', 'free-bookly' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=holidays' ) ); ?>">
					<input type="hidden" name="action" value="fb_add_company_holiday">
					<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>

					<div class="fb-form-group">
						<label for="holiday_date"><?php esc_html_e( 'Data di Chiusura', 'free-bookly' ); ?></label>
						<input type="date" id="holiday_date" name="holiday_date" required style="width: 100%;">
					</div>

					<div class="fb-form-group">
						<label for="holiday_title"><?php esc_html_e( 'Nome / Festività (es. Natale, Chiusura Estiva)', 'free-bookly' ); ?></label>
						<input type="text" id="holiday_title" name="holiday_title" class="regular-text" style="width: 100%;" required>
					</div>

					<div class="fb-form-group">
						<label>
							<input type="checkbox" name="repeat_yearly" value="1">
							<?php esc_html_e( 'Ripeti ogni anno (festività ricorrente)', 'free-bookly' ); ?>
						</label>
					</div>

					<p class="submit" style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
						<button type="submit" class="button button-primary" style="width: 100%;"><?php esc_html_e( 'Aggiungi Festività', 'free-bookly' ); ?></button>
					</p>
				</form>
			</div>

			<!-- Tabella festività attive -->
			<div class="fb-card" style="flex: 2; min-width: 400px;">
				<h2><?php esc_html_e( 'Festività Aziendali Registrate', 'free-bookly' ); ?></h2>
				<?php if ( empty( $holidays ) ) : ?>
					<p style="color: #64748b; font-style: italic;"><?php esc_html_e( 'Nessuna festività configurata. Tutti i giorni feriali risultano aperti secondo gli orari standard.', 'free-bookly' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Data', 'free-bookly' ); ?></th>
								<th><?php esc_html_e( 'Descrizione', 'free-bookly' ); ?></th>
								<th><?php esc_html_e( 'Ricorrenza', 'free-bookly' ); ?></th>
								<th style="width: 80px; text-align: center;"><?php esc_html_e( 'Azione', 'free-bookly' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $holidays as $h ) : ?>
								<tr>
									<td><strong><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $h->holiday_date ) ) ); ?></strong></td>
									<td><?php echo esc_html( $h->title ); ?></td>
									<td>
										<?php if ( $h->repeat_yearly ) : ?>
											<span class="fb-badge" style="background: #e0f2fe; color: #0369a1;"><?php esc_html_e( 'Annuale', 'free-bookly' ); ?></span>
										<?php else : ?>
											<span class="fb-badge" style="background: #f1f5f9; color: #475569;"><?php esc_html_e( 'Una tantum', 'free-bookly' ); ?></span>
										<?php endif; ?>
									</td>
									<td style="text-align: center;">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-settings&tab=holidays&action=fb_delete_holiday&id=' . $h->id ), 'fb_delete_holiday_action' ) ); ?>" 
										   class="button button-small" 
										   style="color: #b91c1c;"
										   onclick="return confirm('<?php esc_attr_e( 'Eliminare questa festività?', 'free-bookly' ); ?>');">
											<?php esc_html_e( 'Elimina', 'free-bookly' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- TAB 6: NOTIFICHE EMAIL -->
	<?php if ( 'notifications' === $active_tab ) : ?>
		<?php
		require_once FREE_BOOKLY_PLUGIN_DIR . 'includes/modules/class-fb-email-notifications.php';
		$module_manager   = new Free_Bookly_Module_Manager();
		$module_active    = in_array( 'email_notifications', $module_manager->get_active_modules(), true );
		$templates        = FB_Email_Notifications::get_templates();
		$template_labels  = FB_Email_Notifications::get_template_labels();
		$placeholders     = FB_Email_Notifications::get_placeholders();
		$email_log        = FB_Email_Notifications::get_log();
		$enable_flags     = array(
			'customer_created'   => __( 'Invia al cliente la ricezione della prenotazione', 'free-bookly' ),
			'customer_approved'  => __( 'Invia al cliente la conferma quando la prenotazione viene approvata', 'free-bookly' ),
			'customer_cancelled' => __( 'Invia al cliente la comunicazione di annullamento', 'free-bookly' ),
			'staff_created'      => __( 'Invia a collaboratore e amministratore la notifica di nuova prenotazione', 'free-bookly' ),
			'attach_ics'         => __( 'Allega il file calendario (.ics) compatibile con Google, Apple e Outlook', 'free-bookly' ),
		);
		?>

		<?php if ( isset( $_GET['test_sent'] ) ) : ?>
			<div class="notice <?php echo '1' === $_GET['test_sent'] ? 'notice-success' : 'notice-error'; ?> is-dismissible">
				<p><?php echo '1' === $_GET['test_sent'] ? esc_html__( 'Email di prova inviata.', 'free-bookly' ) : esc_html__( 'Invio email di prova non riuscito: verifica la configurazione email (SMTP) del server.', 'free-bookly' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! $module_active ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'Il modulo Notifiche Email è disattivato: nessuna email verrà inviata.', 'free-bookly' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-modules' ) ); ?>"><?php esc_html_e( 'Attivalo da Moduli Extra', 'free-bookly' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=notifications' ) ); ?>">
			<input type="hidden" name="action" value="fb_save_settings_notifications">
			<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>

			<div class="fb-card" style="max-width: 900px;">
				<h2><?php esc_html_e( 'Mittente e Abilitazioni', 'free-bookly' ); ?></h2>

				<div class="fb-form-group">
					<label for="fb_email_from_name"><?php esc_html_e( 'Nome mittente', 'free-bookly' ); ?></label>
					<input type="text" id="fb_email_from_name" name="fb_email_from_name" class="regular-text" value="<?php echo esc_attr( FB_Settings::get( 'email_from_name', FB_Settings::get( 'company_name', get_bloginfo( 'name' ) ) ) ); ?>">
				</div>
				<div class="fb-form-group">
					<label for="fb_email_from_address"><?php esc_html_e( 'Indirizzo email mittente', 'free-bookly' ); ?></label>
					<input type="email" id="fb_email_from_address" name="fb_email_from_address" class="regular-text" value="<?php echo esc_attr( FB_Settings::get( 'email_from_address', FB_Settings::get( 'company_email', get_bloginfo( 'admin_email' ) ) ) ); ?>">
				</div>

				<?php foreach ( $enable_flags as $flag => $label ) : ?>
					<div class="fb-form-group">
						<label>
							<input type="checkbox" id="fb_email_enable_<?php echo esc_attr( $flag ); ?>" name="fb_email_enable[<?php echo esc_attr( $flag ); ?>]" value="1" <?php checked( FB_Email_Notifications::is_enabled( $flag ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="fb-card" style="max-width: 900px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Modelli dei Messaggi', 'free-bookly' ); ?></h2>
				<p style="color: #64748b;">
					<?php esc_html_e( 'Segnaposto disponibili (da usare in oggetto e testo):', 'free-bookly' ); ?>
					<?php foreach ( $placeholders as $ph => $desc ) : ?>
						<code title="<?php echo esc_attr( $desc ); ?>"><?php echo esc_html( $ph ); ?></code>
					<?php endforeach; ?>
				</p>

				<?php foreach ( $template_labels as $key => $label ) : ?>
					<h3 style="margin-top: 24px;"><?php echo esc_html( $label ); ?></h3>
					<div class="fb-form-group">
						<label for="fb_tpl_<?php echo esc_attr( $key ); ?>_subject"><?php esc_html_e( 'Oggetto', 'free-bookly' ); ?></label>
						<input type="text" id="fb_tpl_<?php echo esc_attr( $key ); ?>_subject" name="fb_tpl[<?php echo esc_attr( $key ); ?>][subject]" class="large-text" value="<?php echo esc_attr( $templates[ $key ]['subject'] ); ?>">
					</div>
					<div class="fb-form-group">
						<label for="fb_tpl_<?php echo esc_attr( $key ); ?>_body"><?php esc_html_e( 'Testo', 'free-bookly' ); ?></label>
						<textarea id="fb_tpl_<?php echo esc_attr( $key ); ?>_body" name="fb_tpl[<?php echo esc_attr( $key ); ?>][body]" rows="9" class="large-text"><?php echo esc_textarea( $templates[ $key ]['body'] ); ?></textarea>
					</div>
				<?php endforeach; ?>

				<p class="submit" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Salva Notifiche', 'free-bookly' ); ?></button>
					<button type="submit" name="fb_restore_defaults" value="1" class="button" onclick="return confirm('<?php esc_attr_e( 'Ripristinare i modelli predefiniti?', 'free-bookly' ); ?>');"><?php esc_html_e( 'Ripristina modelli predefiniti', 'free-bookly' ); ?></button>
				</p>
			</div>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=notifications' ) ); ?>" class="fb-card" style="max-width: 900px; margin-top: 20px;">
			<h2><?php esc_html_e( 'Email di Prova', 'free-bookly' ); ?></h2>
			<input type="hidden" name="action" value="fb_send_test_email">
			<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>
			<div class="fb-form-group">
				<label for="fb_test_email_to"><?php esc_html_e( 'Invia a', 'free-bookly' ); ?></label>
				<input type="email" id="fb_test_email_to" name="fb_test_email_to" class="regular-text" required value="<?php echo esc_attr( FB_Settings::get( 'company_email', get_bloginfo( 'admin_email' ) ) ); ?>">
			</div>
			<button type="submit" id="fb_send_test_btn" class="button"><?php esc_html_e( 'Invia email di prova', 'free-bookly' ); ?></button>
		</form>

		<div class="fb-card" style="max-width: 900px; margin-top: 20px;">
			<h2><?php esc_html_e( 'Registro Ultimi Invii', 'free-bookly' ); ?></h2>
			<?php if ( empty( $email_log ) ) : ?>
				<p style="color: #64748b; font-style: italic;"><?php esc_html_e( 'Nessuna email inviata finora.', 'free-bookly' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat striped" id="fb-email-log">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Data', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Modello', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Destinatario', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Oggetto', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( '.ics', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Esito', 'free-bookly' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $email_log as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( $entry['time'] ); ?></td>
								<td><?php echo esc_html( $entry['template'] ); ?></td>
								<td><?php echo esc_html( $entry['to'] ); ?></td>
								<td><?php echo esc_html( $entry['subject'] ); ?></td>
								<td><?php echo $entry['ics'] ? '&#10003;' : '&ndash;'; ?></td>
								<td><?php echo $entry['sent'] ? esc_html__( 'Inviata', 'free-bookly' ) : esc_html__( 'Errore', 'free-bookly' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- TAB 5: PAGAMENTI E VALUTA -->
	<?php if ( 'payments' === $active_tab ) : ?>
		<div class="fb-card" style="max-width: 800px;">
			<h2><?php esc_html_e( 'Formato Valuta e Opzioni di Pagamento', 'free-bookly' ); ?></h2>
			<p style="color: #64748b; margin-bottom: 20px;">
				<?php esc_html_e( 'Configura la valuta e la formattazione dei prezzi mostrati nei servizi e nel frontend.', 'free-bookly' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=payments' ) ); ?>">
				<input type="hidden" name="action" value="fb_save_settings_payments">
				<?php wp_nonce_field( 'fb_save_settings_action', 'fb_settings_nonce' ); ?>

				<div class="fb-form-group">
					<label for="fb_currency"><?php esc_html_e( 'Valuta Predefinita', 'free-bookly' ); ?></label>
					<?php $curr = FB_Settings::get_currency(); ?>
					<select id="fb_currency" name="fb_currency" style="min-width: 240px;">
						<option value="EUR" <?php selected( $curr, 'EUR' ); ?>>EUR (€ - Euro)</option>
						<option value="USD" <?php selected( $curr, 'USD' ); ?>>USD ($ - Dollaro USA)</option>
						<option value="GBP" <?php selected( $curr, 'GBP' ); ?>>GBP (£ - Sterlina Britannica)</option>
						<option value="CHF" <?php selected( $curr, 'CHF' ); ?>>CHF (Franco Svizzero)</option>
						<option value="CAD" <?php selected( $curr, 'CAD' ); ?>>CAD (CA$ - Dollaro Canadese)</option>
						<option value="AUD" <?php selected( $curr, 'AUD' ); ?>>AUD (AU$ - Dollaro Australiano)</option>
					</select>
				</div>

				<div class="fb-form-group">
					<label for="fb_currency_format"><?php esc_html_e( 'Formato del Prezzo', 'free-bookly' ); ?></label>
					<?php $curr_format = FB_Settings::get( 'currency_format', '{price} €' ); ?>
					<select id="fb_currency_format" name="fb_currency_format" style="min-width: 240px;">
						<option value="{price} €" <?php selected( $curr_format, '{price} €' ); ?>>50,00 € (Simbolo dopo il prezzo)</option>
						<option value="€ {price}" <?php selected( $curr_format, '€ {price}' ); ?>>€ 50,00 (Simbolo prima del prezzo)</option>
					</select>
				</div>

				<div class="fb-card" style="background: #f8fafc; border: 1px dashed #cbd5e1; margin-top: 16px;">
					<strong><?php esc_html_e( 'Anteprima Prezzo Formattato:', 'free-bookly' ); ?></strong>
					<span style="font-size: 18px; font-weight: 700; color: #0284c7; margin-left: 12px;">
						<?php echo esc_html( FB_Settings::format_price( 49.90 ) ); ?>
					</span>
				</div>

				<p class="submit" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Salva Impostazioni Pagamenti', 'free-bookly' ); ?></button>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<!-- TAB 7: CAMPI PERSONALIZZATI (CUSTOM FIELDS) -->
	<?php if ( 'custom_fields' === $active_tab ) : ?>
		<?php
		require_once FREE_BOOKLY_PLUGIN_DIR . 'includes/class-fb-custom-field.php';
		require_once FREE_BOOKLY_PLUGIN_DIR . 'includes/class-fb-service.php';

		$all_services = FB_Service::get_all();
		$all_custom_fields = FB_Custom_Field::get_all();
		$edit_cf_id = isset( $_GET['edit_field'] ) ? absint( $_GET['edit_field'] ) : 0;
		$cf_to_edit = $edit_cf_id ? FB_Custom_Field::get_by_id( $edit_cf_id ) : null;
		?>

		<div class="fb-layout-columns" style="margin-top: 20px;">
			<!-- Form Aggiungi/Modifica Campo -->
			<div class="fb-col-form" style="max-width: 400px;">
				<div class="fb-card">
					<h2><?php echo $cf_to_edit ? esc_html__( 'Modifica Campo Personalizzato', 'free-bookly' ) : esc_html__( 'Aggiungi Campo Personalizzato', 'free-bookly' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=custom_fields' ) ); ?>">
						<?php wp_nonce_field( 'fb_save_custom_field_action', 'fb_custom_field_nonce' ); ?>
						<input type="hidden" name="action" value="fb_save_custom_field">
						<?php if ( $cf_to_edit ) : ?>
							<input type="hidden" name="field_id" value="<?php echo esc_attr( $cf_to_edit->id ); ?>">
						<?php endif; ?>

						<div class="fb-form-group">
							<label for="fb_cf_label"><?php esc_html_e( 'Etichetta del Campo *', 'free-bookly' ); ?></label>
							<input type="text" id="fb_cf_label" name="label" required value="<?php echo $cf_to_edit ? esc_attr( $cf_to_edit->label ) : ''; ?>" placeholder="<?php esc_attr_e( 'Es. Note aggiuntive, Codice Fiscale', 'free-bookly' ); ?>">
						</div>

						<div class="fb-form-group">
							<label for="fb_cf_type"><?php esc_html_e( 'Tipo di Campo', 'free-bookly' ); ?></label>
							<select id="fb_cf_type" name="field_type" style="width: 100%;">
								<?php foreach ( FB_Custom_Field::$allowed_types as $t_key => $t_label ) : ?>
									<option value="<?php echo esc_attr( $t_key ); ?>" <?php selected( $cf_to_edit ? $cf_to_edit->field_type : 'text', $t_key ); ?>>
										<?php echo esc_html( $t_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="fb-form-group" id="fb_cf_options_group">
							<label for="fb_cf_options"><?php esc_html_e( 'Opzioni Scelta (Select, Radio, Checkbox)', 'free-bookly' ); ?></label>
							<textarea id="fb_cf_options" name="options" rows="4" style="width: 100%;" placeholder="<?php esc_attr_e( 'Inserisci un\'opzione per riga:&#10;Opzione 1&#10;Opzione 2&#10;Opzione 3', 'free-bookly' ); ?>"><?php echo $cf_to_edit ? esc_textarea( $cf_to_edit->options ) : ''; ?></textarea>
							<small style="color: #64748b;"><?php esc_html_e( 'Utilizzato solo per campi con opzioni multiple. Una voce per riga.', 'free-bookly' ); ?></small>
						</div>

						<div class="fb-form-group">
							<label for="fb_cf_service"><?php esc_html_e( 'Servizio Collegato', 'free-bookly' ); ?></label>
							<select id="fb_cf_service" name="service_id" style="width: 100%;">
								<option value="0"><?php esc_html_e( 'Tutti i servizi (Globale)', 'free-bookly' ); ?></option>
								<?php foreach ( $all_services as $srv ) : ?>
									<option value="<?php echo esc_attr( $srv->id ); ?>" <?php selected( $cf_to_edit ? $cf_to_edit->service_id : 0, $srv->id ); ?>>
										<?php echo esc_html( $srv->title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="fb-form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 12px;">
							<input type="checkbox" id="fb_cf_required" name="is_required" value="1" <?php checked( $cf_to_edit ? $cf_to_edit->is_required : 0, 1 ); ?>>
							<label for="fb_cf_required" style="margin: 0; font-weight: 500; cursor: pointer;"><?php esc_html_e( 'Campo Obbligatorio', 'free-bookly' ); ?></label>
						</div>

						<div class="fb-form-group" style="margin-top: 12px;">
							<label for="fb_cf_sort"><?php esc_html_e( 'Ordine di Visualizzazione', 'free-bookly' ); ?></label>
							<input type="number" id="fb_cf_sort" name="sort_order" value="<?php echo $cf_to_edit ? esc_attr( $cf_to_edit->sort_order ) : '0'; ?>" min="0" step="1">
						</div>

						<div class="fb-form-actions" style="margin-top: 20px;">
							<button type="submit" id="fb_save_cf_btn" class="button button-primary"><?php echo $cf_to_edit ? esc_html__( 'Aggiorna Campo', 'free-bookly' ) : esc_html__( 'Salva Campo Personalizzato', 'free-bookly' ); ?></button>
							<?php if ( $cf_to_edit ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=custom_fields' ) ); ?>" class="button"><?php esc_html_e( 'Annulla', 'free-bookly' ); ?></a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>

			<!-- Tabella Campi Esistenti -->
			<div class="fb-col-table">
				<div class="fb-card">
					<h2><?php esc_html_e( 'Campi Personalizzati Attivi', 'free-bookly' ); ?></h2>
					<table class="wp-list-table widefat striped fb-admin-table">
						<thead>
							<tr>
								<th style="width: 45px;">ID</th>
								<th><?php esc_html_e( 'Etichetta', 'free-bookly' ); ?></th>
								<th><?php esc_html_e( 'Tipo', 'free-bookly' ); ?></th>
								<th><?php esc_html_e( 'Servizio', 'free-bookly' ); ?></th>
								<th style="width: 80px; text-align: center;"><?php esc_html_e( 'Obbligo', 'free-bookly' ); ?></th>
								<th style="width: 60px; text-align: center;"><?php esc_html_e( 'Ordine', 'free-bookly' ); ?></th>
								<th style="width: 130px; text-align: center;"><?php esc_html_e( 'Azioni', 'free-bookly' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $all_custom_fields ) ) : ?>
								<tr>
									<td colspan="7"><?php esc_html_e( 'Nessun campo personalizzato definito. Utilizza il modulo a sinistra per aggiungerne uno.', 'free-bookly' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $all_custom_fields as $cf ) : ?>
									<tr>
										<td><?php echo esc_html( $cf->id ); ?></td>
										<td><strong><?php echo esc_html( $cf->label ); ?></strong></td>
										<td>
											<span class="fb-badge fb-badge-secondary">
												<?php echo esc_html( isset( FB_Custom_Field::$allowed_types[ $cf->field_type ] ) ? FB_Custom_Field::$allowed_types[ $cf->field_type ] : $cf->field_type ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $cf->get_service_name() ); ?></td>
										<td style="text-align: center;">
											<?php if ( $cf->is_required ) : ?>
												<span class="fb-badge" style="background: #fee2e2; color: #b91c1c;"><?php esc_html_e( 'Sì', 'free-bookly' ); ?></span>
											<?php else : ?>
												<span style="color: #94a3b8;"><?php esc_html_e( 'No', 'free-bookly' ); ?></span>
											<?php endif; ?>
										</td>
										<td style="text-align: center;"><?php echo esc_html( $cf->sort_order ); ?></td>
										<td style="text-align: center;">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-settings&tab=custom_fields&edit_field=' . $cf->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Modifica', 'free-bookly' ); ?></a>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-settings&tab=custom_fields&action=fb_delete_custom_field&field_id=' . $cf->id ), 'fb_delete_custom_field_action' ) ); ?>" class="button button-small fb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Eliminare questo campo personalizzato?', 'free-bookly' ); ?>');"><?php esc_html_e( 'Elimina', 'free-bookly' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif; ?>

</div>
