<?php

class O2O_Admin {
	public static function init() {
		add_action('add_meta_boxes', array(__CLASS__, 'add_meta_box'), 10, 2);
	}
	
	public static function add_meta_box($post_type, $post) {
		foreach(O2O_Connection_Factory::Get_Connections() as $connection) {
			if( in_array( $post_type, $connection->from() ) ) {
				add_meta_box($connection->name, $connection->args['title']['from'], array(__CLASS__, 'meta_box'), $post_type, 'side', 'low', $connection->name);
			}
		}
	}
	
	public static function meta_box($post, $metabox) {
		$connection_name = $metabox['args'];
		var_dump($connection_name);
	}
}