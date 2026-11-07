<?php
/**
 * Modulo di Prenotazione Classico (Step-by-step Booking Wizard)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pre_service_id  = isset( $atts['service_id'] ) ? absint( $atts['service_id'] ) : 0;
$pre_category_id = isset( $atts['category_id'] ) ? absint( $atts['category_id'] ) : 0;
$pre_staff_id    = isset( $atts['staff_id'] ) ? sanitize_text_field( $atts['staff_id'] ) : 'any';
?>

<div id="free-bookly-wizard" class="fb-wizard-container" 
     data-pre-service="<?php echo esc_attr( $pre_service_id ); ?>" 
     data-pre-category="<?php echo esc_attr( $pre_category_id ); ?>" 
     data-pre-staff="<?php echo esc_attr( $pre_staff_id ); ?>">
	
	<!-- Progress Bar -->
	<div class="fb-progress">
		<div class="fb-step-indicator active" data-step="1"><?php esc_html_e( '1. Servizio', 'free-bookly' ); ?></div>
		<div class="fb-step-indicator" data-step="2"><?php esc_html_e( '2. Data e Ora', 'free-bookly' ); ?></div>
		<div class="fb-step-indicator" data-step="3"><?php esc_html_e( '3. Dettagli', 'free-bookly' ); ?></div>
		<div class="fb-step-indicator" data-step="4"><?php esc_html_e( '4. Conferma', 'free-bookly' ); ?></div>
	</div>

	<!-- Step 1: Selezione Categoria, Servizio e Staff -->
	<div class="fb-step-content active" id="fb-step-1">
		<div class="fb-field-block">
			<label for="fb-category-select"><strong><?php esc_html_e( 'Categoria di Servizio', 'free-bookly' ); ?></strong></label>
			<select id="fb-category-select">
				<option value="0"><?php esc_html_e( '-- Tutte le Categorie --', 'free-bookly' ); ?></option>
			</select>
			<div id="fb-category-info" class="fb-info-box" style="display:none;"></div>
		</div>

		<div class="fb-field-block" style="margin-top: 16px;">
			<label for="fb-service-select"><strong><?php esc_html_e( 'Scegli il Servizio *', 'free-bookly' ); ?></strong></label>
			<select id="fb-service-select" required>
				<option value=""><?php esc_html_e( '-- Seleziona un Servizio --', 'free-bookly' ); ?></option>
			</select>
			<div id="fb-service-info" class="fb-info-box" style="display:none;"></div>
		</div>

		<!-- Container Servizi Extra -->
		<div id="fb-extras-container" class="fb-field-block" style="display:none; margin-top: 16px;">
			<label><strong><?php esc_html_e( 'Servizi Extra & Add-ons Opzionali', 'free-bookly' ); ?></strong></label>
			<div id="fb-extras-list" style="margin-top: 8px; display: flex; flex-direction: column; gap: 8px;"></div>
		</div>

		<div class="fb-field-block" style="margin-top: 16px;">
			<label for="fb-staff-select"><strong><?php esc_html_e( 'Scegli il Collaboratore (Opzionale)', 'free-bookly' ); ?></strong></label>
			<select id="fb-staff-select">
				<option value="any"><?php esc_html_e( 'Qualsiasi collaboratore disponibile', 'free-bookly' ); ?></option>
			</select>
			<div id="fb-staff-info" class="fb-info-box" style="display:none;"></div>
		</div>
		
		<div style="margin-top: 24px;">
			<button class="fb-btn fb-next-step" data-target="2" id="btn-to-step-2" disabled><?php esc_html_e( 'Avanti', 'free-bookly' ); ?></button>
		</div>
	</div>

	<!-- Step 2: Calendario e Selezione Slot -->
	<div class="fb-step-content" id="fb-step-2" style="display:none;">
		<div class="fb-field-block">
			<label for="fb-date-picker"><strong><?php esc_html_e( 'Seleziona la Data *', 'free-bookly' ); ?></strong></label>
			<input type="date" id="fb-date-picker" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" />
		</div>

		<div class="fb-field-block" style="margin-top: 20px;">
			<label><strong><?php esc_html_e( 'Orari Disponibili', 'free-bookly' ); ?></strong></label>
			<div id="fb-time-slots" class="fb-slots-container">
				<p class="fb-no-slots"><?php esc_html_e( 'Seleziona una data per visualizzare gli orari disponibili.', 'free-bookly' ); ?></p>
			</div>
		</div>

		<div style="margin-top: 24px; display: flex; gap: 12px;">
			<button class="fb-btn fb-btn-secondary fb-prev-step" data-target="1"><?php esc_html_e( 'Indietro', 'free-bookly' ); ?></button>
			<button class="fb-btn fb-next-step" data-target="3" disabled id="btn-to-step-3"><?php esc_html_e( 'Avanti', 'free-bookly' ); ?></button>
		</div>
	</div>

	<!-- Step 3: Dettagli Utente -->
	<div class="fb-step-content" id="fb-step-3" style="display:none;">
		<h3><?php esc_html_e( 'I tuoi dati di contatto', 'free-bookly' ); ?></h3>
		
		<div class="fb-field-block" style="margin-bottom: 12px;">
			<input type="text" id="fb-user-name" placeholder="<?php esc_attr_e( 'Nome Completo *', 'free-bookly' ); ?>" required />
		</div>
		<div class="fb-field-block" style="margin-bottom: 12px;">
			<input type="email" id="fb-user-email" placeholder="<?php esc_attr_e( 'Indirizzo Email *', 'free-bookly' ); ?>" required />
		</div>
		<div class="fb-field-block" style="margin-bottom: 12px;">
			<input type="tel" id="fb-user-phone" placeholder="<?php esc_attr_e( 'Numero di Telefono', 'free-bookly' ); ?>" />
		</div>

		<!-- Container Campi Personalizzati Dinamici -->
		<div id="fb-custom-fields-container" style="margin-top: 16px;"></div>

		<div style="margin-top: 24px; display: flex; gap: 12px;">
			<button class="fb-btn fb-btn-secondary fb-prev-step" data-target="2"><?php esc_html_e( 'Indietro', 'free-bookly' ); ?></button>
			<button class="fb-btn fb-next-step" data-target="4" id="btn-to-step-4"><?php esc_html_e( 'Avanti', 'free-bookly' ); ?></button>
		</div>
	</div>

	<!-- Step 4: Riepilogo & Conferma -->
	<div class="fb-step-content" id="fb-step-4" style="display:none;">
		<h3><?php esc_html_e( 'Riepilogo della Prenotazione', 'free-bookly' ); ?></h3>
		<div id="fb-summary" class="fb-summary-card"></div>
		
		<div style="margin-top: 24px; display: flex; gap: 12px;">
			<button class="fb-btn fb-btn-secondary fb-prev-step" data-target="3"><?php esc_html_e( 'Indietro', 'free-bookly' ); ?></button>
			<button class="fb-btn fb-submit" id="fb-confirm-btn"><?php esc_html_e( 'Conferma Prenotazione', 'free-bookly' ); ?></button>
		</div>
	</div>

	<!-- Messaggio di Successo Finale -->
	<div class="fb-step-content" id="fb-step-success" style="display:none; text-align: center; padding: 24px 12px;">
		<div style="font-size: 48px; color: #16a34a; margin-bottom: 12px;">&#10004;</div>
		<h2 style="color: #0f172a; margin-bottom: 8px;"><?php esc_html_e( 'Grazie per la tua prenotazione!', 'free-bookly' ); ?></h2>
		<p id="fb-success-message" style="color: #64748b; font-size: 15px;"></p>
		<div style="margin-top: 20px;">
			<button class="fb-btn" onclick="window.location.reload();"><?php esc_html_e( 'Effettua un\'altra prenotazione', 'free-bookly' ); ?></button>
		</div>
	</div>

</div>
