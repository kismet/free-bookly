<?php

/**
 * Gestore centralizzato delle impostazioni generali e aziendali di Free Bookly.
 */
class FB_Settings {

	/**
	 * Recupera un'opzione di configurazione con valore di fallback.
	 *
	 * @param string $key Chiave dell'opzione (senza prefisso fb_).
	 * @param mixed  $default Valore di default se non impostata.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$value = get_option( 'fb_' . $key, null );
		if ( null === $value || false === $value ) {
			return $default;
		}
		return $value;
	}

	/**
	 * Salva un'opzione di configurazione.
	 *
	 * @param string $key Chiave dell'opzione.
	 * @param mixed  $value Valore da salvare.
	 * @return bool
	 */
	public static function set( $key, $value ) {
		return update_option( 'fb_' . $key, $value );
	}

	/**
	 * Recupera la dimensione predefinita delle fasce orarie in minuti (es. 15, 30, 60).
	 *
	 * @return int
	 */
	public static function get_time_slot_length() {
		return absint( self::get( 'time_slot_length', 15 ) );
	}

	/**
	 * Recupera lo stato predefinito per i nuovi appuntamenti (approved | pending).
	 *
	 * @return string
	 */
	public static function get_default_appointment_status() {
		return self::get( 'default_appointment_status', 'approved' );
	}

	/**
	 * Recupera la modalità di inclusione degli script (all_pages | booking_pages).
	 *
	 * @return string
	 */
	public static function get_script_loading() {
		return self::get( 'script_loading', 'all_pages' );
	}

	/**
	 * Recupera il codice valuta (EUR, USD, GBP, ecc.).
	 *
	 * @return string
	 */
	public static function get_currency() {
		return self::get( 'currency', 'EUR' );
	}

	/**
	 * Ritorna il simbolo visivo della valuta impostata.
	 *
	 * @return string
	 */
	public static function get_currency_symbol() {
		$currency = self::get_currency();
		$symbols  = array(
			'EUR' => '€',
			'USD' => '$',
			'GBP' => '£',
			'CHF' => 'CHF',
			'CAD' => 'CA$',
			'AUD' => 'AU$',
			'JPY' => '¥',
		);
		return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency;
	}

	/**
	 * Formatta un prezzo numerico secondo il formato impostato.
	 *
	 * @param float $price
	 * @return string
	 */
	public static function format_price( $price ) {
		$symbol = self::get_currency_symbol();
		$format = self::get( 'currency_format', '{price} €' );
		$formatted_num = number_format( (float) $price, 2, ',', '.' );

		// Sostituisci il placeholder {price}
		$formatted = str_replace( '{price}', $formatted_num, $format );
		// Se il format usa € ma la valuta è diversa, adegua
		$formatted = str_replace( '€', $symbol, $formatted );

		return $formatted;
	}

	/**
	 * Recupera gli orari di lavoro aziendali predefiniti (giorni 1-7).
	 *
	 * @return array
	 */
	public static function get_company_working_hours() {
		$hours = self::get( 'company_working_hours', null );
		if ( ! is_array( $hours ) || empty( $hours ) ) {
			$hours = array();
			for ( $d = 1; $d <= 7; $d++ ) {
				$hours[ $d ] = array(
					'is_working'  => ( $d <= 5 ) ? 1 : 0,
					'start_time'  => '09:00',
					'end_time'    => '18:00',
					'break_start' => ( $d <= 5 ) ? '13:00' : '',
					'break_end'   => ( $d <= 5 ) ? '14:00' : '',
				);
			}
		}
		return $hours;
	}

	/**
	 * Salva gli orari di lavoro aziendali.
	 *
	 * @param array $hours
	 * @return bool
	 */
	public static function set_company_working_hours( array $hours ) {
		return self::set( 'company_working_hours', $hours );
	}
}
