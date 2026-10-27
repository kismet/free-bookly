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

</div>
