<?php

class O2O_Query_Tests extends WP_UnitTestCase {

	protected $connection_factory;

	public function setup() {
		$this->connection_factory = new O2O_Connection_Factory();
	}

	public function test_init() {
		// assuming that WP Test suite has already run init
		$o2o_query = new O2O_Query( $this->connection_factory );
		$o2o_query->init();

		$this->assertTrue( (bool) has_filter( 'parse_query',  array( $o2o_query, '_action_parse_query' ) ) );
		$this->assertTrue( (bool) has_filter( 'posts_clauses',  array( $o2o_query, '_filter_posts_clauses' ) ) );
		$this->assertTrue( (bool) has_filter( 'posts_results',  array( $o2o_query, '_filter_posts_results' ) ) );
	}

	public function test_action_parse_query() {
		$connection = new O2O_Mock_Connection( 'test', 'post', 'page');
		$this->connection_factory->add( $connection );

		$query_vars = array(
			'o2o_query' => array(
				'connection' => 'test',
				'direction' => 'to',
				'id' => 1
				)
			);

		$query = new WP_Query();
		$query->query_vars = $query_vars;
		$o2o_query = new O2O_Query( $this->connection_factory );
		$o2o_query->_action_parse_query( $query );
		$this->assertTrue( O2O_Mock_Query_Modifier::wasCalled( 'parse_query' ) );
		$this->assertObjectHasAttribute( 'o2o_connection', $query );
		$this->assertEquals( 'test', $query->o2o_connection );
	}

	public function test_filter_posts_results() {
		$connection = new O2O_Mock_Connection( 'test', 'post', 'page');
		$this->connection_factory->add( $connection );

		$query_vars = array(
			'o2o_query' => array(
				'connection' => 'test',
				'direction' => 'to',
				'id' => 1
				)
			);

		$query = new WP_Query();
		$query->query_vars = $query_vars;
		$o2o_query = new O2O_Query( $this->connection_factory );
		$o2o_query->_action_parse_query( $query ); //required for other filters to work properly

		$o2o_query->_filter_posts_results( array(), $query );
		$this->assertTrue( O2O_Mock_Query_Modifier::wasCalled( 'posts_results' ) );
	}

	public function test_filter_posts_clauses() {
		global $wpdb;
		$args = array(
			'from' => array(
				'sortable' => true
				)
			);
		$connection = new O2O_Mock_Connection( 'test', 'post', 'page', $args );
		$this->connection_factory->add( $connection );

		$query_vars = array(
			'o2o_query' => array(
				'connection' => 'test',
				'direction' => 'from',
				'id' => 1
				),
			'orderby' => 'test'
			);

		$query = new WP_Query();
		$query->query_vars = $query_vars;
		$o2o_query = new O2O_Query( $this->connection_factory );
		$o2o_query->_action_parse_query( $query ); //required for other filters to work properly

		$clauses = $o2o_query->_filter_posts_clauses( array(), $query );
		$this->assertInternalType( 'array', $clauses );
		$this->assertArrayHasKey( 'orderby', $clauses );
		$this->assertEquals( " find_in_set({$wpdb->posts}.ID, '1, 2') ASC", $clauses['orderby'] );
	}
	


}