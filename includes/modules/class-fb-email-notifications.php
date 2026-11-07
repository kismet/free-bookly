<?php

require_once dirname( __DIR__ ) . '/class-fb-ics.php';

/**
 * Modulo Notifiche Email & Calendario (.ics).
 *
 * Eventi gestiti:
 *  - fb_appointment_created        -> email cliente, staff e amministratore
 *  - fb_appointment_status_changed -> email cliente (approvato / annullato)
 *
 * @author Stefano Lenzi
 */
class FB_Email_Notifications {

	const LOG_OPTION = 'fb_email_log';
	const LOG_LIMIT  = 20;

	/**
	 * Registra gli hook del modulo (chiamato dal Module Manager).
	 */
	public function init() {
		add_action( 'fb_appointment_created', array( $this, 'on_created' ), 10, 1 );
		add_action( 'fb_appointment_status_changed', array( $this, 'on_status_changed' ), 10, 3 );
	}

	/* ------------------------------------------------------------------
	 * Configurazione
	 * ------------------------------------------------------------------ */

	public static function get_placeholders() {
		return array(
			'{customer_name}'       => 'Nome del cliente',
			'{customer_email}'      => 'Email del cliente',
			'{customer_phone}'      => 'Telefono del cliente',
			'{service_name}'        => 'Nome del servizio',
			'{service_price}'       => 'Prezzo del servizio',
			'{staff_name}'          => 'Nome del collaboratore',
			'{appointment_id}'      => 'Numero prenotazione',
			'{appointment_date}'    => 'Data (es. 05/11/2026)',
			'{appointment_time}'    => 'Ora di inizio',
			'{appointment_end_time}' => 'Ora di fine',
			'{appointment_status}'   => 'Stato (Confermato, In attesa, Annullato)',
			'{service_extras}'       => 'Elenco servizi extra selezionati',
			'{custom_fields}'        => 'Campi personalizzati compilati dal cliente',
			'{total_price}'          => 'Prezzo totale (servizio + extra)',
			'{company_name}'         => 'Nome azienda',
			'{company_phone}'       => 'Telefono azienda',
			'{company_email}'       => 'Email azienda',
			'{company_address}'     => 'Indirizzo azienda',
		);
	}

	public static function get_template_labels() {
		return array(
			'customer_created'   => 'Cliente: prenotazione ricevuta',
			'customer_approved'  => 'Cliente: prenotazione confermata',
			'customer_cancelled' => 'Cliente: prenotazione annullata',
			'staff_created'      => 'Staff e amministratore: nuova prenotazione',
		);
	}

	public static function get_default_templates() {
		return array(
			'customer_created'   => array(
				'subject' => 'Prenotazione {appointment_status} - {service_name} il {appointment_date}',
				'body'    => "Gentile {customer_name},\n\nabbiamo registrato la tua prenotazione.\n\nServizio: {service_name}\nCollaboratore: {staff_name}\nData: {appointment_date}\nOra: {appointment_time} - {appointment_end_time}\nPrezzo: {service_price}\nStato: {appointment_status}\n\nTi aspettiamo,\n{company_name}\n{company_phone}\n{company_address}",
			),
			'customer_approved'  => array(
				'subject' => 'Prenotazione confermata - {service_name} il {appointment_date}',
				'body'    => "Gentile {customer_name},\n\nla tua prenotazione è stata confermata.\n\nServizio: {service_name}\nCollaboratore: {staff_name}\nData: {appointment_date}\nOra: {appointment_time} - {appointment_end_time}\n\nA presto,\n{company_name}",
			),
			'customer_cancelled' => array(
				'subject' => 'Prenotazione annullata - {service_name} del {appointment_date}',
				'body'    => "Gentile {customer_name},\n\nla tua prenotazione del {appointment_date} alle {appointment_time} per {service_name} è stata annullata.\n\nPer qualsiasi informazione puoi contattarci al {company_phone}.\n\n{company_name}",
			),
			'staff_created'      => array(
				'subject' => 'Nuova prenotazione #{appointment_id} - {customer_name}',
				'body'    => "È stata ricevuta una nuova prenotazione.\n\nCliente: {customer_name}\nEmail: {customer_email}\nTelefono: {customer_phone}\nServizio: {service_name}\nCollaboratore: {staff_name}\nData: {appointment_date}\nOra: {appointment_time} - {appointment_end_time}\nStato: {appointment_status}",
			),
		);
	}

	public static function get_templates() {
		$defaults = self::get_default_templates();
		$saved    = FB_Settings::get( 'email_templates', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		foreach ( $defaults as $key => $tpl ) {
			if ( isset( $saved[ $key ]['subject'] ) && '' !== trim( $saved[ $key ]['subject'] ) ) {
				$defaults[ $key ]['subject'] = $saved[ $key ]['subject'];
			}
			if ( isset( $saved[ $key ]['body'] ) && '' !== trim( $saved[ $key ]['body'] ) ) {
				$defaults[ $key ]['body'] = $saved[ $key ]['body'];
			}
		}
		return $defaults;
	}

	/**
	 * Flag booleani di abilitazione (default: tutti attivi).
	 */
	public static function is_enabled( $flag ) {
		return (int) FB_Settings::get( 'email_enable_' . $flag, 1 ) === 1;
	}

	/* ------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */

	/**
	 * Sostituisce i placeholder {chiave}. Se $html è true i valori vengono escapati.
	 */
	public static function render( $text, array $vars, $html = false ) {
		$replace = array();
		foreach ( $vars as $key => $value ) {
			$replace[ '{' . $key . '}' ] = $html ? esc_html( $value ) : $value;
		}
		return strtr( $text, $replace );
	}

	public static function status_label( $status ) {
		$labels = array(
			'approved'  => 'Confermato',
			'pending'   => 'In attesa',
			'cancelled' => 'Annullato',
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * Costruisce le variabili dei placeholder a partire da un appuntamento.
	 *
	 * @return array|null Null se i dati collegati non esistono.
	 */
	public static function build_vars( FB_Appointment $appointment ) {
		$service  = FB_Service::get_by_id( $appointment->service_id );
		$staff    = FB_Staff::get_by_id( $appointment->staff_id );
		$customer = FB_Customer::get_by_id( $appointment->customer_id );

		if ( ! $customer ) {
			return null;
		}

		$start = strtotime( $appointment->start_date );
		$end   = strtotime( $appointment->end_date );

		// Extras
		$extras_list = $appointment->get_extras();
		$extras_strings = array();
		$extras_sum = 0.00;
		if ( ! empty( $extras_list ) ) {
			foreach ( $extras_list as $ex ) {
				$extras_strings[] = $ex['title'] . ' (+' . FB_Settings::format_price( $ex['price'] ) . ( ! empty( $ex['duration'] ) ? ', +' . $ex['duration'] . ' min' : '' ) . ')';
				$extras_sum += (float) $ex['price'];
			}
		}
		$extras_formatted = ! empty( $extras_strings ) ? implode( ', ', $extras_strings ) : 'Nessuno';

		// Custom Fields
		$cf_data = $appointment->get_custom_fields_data();
		$cf_strings = array();
		if ( ! empty( $cf_data ) ) {
			foreach ( $cf_data as $item ) {
				if ( is_array( $item ) && isset( $item['label'] ) && isset( $item['value'] ) ) {
					$cf_strings[] = $item['label'] . ': ' . $item['value'];
				} elseif ( is_string( $item ) ) {
					$cf_strings[] = $item;
				}
			}
		}
		$cf_formatted = ! empty( $cf_strings ) ? implode( "\n", $cf_strings ) : 'Nessuno';

		// Prezzo totale
		$base_price = $service ? (float) $service->price : 0.00;
		$total_price_formatted = FB_Settings::format_price( $base_price + $extras_sum );

		return array(
			'customer_name'        => $customer->full_name,
			'customer_email'       => $customer->email,
			'customer_phone'       => $customer->phone,
			'service_name'         => $service ? $service->title : '',
			'service_price'        => $service ? FB_Settings::format_price( $service->price ) : '',
			'service_extras'       => $extras_formatted,
			'custom_fields'        => $cf_formatted,
			'total_price'          => $total_price_formatted,
			'staff_name'           => $staff ? $staff->full_name : '',
			'appointment_id'       => (string) $appointment->id,
			'appointment_date'     => $start ? gmdate( 'd/m/Y', $start ) : '',
			'appointment_time'     => $start ? gmdate( 'H:i', $start ) : '',
			'appointment_end_time' => $end ? gmdate( 'H:i', $end ) : '',
			'appointment_status'   => self::status_label( $appointment->status ),
			'company_name'         => FB_Settings::get( 'company_name', get_bloginfo( 'name' ) ),
			'company_phone'        => FB_Settings::get( 'company_phone', '' ),
			'company_email'        => FB_Settings::get( 'company_email', get_bloginfo( 'admin_email' ) ),
			'company_address'      => FB_Settings::get( 'company_address', '' ),
		);
	}

	/**
	 * Avvolge il corpo testuale in un layout HTML responsive.
	 */
	public static function wrap_html( $body_text, array $vars ) {
		$company = esc_html( $vars['company_name'] );
		$logo    = FB_Settings::get( 'company_logo', '' );
		$header  = $logo
			? '<img src="' . esc_url( $logo ) . '" alt="' . $company . '" style="max-height:48px;">'
			: '<span style="font-size:20px;font-weight:700;color:#ffffff;">' . $company . '</span>';

		$content = wpautop( $body_text );

		return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f1f5f9;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center">'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">'
			. '<tr><td style="background:#0284c7;padding:18px 24px;">' . $header . '</td></tr>'
			. '<tr><td style="padding:24px;font-size:15px;line-height:1.6;">' . $content . '</td></tr>'
			. '<tr><td style="padding:14px 24px;background:#f8fafc;font-size:12px;color:#64748b;">' . $company . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/**
	 * Genera il contenuto ICS per un appuntamento.
	 *
	 * @param string $status_override Forza lo stato ICS (CANCELLED per annullamenti).
	 */
	public static function build_ics( FB_Appointment $appointment, array $vars, $method, $sequence ) {
		$tz    = wp_timezone();
		$start = new DateTimeImmutable( $appointment->start_date, $tz );
		$end   = new DateTimeImmutable( $appointment->end_date, $tz );

		if ( 'cancelled' === $appointment->status ) {
			$ics_status = 'CANCELLED';
		} elseif ( 'pending' === $appointment->status ) {
			$ics_status = 'TENTATIVE';
		} else {
			$ics_status = 'CONFIRMED';
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		$description = sprintf(
			"Servizio: %s\nCollaboratore: %s\nCliente: %s %s",
			$vars['service_name'],
			$vars['staff_name'],
			$vars['customer_name'],
			$vars['customer_phone']
		);

		return FB_ICS::build( array(
			'uid'             => 'fb-appointment-' . $appointment->id . '@' . ( $host ? $host : 'free-bookly' ),
			'start'           => $start,
			'end'             => $end,
			'summary'         => $vars['service_name'] . ' - ' . $vars['company_name'],
			'description'     => $description,
			'location'        => $vars['company_address'],
			'status'          => $ics_status,
			'method'          => 'CANCEL' === $method ? 'CANCEL' : 'REQUEST',
			'sequence'        => $sequence,
			'organizer_name'  => $vars['company_name'],
			'organizer_email' => $vars['company_email'],
			'attendee_name'   => $vars['customer_name'],
			'attendee_email'  => $vars['customer_email'],
		) );
	}

	/* ------------------------------------------------------------------
	 * Invio
	 * ------------------------------------------------------------------ */

	public function on_created( $appointment_id ) {
		$appointment = FB_Appointment::get_by_id( $appointment_id );
		if ( ! $appointment ) {
			return;
		}

		$vars = self::build_vars( $appointment );
		if ( ! $vars ) {
			return;
		}

		if ( self::is_enabled( 'customer_created' ) ) {
			$this->send( 'customer_created', $vars['customer_email'], $appointment, $vars, 'REQUEST', 0 );
		}

		if ( self::is_enabled( 'staff_created' ) ) {
			$recipients = array();
			$staff      = FB_Staff::get_by_id( $appointment->staff_id );
			if ( $staff && is_email( $staff->email ) ) {
				$recipients[] = $staff->email;
			}
			$admin_email = FB_Settings::get( 'company_email', get_bloginfo( 'admin_email' ) );
			if ( is_email( $admin_email ) ) {
				$recipients[] = $admin_email;
			}
			foreach ( array_unique( $recipients ) as $to ) {
				$this->send( 'staff_created', $to, $appointment, $vars, 'REQUEST', 0 );
			}
		}
	}

	public function on_status_changed( $appointment_id, $new_status, $old_status ) {
		$appointment = FB_Appointment::get_by_id( $appointment_id );
		if ( ! $appointment ) {
			return;
		}

		$vars = self::build_vars( $appointment );
		if ( ! $vars ) {
			return;
		}

		if ( 'approved' === $new_status && self::is_enabled( 'customer_approved' ) ) {
			$this->send( 'customer_approved', $vars['customer_email'], $appointment, $vars, 'REQUEST', 1 );
		} elseif ( 'cancelled' === $new_status && self::is_enabled( 'customer_cancelled' ) ) {
			$this->send( 'customer_cancelled', $vars['customer_email'], $appointment, $vars, 'CANCEL', 2 );
		}
	}

	/**
	 * Invia una singola email usando il template indicato.
	 *
	 * @return bool
	 */
	public function send( $template_key, $to, FB_Appointment $appointment, array $vars, $ics_method, $sequence ) {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$templates = self::get_templates();
		if ( ! isset( $templates[ $template_key ] ) ) {
			return false;
		}

		$subject   = wp_strip_all_tags( self::render( $templates[ $template_key ]['subject'], $vars, false ) );
		$body_text = self::render( $templates[ $template_key ]['body'], $vars, true );
		$message   = self::wrap_html( $body_text, $vars );

		$from_name  = FB_Settings::get( 'email_from_name', $vars['company_name'] );
		$from_email = FB_Settings::get( 'email_from_address', $vars['company_email'] );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( is_email( $from_email ) ) {
			$headers[] = sprintf( 'From: %s <%s>', wp_strip_all_tags( $from_name ), $from_email );
		}

		$attachments = array();
		$tmp_file    = '';
		if ( self::is_enabled( 'attach_ics' ) ) {
			$tmp_file = wp_tempnam( 'fb-appointment' );
			if ( $tmp_file ) {
				$ics_path = $tmp_file . '.ics';
				// phpcs:ignore WordPress.WP_SystemFilesystem
				if ( false !== file_put_contents( $ics_path, self::build_ics( $appointment, $vars, $ics_method, $sequence ) ) ) {
					$attachments[] = $ics_path;
					@unlink( $tmp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
					$tmp_file = $ics_path;
				}
			}
		}

		$sent = wp_mail( $to, $subject, $message, $headers, $attachments );

		if ( $tmp_file && file_exists( $tmp_file ) ) {
			@unlink( $tmp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		self::log( $template_key, $to, $subject, $sent, ! empty( $attachments ) );
		return $sent;
	}

	/* ------------------------------------------------------------------
	 * Registro invii (ultimi N), utile per diagnosi
	 * ------------------------------------------------------------------ */

	public static function log( $template_key, $to, $subject, $sent, $has_ics ) {
		$log = get_option( self::LOG_OPTION, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift( $log, array(
			'time'     => current_time( 'mysql' ),
			'template' => $template_key,
			'to'       => $to,
			'subject'  => $subject,
			'sent'     => (bool) $sent,
			'ics'      => (bool) $has_ics,
		) );
		update_option( self::LOG_OPTION, array_slice( $log, 0, self::LOG_LIMIT ), false );
	}

	public static function get_log() {
		$log = get_option( self::LOG_OPTION, array() );
		return is_array( $log ) ? $log : array();
	}
}
