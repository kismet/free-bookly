<?php
/**
 * Vista di amministrazione: Gestione Staff
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$staff_to_edit = $edit_id ? FB_Staff::get_by_id( $edit_id ) : null;
$staff_list = FB_Staff::get_all();
$all_services = FB_Service::get_all();

$assigned_services = $staff_to_edit ? $staff_to_edit->get_service_ids() : array();

$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$error   = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';

// Recupera utenti WordPress per il collegamento facoltativo
$wp_users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">Gestione Staff</h1>
	<hr class="wp-header-end">

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Membro dello staff salvato con successo.</p>
		</div>
	<?php elseif ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Membro dello staff eliminato con successo.</p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<div class="fb-layout-columns">
		<!-- Form Aggiungi/Modifica Membro dello Staff -->
		<div class="fb-col-form">
			<div class="fb-card">
				<h2><?php echo $staff_to_edit ? 'Modifica Membro Staff' : 'Aggiungi Nuovo Membro Staff'; ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>">
					<?php wp_nonce_field( 'fb_save_staff_action', 'fb_staff_nonce' ); ?>
					<input type="hidden" name="action" value="fb_save_staff">
					<?php if ( $staff_to_edit ) : ?>
						<input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff_to_edit->id ); ?>">
					<?php endif; ?>

					<div class="fb-form-group">
						<label for="fb_staff_name">Nome Completo *</label>
						<input type="text" id="fb_staff_name" name="full_name" required value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->full_name ) : ''; ?>" placeholder="Es. Marco Rossi">
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_email">Indirizzo Email</label>
						<input type="email" id="fb_staff_email" name="email" value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->email ) : ''; ?>" placeholder="m.rossi@example.com">
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_phone">Numero di Telefono</label>
						<input type="tel" id="fb_staff_phone" name="phone" value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->phone ) : ''; ?>" placeholder="+39 333 1234567">
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_wp_user">Collega a Utente WordPress</label>
						<select id="fb_staff_wp_user" name="wp_user_id">
							<option value="">-- Nessun utente WP collegato --</option>
							<?php foreach ( $wp_users as $wp_user ) : ?>
								<option value="<?php echo esc_attr( $wp_user->ID ); ?>" <?php selected( $staff_to_edit ? $staff_to_edit->wp_user_id : '', $wp_user->ID ); ?>>
									<?php echo esc_html( $wp_user->display_name . ' (' . $wp_user->user_email . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fb-form-group">
						<label>Servizi Abilitati per questo Staff</label>
						<?php if ( empty( $all_services ) ) : ?>
							<p class="description">Nessun servizio disponibile. Aggiungi prima dei servizi.</p>
						<?php else : ?>
							<div class="fb-checkbox-list">
								<?php foreach ( $all_services as $service ) : ?>
									<label class="fb-checkbox-item">
										<input type="checkbox" name="services[]" value="<?php echo esc_attr( $service->id ); ?>" <?php checked( in_array( (int) $service->id, $assigned_services, true ) ); ?>>
										<?php echo esc_html( $service->title ); ?> (<?php echo esc_html( $service->duration ); ?> min)
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="fb-form-actions">
						<button type="submit" class="button button-primary">
							<?php echo $staff_to_edit ? 'Aggiorna Membro Staff' : 'Salva Membro Staff'; ?>
						</button>
						<?php if ( $staff_to_edit ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>" class="button fb-btn-cancel">Annulla</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Tabella Elenco Staff -->
		<div class="fb-col-table">
			<div class="fb-card">
				<h2>Membri del Team (<?php echo count( $staff_list ); ?>)</h2>
				<table class="wp-list-table widefat striped fb-admin-table">
					<thead>
						<tr>
							<th style="width: 50px;">ID</th>
							<th>Nome</th>
							<th>Contatti</th>
							<th>Utente WP</th>
							<th>Servizi Erogati</th>
							<th style="width: 140px;">Azioni</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $staff_list ) ) : ?>
							<tr>
								<td colspan="6">Nessun membro dello staff inserito. Utilizza il modulo a sinistra per aggiungere il primo.</td>
							</tr>
						<?php else : ?>
							<?php 
							// Indicizzazione veloce dei servizi per ID
							$services_by_id = array();
							foreach ( $all_services as $s ) {
								$services_by_id[ $s->id ] = $s;
							}
							?>
							<?php foreach ( $staff_list as $staff ) : ?>
								<?php 
								$staff_srv_ids = $staff->get_service_ids(); 
								$wp_user = $staff->wp_user_id ? get_user_by( 'id', $staff->wp_user_id ) : null;
								?>
								<tr>
									<td><strong>#<?php echo esc_html( $staff->id ); ?></strong></td>
									<td>
										<strong><?php echo esc_html( $staff->full_name ); ?></strong>
									</td>
									<td>
										<?php if ( $staff->email ) : ?>
											<div><a href="mailto:<?php echo esc_attr( $staff->email ); ?>"><?php echo esc_html( $staff->email ); ?></a></div>
										<?php endif; ?>
										<?php if ( $staff->phone ) : ?>
											<div><small><?php echo esc_html( $staff->phone ); ?></small></div>
										<?php endif; ?>
										<?php if ( ! $staff->email && ! $staff->phone ) : ?>
											<span class="description">-</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $wp_user ) : ?>
											<span class="fb-badge fb-badge-secondary"><?php echo esc_html( $wp_user->display_name ); ?></span>
										<?php else : ?>
											<span class="description">Nessuno</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( empty( $staff_srv_ids ) ) : ?>
											<span class="description">Nessun servizio assegnato</span>
										<?php else : ?>
											<?php foreach ( $staff_srv_ids as $s_id ) : ?>
												<?php if ( isset( $services_by_id[ $s_id ] ) ) : ?>
													<span class="fb-badge"><?php echo esc_html( $services_by_id[ $s_id ]->title ); ?></span>
												<?php endif; ?>
											<?php endforeach; ?>
										<?php endif; ?>
									</td>
									<td class="fb-table-actions">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&edit=' . $staff->id ) ); ?>" class="button button-small">Modifica</a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-staff&action=delete&id=' . $staff->id ), 'fb_delete_staff_' . $staff->id ) ); ?>" 
										   class="button button-small delete-link" 
										   onclick="return confirm('Sei sicuro di voler eliminare questo membro dello staff?');">Elimina</a>
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
