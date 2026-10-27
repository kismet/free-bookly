<?php

class Test_FB_Holiday extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-holiday.php';
	}

	public function test_company_holiday() {
		$holiday = new FB_Holiday( array(
			'staff_id'      => 0,
			'holiday_date'  => '2026-12-25',
			'title'         => 'Natale',
			'repeat_yearly' => 1,
		) );
		$this->assertTrue( $holiday->save() );
		$this->assertGreaterThan( 0, $holiday->id );

		$this->assertTrue( FB_Holiday::is_holiday( '2026-12-25' ) );
		// In un altro anno, se repeat_yearly è 1
		$this->assertTrue( FB_Holiday::is_holiday( '2027-12-25' ) );
		$this->assertFalse( FB_Holiday::is_holiday( '2026-12-24' ) );
	}

	public function test_staff_holiday() {
		$holiday = new FB_Holiday( array(
			'staff_id'      => 5,
			'holiday_date'  => '2026-09-10',
			'title'         => 'Ferie Marco',
			'repeat_yearly' => 0,
		) );
		$this->assertTrue( $holiday->save() );

		// Verifichiamo che il collaboratore 5 sia in ferie, ma il collaboratore 6 no
		$this->assertTrue( FB_Holiday::is_holiday( '2026-09-10', 5 ) );
		$this->assertFalse( FB_Holiday::is_holiday( '2026-09-10', 6 ) );
	}
}
