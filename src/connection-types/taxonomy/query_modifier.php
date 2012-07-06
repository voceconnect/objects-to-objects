<?php

class O2O_Query_Modifier_Taxonomy extends O2O_Query_Modifier {
	
	/**
	 * 
	 * @param WP_Query $wp_query
	 * @param O2O_Connection_Taxonomy $connection
	 * @param array $o2o_query 
	 */
	public static function parse_query($wp_query, $connection, $o2o_query) {
		if($o2o_query['direction'] == 'to') {
			parent::parse_query($wp_query, $connection, $o2o_query);
		} else {
			$object_term = O2O_Connection_Taxonomy::GetObjectTermID($o2o_query['id'], false);
			$tax_query = isset($wp_query->query_vars['tax_query']) ? $wp_query->query_vars['tax_query'] : array();
			
			$tax_query[] = array(
				'taxonomy' => $connection->get_taxonomy(),
				'field' => 'id',
				'terms' => $object_term
			);
			
			$wp_query->query_vars['tax_query'] = $tax_query;
			
			$wp_query->parse_tax_query($wp_query->query_vars);
		}

		
	}
	
	public static function posts_results($wp_query, $connection, $o2o_query) {
		//do nothing
	}
}