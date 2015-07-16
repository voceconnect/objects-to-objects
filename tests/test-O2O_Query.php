<?php

/**
 * @coversDefaultClass O2O_Query
 */
class O2O_Query_Tests extends WP_UnitTestCase {

	protected static function getMethod($name) {
		$class = new ReflectionClass('O2O_Query');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}

	/**
	 * @covers ::init
	 */
	public function test_init() {
		// assuming that WP Test suite has already run init
		$o2o_query = new O2O_Query( new O2O_Connection_Factory() );
		$o2o_query->init();

		$this->assertTrue( ( bool ) has_filter( 'parse_query', array( $o2o_query, '_action_parse_query' ) ) );
		$this->assertTrue( ( bool ) has_filter( 'posts_clauses', array( $o2o_query, '_filter_posts_clauses' ) ) );

		$o2o_query->deinit();
	}

	/**
	 * @covers ::_transform_query_vars
	 */
	public function test_transform_query_vars() {
		$transform_query = $this->getMethod('_transform_query_vars');

		$query_vars = array(
			'connection_name' => 'test_connection_name',
			'connection_dir' => 'to',
			'connected_id' => '1',
			'connected_name' => 'some-post'
			);

		$o2o_query = new O2O_Query(new O2O_Connection_Factory());
		$transform_query->invokeArgs($o2o_query, array(&$query_vars));
		$this->assertArrayHasKey('o2o_query', $query_vars);
		$this->assertInternalType('array', $query_vars['o2o_query']);
		$o2o_qv = $query_vars['o2o_query'];
		$expected = array(
			'connection' => 'test_connection_name',
			'direction' => 'to',
			'id' => '1',
			'post_name' => 'some-post'
			);
		$this->assertEquals($expected, $o2o_qv);
	}

	/**
	 * @covers ::_action_parse_query
	 */
	public function test_action_parse_query() {
		$connection = new O2O_Mock_Connection( 'test', 'post', 'page' );
		$connection_factory = new O2O_Connection_Factory();
		$connection_factory->add( $connection );

		$query_vars = array(
			'o2o_query' => array(
				'connection' => 'test',
				'direction' => 'to',
				'id' => 1
			)
		);

		$query = new WP_Query();
		$query->query_vars = $query_vars;
		$o2o_query = new O2O_Query( $connection_factory );
		$o2o_query->_action_parse_query( $query );
		$this->assertTrue( O2O_Mock_Query_Modifier::wasCalled( 'parse_query' ) );
		$this->assertObjectHasAttribute( 'o2o_connection', $query );
		$this->assertEquals( 'test', $query->o2o_connection );
	}

	/**
	 * @covers ::_filter_posts_clauses
	 */
	public function test_filter_posts_clauses() {
		global $wpdb;
		$args = array(
			'from' => array(
				'sortable' => true
			)
		);
		$connection = new O2O_Mock_Connection( 'test', 'post', 'page', $args );
		$connection_factory = new O2O_Connection_Factory();
		$connection_factory->add( $connection );

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
		$o2o_query = new O2O_Query( $connection_factory );
		$o2o_query->_action_parse_query( $query ); //required for other filters to work properly

		$clauses = $o2o_query->_filter_posts_clauses( array( ), $query );
		$this->assertInternalType( 'array', $clauses );
		$this->assertArrayHasKey( 'orderby', $clauses );
		$this->assertEquals( " find_in_set({$wpdb->posts}.ID, '1, 2') ASC", $clauses['orderby'] );
	}

}