<?php
/**
 * Eseguito automaticamente quando il plugin viene disinstallato (eliminato) da WordPress.
 * Pulisce in modo sicuro tutte le tabelle personalizzate e le impostazioni del database.
 *
 * @package Free_Bookly
 */

// Se la disinstallazione non è invocata da WordPress, interrompi.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Rimuovi le tabelle custom nell'ordine corretto
$tables = array(
	$wpdb->prefix . 'fb_appointments',
	$wpdb->prefix . 'fb_customers',
	$wpdb->prefix . 'fb_staff_services',
	$wpdb->prefix . 'fb_staff',
	$wpdb->prefix . 'fb_services',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// 2. Rimuovi le opzioni di configurazione registrate
delete_option( 'free_bookly_active_modules' );
delete_option( 'fb_active_modules' );
