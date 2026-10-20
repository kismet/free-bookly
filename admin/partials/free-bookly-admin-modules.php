<?php
/**
 * Vista di amministrazione: Moduli Avanzati (Add-ons)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$module_manager = new Free_Bookly_Module_Manager();
$active_modules = $module_manager->get_active_modules();

$available_modules = array(
	'email_notifications' => array(
		'title'       => 'Modulo Notifiche Email',
		'description' => 'Invia conferme di prenotazione e promemoria automatici ai clienti e allo staff via email.',
		'icon'        => 'dashicons-email-alt',
		'badge'       => 'Essenziale',
	),
	'online_payments' => array(
		'title'       => 'Modulo Pagamenti (Stripe / PayPal)',
		'description' => 'Abilita il pagamento online anticipato, caparre, o pagamento al momento del servizio.',
		'icon'        => 'dashicons-money-alt',
		'badge'       => 'Fiscale & Pagamenti',
	),
	'custom_fields' => array(
		'title'       => 'Modulo Campi Personalizzati',
		'description' => 'Aggiungi note, domande specifiche, upload file o richieste particolari al form di prenotazione.',
		'icon'        => 'dashicons-forms',
		'badge'       => 'Personalizzazione',
	),
	'service_extras' => array(
		'title'       => 'Modulo Servizi Extra & Add-ons',
		'description' => 'Permetti ai clienti di aggiungere trattamenti opzionali che incrementano durata e prezzo.',
		'icon'        => 'dashicons-plus-alt',
		'badge'       => 'Up-selling',
	),
	'coupons_discounts' => array(
		'title'       => 'Modulo Coupon & Promozioni',
		'description' => 'Crea sconti in percentuale o importo fisso per campagne marketing e clienti fidelizzati.',
		'icon'        => 'dashicons-tickets-alt',
		'badge'       => 'Marketing',
	),
	'waiting_list' => array(
		'title'       => 'Modulo Lista d\'Attesa',
		'description' => 'Consente ai clienti di registrarsi in coda se gli slot sono esauriti in una data specifica.',
		'icon'        => 'dashicons-clock',
		'badge'       => 'Logistica',
	),
);

$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
?>

<div class="wrap fb-admin-wrap">
	<h1 class="wp-heading-inline">Moduli Avanzati (Add-ons Gratuiti)</h1>
	<hr class="wp-header-end">

	<p class="description" style="font-size: 14px; margin-bottom: 20px;">
		Tutti i moduli avanzati di Free Bookly sono <strong>100% gratuiti e open source</strong>. Attiva solo le funzioni che ti servono per mantenere WordPress leggero e veloce.
	</p>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Stato dei moduli aggiornato con successo.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=free-bookly-modules' ) ); ?>">
		<?php wp_nonce_field( 'fb_save_modules_action', 'fb_modules_nonce' ); ?>
		<input type="hidden" name="action" value="fb_save_modules">

		<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
			<?php foreach ( $available_modules as $mod_key => $mod_info ) : ?>
				<?php $is_active = in_array( $mod_key, $active_modules, true ); ?>
				<div class="fb-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid <?php echo $is_active ? '#10b981' : '#cbd5e1'; ?>;">
					<div>
						<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
							<div style="display: flex; align-items: center; gap: 8px;">
								<span class="dashicons <?php echo esc_attr( $mod_info['icon'] ); ?>" style="font-size: 24px; width: 24px; height: 24px; color: #0284c7;"></span>
								<h3 style="margin: 0; font-size: 16px; font-weight: 600;"><?php echo esc_html( $mod_info['title'] ); ?></h3>
							</div>
							<span class="fb-badge fb-badge-secondary"><?php echo esc_html( $mod_info['badge'] ); ?></span>
						</div>

						<p style="color: #64748b; font-size: 13px; line-height: 1.5; margin: 0 0 16px 0;">
							<?php echo esc_html( $mod_info['description'] ); ?>
						</p>
					</div>

					<div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f1f5f9;">
						<span style="font-size: 13px; font-weight: 600; color: <?php echo $is_active ? '#10b981' : '#64748b'; ?>;">
							<?php echo $is_active ? 'Attivo' : 'Disattivato'; ?>
						</span>
						<label style="position: relative; display: inline-block; width: 44px; height: 24px;">
							<input type="checkbox" name="active_modules[]" value="<?php echo esc_attr( $mod_key ); ?>" <?php checked( $is_active ); ?> style="opacity: 0; width: 0; height: 0;">
							<span class="fb-switch" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $is_active ? '#10b981' : '#cbd5e1'; ?>; transition: .3s; border-radius: 24px;">
								<span style="position: absolute; height: 18px; width: 18px; left: <?php echo $is_active ? '23px' : '3px'; ?>; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%;"></span>
							</span>
						</label>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<button type="submit" class="button button-primary button-hero">Salva Configurazione Moduli</button>
	</form>
</div>
