<?php

class Test_FB_Staff_Schedule extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();

		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-service.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-staff.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-staff-schedule.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-appointment.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-availability.php';
	}

	public function test_default_schedule_generation() {
		$staff = new FB_Staff( array( 'full_name' => 'Dr. Default' ) );
		$staff->save();

		$schedule = FB_Staff_Schedule::get_schedule( $staff->id );

		$this->assertCount( 7, $schedule );
		$this->assertEquals( 1, $schedule[1]['is_working_day'] ); // Lunedì
		$this->assertEquals( '09:00', $schedule[1]['start_time'] );
		$this->assertEquals( '18:00', $schedule[1]['end_time'] );
		$this->assertNotEmpty( $schedule[1]['breaks'] );
		$this->assertEquals( '13:00', $schedule[1]['breaks'][0]['start_time'] );
		$this->assertEquals( '14:00', $schedule[1]['breaks'][0]['end_time'] );

		// Sabato e Domenica sono di riposo per default
		$this->assertEquals( 0, $schedule[6]['is_working_day'] );
		$this->assertEquals( 0, $schedule[7]['is_working_day'] );
	}

	public function test_save_and_retrieve_custom_schedule() {
		$staff = new FB_Staff( array( 'full_name' => 'Elena Neri' ) );
		$staff->save();

		$custom_days = array(
			2 => array( // Martedì
				'is_working_day' => 1,
				'start_time'     => '10:00',
				'end_time'       => '16:00',
				'breaks'         => array(
					array( 'start_time' => '12:30', 'end_time' => '13:30' ),
				),
			),
			3 => array( // Mercoledì: Giorno libero
				'is_working_day' => 0,
				'start_time'     => '09:00',
				'end_time'       => '18:00',
				'breaks'         => array(),
			),
		);

		$res = FB_Staff_Schedule::save_schedule( $staff->id, $custom_days );
		$this->assertTrue( $res );

		$saved_schedule = FB_Staff_Schedule::get_schedule( $staff->id );

		// Martedì
		$this->assertEquals( 1, $saved_schedule[2]['is_working_day'] );
		$this->assertEquals( '10:00', $saved_schedule[2]['start_time'] );
		$this->assertEquals( '16:00', $saved_schedule[2]['end_time'] );
		$this->assertCount( 1, $saved_schedule[2]['breaks'] );
		$this->assertEquals( '12:30', $saved_schedule[2]['breaks'][0]['start_time'] );
		$this->assertEquals( '13:30', $saved_schedule[2]['breaks'][0]['end_time'] );

		// Mercoledì
		$this->assertEquals( 0, $saved_schedule[3]['is_working_day'] );
	}

	public function test_availability_respects_custom_schedule_and_breaks() {
		$service = new FB_Service( array( 'title' => 'Consulto 60m', 'duration' => 60 ) );
		$service->save();

		$staff = new FB_Staff( array( 'full_name' => 'Prof. Orario' ) );
		$staff->save();
		$staff->set_services( array( $service->id ) );

		// Impostiamo il martedì (giorno 2) per lavorare dalle 10:00 alle 14:00 con pausa 12:00-13:00
		$custom_days = array(
			2 => array(
				'is_working_day' => 1,
				'start_time'     => '10:00',
				'end_time'       => '14:00',
				'breaks'         => array(
					array( 'start_time' => '12:00', 'end_time' => '13:00' ),
				),
			),
		);
		FB_Staff_Schedule::save_schedule( $staff->id, $custom_days );

		// Data che corrisponde a un martedì (es. 2026-10-27 è martedì)
		$tuesday_date = '2026-10-27';

		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service->id, $staff->id, $tuesday_date );

		// Slot attesi:
		// 10:00 (10:00-11:00) -> DISPONIBILE
		// 11:00 (11:00-12:00) -> DISPONIBILE
		// 12:00 (12:00-13:00) -> PAUSA (NON DISPONIBILE)
		// 13:00 (13:00-14:00) -> DISPONIBILE
		// 14:00+ -> Fine turno

		$this->assertContains( '10:00', $slots );
		$this->assertContains( '11:00', $slots );
		$this->assertNotContains( '12:00', $slots, 'Lo slot durante la pausa pranzo non deve essere generato!' );
		$this->assertContains( '13:00', $slots );
		$this->assertNotContains( '09:00', $slots, 'Orari prima del turno non devono essere generati!' );
		$this->assertNotContains( '14:00', $slots, 'Orari oltre la fine turno non devono essere generati!' );
	}

	public function test_availability_on_day_off_returns_empty() {
		$service = new FB_Service( array( 'title' => 'Taglio', 'duration' => 30 ) );
		$service->save();

		$staff = new FB_Staff( array( 'full_name' => 'Operatore Riposo' ) );
		$staff->save();

		// Imposta mercoledì (giorno 3) come giorno di riposo
		$custom_days = array(
			3 => array(
				'is_working_day' => 0,
				'start_time'     => '09:00',
				'end_time'       => '18:00',
				'breaks'         => array(),
			),
		);
		FB_Staff_Schedule::save_schedule( $staff->id, $custom_days );

		// 2026-10-28 è mercoledì
		$wednesday_date = '2026-10-28';

		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service->id, $staff->id, $wednesday_date );

		$this->assertEmpty( $slots, 'In un giorno di riposo non devono esserci slot disponibili.' );
	}
}
