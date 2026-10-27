<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$staff_id = isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0;
$staff = FB_Staff::get_by_id( $staff_id );

if ( ! $staff ) {
	wp_die( esc_html__( 'Membro dello staff non trovato.', 'free-bookly' ) );
}

$staff_holidays = FB_Holiday::get_staff_holidays( $staff_id );
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( sprintf( __( 'Giorni Liberi & Ferie: %s', 'free-bookly' ), $staff->full_name ) ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>" class="page-title-action">
		&larr; <?php esc_html_e( 'Torna allo Staff', 'free-bookly' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible" style="margin-top: 15px;">
			<p><strong><?php esc_html_e( 'Giorni liberi aggiornati con successo!', 'free-bookly' ); ?></strong></p>
		</div>
	<?php endif; ?>

	<div style="display: flex; gap: 24px; flex-wrap: wrap; margin-top: 20px;">
		<!-- Modulo aggiunta giorno libero -->
		<div class="fb-card" style="flex: 1; min-width: 320px; max-width: 400px; height: fit-content;">
			<h2><?php esc_html_e( 'Aggiungi Giorno Libero / Ferie', 'free-bookly' ); ?></h2>
			<p style="color: #64748b; font-size: 13px;">
				<?php esc_html_e( 'Nelle date indicate questo collaboratore non sarà disponibile per gli appuntamenti.', 'free-bookly' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&view=holidays&staff_id=' . $staff->id ) ); ?>">
				<input type="hidden" name="action" value="fb_add_staff_holiday">
				<input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff->id ); ?>">
				<?php wp_nonce_field( 'fb_staff_holiday_action', 'fb_staff_holiday_nonce' ); ?>

				<div class="fb-form-group">
					<label for="holiday_date"><?php esc_html_e( 'Data', 'free-bookly' ); ?></label>
					<input type="date" id="holiday_date" name="holiday_date" required style="width: 100%;">
				</div>

				<div class="fb-form-group">
					<label for="holiday_title"><?php esc_html_e( 'Motivo / Descrizione (es. Ferie, Permesso)', 'free-bookly' ); ?></label>
					<input type="text" id="holiday_title" name="holiday_title" class="regular-text" style="width: 100%;" required>
				</div>

				<div class="fb-form-group">
					<label>
						<input type="checkbox" name="repeat_yearly" value="1">
						<?php esc_html_e( 'Ripeti ogni anno', 'free-bookly' ); ?>
					</label>
				</div>

				<p class="submit" style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
					<button type="submit" class="button button-primary" style="width: 100%;"><?php esc_html_e( 'Salva Giorno Libero', 'free-bookly' ); ?></button>
				</p>
			</form>
		</div>

		<!-- Tabella giorni liberi dello staff -->
		<div class="fb-card" style="flex: 2; min-width: 400px;">
			<h2><?php esc_html_e( 'Giorni Liberi Attualmente Configurati', 'free-bookly' ); ?></h2>
			<?php if ( empty( $staff_holidays ) ) : ?>
				<p style="color: #64748b; font-style: italic;"><?php esc_html_e( 'Nessun giorno libero individuale impostato. Il collaboratore segue il calendario aziendale e i suoi orari settimanali.', 'free-bookly' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Data', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Descrizione / Motivo', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Ricorrenza', 'free-bookly' ); ?></th>
							<th style="width: 80px; text-align: center;"><?php esc_html_e( 'Azione', 'free-bookly' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $staff_holidays as $h ) : ?>
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
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-staff&view=holidays&staff_id=' . $staff->id . '&action=fb_delete_staff_holiday&holiday_id=' . $h->id ), 'fb_delete_staff_holiday_action' ) ); ?>" 
									   class="button button-small" 
									   style="color: #b91c1c;"
									   onclick="return confirm('<?php esc_attr_e( 'Eliminare questo giorno libero?', 'free-bookly' ); ?>');">
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
</div>
