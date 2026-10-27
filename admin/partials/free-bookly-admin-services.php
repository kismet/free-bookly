<?php
/**
 * Vista di amministrazione: Gestione Servizi & Categorie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$service_to_edit = $edit_id ? FB_Service::get_by_id( $edit_id ) : null;

// Gestione Categorie
$categories = FB_Category::get_all();
$selected_category = isset( $_GET['category_filter'] ) ? absint( $_GET['category_filter'] ) : 0;

if ( $selected_category > 0 ) {
	$services = FB_Service::get_by_category( $selected_category );
} else {
	$services = FB_Service::get_all();
}

$show_categories_panel = isset( $_GET['view'] ) && 'categories' === $_GET['view'];

$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$error   = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Gestione Servizi & Categorie', 'free-bookly' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services&view=' . ( $show_categories_panel ? 'services' : 'categories' ) ) ); ?>" class="page-title-action">
		<?php echo $show_categories_panel ? '&larr; ' . esc_html__( 'Torna ai Servizi', 'free-bookly' ) : esc_html__( 'Gestisci Categorie', 'free-bookly' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Operazione completata con successo.', 'free-bookly' ); ?></p>
		</div>
	<?php elseif ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Elemento eliminato con successo.', 'free-bookly' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $show_categories_panel ) : ?>
		<!-- ================= PANNELLO CATEGORIE ================= -->
		<div class="fb-layout-columns" style="margin-top: 20px;">
			<!-- Form Nuova Categoria -->
			<div class="fb-col-form" style="max-width: 380px;">
				<div class="fb-card">
					<h2><?php esc_html_e( 'Aggiungi Categoria', 'free-bookly' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services&view=categories' ) ); ?>">
						<?php wp_nonce_field( 'fb_save_category_action', 'fb_category_nonce' ); ?>
						<input type="hidden" name="action" value="fb_save_category">

						<div class="fb-form-group">
							<label for="cat_name"><?php esc_html_e( 'Nome Categoria *', 'free-bookly' ); ?></label>
							<input type="text" id="cat_name" name="name" required placeholder="<?php esc_attr_e( 'Es. Cura del Viso, Massaggi', 'free-bookly' ); ?>">
						</div>

						<div class="fb-form-group">
							<label for="cat_description"><?php esc_html_e( 'Descrizione Categoria', 'free-bookly' ); ?></label>
							<textarea id="cat_description" name="description" rows="3" placeholder="<?php esc_attr_e( 'Descrizione visibile nei form e nelle email', 'free-bookly' ); ?>"></textarea>
						</div>

						<div class="fb-form-group">
							<label for="cat_image"><?php esc_html_e( 'URL Immagine Categoria', 'free-bookly' ); ?></label>
							<input type="url" id="cat_image" name="image_url" placeholder="https://tuosito.com/uploads/categoria.jpg">
						</div>

						<div class="fb-form-group">
							<label for="cat_sort"><?php esc_html_e( 'Ordinamento', 'free-bookly' ); ?></label>
							<input type="number" id="cat_sort" name="sort_order" value="0" min="0" step="1">
						</div>

						<div class="fb-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Salva Categoria', 'free-bookly' ); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Tabella Categorie -->
			<div class="fb-col-table">
				<div class="fb-card">
					<h2><?php esc_html_e( 'Elenco Categorie Esistenti', 'free-bookly' ); ?></h2>
					<table class="wp-list-table widefat striped fb-admin-table">
						<thead>
							<tr>
								<th style="width: 50px;">ID</th>
								<th><?php esc_html_e( 'Nome Categoria', 'free-bookly' ); ?></th>
								<th><?php esc_html_e( 'Descrizione', 'free-bookly' ); ?></th>
								<th style="width: 90px; text-align: center;"><?php esc_html_e( 'Servizi', 'free-bookly' ); ?></th>
								<th style="width: 90px; text-align: center;"><?php esc_html_e( 'Ordine', 'free-bookly' ); ?></th>
								<th style="width: 100px; text-align: center;"><?php esc_html_e( 'Azioni', 'free-bookly' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $categories ) ) : ?>
								<tr>
									<td colspan="6"><?php esc_html_e( 'Nessuna categoria presente.', 'free-bookly' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $categories as $cat ) : ?>
									<tr>
										<td><?php echo esc_html( $cat->id ); ?></td>
										<td><strong><?php echo esc_html( $cat->name ); ?></strong></td>
										<td><?php echo esc_html( wp_trim_words( $cat->description, 10 ) ); ?></td>
										<td style="text-align: center;">
											<span class="fb-badge" style="background: #e0f2fe; color: #0369a1;">
												<?php echo esc_html( FB_Category::count_services( $cat->id ) ); ?>
											</span>
										</td>
										<td style="text-align: center;"><?php echo esc_html( $cat->sort_order ); ?></td>
										<td style="text-align: center;">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-services&view=categories&action=fb_delete_category&cat_id=' . $cat->id ), 'fb_delete_category_action' ) ); ?>" 
											   class="button button-small" 
											   style="color: #b91c1c;" 
											   onclick="return confirm('<?php esc_attr_e( 'Eliminare questa categoria? I servizi collegati non verranno eliminati.', 'free-bookly' ); ?>');">
												<?php esc_html_e( 'Elimina', 'free-bookly' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

	<?php else : ?>
		<!-- ================= PANNELLO SERVIZI ================= -->
		<div class="fb-layout-columns" style="margin-top: 20px;">
			<!-- Form Aggiungi/Modifica Servizio -->
			<div class="fb-col-form" style="max-width: 440px;">
				<div class="fb-card">
					<h2><?php echo $service_to_edit ? esc_html__( 'Modifica Servizio', 'free-bookly' ) : esc_html__( 'Aggiungi Nuovo Servizio', 'free-bookly' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services' ) ); ?>">
						<?php wp_nonce_field( 'fb_save_service_action', 'fb_service_nonce' ); ?>
						<input type="hidden" name="action" value="fb_save_service">
						<?php if ( $service_to_edit ) : ?>
							<input type="hidden" name="service_id" value="<?php echo esc_attr( $service_to_edit->id ); ?>">
						<?php endif; ?>

						<!-- Categoria di appartenenza -->
						<div class="fb-form-group">
							<label for="fb_service_category"><?php esc_html_e( 'Categoria di Appartenenza *', 'free-bookly' ); ?></label>
							<select id="fb_service_category" name="category_id" required style="width: 100%;">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $service_to_edit ? $service_to_edit->category_id : 0, $cat->id ); ?>>
										<?php echo esc_html( $cat->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="fb-form-group">
							<label for="fb_service_title"><?php esc_html_e( 'Titolo del Servizio *', 'free-bookly' ); ?></label>
							<input type="text" id="fb_service_title" name="title" required value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->title ) : ''; ?>" placeholder="<?php esc_attr_e( 'Es. Consulenza Fiscale Specialistica', 'free-bookly' ); ?>">
						</div>

						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
							<div class="fb-form-group">
								<label for="fb_service_duration"><?php esc_html_e( 'Durata (minuti) *', 'free-bookly' ); ?></label>
								<input type="number" id="fb_service_duration" name="duration" min="5" step="5" required value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->duration ) : '60'; ?>">
							</div>

							<div class="fb-form-group">
								<label for="fb_service_price"><?php esc_html_e( 'Prezzo Base (€)', 'free-bookly' ); ?></label>
								<input type="number" id="fb_service_price" name="price" min="0" step="0.50" value="<?php echo $service_to_edit ? esc_attr( number_format( $service_to_edit->price, 2, '.', '' ) ) : '0.00'; ?>">
							</div>
						</div>

						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
							<div class="fb-form-group">
								<label for="fb_service_capacity"><?php esc_html_e( 'Capacità Clienti', 'free-bookly' ); ?></label>
								<input type="number" id="fb_service_capacity" name="capacity" min="1" step="1" value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->capacity ) : '1'; ?>">
							</div>

							<div class="fb-form-group">
								<label for="fb_service_color"><?php esc_html_e( 'Colore Calendario', 'free-bookly' ); ?></label>
								<input type="color" id="fb_service_color" name="color" value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->color ) : '#0284c7'; ?>" style="width: 100%; height: 38px; padding: 2px;">
							</div>
						</div>

						<!-- Visibilità e Fascia Oraria -->
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
							<div class="fb-form-group">
								<label for="fb_service_visibility"><?php esc_html_e( 'Visibilità', 'free-bookly' ); ?></label>
								<select id="fb_service_visibility" name="visibility" style="width: 100%;">
									<option value="public" <?php selected( $service_to_edit ? $service_to_edit->visibility : 'public', 'public' ); ?>><?php esc_html_e( 'Pubblico', 'free-bookly' ); ?></option>
									<option value="private" <?php selected( $service_to_edit ? $service_to_edit->visibility : '', 'private' ); ?>><?php esc_html_e( 'Privato (Solo Admin)', 'free-bookly' ); ?></option>
								</select>
							</div>

							<div class="fb-form-group">
								<label for="fb_service_time_slot"><?php esc_html_e( 'Fascia Oraria (Slot Step)', 'free-bookly' ); ?></label>
								<select id="fb_service_time_slot" name="time_slot_length" style="width: 100%;">
									<option value="0" <?php selected( $service_to_edit ? $service_to_edit->time_slot_length : 0, 0 ); ?>><?php esc_html_e( 'Predefinito Globale', 'free-bookly' ); ?></option>
									<option value="15" <?php selected( $service_to_edit ? $service_to_edit->time_slot_length : 0, 15 ); ?>>15 min</option>
									<option value="30" <?php selected( $service_to_edit ? $service_to_edit->time_slot_length : 0, 30 ); ?>>30 min</option>
									<option value="45" <?php selected( $service_to_edit ? $service_to_edit->time_slot_length : 0, 45 ); ?>>45 min</option>
									<option value="60" <?php selected( $service_to_edit ? $service_to_edit->time_slot_length : 0, 60 ); ?>>60 min</option>
								</select>
							</div>
						</div>

						<!-- Margine Cuscinetto / Buffer Time -->
						<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px;">
							<strong style="font-size: 12px; color: #0f172a; display: block; margin-bottom: 6px;"><?php esc_html_e( 'Tempo di Margine (Buffer Time per pulizia/preparazione):', 'free-bookly' ); ?></strong>
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
								<div>
									<label style="font-size: 11px;"><?php esc_html_e( 'Prima (minuti)', 'free-bookly' ); ?></label>
									<input type="number" name="buffer_before" min="0" step="5" value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->buffer_before ) : '0'; ?>">
								</div>
								<div>
									<label style="font-size: 11px;"><?php esc_html_e( 'Dopo (minuti)', 'free-bookly' ); ?></label>
									<input type="number" name="buffer_after" min="0" step="5" value="<?php echo $service_to_edit ? esc_attr( $service_to_edit->buffer_after ) : '0'; ?>">
								</div>
							</div>
						</div>

						<!-- Regola Assegnazione fornitore QUALSIASI -->
						<div class="fb-form-group">
							<label for="fb_any_rule"><?php esc_html_e( 'Regola di Assegnazione per "QUALSIASI Collaboratore"', 'free-bookly' ); ?></label>
							<select id="fb_any_rule" name="any_provider_rule" style="width: 100%;">
								<option value="random" <?php selected( $service_to_edit ? $service_to_edit->any_provider_rule : 'random', 'random' ); ?>><?php esc_html_e( 'Casuale tra i disponibili', 'free-bookly' ); ?></option>
								<option value="first" <?php selected( $service_to_edit ? $service_to_edit->any_provider_rule : '', 'first' ); ?>><?php esc_html_e( 'Primo in ordine di elenco', 'free-bookly' ); ?></option>
								<option value="least_busy" <?php selected( $service_to_edit ? $service_to_edit->any_provider_rule : '', 'least_busy' ); ?>><?php esc_html_e( 'Meno occupato in quel giorno', 'free-bookly' ); ?></option>
								<option value="least_expensive" <?php selected( $service_to_edit ? $service_to_edit->any_provider_rule : '', 'least_expensive' ); ?>><?php esc_html_e( 'Più economico (in base al prezzo personalizzato)', 'free-bookly' ); ?></option>
							</select>
						</div>

						<div class="fb-form-group">
							<label for="fb_service_image"><?php esc_html_e( 'URL Immagine Servizio (Catalogo & Notifiche)', 'free-bookly' ); ?></label>
							<input type="url" id="fb_service_image" name="image_url" value="<?php echo $service_to_edit ? esc_url( $service_to_edit->image_url ) : ''; ?>" placeholder="https://tuosito.com/uploads/servizio.jpg">
						</div>

						<div class="fb-form-group">
							<label for="fb_service_info"><?php esc_html_e( 'Descrizione & Informazioni ({service_info})', 'free-bookly' ); ?></label>
							<textarea id="fb_service_info" name="info" rows="3"><?php echo $service_to_edit ? esc_textarea( $service_to_edit->info ) : ''; ?></textarea>
						</div>

						<div class="fb-form-actions">
							<button type="submit" class="button button-primary">
								<?php echo $service_to_edit ? esc_html__( 'Aggiorna Servizio', 'free-bookly' ) : esc_html__( 'Salva Servizio', 'free-bookly' ); ?>
							</button>
							<?php if ( $service_to_edit ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services' ) ); ?>" class="button fb-btn-cancel"><?php esc_html_e( 'Annulla', 'free-bookly' ); ?></a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>

			<!-- Tabella Elenco Servizi -->
			<div class="fb-col-table">
				<div class="fb-card">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
						<h2 style="margin: 0;"><?php esc_html_e( 'Elenco Servizi', 'free-bookly' ); ?> (<?php echo count( $services ); ?>)</h2>
						
						<!-- Filtro Categoria -->
						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; align-items: center; gap: 6px;">
							<input type="hidden" name="page" value="free-bookly-services">
							<label for="filter_cat" style="font-size: 12px; color: #64748b;"><?php esc_html_e( 'Filtra categoria:', 'free-bookly' ); ?></label>
							<select id="filter_cat" name="category_filter" onchange="this.form.submit();">
								<option value="0"><?php esc_html_e( 'Tutte le Categorie', 'free-bookly' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $selected_category, $cat->id ); ?>>
										<?php echo esc_html( $cat->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</form>
					</div>

					<table class="wp-list-table widefat striped fb-admin-table">
						<thead>
							<tr>
								<th style="width: 40px;">ID</th>
								<th style="width: 30px;"></th>
								<th><?php esc_html_e( 'Titolo & Categoria', 'free-bookly' ); ?></th>
								<th style="width: 100px;"><?php esc_html_e( 'Durata', 'free-bookly' ); ?></th>
								<th style="width: 100px;"><?php esc_html_e( 'Prezzo', 'free-bookly' ); ?></th>
								<th style="width: 90px; text-align: center;"><?php esc_html_e( 'Visibilità', 'free-bookly' ); ?></th>
								<th style="width: 140px; text-align: center;"><?php esc_html_e( 'Azioni', 'free-bookly' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $services ) ) : ?>
								<tr>
									<td colspan="7"><?php esc_html_e( 'Nessun servizio registrato. Utilizza il modulo a sinistra per aggiungere il primo.', 'free-bookly' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $services as $service ) : ?>
									<tr>
										<td><?php echo esc_html( $service->id ); ?></td>
										<td style="text-align: center;">
											<span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: <?php echo esc_attr( $service->color ); ?>;" title="<?php echo esc_attr( $service->color ); ?>"></span>
										</td>
										<td>
											<strong><?php echo esc_html( $service->title ); ?></strong>
											<div style="font-size: 11px; color: #64748b;">
												<?php esc_html_e( 'Categoria:', 'free-bookly' ); ?> <strong><?php echo esc_html( $service->get_category_name() ); ?></strong>
												<?php if ( $service->buffer_before > 0 || $service->buffer_after > 0 ) : ?>
													&bull; <?php esc_html_e( 'Buffer:', 'free-bookly' ); ?> +<?php echo esc_html( $service->buffer_before ); ?>m / +<?php echo esc_html( $service->buffer_after ); ?>m
												<?php endif; ?>
											</div>
										</td>
										<td><?php echo esc_html( $service->duration ); ?> min</td>
										<td><strong><?php echo esc_html( $service->get_formatted_price() ); ?></strong></td>
										<td style="text-align: center;">
											<?php if ( 'private' === $service->visibility ) : ?>
												<span class="fb-badge" style="background: #f1f5f9; color: #64748b;"><?php esc_html_e( 'Privato', 'free-bookly' ); ?></span>
											<?php else : ?>
												<span class="fb-badge" style="background: #dcfce7; color: #15803d;"><?php esc_html_e( 'Pubblico', 'free-bookly' ); ?></span>
											<?php endif; ?>
										</td>
										<td style="text-align: center;">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-services&edit=' . $service->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Modifica', 'free-bookly' ); ?></a>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=free-bookly-services&action=fb_delete_service&service_id=' . $service->id ), 'fb_delete_service_action' ) ); ?>" class="button button-small fb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Sei sicuro di voler eliminare questo servizio?', 'free-bookly' ); ?>');"><?php esc_html_e( 'Elimina', 'free-bookly' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
