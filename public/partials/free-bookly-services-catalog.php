<?php
/**
 * Modulo di Prenotazione Moderno: Catalogo Servizi (Services Catalog View)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories = FB_Category::get_all();
$services   = FB_Service::get_all( true ); // solo pubblici
?>

<div class="fb-modern-catalog-container">
	<div class="fb-catalog-header">
		<h2><?php esc_html_e( 'I Nostri Servizi', 'free-bookly' ); ?></h2>
		<p style="color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Scegli il servizio che desideri prenotare', 'free-bookly' ); ?></p>
	</div>

	<!-- Filtro Categorie a Pulsanti -->
	<?php if ( count( $categories ) > 1 ) : ?>
		<div class="fb-category-filter-pills">
			<button class="fb-pill-btn active" data-category="0"><?php esc_html_e( 'Tutti i Servizi', 'free-bookly' ); ?></button>
			<?php foreach ( $categories as $cat ) : ?>
				<button class="fb-pill-btn" data-category="<?php echo esc_attr( $cat->id ); ?>">
					<?php echo esc_html( $cat->name ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- Griglia dei Servizi -->
	<div class="fb-services-grid">
		<?php if ( empty( $services ) ) : ?>
			<p style="color: #64748b;"><?php esc_html_e( 'Nessun servizio disponibile.', 'free-bookly' ); ?></p>
		<?php else : ?>
			<?php foreach ( $services as $service ) : ?>
				<div class="fb-service-card" data-category-id="<?php echo esc_attr( $service->category_id ); ?>">
					<div class="fb-card-thumb" style="background-color: <?php echo esc_attr( $service->color ); ?>;">
						<?php if ( ! empty( $service->image_url ) ) : ?>
							<img src="<?php echo esc_url( $service->image_url ); ?>" alt="<?php echo esc_attr( $service->title ); ?>" />
						<?php else : ?>
							<span class="dashicons dashicons-calendar-alt fb-thumb-placeholder"></span>
						<?php endif; ?>
					</div>

					<div class="fb-card-body">
						<div class="fb-card-category"><?php echo esc_html( $service->get_category_name() ); ?></div>
						<h3 class="fb-card-title"><?php echo esc_html( $service->title ); ?></h3>
						
						<?php if ( ! empty( $service->info ) ) : ?>
							<p class="fb-card-info"><?php echo esc_html( wp_trim_words( $service->info, 16 ) ); ?></p>
						<?php endif; ?>

						<div class="fb-card-footer">
							<div class="fb-card-meta">
								<span class="fb-meta-duration"><span class="dashicons dashicons-clock"></span> <?php echo esc_html( $service->duration ); ?> min</span>
								<span class="fb-meta-price"><?php echo esc_html( $service->get_formatted_price() ); ?></span>
							</div>
							<button class="fb-btn fb-btn-book-service" data-service-id="<?php echo esc_attr( $service->id ); ?>" data-category-id="<?php echo esc_attr( $service->category_id ); ?>">
								<?php esc_html_e( 'Prenota Ora', 'free-bookly' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Modale Inline per il completamento della prenotazione -->
	<div id="fb-catalog-modal" class="fb-modal" style="display:none;">
		<div class="fb-modal-overlay"></div>
		<div class="fb-modal-content">
			<button class="fb-modal-close" aria-label="Chiudi">&times;</button>
			<div id="fb-catalog-wizard-mount"></div>
		</div>
	</div>
</div>
