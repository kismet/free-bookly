<?php

class Test_FB_Settings extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-settings.php';
	}

	public function test_settings_get_set() {
		FB_Settings::set( 'test_key', 'test_value' );
		$this->assertEquals( 'test_value', FB_Settings::get( 'test_key' ) );
		$this->assertEquals( 'fallback', FB_Settings::get( 'non_existent_key', 'fallback' ) );
	}

	public function test_currency_and_price_formatting() {
		FB_Settings::set( 'currency', 'EUR' );
		FB_Settings::set( 'currency_format', '{price} €' );

		$formatted = FB_Settings::format_price( 45.5 );
		$this->assertEquals( '45,50 €', $formatted );

		FB_Settings::set( 'currency_format', '€ {price}' );
		$formatted2 = FB_Settings::format_price( 1200 );
		$this->assertEquals( '€ 1.200,00', $formatted2 );
	}

	public function test_default_slot_length_and_status() {
		FB_Settings::set( 'time_slot_length', 20 );
		$this->assertEquals( 20, FB_Settings::get_time_slot_length() );

		FB_Settings::set( 'default_appointment_status', 'pending' );
		$this->assertEquals( 'pending', FB_Settings::get_default_appointment_status() );
	}

	public function test_company_working_hours() {
		$hours = FB_Settings::get_company_working_hours();
		$this->assertIsArray( $hours );
		$this->assertCount( 7, $hours );
		$this->assertEquals( '09:00', $hours[1]['start_time'] );
	}
}
