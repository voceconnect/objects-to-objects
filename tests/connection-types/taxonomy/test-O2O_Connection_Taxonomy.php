<?php
/**
 * @coversDefaultClass O2O_Connection_Taxonomy
 */
class O2O_Connection_Taxonomy_Test extends WP_UnitTestCase {

	protected static function getMethod($name) {
		$class = new ReflectionClass('O2O_Connection_Taxonomy');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}

	public function setup() {
		//register test post_types
		$args = array(
			'hierarchical' => false,
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => true,
			'rewrite' => array(
				'slug' => 'flat-posts',
				'with_front' => false,
				'pages' => true,
				'feeds' => true,
			),
			'capability_type' => 'page',
		);
		register_post_type( 'flat_post_type', $args );

		$args = array(
			'hierarchical' => true,
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => true,
			'rewrite' => array(
				'slug' => 'hierarchical-posts',
				'with_front' => false,
				'pages' => true,
				'feeds' => true,
			),
			'capability_type' => 'page',
		);
		register_post_type( 'hier_post_type', $args );

	}

	/**
	 * @covers ::is_hierarchical
	 */
	public function test_not_hierarchical_for_from_direction() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );
		$this->assertFalse( $connection->is_hierarchical( 'from' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'hier_post_type' );
		$this->assertFalse( $connection->is_hierarchical( 'from' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'hier_post_type', 'post' );
		$this->assertFalse( $connection->is_hierarchical( 'from' ) );
	}

	/**
	 * @covers ::is_hierarchical
	 */
	public function test_hierarchical_for_to_direction_hierarchical_post_type() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'hier_post_type' );
		$this->assertTrue( $connection->is_hierarchical( 'to' ) );
	}

	/**
	 * @covers ::is_hierarchical
	 */
	public function test_not_hierarchical_for_to_direction_flat_post_type() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );
		$this->assertFalse( $connection->is_hierarchical( 'to' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'hier_post_type', 'post' );
		$this->assertFalse( $connection->is_hierarchical( 'to' ) );
	}

	/**
	 * @covers ::is_sortable
	 */
	public function test_not_sortable_from() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'from' => array(
				'sortable' => true
			)
			) );
		$this->assertFalse( $connection->is_sortable( 'from' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
			) );
		$this->assertFalse( $connection->is_sortable( 'from' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type');
		$this->assertFalse( $connection->is_sortable( 'from' ) );
	}

	/**
	 * @covers ::is_sortable
	 */
	public function test_sortable_defaults_to_false() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );
		$this->assertFalse( $connection->is_sortable( 'to' ) );
	}

	/**
	 * @covers ::is_sortable
	 */
	public function test_sortable_false_when_set_to_false() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => false
			)
			) );
		$this->assertFalse( $connection->is_sortable( 'to' ) );
	}

	/**
	 * @covers ::is_sortable
	 */
	public function test_sortable_true_when_set_to_true() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'from' => array(
				'sortable' => true
			)
			) );
		$this->assertFalse( $connection->is_sortable( 'to' ) );
	}

	/**
	 * @covers ::create_term_for_object
	 */
	public function test_create_term_for_object_returns_valid_term_id() {
		$create_term = $this->getMethod('create_term_for_object');
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$term_id = $create_term->invokeArgs($connection, array($post_id));

		$this->assertInternalType('int', $term_id);
		$term = get_term($term_id, $connection->get_taxonomy());
		$this->assertNotNull($term);
	}

	/**
	 * @covers ::create_term_for_object
	 */
	public function test_create_term_for_object_returns_same_term_id() {
		$create_term = $this->getMethod('create_term_for_object');
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$term_id1 = $create_term->invokeArgs($connection, array($post_id));

		$term_id2 = $create_term->invokeArgs($connection, array($post_id));
		
		$this->assertEquals($term_id1, $term_id2);
	}

	/**
	 * @covers ::create_term_for_object
	 */
	public function test_create_term_for_object_sets_term_parent_for_hierarchical_object() {
		$create_term = $this->getMethod('create_term_for_object');
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'hier_post_type' );

		$post_id1 = wp_insert_post( array( 'post_title' => 'test post', 'post_type' => 'hier_post_type' ) );
		$post_id2 = wp_insert_post( array( 'post_title' => 'test post', 'post_type' => 'hier_post_type',  'post_parent' => $post_id1 ) );

		$term_id1 = $create_term->invokeArgs($connection, array($post_id1));

		$term_id2 = $create_term->invokeArgs($connection, array($post_id2));
		
		$term = get_term($term_id2, $connection->get_taxonomy());
		$this->assertEquals($term_id1, $term->parent);
	}

	/**
	 * @covers ::get_object_termID
	 */
	public function test_get_object_termID_returns_false_for_invalid_post_type() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );

		$term_id = $connection->get_object_termID($post_id);
		$this->assertFalse($term_id);
	}

	/**
	 * @covers ::get_object_termID
	 */
	public function test_get_object_termID_returns_newly_created_term_id() {
        $connection = $this->getMockBuilder('O2O_Connection_Taxonomy')
        	->setConstructorArgs(array('test', 'post', 'post'))
        	->setMethods(array('create_term_for_object'))
        	->getMock();

        $post_id = wp_insert_post( array( 'post_title' => 'test post' ) );

        $connection->expects($this->once())
        	->method('create_term_for_object')
        	->with( $this->equalTo( $post_id ) )
			->will( $this->returnValue( 99 ) );

		$term_id = $connection->get_object_termID($post_id);
		$this->assertEquals(99, $term_id);
	}

	/**
	 * @covers ::get_object_termID
	 */
	public function test_get_object_termID_returns_previously_created_term_id() {
        $connection = $this->getMockBuilder('O2O_Connection_Taxonomy')
        	->setConstructorArgs(array('test', 'post', 'post'))
        	->setMethods(array('create_term_for_object'))
        	->getMock();

        $post_id = wp_insert_post( array( 'post_title' => 'test post' ) );

        $create_term = $this->getMethod('create_term_for_object');

        $original_term_id = $create_term->invokeArgs($connection, array($post_id));
		$term_id = $connection->get_object_termID($post_id);
		$this->assertEquals($original_term_id, $term_id);
	}

	/**
	 * @covers ::get_object_termID
	 */
	public function test_get_object_termID_returns_false_if_does_not_exist_and_create_is_false() {
		$connection = $this->getMockBuilder('O2O_Connection_Taxonomy')
        	->setConstructorArgs(array('test', 'post', 'post'))
        	->setMethods(array('create_term_for_object'))
        	->getMock();

        $post_id = wp_insert_post( array( 'post_title' => 'test post' ) );

        $connection->expects($this->never())
        	->method('create_term_for_object');

		$term_id = $connection->get_object_termID($post_id, false);
		$this->assertFalse($term_id);
	}

	/**
	 * @covers ::set_connected_to
	 */
	public function test_set_connected_to_returns_wperror_for_invalid_from_posttype() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type');

		$post_id = wp_insert_post( array( 'post_title' => 'test post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );

		//test setting 1
		$result = $connection->set_connected_to( $post_id, array( $flat_post_id ) );

		$this->assertWPError($result);
	}

	/**
	 * @covers ::set_connected_to
	 */
	public function test_set_connected_to_sets_terms() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type');

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id2 = wp_insert_post( array( 'post_title' => 'test flat post2', 'post_type' => 'flat_post_type' ) );

		
		$result = $connection->set_connected_to( $post_id, array( $flat_post_id, $flat_post_id2 ) );

		$this->assertTrue($result);

		$term_id1 = $connection->get_object_termID($flat_post_id);
		$term_id2 = $connection->get_object_termID($flat_post_id2);
		$expected = array($term_id1, $term_id2);

		sort($expected);

		$terms = wp_get_object_terms($post_id, $connection->get_taxonomy(), array('fields' => 'ids'));
		$this->assertEquals($expected, $terms);
	}

	/**
	 * @covers ::set_connected_to
	 */
	public function test_set_connected_to_appends_terms() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type');

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id2 = wp_insert_post( array( 'post_title' => 'test flat post2', 'post_type' => 'flat_post_type' ) );
		$flat_post_id3 = wp_insert_post( array( 'post_title' => 'test flat post3', 'post_type' => 'flat_post_type' ) );

		
		$connection->set_connected_to( $post_id, array( $flat_post_id ) );

		$result = $connection->set_connected_to( $post_id, array( $flat_post_id2, $flat_post_id3 ), true );

		$this->assertTrue($result);

		$term_id1 = $connection->get_object_termID($flat_post_id);
		$term_id2 = $connection->get_object_termID($flat_post_id2);
		$term_id3 = $connection->get_object_termID($flat_post_id3);
		$expected = array($term_id1, $term_id2, $term_id3);

		sort($expected);

		$terms = wp_get_object_terms($post_id, $connection->get_taxonomy(), array('fields' => 'ids'));
		$this->assertEquals($expected, $terms);
	}

	/**
	 * @covers ::set_connected_to
	 */
	public function test_set_connected_to_sets_terms_sorted() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
		));

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id2 = wp_insert_post( array( 'post_title' => 'test flat post2', 'post_type' => 'flat_post_type' ) );

		
		$result = $connection->set_connected_to( $post_id, array( $flat_post_id2, $flat_post_id ) );

		$this->assertTrue($result);

		$term_id1 = $connection->get_object_termID($flat_post_id);
		$term_id2 = $connection->get_object_termID($flat_post_id2);
		$expected = array($term_id2, $term_id1);

		$terms = wp_get_object_terms($post_id, $connection->get_taxonomy(), array('orderby' => 'term_order', 'fields' => 'ids'));
		$this->assertEquals($expected, $terms);
	}

	/**
	 * @covers ::set_connected_to
	 */
	public function test_set_connected_to_appends_terms_sorted() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
		));

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id2 = wp_insert_post( array( 'post_title' => 'test flat post2', 'post_type' => 'flat_post_type' ) );
		$flat_post_id3 = wp_insert_post( array( 'post_title' => 'test flat post3', 'post_type' => 'flat_post_type' ) );

		$connection->set_connected_to( $post_id, array( $flat_post_id2 ) );

		$result = $connection->set_connected_to( $post_id, array( $flat_post_id3, $flat_post_id ), true );

		$this->assertTrue($result);

		$term_id1 = $connection->get_object_termID($flat_post_id);
		$term_id2 = $connection->get_object_termID($flat_post_id2);
		$term_id3 = $connection->get_object_termID($flat_post_id3);
		$expected = array($term_id2, $term_id3, $term_id1);

		$terms = wp_get_object_terms($post_id, $connection->get_taxonomy(), array('orderby' => 'term_order', 'fields' => 'ids'));
		$this->assertEquals($expected, $terms);
	}

	/**
	 * @covers ::get_connected_to_objects
	 */
	public function test_get_connected_to_objects_returns_wperror_for_invalid_from_post_type() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'flat_post_type', 'post');

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );

		$result = $connection->get_connected_to_objects($post_id);

		$this->assertWPError($result);
	}

	/**
	 * @covers ::get_connected_to_objects
	 * @depends test_set_connected_to_sets_terms_sorted
	 */
	public function test_get_connected_to_objects_returns_sorted_object_ids() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
		));

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id2 = wp_insert_post( array( 'post_title' => 'test flat post2', 'post_type' => 'flat_post_type' ) );
		$flat_post_id3 = wp_insert_post( array( 'post_title' => 'test flat post3', 'post_type' => 'flat_post_type' ) );

		$expected = array( $flat_post_id, $flat_post_id3, $flat_post_id2 );
		$connection->set_connected_to( $post_id,  $expected);

		$result = $connection->get_connected_to_objects($post_id);

		$this->assertEquals($expected, $result);		
	}

	/**
	 * @covers ::get_connected_from_objects
	 */
	public function test_get_connected_from_objects_returns_wperror_for_invalid_from_post_type() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'flat_post_type', 'post');

		$post_id = wp_insert_post( array( 'post_title' => 'test post', 'post_type' => 'flat_post_type') );

		$result = $connection->get_connected_from_objects($post_id);

		$this->assertWPError($result);
	}

	/**
	 * @covers ::get_connected_from_objects
	 * @depends test_set_connected_to_sets_terms
	 */
	public function test_get_connected_from_objects() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
		));

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$post_id2 = wp_insert_post( array( 'post_title' => 'test post2' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );

		$connection->set_connected_to( $post_id,  array( $flat_post_id ) );
		$connection->set_connected_to( $post_id2,  array( $flat_post_id ) );

		$expected = array($post_id, $post_id2);
		sort($expected);

		$result = $connection->get_connected_from_objects($flat_post_id);
		$this->assertInternalType('array', $result);

		sort($result);
		$this->assertEquals($expected, $result);		
	}

}