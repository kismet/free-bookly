<?php
/**
 * Vista di amministrazione: Calendario e Lista Appuntamenti
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$filter_status  = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$filter_staff   = isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0;
$filter_service = isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0;

$where_clauses = array( '1=1' );
if ( ! empty( $filter_status ) ) {
	$where_clauses[] = $wpdb->prepare( 'a.status = %s', $filter_status );
}
if ( ! empty( $filter_staff ) ) {
	$where_clauses[] = $wpdb->prepare( 'a.staff_id = %d', $filter_staff );
}
if ( ! empty( $filter_service ) ) {
	$where_clauses[] = $wpdb->prepare( 'a.service_id = %d', $filter_service );
}
$where_sql = implode( ' AND ', $where_clauses );

$appointments = $wpdb->get_results(
	"SELECT a.*, s.title as service_title, s.price as service_price, st.full_name as staff_name, c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone
	FROM {$wpdb->prefix}fb_appointments a
	LEFT JOIN {$wpdb->prefix}fb_services s ON a.service_id = s.id
	LEFT JOIN {$wpdb->prefix}fb_staff st ON a.staff_id = st.id
	LEFT JOIN {$wpdb->prefix}fb_customers c ON a.customer_id = c.id
	WHERE $where_sql
	ORDER BY a.start_date DESC",
	ARRAY_A
);

$all_staff    = FB_Staff::get_all();
$all_services = FB_Service::get_all();

$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">Gestione Appuntamenti</h1>
	<hr class="wp-header-end">

	<?php if ( 'approved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Appuntamento approvato con successo.</p></div>
	<?php elseif ( 'cancelled' === $message ) : ?>
		<div class="notice notice-warning is-dismissible"><p>Appuntamento contrassegnato come annullato.</p></div>
	<?php elseif ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Appuntamento eliminato con successo.</p></div>
	<?php endif; ?>

	<div class="fb-card">
		<!-- Filtri -->
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
			<input type="hidden" name="page" value="free-bookly-calendar">

			<label for="filter_status"><strong>Stato:</strong></label>
			<select name="status" id="filter_status">
				<option value="">Tutti gli stati</option>
				<option value="pending" <?php selected( $filter_status, 'pending' ); ?>>In Attesa</option>
				<option value="approved" <?php selected( $filter_status, 'approved' ); ?>>Confermati</option>
				<option value="cancelled" <?php selected( $filter_status, 'cancelled' ); ?>>Annullati</option>
			</select>

			<label for="filter_staff"><strong>Staff:</strong></label>
			<select name="staff_id" id="filter_staff">
				<option value="">Tutto lo staff</option>
				<?php foreach ( $all_staff as $st ) : ?>
					<option value="<?php echo esc_attr( $st->id ); ?>" <?php selected( $filter_staff, $st->id ); ?>>
						<?php echo esc_html( $st->full_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="filter_service"><strong>Servizio:</strong></label>
			<select name="service_id" id="filter_service">
				<option value="">Tutti i servizi</option>
				<?php foreach ( $all_services as $srv ) : ?>
					<option value="<?php echo esc_attr( $srv->id ); ?>" <?php selected( $filter_service, $srv->id ); ?>>
						<?php echo esc_html( $srv->title ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="button button-secondary">Filtra</button>
			<?php if ( ! empty( $filter_status ) || ! empty( $filter_staff ) || ! empty( $filter_service ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-calendar' ) ); ?>" class="button">Reset</a>
			<?php endif; ?>
		</form>

		<!-- Tabella Appuntamenti -->
		<table class="wp-list-table widefat striped fb-admin-table">
			<thead>
				<tr>
					<th style="width: 50px;">ID</th>
					<th>Data e Ora</th>
					<th>Cliente</th>
					<th>Servizio</th>
					<th>Operatore</th>
					<th>Stato</th>
					<th style="width: 220px;">Azioni</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $appointments ) ) : ?>
					<tr>
						<td colspan="7">Nessun appuntamento trovato per i filtri selezionati.</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $appointments as $app ) : ?>
						<tr>
							<td><strong>#<?php echo esc_html( $app['id'] ); ?></strong></td>
							<td>
								<strong><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $app['start_date'] ) ) ); ?></strong><br>
								<span><?php echo esc_html( date_i18n( 'H:i', strtotime( $app['start_date'] ) ) . ' - ' . date_i18n( 'H:i', strtotime( $app['end_date'] ) ) ); ?></span>
							</td>
							<td>
								<strong><?php echo esc_html( $app['customer_name'] ); ?></strong><br>
								<small><a href="mailto:<?php echo esc_attr( $app['customer_email'] ); ?>"><?php echo esc_html( $app['customer_email'] ); ?></a></small><br>
								<small><?php echo esc_html( $app['customer_phone'] ); ?></small>
							</td>
							<td>
								<strong><?php echo esc_html( $app['service_title'] ? $app['service_title'] : 'Nessuno' ); ?></strong><br>
								<small>€ <?php echo esc_html( number_format( (float) $app['service_price'], 2, ',', '.' ) ); ?></small>
							</td>
							<td>
								<span class="fb-badge fb-badge-secondary"><?php echo esc_html( $app['staff_name'] ? $app['staff_name'] : 'Qualsiasi' ); ?></span>
							</td>
							<td>
								<?php if ( 'approved' === $app['status'] ) : ?>
									<span class="fb-badge" style="background: #dcfce7; color: #15803d;">Confermato</span>
								<?php elseif ( 'cancelled' === $app['status'] ) : ?>
									<span class="fb-badge" style="background: #fee2e2; color: #b91c1c;">Annullato</span>
								<?php else : ?>
									<span class="fb-badge" style="background: #fef3c7; color: #b45309;">In Attesa</span>
								<?php endif; ?>
							</td>
							<td class="fb-table-actions">
								<?php if ( 'approved' !== $app['status'] ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-calendar&action=approve&id=' . $app['id'] ), 'fb_status_app_' . $app['id'] ) ); ?>" class="button button-small" style="color: #15803d;">Approva</a>
								<?php endif; ?>

								<?php if ( 'cancelled' !== $app['status'] ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-calendar&action=cancel&id=' . $app['id'] ), 'fb_status_app_' . $app['id'] ) ); ?>" class="button button-small" style="color: #b45309;">Annulla</a>
								<?php endif; ?>

								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-calendar&action=delete&id=' . $app['id'] ), 'fb_delete_app_' . $app['id'] ) ); ?>" 
								   class="button button-small delete-link" 
								   onclick="return confirm('Vuoi eliminare definitivamente questo appuntamento?');">Elimina</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
