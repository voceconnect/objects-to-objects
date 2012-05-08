<?php

/**
 * Query helper used to manipulate connection queries into needed format 
 */
class O2O_Query {
	
	public static function init() {
		add_action('parse_query', array(__CLASS__, '_action_parse_query'));
		add_filter('posts_clauses', array(__CLASS__, '_filter_posts_clauses'), 10, 2);
		add_filter('the_posts', array(__CLASS__, '_filter_the_posts'), 10, 2);
	}
	
	/**
	 * Filters the posts results from the query to reorder the results if needed.
	 * @param array $posts
	 * @param WP_Query $wp_query
	 * @return array 
	 */
	public static function _filter_the_posts($posts, $wp_query) {
		return $posts;
	}
	
	/**
	 * Filters post query clauses based on the connection
	 * @param array $clauses
	 * @param WP_Query $wp_query
	 * @return array 
	 */
	public static function _filter_posts_clauses($clauses, $wp_query) {
		return $clauses;
	}
	
	/**
	 * Runs on the parse_query action to alter the WP_Query->query_vars
	 * based on any used connections in the query
	 * @param WP_Query $wp_query 
	 */
	public static function _action_parse_query($wp_query) {
	}
}
