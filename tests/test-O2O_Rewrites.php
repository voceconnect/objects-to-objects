<?php

class O2O_Rewrites_Test extends WP_UnitTestCase {

	protected $connection_factory;

	public function setup() {
		global $wp_rewrite;

		//set the permalink structure
		$wp_rewrite->set_permalink_structure( '/blog/%year%/%monthnum%/%day%/%postname%/' );

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
			'hierarchical' => false,
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => true,
			'rewrite' => array(
				'slug' => 'flat-posts-no-page',
				'with_front' => false,
				'pages' => false,
				'feeds' => false,
			),
			'capability_type' => 'page',
		);
		register_post_type( 'flat_no_pages', $args );

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

	public function test_init() {
		$rewrites = new O2O_Rewrites( new O2O_Connection_Factory() );
		$rewrites->init();
		$this->assertTrue( ( bool ) has_filter( 'query_vars', array( $rewrites, 'filter_query_vars' ) ) );
		$this->assertTrue( ( bool ) has_filter( 'delete_option_rewrite_rules', array( $rewrites, 'add_rewrite_rules' ) ) );

		$rewrites->deinit();
	}

	public function test_filter_query_vars() {
		$rewrites = new O2O_Rewrites( new O2O_Connection_Factory() );
		$query_vars = $rewrites->filter_query_vars( array( ) );

		$this->assertTrue( in_array( 'connection_name', $query_vars ) );
		$this->assertTrue( in_array( 'connected_name', $query_vars ) );
		$this->assertTrue( in_array( 'connection_dir', $query_vars ) );
	}

	public function test_add_rewrite_rules_flat_post_type() {
		global $wp_rewrite;

		$args = array(
			'rewrite' => 'to'
		);

		$connection_factory = new O2O_Connection_Factory();

		$connection_factory->register( 'flat_to_flat', 'flat_post_type', 'flat_post_type', $args );
		$connection_factory->register( 'flat_to_flat_np', 'flat_post_type', 'flat_no_pages', $args );

		$o2o_rewrites = new O2O_Rewrites( $connection_factory );

		$o2o_rewrites->add_rewrite_rules();
		$rules = $wp_rewrite->rewrite_rules();

		$required_rewrites = array(
			'flat-posts/([^/]+)/flat-posts/feed/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=flat_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/flat-posts/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=flat_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/flat-posts/page/?([0-9]{1,})/?$' =>
			'index.php?connection_name=flat_to_flat&connected_name=$matches[1]&paged=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/flat-posts/?$' =>
			'index.php?connection_name=flat_to_flat&connected_name=$matches[1]&connection_dir=to',
			'flat-posts/([^/]+)/flat-posts-no-page/?$' =>
			'index.php?connection_name=flat_to_flat_np&connected_name=$matches[1]&connection_dir=to'
		);

		//rewrites skipped since no-page post type has feeds and paging turned off
		$invalid_rewrites = array(
			'flat-posts/([^/]+)/flat-posts-no-page/feed/(feed|rdf|rss|rss2|atom)/?$',
			'flat-posts/([^/]+)/flat-posts-no-page/(feed|rdf|rss|rss2|atom)/?$',
			'flat-posts/([^/]+)/flat-posts-no-page/page/?([0-9]{1,})/?$'
		);

		foreach ( $required_rewrites as $regex => $replace ) {
			$this->assertArrayHasKey( $regex, $rules );
			$this->assertEquals( $rules[$regex], $replace );
		}

		foreach ( $invalid_rewrites as $regex ) {
			$this->assertArrayNotHasKey( $regex, $rules );
		}
	}

	public function test_add_rewrite_rules_hierarchical_post_type() {
		global $wp_rewrite;

		$args = array(
			'rewrite' => 'to'
		);

		$connection_factory = new O2O_Connection_Factory();

		$connection_factory->register( 'hier_to_flat', 'hier_post_type', 'flat_post_type', $args );
		$connection_factory->register( 'flat_to_hier', 'flat_post_type', 'hier_post_type', $args );

		$o2o_rewrites = new O2O_Rewrites( $connection_factory );

		$o2o_rewrites->add_rewrite_rules();
		$rules = $wp_rewrite->rewrite_rules();

		$required_rewrites = array(
			'hierarchical-posts/(.+?)/flat-posts/feed/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=hier_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'hierarchical-posts/(.+?)/flat-posts/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=hier_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'hierarchical-posts/(.+?)/flat-posts/page/?([0-9]{1,})/?$' =>
			'index.php?connection_name=hier_to_flat&connected_name=$matches[1]&paged=$matches[2]&connection_dir=to',
			'hierarchical-posts/(.+?)/flat-posts/?$' =>
			'index.php?connection_name=hier_to_flat&connected_name=$matches[1]&connection_dir=to',
			'flat-posts/([^/]+)/hierarchical-posts/feed/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=flat_to_hier&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/hierarchical-posts/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=flat_to_hier&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/hierarchical-posts/page/?([0-9]{1,})/?$' =>
			'index.php?connection_name=flat_to_hier&connected_name=$matches[1]&paged=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/hierarchical-posts/?$' =>
			'index.php?connection_name=flat_to_hier&connected_name=$matches[1]&connection_dir=to',
		);

		//rewrites skipped since no-page post type has feeds and paging turned off
		$invalid_rewrites = array(
			'flat-posts/([^/]+)/flat-posts-no-page/feed/(feed|rdf|rss|rss2|atom)/?$',
			'flat-posts/([^/]+)/flat-posts-no-page/(feed|rdf|rss|rss2|atom)/?$',
			'flat-posts/([^/]+)/flat-posts-no-page/page/?([0-9]{1,})/?$'
		);

		foreach ( $required_rewrites as $regex => $replace ) {
			$this->assertArrayHasKey( $regex, $rules );
			$this->assertEquals( $rules[$regex], $replace );
		}

		foreach ( $invalid_rewrites as $regex ) {
			$this->assertArrayNotHasKey( $regex, $rules );
		}
	}

	public function test_add_rewrite_rules_post() {
		global $wp_rewrite;

		global $wp_rewrite;

		$args = array(
			'rewrite' => 'to'
		);

		$connection_factory = new O2O_Connection_Factory();

		$connection_factory->register( 'post_to_flat', 'post', 'flat_post_type', $args );
		$connection_factory->register( 'flat_to_post', 'flat_post_type', 'post', $args );

		$o2o_rewrites = new O2O_Rewrites( $connection_factory );

		$o2o_rewrites->add_rewrite_rules();
		$rules = $wp_rewrite->rewrite_rules();

		$required_rewrites = array(
			'blog/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/flat-posts/feed/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=post_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'blog/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/flat-posts/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=post_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'blog/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/flat-posts/page/?([0-9]{1,})/?$' =>
			'index.php?connection_name=post_to_flat&connected_name=$matches[1]&paged=$matches[2]&connection_dir=to',
			'blog/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/flat-posts/?$' =>
			'index.php?connection_name=post_to_flat&connected_name=$matches[1]&connection_dir=to',
			'flat-posts/([^/]+)/blog/feed/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=flat_to_post&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/blog/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=flat_to_post&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/blog/page/?([0-9]{1,})/?$' =>
			'index.php?connection_name=flat_to_post&connected_name=$matches[1]&paged=$matches[2]&connection_dir=to',
			'flat-posts/([^/]+)/blog/?$' =>
			'index.php?connection_name=flat_to_post&connected_name=$matches[1]&connection_dir=to'
		);

		foreach ( $required_rewrites as $regex => $replace ) {
			$this->assertArrayHasKey( $regex, $rules );
			$this->assertEquals( $rules[$regex], $replace );
		}
	}

	public function test_add_rewrite_rules_page() {
		global $wp_rewrite;

		$args = array(
			'rewrite' => 'to'
		);

		$connection_factory = new O2O_Connection_Factory();

		$connection_factory->register( 'page_to_flat', 'page', 'flat_post_type', $args );

		$o2o_rewrites = new O2O_Rewrites( $connection_factory );

		$o2o_rewrites->add_rewrite_rules();
		$rules = $wp_rewrite->rewrite_rules();

		$required_rewrites = array(
			'(.?.+?)/flat-posts/feed/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=page_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'(.?.+?)/flat-posts/(feed|rdf|rss|rss2|atom)/?$' =>
			'index.php?connection_name=page_to_flat&connected_name=$matches[1]&feed=$matches[2]&connection_dir=to',
			'(.?.+?)/flat-posts/page/?([0-9]{1,})/?$' =>
			'index.php?connection_name=page_to_flat&connected_name=$matches[1]&paged=$matches[2]&connection_dir=to',
			'(.?.+?)/flat-posts/?$' =>
			'index.php?connection_name=page_to_flat&connected_name=$matches[1]&connection_dir=to'
		);

		foreach ( $required_rewrites as $regex => $replace ) {
			$this->assertArrayHasKey( $regex, $rules );
			$this->assertEquals( $rules[$regex], $replace );
		}
	}

}