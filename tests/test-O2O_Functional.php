<?php

class O2O_Functional_Tests extends WP_UnitTestCase {

	public function setup() {
		global $wp_rewrite;

		//set the permalink structure
		$wp_rewrite->set_permalink_structure( '/blog/%year%/%monthnum%/%day%/%postname%/' );

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

		O2O::Register_Connection( 'post_to_flat', array( 'post' ), array( 'flat_post_type' ), array(
			'rewrite' => 'from',
			'to' => array(
				'sortable' => true
			)
		) );

		O2O::Register_Connection( 'flat_to_hier', array( 'flat_post_type' ), array( 'hier_post_type' ), array(
			'rewrite' => 'from', //from is being listed in the rewrite
		) );
	}

	public function test_sorted_query() {
		global $wpdb;
		$base_post = wp_insert_post( array(
			'post_type' => 'post',
			'post_title' => 'post 1',
			'post_status' => 'publish'
			) );

		$child_flat_1 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 1',
			'post_status' => 'publish'
			) );

		$child_flat_2 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 2',
			'post_status' => 'publish'
			) );

		$child_flat_3 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 3',
			'post_status' => 'publish'
			) );

		$child_flat_4 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 4',
			'post_status' => 'publish'
			) );

		$connection_factory = O2O::GetInstance()->get_connection_factory();

		$connection = $connection_factory->get_connection( 'post_to_flat' );

		$connection->set_connected_to( $base_post, array( $child_flat_1, $child_flat_2, $child_flat_3, $child_flat_4 ) );

		$query = new WP_Query( array(
			'o2o_orderby' => 'post_to_flat',
			'o2o_query' => array(
				'connection' => 'post_to_flat',
				'direction' => 'to',
				'id' => $base_post
			),
			'fields' => 'ids',
			'no_found_rows' => true
			) );

		$post_ids = array_map( 'intval', $query->get_posts() );
		$this->assertEquals( array( $child_flat_1, $child_flat_2, $child_flat_3, $child_flat_4 ), $post_ids );
	}

	public function test_hierarchical_query() {
		global $wpdb;
		$post1 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 1',
			'post_status' => 'publish'
			) );

		$post2 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 2',
			'post_status' => 'publish'
			) );

		$post3 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 3',
			'post_status' => 'publish'
			) );

		$post4 = wp_insert_post( array(
			'post_type' => 'flat_post_type',
			'post_title' => 'flat post 4',
			'post_status' => 'publish'
			) );

		$hier_parent = wp_insert_post( array(
			'post_type' => 'hier_post_type',
			'post_title' => 'hier post 1',
			'post_status' => 'publish'
			) );

		$hier_child_1 = wp_insert_post( array(
			'post_type' => 'hier_post_type',
			'post_title' => 'hier post 2',
			'post_status' => 'publish',
			'post_parent' => $hier_parent
			) );

		$hier_child_2 = wp_insert_post( array(
			'post_type' => 'hier_post_type',
			'post_title' => 'hier post 3',
			'post_status' => 'publish',
			'post_parent' => $hier_parent
			) );

		$hier_1_grand_child = wp_insert_post( array(
			'post_type' => 'hier_post_type',
			'post_title' => 'hier post 4',
			'post_status' => 'publish',
			'post_parent' => $hier_child_1
			) );

		$connection_factory = O2O::GetInstance()->get_connection_factory();

		$connection = $connection_factory->get_connection( 'flat_to_hier' );

		$connection->set_connected_to( $post1, array( $hier_child_1 ) );
		$connection->set_connected_to( $post2, array( $hier_child_2 ) );
		$connection->set_connected_to( $post3, array( $hier_1_grand_child ) );
		$connection->set_connected_to( $post4, array( $hier_1_grand_child ) );

		$query = new WP_Query( array(
			'o2o_query' => array(
				'connection' => 'flat_to_hier',
				'direction' => 'from',
				'id' => $hier_parent
			),
			'fields' => 'ids',
			'no_found_rows' => true
			) );

		$post_ids = array_map( 'intval', $query->get_posts() );
		sort( $post_ids );


		$expected = array( $post1, $post2, $post3, $post4 );
		sort( $expected );

		$this->assertEquals( $expected, $post_ids );
	}

}