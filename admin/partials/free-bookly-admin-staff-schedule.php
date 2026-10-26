<?php
/**
 * Vista di amministrazione: Orari di Lavoro e Pause per Membro Staff
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$staff_id = isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0;
$staff = $staff_id ? FB_Staff::get_by_id( $staff_id ) : null;

if ( ! $staff ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>Membro dello staff non trovato.</p></div></div>';
	return;
}

$weekly_schedule = FB_Staff_Schedule::get_schedule( $staff->id );
$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">
		Orari e Pause: <?php echo esc_html( $staff->full_name ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>" class="page-title-action">
		&larr; Torna alla lista Staff
	</a>
	<hr class="wp-header-end">

	<?php if ( 'schedule_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Orari di lavoro e pause aggiornati con successo per <strong><?php echo esc_html( $staff->full_name ); ?></strong>.</p>
		</div>
	<?php endif; ?>

	<div class="fb-card">
		<p style="margin-top: 0; color: #64748b; font-size: 14px;">
			Configura i giorni lavorativi, l'orario di inizio/fine turno e le pause pranzo o intervalli. 
			Il motore di prenotazione calcolerà gli slot orari disponibili escludendo automaticamente i giorni di riposo e le fasce di pausa.
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&view=schedule&staff_id=' . $staff->id ) ); ?>">
			<?php wp_nonce_field( 'fb_save_schedule_action', 'fb_schedule_nonce' ); ?>
			<input type="hidden" name="action" value="fb_save_staff_schedule">
			<input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff->id ); ?>">

			<table class="wp-list-table widefat striped fb-admin-table" style="margin-bottom: 24px;">
				<thead>
					<tr>
						<th style="width: 140px;">Giorno</th>
						<th style="width: 160px;">Stato Lavorativo</th>
						<th style="width: 280px;">Orario di Lavoro (Turno)</th>
						<th>Pausa / Intervallo (Opzionale)</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $weekly_schedule as $day_idx => $day_data ) : ?>
						<?php 
						$is_working = ! empty( $day_data['is_working_day'] );
						$first_break = ! empty( $day_data['breaks'][0] ) ? $day_data['breaks'][0] : null;
						$break_start = $first_break ? $first_break['start_time'] : '';
						$break_end   = $first_break ? $first_break['end_time'] : '';
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $day_data['day_name'] ); ?></strong>
							</td>
							<td>
								<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
									<input type="checkbox" name="schedule[<?php echo esc_attr( $day_idx ); ?>][is_working_day]" value="1" <?php checked( $is_working ); ?> class="fb-toggle-working-day" data-day="<?php echo esc_attr( $day_idx ); ?>">
									<span class="fb-status-label" style="font-weight: 600; color: <?php echo $is_working ? '#16a34a' : '#94a3b8'; ?>;">
										<?php echo $is_working ? 'In Servizio' : 'Giorno di Riposo'; ?>
									</span>
								</label>
							</td>
							<td>
								<div style="display: flex; align-items: center; gap: 8px;">
									<span>Da:</span>
									<input type="time" name="schedule[<?php echo esc_attr( $day_idx ); ?>][start_time]" value="<?php echo esc_attr( $day_data['start_time'] ); ?>" style="width: 110px;" required>
									<span>A:</span>
									<input type="time" name="schedule[<?php echo esc_attr( $day_idx ); ?>][end_time]" value="<?php echo esc_attr( $day_data['end_time'] ); ?>" style="width: 110px;" required>
								</div>
							</td>
							<td>
								<div style="display: flex; align-items: center; gap: 8px;">
									<span>Pausa dalle:</span>
									<input type="time" name="schedule[<?php echo esc_attr( $day_idx ); ?>][break_start]" value="<?php echo esc_attr( $break_start ); ?>" style="width: 110px;">
									<span>alle:</span>
									<input type="time" name="schedule[<?php echo esc_attr( $day_idx ); ?>][break_end]" value="<?php echo esc_attr( $break_end ); ?>" style="width: 110px;">
									<span class="description" style="font-size: 11px;">(Lasciare vuoto se nessuna pausa)</span>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<button type="submit" class="button button-primary button-hero">
				Salva Orari e Pause di <?php echo esc_html( $staff->full_name ); ?>
			</button>
		</form>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('.fb-toggle-working-day').forEach(function(cb) {
		cb.addEventListener('change', function() {
			var label = this.closest('label').querySelector('.fb-status-label');
			if (this.checked) {
				label.textContent = 'In Servizio';
				label.style.color = '#16a34a';
			} else {
				label.textContent = 'Giorno di Riposo';
				label.style.color = '#94a3b8';
			}
		});
	});
});
</script>
