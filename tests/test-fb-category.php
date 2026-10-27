<?php

class Test_FB_Category extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-category.php';
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-service.php';
	}

	public function test_category_crud() {
		$cat = new FB_Category( array(
			'name'        => 'Massaggi',
			'description' => 'Tutti i tipi di massaggi',
			'sort_order'  => 1,
		) );
		$this->assertTrue( $cat->save() );
		$this->assertGreaterThan( 0, $cat->id );

		$retrieved = FB_Category::get_by_id( $cat->id );
		$this->assertNotNull( $retrieved );
		$this->assertEquals( 'Massaggi', $retrieved->name );

		$retrieved->name = 'Massaggi & Benessere';
		$retrieved->save();

		$updated = FB_Category::get_by_id( $cat->id );
		$this->assertEquals( 'Massaggi & Benessere', $updated->name );

		$this->assertTrue( $updated->delete() );
		$this->assertNull( FB_Category::get_by_id( $cat->id ) );
	}

	public function test_category_service_count() {
		$cat = new FB_Category( array( 'name' => 'Estetica' ) );
		$cat->save();

		$service = new FB_Service( array(
			'title'       => 'Pulizia Viso',
			'category_id' => $cat->id,
			'duration'    => 45,
			'price'       => 50,
		) );
		$service->save();

		$this->assertEquals( 1, FB_Category::count_services( $cat->id ) );
	}
}
