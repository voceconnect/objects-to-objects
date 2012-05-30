<?php

/**
 * Query helper used to manipulate the WP_Query for the Taxonomy Connection type handler
 * WP_Query Args:
 *   'o2o_query' array(
 *      'direction' : the side of the connection which the queried objects are pulled in, options are 'to' and 'from'
 * 			'id' : the ID of the object being used as the base of the connections
 *   )
 *   'orderby' => 'connection' : allows the connection to be ordered by the user set connection order if the connection type supports it
 */
class O2O_Query {

	private static $initialized = false;

	public static function init() {
		add_action( 'parse_query', array( __CLASS__, '_action_parse_query' ) );
		add_filter( 'posts_clauses', array( __CLASS__, '_filter_posts_clauses' ), 10, 2 );
		add_filter( 'the_posts', array( __CLASS__, '_filter_the_posts' ), 10, 2 );
	}

	/**
	 * Filters the posts results from the query to reorder the results if needed.
	 * @param array $posts
	 * @param WP_Query $wp_query
	 * @return array 
	 */
	public static function _filter_the_posts( $posts, $wp_query ) {
		return $posts;
	}

	/**
	 * Filters post query clauses based on the connection
	 * @param array $clauses
	 * @param WP_Query $wp_query
	 * @return array 
	 */
	public static function _filter_posts_clauses( $clauses, $wp_query ) {
		return $clauses;
	}

	/**
	 * Runs on the parse_query action to alter the WP_Query->query_vars
	 * based on any used connections in the query.  This standardizes the o2o_query
	 * args and makes any needed property changes to the WP_Query instance
	 * @param WP_Query $wp_query 
	 * 
	 * @todo add query param validation
	 */
	public static function _action_parse_query( $wp_query ) {
		if ( isset( $wp_query->query_vars['o2o_query'] ) && is_array( $wp_query->query_vars['o2o_query'] ) && isset( $wp_query->query_vars['o2o_query']['connection'] ) ) {
			$o2o_query = $wp_query->query_vars['o2o_query'];

			if ( $connection = O2O_Connection_Factory::Get_Connection( $o2o_query['connection'] ) ) {

				$o2o_query = wp_parse_args( $o2o_query, array(
					'direction' => 'to'
					) );

				if ( !in_array( $o2o_query['direction'], array( 'to', 'from' ) ) )
					$o2o_query['direction'] = 'to';

				//orderby handling
				if ( $wp_query->get( 'orderby' ) == $connection->get_name() ) {
					if ( $connection->is_sortable( $o2o_query['direction'] ) ) {
						
					} else {
						//this connection isn't user sortable
						unset( $wp_query->query_vars( 'orderby' ) );
					}
				}
			}
		}
	}

}
