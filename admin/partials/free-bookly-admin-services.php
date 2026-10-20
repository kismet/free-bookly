<?php
/**
 * Vista di amministrazione: Gestione Servizi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$service_to_edit = $edit_id ? FB_Service::get_by_id( $edit_id ) : null;
$services = FB_Service::get_all();

$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$error   = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">Gestione Servizi</h1>
	<hr class="wp-header-end">

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Servizio salvato con successo.</p>
		</div>
	<?php elseif ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Servizio eliminato con successo.</p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<div class="fb-layout-columns">
		<!-- Form Aggiungi/Modifica Servizio -->
		<div class="fb-col-form">
			<div class="fb-card">
				<h2><?php echo $service_to_edit ? 'Modifica Servizio' : 'Aggiungi Nuovo Servizio'; ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services' ) ); ?>">
					<?php wp_nonce_field( 'fb_save_service_action', 'fb_service_nonce' ); ?>
					<input type="hidden" name="action" value="fb_save_service">
					<?php if ( $service_to_edit ) : ?>
						<input type="hidden" name="service_id" value="<?php echo esc_attr( $service_to_edit->id ); ?>">
					<?php endif; ?>

					<div class="fb-form-group">
						<label for="fb_service_title">Titolo del Servizio *</label>
						<input type="text" id="fb_service_title" name="title" required value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->title ) : ''; ?>" placeholder="Es. Taglio e Piega">
					</div>

					<div class="fb-form-group">
						<label for="fb_service_duration">Durata (minuti) *</label>
						<input type="number" id="fb_service_duration" name="duration" min="5" step="5" required value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->duration ) : '60'; ?>">
					</div>

					<div class="fb-form-group">
						<label for="fb_service_price">Prezzo (€)</label>
						<input type="number" id="fb_service_price" name="price" min="0" step="0.50" value="<?php echo $service_to_edit ? esc_attr( number_format( $service_to_edit->price, 2, '.', '' ) ) : '0.00'; ?>">
					</div>

					<div class="fb-form-group">
						<label for="fb_service_capacity">Capacità Clienti (contemporanei)</label>
						<input type="number" id="fb_service_capacity" name="capacity" min="1" step="1" value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->capacity ) : '1'; ?>">
					</div>

					<div class="fb-form-actions">
						<button type="submit" class="button button-primary">
							<?php echo $service_to_edit ? 'Aggiorna Servizio' : 'Salva Servizio'; ?>
						</button>
						<?php if ( $service_to_edit ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services' ) ); ?>" class="button fb-btn-cancel">Annulla</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Tabella Elenco Servizi -->
		<div class="fb-col-table">
			<div class="fb-card">
				<h2>Elenco Servizi (<?php echo count( $services ); ?>)</h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 50px;">ID</th>
							<th>Titolo</th>
							<th style="width: 120px;">Durata</th>
							<th style="width: 110px;">Prezzo</th>
							<th style="width: 90px;">Capacità</th>
							<th style="width: 140px;">Azioni</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $services ) ) : ?>
							<tr>
								<td colspan="6">Nessun servizio registrato. Utilizza il modulo a sinistra per aggiungere il primo.</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $services as $service ) : ?>
								<tr>
									<td><strong>#<?php echo esc_html( $service->id ); ?></strong></td>
									<td>
										<strong><?php echo esc_html( $service->title ); ?></strong>
									</td>
									<td><?php echo esc_html( $service->duration ); ?> min</td>
									<td>€ <?php echo esc_html( number_format( $service->price, 2, ',', '.' ) ); ?></td>
									<td><?php echo esc_html( $service->capacity ); ?> pax</td>
									<td class="fb-table-actions">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services&edit=' . $service->id ) ); ?>" class="button button-small">Modifica</a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-services&action=delete&id=' . $service->id ), 'fb_delete_service_' . $service->id ) ); ?>" 
										   class="button button-small delete-link" 
										   onclick="return confirm('Sei sicuro di voler eliminare questo servizio?');">Elimina</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
