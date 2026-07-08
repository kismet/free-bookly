<?php

/**
 * Gestisce la registrazione e l'attivazione dei moduli avanzati.
 */
class Free_Bookly_Module_Manager {

	/**
	 * Array dei moduli registrati.
	 */
	private $modules = array();

	/**
	 * Registra un nuovo modulo nel sistema.
	 * 
	 * @param string $id L'id univoco del modulo.
	 * @param array $args Informazioni sul modulo (nome, descrizione, file_classe).
	 */
	public function register_module( $id, $args ) {
		$this->modules[ $id ] = $args;
	}

	/**
	 * Ritorna tutti i moduli registrati.
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Controlla se un modulo è attivo.
	 */
	public function is_active( $id ) {
		$active_modules = get_option( 'free_bookly_active_modules', array() );
		return in_array( $id, $active_modules );
	}

	/**
	 * Attiva o disattiva un modulo salvando l'opzione nel database.
	 */
	public function toggle_module( $id, $active ) {
		$active_modules = get_option( 'free_bookly_active_modules', array() );

		if ( $active ) {
			if ( ! in_array( $id, $active_modules ) ) {
				$active_modules[] = $id;
			}
		} else {
			if ( ( $key = array_search( $id, $active_modules ) ) !== false ) {
				unset( $active_modules[ $key ] );
			}
		}

		update_option( 'free_bookly_active_modules', array_values( $active_modules ) );
	}

	/**
	 * Carica le classi di tutti i moduli attualmente attivi.
	 */
	public function load_active_modules() {
		foreach ( $this->modules as $id => $args ) {
			if ( $this->is_active( $id ) && isset( $args['class_file'] ) ) {
				require_once $args['class_file'];
				if ( isset( $args['class_name'] ) && class_exists( $args['class_name'] ) ) {
					$module_instance = new $args['class_name']();
					if ( method_exists( $module_instance, 'init' ) ) {
						$module_instance->init();
					}
				}
			}
		}
	}
}
