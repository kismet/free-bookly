<?php
/**
 * Modulo di Prenotazione Moderno: Ricerca per Data (Search-First Form)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$services = FB_Service::get_all( true );
?>

<div class="fb-modern-search-container">
	<div class="fb-catalog-header">
		<h2><?php esc_html_e( 'Cerca Disponibilità per Data', 'free-bookly' ); ?></h2>
		<p style="color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Indica quando desideri effettuare l\'appuntamento e scopri gli orari liberi', 'free-bookly' ); ?></p>
	</div>

	<div class="fb-search-bar-card">
		<div class="fb-search-fields-grid">
			<div class="fb-search-col">
				<label for="fb-search-date"><strong><?php esc_html_e( 'Data Desiderata', 'free-bookly' ); ?></strong></label>
				<input type="date" id="fb-search-date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
			</div>

			<div class="fb-search-col">
				<label for="fb-search-service"><strong><?php esc_html_e( 'Servizio Richiesto', 'free-bookly' ); ?></strong></label>
				<select id="fb-search-service">
					<?php foreach ( $services as $s ) : ?>
						<option value="<?php echo esc_attr( $s->id ); ?>" data-duration="<?php echo esc_attr( $s->duration ); ?>">
							<?php echo esc_html( $s->title ); ?> (<?php echo esc_html( $s->get_formatted_price() ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="fb-search-col">
				<label for="fb-search-timeframe"><strong><?php esc_html_e( 'Fascia Oraria', 'free-bookly' ); ?></strong></label>
				<select id="fb-search-timeframe">
					<option value="all"><?php esc_html_e( 'Tutto il Giorno (09:00 - 18:00)', 'free-bookly' ); ?></option>
					<option value="morning"><?php esc_html_e( 'Mattina (08:00 - 13:00)', 'free-bookly' ); ?></option>
					<option value="afternoon"><?php esc_html_e( 'Pomeriggio (13:00 - 18:00)', 'free-bookly' ); ?></option>
					<option value="evening"><?php esc_html_e( 'Sera (18:00 - 22:00)', 'free-bookly' ); ?></option>
				</select>
			</div>

			<div class="fb-search-col fb-search-btn-col">
				<label>&nbsp;</label>
				<button class="fb-btn" id="fb-btn-execute-search" style="width: 100%;">
					<span class="dashicons dashicons-search" style="margin-right: 4px; line-height: 1.4;"></span>
					<?php esc_html_e( 'Cerca Slot', 'free-bookly' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Risultati Ricerca -->
	<div id="fb-search-results" style="margin-top: 24px; display: none;">
		<h3><?php esc_html_e( 'Orari Disponibili Trovati:', 'free-bookly' ); ?></h3>
		<div id="fb-search-slots-grid" class="fb-slots-container"></div>
	</div>

	<!-- Modale di completamento dati utente -->
	<div id="fb-search-modal" class="fb-modal" style="display:none;">
		<div class="fb-modal-overlay"></div>
		<div class="fb-modal-content">
			<button class="fb-modal-close" aria-label="Chiudi">&times;</button>
			<div id="fb-search-wizard-mount"></div>
		</div>
	</div>
</div>
