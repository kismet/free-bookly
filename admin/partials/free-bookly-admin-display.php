<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$today = current_time( 'Y-m-d' );

$count_today = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->prefix}fb_appointments WHERE DATE(start_date) = %s AND status != 'cancelled'",
	$today
) );

$count_pending = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->prefix}fb_appointments WHERE status = 'pending'"
);

$count_staff = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->prefix}fb_staff"
);

$count_services = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->prefix}fb_services"
);

// Recupera ultimi 5 appuntamenti
$recent_appointments = $wpdb->get_results(
	"SELECT a.*, s.title as service_title, st.full_name as staff_name, c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone
	FROM {$wpdb->prefix}fb_appointments a
	LEFT JOIN {$wpdb->prefix}fb_services s ON a.service_id = s.id
	LEFT JOIN {$wpdb->prefix}fb_staff st ON a.staff_id = st.id
	LEFT JOIN {$wpdb->prefix}fb_customers c ON a.customer_id = c.id
	ORDER BY a.start_date DESC LIMIT 5",
	ARRAY_A
);
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">Free Bookly - Dashboard</h1>
	<hr class="wp-header-end">

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 20px; margin-bottom: 24px;">
		<div class="fb-card" style="margin-bottom: 0;">
			<div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Appuntamenti Oggi</div>
			<div style="font-size: 32px; font-weight: 700; color: #0284c7; margin-top: 8px;"><?php echo esc_html( $count_today ); ?></div>
		</div>

		<div class="fb-card" style="margin-bottom: 0;">
			<div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">In Attesa di Conferma</div>
			<div style="font-size: 32px; font-weight: 700; color: #f59e0b; margin-top: 8px;"><?php echo esc_html( $count_pending ); ?></div>
		</div>

		<div class="fb-card" style="margin-bottom: 0;">
			<div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Membri Staff</div>
			<div style="font-size: 32px; font-weight: 700; color: #10b981; margin-top: 8px;"><?php echo esc_html( $count_staff ); ?></div>
		</div>

		<div class="fb-card" style="margin-bottom: 0;">
			<div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Servizi Offerti</div>
			<div style="font-size: 32px; font-weight: 700; color: #8b5cf6; margin-top: 8px;"><?php echo esc_html( $count_services ); ?></div>
		</div>
	</div>

	<div class="fb-card">
		<h2>Ultimi Appuntamenti Ricevuti</h2>
		<?php if ( empty( $recent_appointments ) ) : ?>
			<p>Nessun appuntamento registrato finora.</p>
		<?php else : ?>
			<table class="wp-list-table widefat striped fb-admin-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Cliente</th>
						<th>Servizio</th>
						<th>Staff</th>
						<th>Data & Ora</th>
						<th>Stato</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_appointments as $app ) : ?>
						<tr>
							<td>#<?php echo esc_html( $app['id'] ); ?></td>
							<td>
								<strong><?php echo esc_html( $app['customer_name'] ); ?></strong><br>
								<small><?php echo esc_html( $app['customer_email'] . ' ' . $app['customer_phone'] ); ?></small>
							</td>
							<td><?php echo esc_html( $app['service_title'] ? $app['service_title'] : 'N/D' ); ?></td>
							<td><?php echo esc_html( $app['staff_name'] ? $app['staff_name'] : 'Qualsiasi' ); ?></td>
							<td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $app['start_date'] ) ) ); ?></td>
							<td>
								<?php if ( 'approved' === $app['status'] ) : ?>
									<span class="fb-badge" style="background: #dcfce7; color: #15803d;">Confermato</span>
								<?php elseif ( 'cancelled' === $app['status'] ) : ?>
									<span class="fb-badge" style="background: #fee2e2; color: #b91c1c;">Annullato</span>
								<?php else : ?>
									<span class="fb-badge" style="background: #fef3c7; color: #b45309;">In Attesa</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<div class="fb-card" style="margin-top: 24px;">
		<h2>Come Pubblicare il Form di Prenotazione</h2>
		<p>Inserisci questo shortcode in qualunque pagina o articolo di WordPress per mostrare il booking wizard ai tuoi clienti:</p>
		<p><code style="font-size: 16px; padding: 6px 12px; background: #f1f5f9; border-radius: 4px;">[free_bookly]</code></p>
	</div>
</div>
