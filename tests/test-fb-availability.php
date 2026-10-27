<?php

class Test_FB_Availability extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-service.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-staff.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-appointment.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-availability.php';
	}

	public function test_get_available_slots_without_collision() {
		// Crea Servizio (durata 60 min)
		$service = new FB_Service( array( 'title' => 'Taglio Capelli', 'duration' => 60 ) );
		$service->save();

		// Crea Staff
		$staff = new FB_Staff( array( 'full_name' => 'Mario' ) );
		$staff->save();

		$checker = new FB_Availability();
		// Oramai mockato dalle 09:00 alle 18:00
		$slots = $checker->get_available_slots( $service->id, $staff->id, '2026-08-01' );

		// 9 ore di lavoro = 9 slot da 60 min
		$this->assertCount( 9, $slots );
		$this->assertEquals( '09:00', $slots[0] );
		$this->assertEquals( '10:00', $slots[1] );
	}

	public function test_get_available_slots_with_collision() {
		$service = new FB_Service( array( 'title' => 'Colore', 'duration' => 120 ) );
		$service->save();

		$staff = new FB_Staff( array( 'full_name' => 'Luigi' ) );
		$staff->save();

		// Creiamo un appuntamento per bloccare lo slot dalle 11:00 alle 13:00
		$appointment = new FB_Appointment( array(
			'staff_id'   => $staff->id,
			'service_id' => $service->id,
			'start_date' => '2026-08-01 11:00:00',
			'end_date'   => '2026-08-01 13:00:00',
			'status'     => 'approved'
		) );
		$appointment->save();

		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service->id, $staff->id, '2026-08-01' );

		// Inizia alle 09:00.
		// Slot:
		// 09:00 - 11:00 -> LIBERO
		// 11:00 - 13:00 -> OCCUPATO
		// 13:00 - 15:00 -> LIBERO
		// 15:00 - 17:00 -> LIBERO
		// 17:00 - 19:00 -> Oltre le 18, ignorato.
		
		$this->assertContains( '09:00', $slots );
		$this->assertNotContains( '11:00', $slots );
		$this->assertContains( '13:00', $slots );
		$this->assertContains( '15:00', $slots );
	}

	public function test_availability_with_holiday() {
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-holiday.php';

		$service = new FB_Service( array( 'title' => 'Taglio', 'duration' => 30 ) );
		$service->save();
		$staff = new FB_Staff( array( 'full_name' => 'Sara' ) );
		$staff->save();

		// Impostiamo giorno festivo
		$holiday = new FB_Holiday( array(
			'staff_id'     => 0,
			'holiday_date' => '2026-12-25',
			'title'        => 'Natale'
		) );
		$holiday->save();

		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service->id, $staff->id, '2026-12-25' );
		$this->assertEmpty( $slots );
	}

	public function test_availability_with_buffer_time() {
		// Servizio con buffer_after di 15 min
		$service = new FB_Service( array(
			'title'        => 'Trattamento Speciale',
			'duration'     => 45,
			'buffer_after' => 15,
		) );
		$service->save();
		$staff = new FB_Staff( array( 'full_name' => 'Chiara' ) );
		$staff->save();

		// Appuntamento dalle 10:00 alle 10:45
		$appointment = new FB_Appointment( array(
			'staff_id'   => $staff->id,
			'service_id' => $service->id,
			'start_date' => '2026-08-01 10:00:00',
			'end_date'   => '2026-08-01 10:45:00',
			'status'     => 'approved'
		) );
		$appointment->save();

		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service->id, $staff->id, '2026-08-01' );

		// Poiché c'è un buffer after di 15 min fino alle 11:00, lo slot delle 10:00 e 10:45 non deve essere disponibile
		$this->assertNotContains( '10:00', $slots );
		$this->assertNotContains( '10:45', $slots );
		// Ma alle 11:00 il collaboratore è nuovamente libero
		$this->assertContains( '11:00', $slots );
	}
}
