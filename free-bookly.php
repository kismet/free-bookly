<?php
/**
 * Plugin Name:       Free Bookly
 * Plugin URI:        https://github.com/example/free-bookly
 * Description:       L'alternativa Open Source a Bookly per la gestione delle prenotazioni. Completamente modulare e gratuita.
 * Version:           1.0.0
 * Author:            Open Source Community
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       free-bookly
 * Domain Path:       /languages
 */

// Se questo file viene chiamato direttamente, interrompi l'esecuzione.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Attualmente definita la versione del plugin.
 */
define( 'FREE_BOOKLY_VERSION', '1.0.0' );
define( 'FREE_BOOKLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FREE_BOOKLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Il codice che avvia e dirige le operazioni principali del plugin.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-free-bookly.php';

/**
 * Inizia l'esecuzione del plugin.
 *
 * Poiché è tutto basato su oggetti, tramite una singola instanza
 * si controlla il ciclo di vita del plugin.
 */
function run_free_bookly() {
	$plugin = new Free_Bookly();
	$plugin->run();
}
run_free_bookly();
