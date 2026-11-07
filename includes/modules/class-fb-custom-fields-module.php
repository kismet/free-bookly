<?php
/**
 * Modulo Campi Personalizzati (Custom Fields)
 *
 * @package Free_Bookly
 * @author Stefano Lenzi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/class-fb-custom-field.php';

class FB_Custom_Fields_Module {

	public function init() {
		// Eventuali hook o filtri addizionali
	}
}
