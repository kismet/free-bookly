<?php

/**
 * Classe responsabile del calcolo degli slot disponibili per la prenotazione,
 * considerando orari di lavoro personalizzati, festività, pause, buffer time,
 * dimensione delle fasce orarie e assegnazione del collaboratore per la selezione "Qualsiasi".
 */
class FB_Availability {

	/**
	 * Ritorna gli slot disponibili per una data specifica.
	 * 
	 * @param int $service_id ID del servizio richiesto
	 * @param mixed $staff_id ID del membro dello staff (int) o 'any'/0
	 * @param string $date Data nel formato 'Y-m-d'
	 * @return array Array di slot disponibili ordinati (es. ['09:00', '09:15', '09:30', ...])
	 */
	public function get_available_slots( $service_id, $staff_id, $date ) {
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-staff.php';
		require_once dirname( __FILE__ ) . '/class-fb-staff-schedule.php';
		require_once dirname( __FILE__ ) . '/class-fb-holiday.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		$service = FB_Service::get_by_id( $service_id );
		if ( ! $service ) {
			return array();
		}

		// 1. Verifica se l'intera azienda è chiusa per festività in questa data
		if ( FB_Holiday::is_holiday( $date, 0 ) ) {
			return array();
		}

		// 2. Se lo staff è "any" o 0, controlla tutti i membri del team abilitati al servizio
		if ( empty( $staff_id ) || 'any' === (string) $staff_id ) {
			$staff_list = FB_Staff::get_by_service_id( $service_id, true ); // solo staff pubblici e attivi
			if ( empty( $staff_list ) ) {
				$staff_list = FB_Staff::get_all( false, true );
			}

			if ( empty( $staff_list ) ) {
				return array();
			}

			$merged_slots = array();
			foreach ( $staff_list as $member ) {
				$slots = $this->get_staff_slots( $service, $member->id, $date );
				$merged_slots = array_merge( $merged_slots, $slots );
			}

			$unique_slots = array_values( array_unique( $merged_slots ) );
			sort( $unique_slots );
			return $unique_slots;
		}

		return $this->get_staff_slots( $service, absint( $staff_id ), $date );
	}

	/**
	 * Calcola gli slot per un singolo membro dello staff considerando festività, turni, pause e buffer time.
	 *
	 * @param FB_Service $service
	 * @param int $staff_id
	 * @param string $date
	 * @return array
	 */
	public function get_staff_slots( $service, $staff_id, $date ) {
		require_once dirname( __FILE__ ) . '/class-fb-holiday.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		// 1. Verifica se lo staff è in ferie / giorno libero
		if ( FB_Holiday::is_holiday( $date, $staff_id ) ) {
			return array();
		}

		$day_index = (int) date( 'N', strtotime( $date ) ); // 1 = Lunedì, 7 = Domenica

		// 2. Recupera la pianificazione del collaboratore per questo giorno
		$day_schedule = FB_Staff_Schedule::get_day_schedule( $staff_id, $day_index );

		if ( ! $day_schedule || empty( $day_schedule['is_working_day'] ) ) {
			// Giorno di riposo / chiuso
			return array();
		}

		$work_start = new DateTime( $date . ' ' . $day_schedule['start_time'] . ':00' );
		$work_end   = new DateTime( $date . ' ' . $day_schedule['end_time'] . ':00' );

		if ( $work_start >= $work_end ) {
			return array();
		}

		$breaks = ! empty( $day_schedule['breaks'] ) ? $day_schedule['breaks'] : array();

		// 3. Recupera appuntamenti esistenti per questo collaboratore in questa data
		$existing_appointments = $this->get_appointments_for_date( $staff_id, $date );

		// 4. Parametri temporali del servizio
		$duration_minutes = $service->duration;
		$buffer_before    = absint( $service->buffer_before );
		$buffer_after     = absint( $service->buffer_after );

		// Dimensione fascia oraria (Time slot length): step di avanzamento per l'orario di inizio
		$slot_step = ! empty( $service->time_slot_length ) ? $service->time_slot_length : FB_Settings::get_time_slot_length();
		if ( $slot_step <= 0 ) {
			$slot_step = $duration_minutes;
		}

		$slots = array();
		$current_time = clone $work_start;

		while ( $current_time < $work_end ) {
			// Lo slot effettivo dell'appuntamento (durata pura)
			$slot_end = clone $current_time;
			$slot_end->modify( '+' . $duration_minutes . ' minutes' );

			// Con buffer time per la verifica di occupazione
			$occupied_start = clone $current_time;
			if ( $buffer_before > 0 ) {
				$occupied_start->modify( '-' . $buffer_before . ' minutes' );
			}

			$occupied_end = clone $slot_end;
			if ( $buffer_after > 0 ) {
				$occupied_end->modify( '+' . $buffer_after . ' minutes' );
			}

			// Se la durata del servizio sfora l'orario di lavoro dello staff
			if ( $slot_end > $work_end ) {
				break;
			}

			// Se il buffer prima sfora l'inizio turno
			if ( $occupied_start < $work_start ) {
				$current_time->modify( '+' . $slot_step . ' minutes' );
				continue;
			}

			$is_free = true;
			$break_jump = false;

			// Controlla collisioni con pause
			foreach ( $breaks as $break ) {
				$break_start = new DateTime( $date . ' ' . $break['start_time'] . ':00' );
				$break_end   = new DateTime( $date . ' ' . $break['end_time'] . ':00' );

				// Sovrapposizione: Max(A1, B1) < Min(A2, B2)
				if ( max( $occupied_start, $break_start ) < min( $occupied_end, $break_end ) ) {
					$is_free = false;
					if ( $current_time < $break_end ) {
						$current_time = clone $break_end;
						$break_jump = true;
					}
					break;
				}
			}

			if ( $break_jump ) {
				continue;
			}

			// Controlla collisioni con appuntamenti esistenti
			if ( $is_free ) {
				foreach ( $existing_appointments as $appointment ) {
					$app_start = new DateTime( $appointment->start_date );
					$app_end   = new DateTime( $appointment->end_date );

					if ( max( $occupied_start, $app_start ) < min( $occupied_end, $app_end ) ) {
						$is_free = false;
						break;
					}
				}
			}

			if ( $is_free ) {
				$slots[] = $current_time->format( 'H:i' );
			}

			// Avanza secondo la dimensione della fascia oraria (step standard es. 15 min)
			$current_time->modify( '+' . $slot_step . ' minutes' );
		}

		return $slots;
	}

	/**
	 * Seleziona automaticamente il miglior membro dello staff quando il cliente sceglie "Qualsiasi" (ANY provider).
	 *
	 * @param int    $service_id
	 * @param string $date
	 * @param string $time
	 * @return int ID del membro dello staff scelto.
	 */
	public static function assign_staff_for_any( $service_id, $date, $time ) {
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-staff.php';

		$service = FB_Service::get_by_id( $service_id );
		if ( ! $service ) {
			return 0;
		}

		$rule = ! empty( $service->any_provider_rule ) ? $service->any_provider_rule : 'random';

		$candidates = FB_Staff::get_by_service_id( $service_id, true );
		if ( empty( $candidates ) ) {
			$candidates = FB_Staff::get_all( false, true );
		}

		if ( empty( $candidates ) ) {
			return 0;
		}

		$avail = new self();
		$available_staff = array();

		// Filtra solo quelli effettivamente liberi in questo slot
		foreach ( $candidates as $member ) {
			$slots = $avail->get_staff_slots( $service, $member->id, $date );
			if ( in_array( $time, $slots, true ) ) {
				$available_staff[] = $member;
			}
		}

		if ( empty( $available_staff ) ) {
			return $candidates[0]->id;
		}

		// Applica la regola di selezione
		if ( 'first' === $rule ) {
			return $available_staff[0]->id;
		}

		if ( 'least_busy' === $rule || 'most_busy' === $rule ) {
			global $wpdb;
			$table_app = $wpdb->prefix . 'fb_appointments';
			$counts = array();

			foreach ( $available_staff as $staff ) {
				$count = (int) $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_app} WHERE staff_id = %d AND DATE(start_date) = %s AND status != 'cancelled'",
					$staff->id,
					$date
				) );
				$counts[ $staff->id ] = $count;
			}

			if ( 'least_busy' === $rule ) {
				asort( $counts );
			} else {
				arsort( $counts );
			}

			$chosen_id = array_key_first( $counts );
			return $chosen_id ? $chosen_id : $available_staff[0]->id;
		}

		if ( 'least_expensive' === $rule || 'most_expensive' === $rule ) {
			$prices = array();
			foreach ( $available_staff as $staff ) {
				$custom = $staff->get_custom_price( $service_id );
				$prices[ $staff->id ] = ( null !== $custom ) ? $custom : $service->price;
			}

			if ( 'least_expensive' === $rule ) {
				asort( $prices );
			} else {
				arsort( $prices );
			}

			$chosen_id = array_key_first( $prices );
			return $chosen_id ? $chosen_id : $available_staff[0]->id;
		}

		// Default: 'random'
		$random_key = array_rand( $available_staff );
		return $available_staff[ $random_key ]->id;
	}

	/**
	 * Recupera gli appuntamenti di un membro dello staff per un determinato giorno.
	 */
	private function get_appointments_for_date( $staff_id, $date ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_appointments';
		
		$start_of_day = $date . ' 00:00:00';
		$end_of_day   = $date . ' 23:59:59';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} 
			WHERE staff_id = %d 
			AND status != 'cancelled' 
			AND (start_date <= %s AND end_date >= %s)",
			$staff_id,
			$end_of_day,
			$start_of_day
		);

		$results = $wpdb->get_results( $query, ARRAY_A );
		$appointments = array();
		
		if ( $results ) {
			require_once dirname( __FILE__ ) . '/class-fb-appointment.php';
			foreach ( $results as $row ) {
				$appointments[] = new FB_Appointment( $row );
			}
		}

		return $appointments;
	}
}
