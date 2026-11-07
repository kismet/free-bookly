<?php

class Test_FB_Extras_Custom_Fields extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-service-extra.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-custom-field.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-service.php';
	}

	public function test_service_extras_crud_and_query() {
		$service = new FB_Service( array(
			'title'    => 'Taglio Capelli',
			'duration' => 30,
			'price'    => 25.00,
		) );
		$service->save();

		// Extra specifico per il servizio
		$extra1 = new FB_Service_Extra( array(
			'service_id' => $service->id,
			'title'      => 'Trattamento Lozione Rinforzante',
			'price'      => 10.00,
			'duration'   => 15,
			'status'     => 'active',
		) );
		$this->assertTrue( $extra1->save() );
		$this->assertGreaterThan( 0, $extra1->id );

		// Extra globale per tutti i servizi
		$extra_global = new FB_Service_Extra( array(
			'service_id' => 0,
			'title'      => 'Caffè di benvenuto & Relax',
			'price'      => 2.50,
			'duration'   => 5,
			'status'     => 'active',
		) );
		$this->assertTrue( $extra_global->save() );

		// Recupera extra per il servizio specifico: devono tornare sia il globale sia quello specifico
		$extras = FB_Service_Extra::get_by_service( $service->id, true );
		$this->assertCount( 2, $extras );

		// Modifica
		$extra1->price = 12.00;
		$extra1->save();
		$retrieved = FB_Service_Extra::get_by_id( $extra1->id );
		$this->assertEquals( 12.00, (float) $retrieved->price );

		// Elimina
		$this->assertTrue( FB_Service_Extra::delete( $extra1->id ) );
		$this->assertNull( FB_Service_Extra::get_by_id( $extra1->id ) );
	}

	public function test_custom_fields_crud_and_options() {
		$field = new FB_Custom_Field( array(
			'label'       => 'Come ci hai conosciuto?',
			'field_type'  => 'select',
			'options'     => "Google\nPassaparola\nSocial Network",
			'is_required' => 1,
			'service_id'  => 0,
			'sort_order'  => 2,
		) );
		$this->assertTrue( $field->save() );
		$this->assertGreaterThan( 0, $field->id );

		$retrieved = FB_Custom_Field::get_by_id( $field->id );
		$this->assertNotNull( $retrieved );
		$this->assertEquals( 'Come ci hai conosciuto?', $retrieved->label );
		$this->assertEquals( 1, $retrieved->is_required );

		$options = $retrieved->get_options_array();
		$this->assertCount( 3, $options );
		$this->assertEquals( 'Passaparola', $options[1] );

		// Delete
		$this->assertTrue( FB_Custom_Field::delete( $field->id ) );
		$this->assertNull( FB_Custom_Field::get_by_id( $field->id ) );
	}
}
