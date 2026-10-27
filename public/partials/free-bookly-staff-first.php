<?php
/**
 * Modulo di Prenotazione Moderno: Staff-First (Team Providers View)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$staff_members = FB_Staff::get_all( false, true ); // solo attivi e pubblici
?>

<div class="fb-modern-staff-container">
	<div class="fb-catalog-header">
		<h2><?php esc_html_e( 'Il Nostro Team di Professionisti', 'free-bookly' ); ?></h2>
		<p style="color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Seleziona il collaboratore con cui desideri effettuare l\'appuntamento', 'free-bookly' ); ?></p>
	</div>

	<div class="fb-staff-grid">
		<?php if ( empty( $staff_members ) ) : ?>
			<p style="color: #64748b;"><?php esc_html_e( 'Nessun membro dello staff disponibile al momento.', 'free-bookly' ); ?></p>
		<?php else : ?>
			<?php foreach ( $staff_members as $staff ) : 
				$service_ids = $staff->get_service_ids();
			?>
				<div class="fb-staff-card">
					<div class="fb-staff-card-header" style="border-top: 4px solid <?php echo esc_attr( $staff->color ); ?>;">
						<div class="fb-staff-avatar-wrap">
							<?php if ( ! empty( $staff->avatar_url ) ) : ?>
								<img src="<?php echo esc_url( $staff->avatar_url ); ?>" alt="<?php echo esc_attr( $staff->full_name ); ?>" class="fb-staff-avatar" />
							<?php else : ?>
								<div class="fb-staff-avatar-fallback" style="background: <?php echo esc_attr( $staff->color ); ?>;">
									<?php echo esc_html( strtoupper( substr( $staff->full_name, 0, 1 ) ) ); ?>
								</div>
							<?php endif; ?>
						</div>

						<h3 class="fb-staff-name"><?php echo esc_html( $staff->full_name ); ?></h3>
						
						<?php if ( ! empty( $staff->info ) ) : ?>
							<p class="fb-staff-info"><?php echo esc_html( $staff->info ); ?></p>
						<?php endif; ?>
					</div>

					<div class="fb-staff-card-body">
						<div class="fb-staff-services-label"><?php esc_html_e( 'Servizi Erogati:', 'free-bookly' ); ?></div>
						<div class="fb-staff-services-tags">
							<?php if ( empty( $service_ids ) ) : ?>
								<span style="font-size: 11px; color: #94a3b8;"><?php esc_html_e( 'Tutti i servizi generali', 'free-bookly' ); ?></span>
							<?php else : ?>
								<?php foreach ( $service_ids as $sid ) : 
									$srv = FB_Service::get_by_id( $sid );
									if ( $srv && 'public' === $srv->visibility ) :
										$c_price = $staff->get_custom_price( $sid );
										$display_price = ( null !== $c_price ) ? FB_Settings::format_price( $c_price ) : $srv->get_formatted_price();
								?>
									<span class="fb-service-badge">
										<?php echo esc_html( $srv->title ); ?> (<?php echo esc_html( $display_price ); ?>)
									</span>
								<?php endif; endforeach; ?>
							<?php endif; ?>
						</div>
					</div>

					<div class="fb-staff-card-footer">
						<button class="fb-btn fb-btn-book-staff" data-staff-id="<?php echo esc_attr( $staff->id ); ?>">
							<?php echo esc_html( sprintf( __( 'Prenota con %s', 'free-bookly' ), $staff->full_name ) ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Modale Inline per il completamento della prenotazione -->
	<div id="fb-staff-modal" class="fb-modal" style="display:none;">
		<div class="fb-modal-overlay"></div>
		<div class="fb-modal-content">
			<button class="fb-modal-close" aria-label="Chiudi">&times;</button>
			<div id="fb-staff-wizard-mount"></div>
		</div>
	</div>
</div>
