<?php

class FB_Ajax {

	public function __construct() {
		// Categorie
		add_action( 'wp_ajax_fb_get_categories', array( $this, 'get_categories' ) );
		add_action( 'wp_ajax_nopriv_fb_get_categories', array( $this, 'get_categories' ) );

		// Servizi
		add_action( 'wp_ajax_fb_get_services', array( $this, 'get_services' ) );
		add_action( 'wp_ajax_nopriv_fb_get_services', array( $this, 'get_services' ) );

		// Staff
		add_action( 'wp_ajax_fb_get_staff', array( $this, 'get_staff' ) );
		add_action( 'wp_ajax_nopriv_fb_get_staff', array( $this, 'get_staff' ) );

		// Slot orari disponibili
		add_action( 'wp_ajax_fb_get_available_slots', array( $this, 'get_available_slots' ) );
		add_action( 'wp_ajax_nopriv_fb_get_available_slots', array( $this, 'get_available_slots' ) );

		// Servizi Extra (Add-ons)
		add_action( 'wp_ajax_fb_get_service_extras', array( $this, 'get_service_extras' ) );
		add_action( 'wp_ajax_nopriv_fb_get_service_extras', array( $this, 'get_service_extras' ) );

		// Campi Personalizzati (Custom Fields)
		add_action( 'wp_ajax_fb_get_custom_fields', array( $this, 'get_custom_fields' ) );
		add_action( 'wp_ajax_nopriv_fb_get_custom_fields', array( $this, 'get_custom_fields' ) );

		// Salvataggio Appuntamento
		add_action( 'wp_ajax_fb_save_appointment', array( $this, 'save_appointment' ) );
		add_action( 'wp_ajax_nopriv_fb_save_appointment', array( $this, 'save_appointment' ) );
	}

	/**
	 * Ritorna l'elenco delle categorie di servizi.
	 */
	public function get_categories() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-fb-category.php';
		$categories = FB_Category::get_all();

		$data = array();
		foreach ( $categories as $cat ) {
			$data[] = array(
				'id'          => $cat->id,
				'name'        => $cat->name,
				'description' => $cat->description,
				'image_url'   => $cat->image_url,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Ritorna l'elenco dei servizi (filtrabili per categoria e solo pubblici).
	 */
	public function get_services() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;

		if ( $category_id > 0 ) {
			$services = FB_Service::get_by_category( $category_id, true );
		} else {
			$services = FB_Service::get_all( true );
		}

		$data = array();
		foreach ( $services as $service ) {
			$data[] = array(
				'id'              => $service->id,
				'category_id'     => $service->category_id,
				'category_name'   => $service->get_category_name(),
				'title'           => $service->title,
				'duration'        => $service->duration,
				'price'           => number_format( (float) $service->price, 2, '.', '' ),
				'formatted_price' => $service->get_formatted_price(),
				'image_url'       => $service->image_url,
				'color'           => $service->color,
				'info'            => $service->info,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Ritorna l'elenco dei membri dello staff abilitati (solo pubblici e attivi).
	 */
	public function get_staff() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-fb-staff.php';
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$service = $service_id ? FB_Service::get_by_id( $service_id ) : null;

		if ( $service_id > 0 ) {
			$staff_list = FB_Staff::get_by_service_id( $service_id, true );
			if ( empty( $staff_list ) ) {
				$staff_list = FB_Staff::get_all( false, true );
			}
		} else {
			$staff_list = FB_Staff::get_all( false, true );
		}

		$data = array();
		foreach ( $staff_list as $staff ) {
			$custom_price = $service_id ? $staff->get_custom_price( $service_id ) : null;
			$price_to_format = ( null !== $custom_price ) ? $custom_price : ( $service ? $service->price : 0 );

			$data[] = array(
				'id'              => $staff->id,
				'full_name'       => $staff->full_name,
				'avatar_url'      => $staff->avatar_url,
				'color'           => $staff->color,
				'info'            => $staff->info,
				'custom_price'    => $custom_price,
				'formatted_price' => FB_Settings::format_price( $price_to_format ),
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Calcola e ritorna gli slot disponibili per servizio, staff e data,
	 * considerando la durata base del servizio sommata all'eventuale durata degli extra selezionati.
	 */
	public function get_available_slots() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		$service_id      = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$staff_id        = isset( $_POST['staff_id'] ) ? sanitize_text_field( $_POST['staff_id'] ) : '';
		$date            = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
		$extras_duration = isset( $_POST['extras_duration'] ) ? absint( $_POST['extras_duration'] ) : 0;

		if ( ! $service_id || ! $date ) {
			wp_send_json_error( __( 'Parametri mancanti.', 'free-bookly' ) );
		}

		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-availability.php';

		$service = FB_Service::get_by_id( $service_id );
		$total_duration = $service ? ( $service->duration + $extras_duration ) : 60;

		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service_id, $staff_id, $date, $total_duration );

		wp_send_json_success( $slots );
	}

	/**
	 * Ritorna gli extra attivi disponibili per il servizio selezionato.
	 */
	public function get_service_extras() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-module-manager.php';
		if ( ! Free_Bookly_Module_Manager::is_module_active( 'service_extras' ) ) {
			wp_send_json_success( array() );
		}

		require_once dirname( __FILE__ ) . '/class-fb-service-extra.php';
		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$extras = FB_Service_Extra::get_by_service( $service_id, true );

		$data = array();
		foreach ( $extras as $extra ) {
			$data[] = array(
				'id'              => $extra->id,
				'service_id'      => $extra->service_id,
				'title'           => $extra->title,
				'price'           => number_format( (float) $extra->price, 2, '.', '' ),
				'formatted_price' => $extra->get_formatted_price(),
				'duration'        => $extra->duration,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Ritorna i campi personalizzati definiti per il servizio selezionato o globali.
	 */
	public function get_custom_fields() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-module-manager.php';
		if ( ! Free_Bookly_Module_Manager::is_module_active( 'custom_fields' ) ) {
			wp_send_json_success( array() );
		}

		require_once dirname( __FILE__ ) . '/class-fb-custom-field.php';
		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$fields = FB_Custom_Field::get_by_service( $service_id );

		$data = array();
		foreach ( $fields as $field ) {
			$data[] = array(
				'id'            => $field->id,
				'label'         => $field->label,
				'field_type'    => $field->field_type,
				'options'       => $field->options,
				'options_array' => $field->get_options_array(),
				'is_required'   => (int) $field->is_required,
				'sort_order'    => (int) $field->sort_order,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Salva la prenotazione nel database con stato configurato, fornitore automatico,
	 * servizi extra selezionati e campi personalizzati.
	 */
	public function save_appointment() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		$payload_raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
		$payload = json_decode( $payload_raw, true );

		if ( ! $payload || empty( $payload['service_id'] ) || empty( $payload['date'] ) || empty( $payload['time'] ) ) {
			wp_send_json_error( __( 'Dati prenotazione non validi.', 'free-bookly' ) );
		}

		// 1. Salva o aggiorna Cliente
		require_once dirname( __FILE__ ) . '/class-fb-customer.php';
		$customer = new FB_Customer( array(
			'full_name' => sanitize_text_field( $payload['user']['name'] ),
			'email'     => sanitize_email( $payload['user']['email'] ),
			'phone'     => sanitize_text_field( $payload['user']['phone'] ),
		) );
		$customer->save();

		// 2. Calcolo Extra e Durata Complessiva
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-service-extra.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';
		require_once dirname( __FILE__ ) . '/class-module-manager.php';

		$service = FB_Service::get_by_id( $payload['service_id'] );
		$base_duration = $service ? (int) $service->duration : 60;

		$extras_duration = 0;
		$extras_price    = 0.00;
		$selected_extras = array();

		if ( Free_Bookly_Module_Manager::is_module_active( 'service_extras' ) && ! empty( $payload['extras'] ) && is_array( $payload['extras'] ) ) {
			foreach ( $payload['extras'] as $extra_item ) {
				$extra_id = is_array( $extra_item ) ? absint( $extra_item['id'] ) : absint( $extra_item );
				$extra_obj = FB_Service_Extra::get_by_id( $extra_id );
				if ( $extra_obj && 'active' === $extra_obj->status ) {
					$extras_duration += (int) $extra_obj->duration;
					$extras_price    += (float) $extra_obj->price;
					$selected_extras[] = array(
						'extra_id' => $extra_obj->id,
						'title'    => $extra_obj->title,
						'price'    => $extra_obj->price,
						'duration' => $extra_obj->duration,
					);
				}
			}
		}

		$total_duration = $base_duration + $extras_duration;
		
		$start_date = $payload['date'] . ' ' . $payload['time'] . ':00';
		$start_obj = new DateTime( $start_date );
		$end_obj = clone $start_obj;
		$end_obj->modify( '+' . $total_duration . ' minutes' );
		$end_date = $end_obj->format( 'Y-m-d H:i:s' );

		// 3. Risolvi lo Staff: se "any" o non specificato, assegna secondo la regola del servizio considerando la durata totale
		require_once dirname( __FILE__ ) . '/class-fb-availability.php';
		if ( empty( $payload['staff_id'] ) || 'any' === (string) $payload['staff_id'] ) {
			$assigned_staff_id = FB_Availability::assign_staff_for_any( $payload['service_id'], $payload['date'], $payload['time'], $total_duration );
		} else {
			$assigned_staff_id = absint( $payload['staff_id'] );
		}

		// 4. Gestione Campi Personalizzati
		require_once dirname( __FILE__ ) . '/class-fb-custom-field.php';
		$custom_fields_saved = array();

		if ( Free_Bookly_Module_Manager::is_module_active( 'custom_fields' ) ) {
			$defined_fields = FB_Custom_Field::get_by_service( $payload['service_id'] );
			$user_inputs = isset( $payload['custom_fields'] ) && is_array( $payload['custom_fields'] ) ? $payload['custom_fields'] : array();

			// Normalizza user_inputs in mappa [ field_id => value ]
			$inputs_by_id = array();
			foreach ( $user_inputs as $k => $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) && isset( $item['value'] ) ) {
					$inputs_by_id[ (int) $item['id'] ] = $item['value'];
				} elseif ( is_scalar( $item ) ) {
					$clean_k = (int) str_replace( 'field_', '', (string) $k );
					$inputs_by_id[ $clean_k ] = $item;
				}
			}

			foreach ( $defined_fields as $df ) {
				$val = isset( $inputs_by_id[ $df->id ] ) ? $inputs_by_id[ $df->id ] : '';
				
				if ( is_array( $val ) ) {
					$val_clean = implode( ', ', array_map( 'sanitize_text_field', $val ) );
				} else {
					$val_clean = sanitize_textarea_field( (string) $val );
				}

				if ( $df->is_required && '' === trim( $val_clean ) ) {
					/* translators: %s: Nome del campo obbligatorio */
					wp_send_json_error( sprintf( __( 'Il campo obbligatorio "%s" non è stato compilato.', 'free-bookly' ), $df->label ) );
				}

				if ( '' !== trim( $val_clean ) ) {
					$custom_fields_saved[] = array(
						'id'    => $df->id,
						'label' => $df->label,
						'type'  => $df->field_type,
						'value' => $val_clean,
					);
				}
			}
		}

		// 5. Stato predefinito configurato in Impostazioni Generali
		$default_status = FB_Settings::get_default_appointment_status();

		// 6. Salva Appuntamento
		require_once dirname( __FILE__ ) . '/class-fb-appointment.php';
		$appointment = new FB_Appointment( array(
			'staff_id'      => $assigned_staff_id,
			'service_id'    => $payload['service_id'],
			'customer_id'   => $customer->id,
			'start_date'    => $start_date,
			'end_date'      => $end_date,
			'status'        => $default_status,
			'custom_fields' => $custom_fields_saved,
			'extras_price'  => $extras_price,
		) );

		if ( $appointment->save() ) {
			// Inserisci le righe degli extra collegati
			if ( ! empty( $selected_extras ) ) {
				global $wpdb;
				$extras_table = $wpdb->prefix . 'fb_appointment_extras';
				foreach ( $selected_extras as $extra_row ) {
					$wpdb->insert(
						$extras_table,
						array(
							'appointment_id' => $appointment->id,
							'extra_id'       => $extra_row['extra_id'],
							'title'          => $extra_row['title'],
							'price'          => $extra_row['price'],
							'duration'       => $extra_row['duration'],
						),
						array( '%d', '%d', '%s', '%f', '%d' )
					);
				}
			}

			/**
			 * Scatta dopo la creazione di una prenotazione dal frontend.
			 *
			 * @param int $appointment_id
			 */
			do_action( 'fb_appointment_created', $appointment->id );

			wp_send_json_success( array(
				'message'        => __( 'Prenotazione confermata con successo!', 'free-bookly' ),
				'appointment_id' => $appointment->id,
				'status'         => $default_status,
			) );
		} else {
			wp_send_json_error( __( 'Errore durante il salvataggio della prenotazione.', 'free-bookly' ) );
		}
	}
}

new FB_Ajax();
