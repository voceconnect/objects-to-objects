<?php
/**
 * @coversDefaultClass O2O_Connection_Taxonomy
 */
class O2O_Connection_Taxonomy_Test extends WP_UnitTestCase {

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

		/*
		 * to
		 * from
		 * set_connected_to
		 * get_connected_to_objects
		 * get_connected_from_objects
		 * is_sortable
		 * is_hierarchical
		 * get_query_modifier
		 * get_name
		 * get_args
		 */
	}

	public function test_notHierarchicalFrom() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );
		$this->assertFalse( $connection->is_hierarchical( 'from' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'hier_post_type' );
		$this->assertFalse( $connection->is_hierarchical( 'from' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'hier_post_type', 'post' );
		$this->assertFalse( $connection->is_hierarchical( 'from' ) );
	}

	public function test_hierarchicalToForHierarchicalTypesOnly() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type' );
		$this->assertFalse( $connection->is_hierarchical( 'to' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'hier_post_type' );
		$this->assertTrue( $connection->is_hierarchical( 'to' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'hier_post_type', 'post' );
		$this->assertFalse( $connection->is_hierarchical( 'to' ) );
	}

	public function test_notSortableFrom() {
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
	}

	public function test_sortableTo() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'from' => array(
				'sortable' => true
			)
			) );
		$this->assertFalse( $connection->is_sortable( 'to' ) );

		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
			) );
		$this->assertTrue( $connection->is_sortable( 'to' ) );
	}

	public function test_setConnectedTo() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
			) );

		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );
		$flat_post_id2 = wp_insert_post( array( 'post_title' => 'test flat post2', 'post_type' => 'flat_post_type' ) );

		//test setting 1
		$result = $connection->set_connected_to( $post_id, array( $flat_post_id ) );

		$this->assertTrue( $result );
		$this->assertEquals( array( $flat_post_id ), $connection->get_connected_to_objects( $post_id ) );


		//test setting 1 that it replaced previous
		$result = $connection->set_connected_to( $post_id, array( $flat_post_id2 ) );

		$this->assertTrue( $result );
		$this->assertEquals( array( $flat_post_id2 ), $connection->get_connected_to_objects( $post_id ) );


		//test appending
		$result = $connection->set_connected_to( $post_id, array( $flat_post_id ), true );

		$this->assertTrue( $result );
		$this->assertEquals( array( $flat_post_id2, $flat_post_id ), $connection->get_connected_to_objects( $post_id ) );
	}

	public function test_setConnectedToErrors() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			'to' => array(
				'sortable' => true
			)
			) );


		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );

		//test that wp error returned if invalid post type in from field
		$result = $connection->set_connected_to( $flat_post_id, array( $flat_post_id ) );
		$this->assertInstanceOf( 'WP_Error', $result );

		//test that invalid post type in to array isn't included
		$connection->set_connected_to( $post_id, array( $post_id, $flat_post_id ) );
		$this->assertEquals( array( $flat_post_id ), $connection->get_connected_to_objects( $post_id ) );
	}

	public function test_getConnectedFrom() {
		$connection = new O2O_Connection_Taxonomy( 'test', 'post', 'flat_post_type', array(
			) );


		$post_id = wp_insert_post( array( 'post_title' => 'test post' ) );
		$flat_post_id = wp_insert_post( array( 'post_title' => 'test flat post', 'post_type' => 'flat_post_type' ) );

		$connection->set_connected_to( $post_id, array( $flat_post_id ), true );

		$connected_from = $connection->get_connected_from_objects( $flat_post_id );
		$this->assertEquals( array( $post_id ), $connected_from );
	}

}