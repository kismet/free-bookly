<?php
/**
 * Vista di amministrazione: Gestione Staff
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Se richiesta la vista orari e pause per un collaboratore
if ( isset( $_GET['view'] ) && 'schedule' === $_GET['view'] && ! empty( $_GET['staff_id'] ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'free-bookly-admin-staff-schedule.php';
	return;
}

// Se richiesta la vista giorni liberi & ferie
if ( isset( $_GET['view'] ) && 'holidays' === $_GET['view'] && ! empty( $_GET['staff_id'] ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'free-bookly-admin-staff-holidays.php';
	return;
}

$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$staff_to_edit = $edit_id ? FB_Staff::get_by_id( $edit_id ) : null;

$show_archived = ! empty( $_GET['show_archived'] );
$staff_list = FB_Staff::get_all( $show_archived );
$all_services = FB_Service::get_all();

$assigned_services = $staff_to_edit ? $staff_to_edit->get_service_ids() : array();

$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$error   = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';

// Recupera utenti WordPress per il collegamento facoltativo
$wp_users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Membri dello Staff', 'free-bookly' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Membro dello staff salvato con successo.', 'free-bookly' ); ?></p>
		</div>
	<?php elseif ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Membro dello staff eliminato con successo.', 'free-bookly' ); ?></p>
		</div>
	<?php elseif ( 'archived' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Membro dello staff archiviato.', 'free-bookly' ); ?></p>
		</div>
	<?php elseif ( 'unarchived' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Membro dello staff ripristinato tra gli attivi.', 'free-bookly' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<div class="fb-layout-columns" style="margin-top: 20px;">
		<!-- Form Aggiungi/Modifica Membro dello Staff -->
		<div class="fb-col-form" style="max-width: 440px;">
			<div class="fb-card">
				<h2><?php echo $staff_to_edit ? esc_html__( 'Modifica Membro Staff', 'free-bookly' ) : esc_html__( 'Aggiungi Nuovo Membro Staff', 'free-bookly' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>">
					<?php wp_nonce_field( 'fb_save_staff_action', 'fb_staff_nonce' ); ?>
					<input type="hidden" name="action" value="fb_save_staff">
					<?php if ( $staff_to_edit ) : ?>
						<input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff_to_edit->id ); ?>">
					<?php endif; ?>

					<div class="fb-form-group">
						<label for="fb_staff_name"><?php esc_html_e( 'Nome Completo *', 'free-bookly' ); ?></label>
						<input type="text" id="fb_staff_name" name="full_name" required value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->full_name ) : ''; ?>" placeholder="<?php esc_attr_e( 'Es. Marco Rossi', 'free-bookly' ); ?>">
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_email"><?php esc_html_e( 'Indirizzo Email (anche multiple separate da virgola)', 'free-bookly' ); ?></label>
						<input type="text" id="fb_staff_email" name="email" value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->email ) : ''; ?>" placeholder="m.rossi@example.com">
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_phone"><?php esc_html_e( 'Numero di Telefono', 'free-bookly' ); ?></label>
						<input type="tel" id="fb_staff_phone" name="phone" value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->phone ) : ''; ?>" placeholder="+39 333 1234567">
					</div>

					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
						<div class="fb-form-group">
							<label for="fb_staff_color"><?php esc_html_e( 'Colore Calendario', 'free-bookly' ); ?></label>
							<input type="color" id="fb_staff_color" name="color" value="<?php echo $staff_to_edit ? esc_attr( $staff_to_edit->color ) : '#0284c7'; ?>" style="width: 100%; height: 38px; padding: 2px;">
						</div>

						<div class="fb-form-group">
							<label for="fb_staff_visibility"><?php esc_html_e( 'Visibilità', 'free-bookly' ); ?></label>
							<select id="fb_staff_visibility" name="visibility" style="width: 100%;">
								<option value="public" <?php selected( $staff_to_edit ? $staff_to_edit->visibility : 'public', 'public' ); ?>><?php esc_html_e( 'Pubblico', 'free-bookly' ); ?></option>
								<option value="private" <?php selected( $staff_to_edit ? $staff_to_edit->visibility : '', 'private' ); ?>><?php esc_html_e( 'Privato (Solo Admin)', 'free-bookly' ); ?></option>
							</select>
						</div>
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_avatar"><?php esc_html_e( 'URL Foto / Avatar Staff', 'free-bookly' ); ?></label>
						<input type="url" id="fb_staff_avatar" name="avatar_url" value="<?php echo $staff_to_edit ? esc_url( $staff_to_edit->avatar_url ) : ''; ?>" placeholder="https://tuosito.com/uploads/collaboratore.jpg">
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_wp_user"><?php esc_html_e( 'Collega a Utente WordPress (opzionale)', 'free-bookly' ); ?></label>
						<select id="fb_staff_wp_user" name="wp_user_id" style="width: 100%;">
							<option value="">-- <?php esc_html_e( 'Nessun utente WP collegato', 'free-bookly' ); ?> --</option>
							<?php foreach ( $wp_users as $wp_user ) : ?>
								<option value="<?php echo esc_attr( $wp_user->ID ); ?>" <?php selected( $staff_to_edit ? $staff_to_edit->wp_user_id : '', $wp_user->ID ); ?>>
									<?php echo esc_html( $wp_user->display_name . ' (' . $wp_user->user_email . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fb-form-group">
						<label for="fb_staff_info"><?php esc_html_e( 'Informazioni & Bio ({staff_info})', 'free-bookly' ); ?></label>
						<textarea id="fb_staff_info" name="info" rows="2"><?php echo $staff_to_edit ? esc_textarea( $staff_to_edit->info ) : ''; ?></textarea>
					</div>

					<div class="fb-form-group">
						<label><strong><?php esc_html_e( 'Servizi Abilitati per questo Staff:', 'free-bookly' ); ?></strong></label>
						<?php if ( empty( $all_services ) ) : ?>
							<p class="description"><?php esc_html_e( 'Nessun servizio disponibile. Aggiungi prima dei servizi.', 'free-bookly' ); ?></p>
						<?php else : ?>
							<div class="fb-checkbox-list" style="max-height: 180px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px;">
								<?php foreach ( $all_services as $service ) : ?>
									<label class="fb-checkbox-item" style="display: block; margin-bottom: 6px;">
										<input type="checkbox" name="services[]" value="<?php echo esc_attr( $service->id ); ?>" <?php checked( in_array( (int) $service->id, $assigned_services, true ) ); ?>>
										<?php echo esc_html( $service->title ); ?> (<?php echo esc_html( $service->duration ); ?> min - <?php echo esc_html( $service->get_formatted_price() ); ?>)
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="fb-form-actions">
						<button type="submit" class="button button-primary">
							<?php echo $staff_to_edit ? esc_html__( 'Aggiorna Staff', 'free-bookly' ) : esc_html__( 'Salva Staff', 'free-bookly' ); ?>
						</button>
						<?php if ( $staff_to_edit ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>" class="button fb-btn-cancel"><?php esc_html_e( 'Annulla', 'free-bookly' ); ?></a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Tabella Elenco Staff -->
		<div class="fb-col-table">
			<div class="fb-card">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Elenco Staff', 'free-bookly' ); ?> (<?php echo count( $staff_list ); ?>)</h2>
					<div>
						<?php if ( $show_archived ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff' ) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'Nascondi Archiviati', 'free-bookly' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&show_archived=1' ) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'Mostra Archiviati', 'free-bookly' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<table class="wp-list-table widefat striped fb-admin-table">
					<thead>
						<tr>
							<th style="width: 30px;"></th>
							<th><?php esc_html_e( 'Nome & Contatti', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Utente WP', 'free-bookly' ); ?></th>
							<th><?php esc_html_e( 'Servizi Abilitati', 'free-bookly' ); ?></th>
							<th style="width: 90px; text-align: center;"><?php esc_html_e( 'Stato', 'free-bookly' ); ?></th>
							<th style="width: 240px; text-align: center;"><?php esc_html_e( 'Azioni & Orari', 'free-bookly' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $staff_list ) ) : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'Nessun membro dello staff presente. Utilizza il modulo a sinistra per aggiungerne uno.', 'free-bookly' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $staff_list as $member ) : 
								$assigned = $member->get_service_ids();
								$wp_user = $member->wp_user_id ? get_userdata( $member->wp_user_id ) : null;
								$is_archived = ( 'archived' === $member->status );
							?>
								<tr style="<?php echo $is_archived ? 'opacity: 0.65;' : ''; ?>">
									<td style="text-align: center;">
										<span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: <?php echo esc_attr( $member->color ); ?>;" title="<?php echo esc_attr( $member->color ); ?>"></span>
									</td>
									<td>
										<strong><?php echo esc_html( $member->full_name ); ?></strong>
										<?php if ( 'private' === $member->visibility ) : ?>
											<span class="fb-badge" style="background: #f1f5f9; color: #64748b; font-size: 10px; margin-left: 4px;"><?php esc_html_e( 'Privato', 'free-bookly' ); ?></span>
										<?php endif; ?>
										<div style="font-size: 11px; color: #64748b;">
											<?php if ( $member->email ) : ?>
												<span><?php echo esc_html( $member->email ); ?></span>
											<?php endif; ?>
											<?php if ( $member->phone ) : ?>
												&bull; <span><?php echo esc_html( $member->phone ); ?></span>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<?php if ( $wp_user ) : ?>
											<code><?php echo esc_html( $wp_user->user_login ); ?></code>
										<?php else : ?>
											<span style="color: #94a3b8;">—</span>
										<?php endif; ?>
									</td>
									<td>
										<span class="fb-badge" style="background: #e0f2fe; color: #0369a1;">
											<?php echo esc_html( sprintf( _n( '%d servizio', '%d servizi', count( $assigned ), 'free-bookly' ), count( $assigned ) ) ); ?>
										</span>
									</td>
									<td style="text-align: center;">
										<?php if ( $is_archived ) : ?>
											<span class="fb-badge" style="background: #fee2e2; color: #991b1b;"><?php esc_html_e( 'Archiviato', 'free-bookly' ); ?></span>
										<?php else : ?>
											<span class="fb-badge" style="background: #dcfce7; color: #166534;"><?php esc_html_e( 'Attivo', 'free-bookly' ); ?></span>
										<?php endif; ?>
									</td>
									<td style="text-align: center;">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&view=schedule&staff_id=' . $member->id ) ); ?>" class="button button-small" style="margin-bottom: 2px;" title="<?php esc_attr_e( 'Configura orari settimanali e pause', 'free-bookly' ); ?>">
											<span class="dashicons dashicons-clock" style="font-size: 14px; line-height: 1.5; margin-right: 2px;"></span><?php esc_html_e( 'Orari', 'free-bookly' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&view=holidays&staff_id=' . $member->id ) ); ?>" class="button button-small" style="margin-bottom: 2px;" title="<?php esc_attr_e( 'Configura ferie e giorni liberi', 'free-bookly' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="font-size: 14px; line-height: 1.5; margin-right: 2px;"></span><?php esc_html_e( 'Ferie', 'free-bookly' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-staff&edit=' . $member->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Modifica', 'free-bookly' ); ?></a>
										
										<?php if ( $is_archived ) : ?>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-staff&action=fb_unarchive_staff&staff_id=' . $member->id ), 'fb_unarchive_staff_action' ) ); ?>" class="button button-small"><?php esc_html_e( 'Ripristina', 'free-bookly' ); ?></a>
										<?php else : ?>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-staff&action=fb_archive_staff&staff_id=' . $member->id ), 'fb_archive_staff_action' ) ); ?>" class="button button-small" style="color: #d97706;"><?php esc_html_e( 'Archivia', 'free-bookly' ); ?></a>
										<?php endif; ?>

										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-staff&action=fb_delete_staff&staff_id=' . $member->id ), 'fb_delete_staff_action' ) ); ?>" class="button button-small fb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Eliminare definitivamente questo membro dello staff?', 'free-bookly' ); ?>');"><?php esc_html_e( 'Elimina', 'free-bookly' ); ?></a>
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
