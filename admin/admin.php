<?php

class O2O_Admin {

	public static function init() {
		if ( !class_exists( 'Post_Selection_UI' ) ) {
			require_once(__DIR__ . '/post-selection-ui/post-selection-ui.php');
			Post_Selection_UI::init();
		}
		add_action( 'add_meta_boxes', array( __CLASS__, '__action_add_meta_box' ), 10, 2 );
		add_action('save_post', array( __CLASS__, '__action_save_post'));
	}

	public static function __action_add_meta_box( $post_type, $post ) {
		foreach ( O2O_Connection_Factory::Get_Connections() as $connection ) {
			if ( in_array( $post_type, $connection->from() ) ) {
				add_meta_box( $connection->name, $connection->args['title']['from'], array( __CLASS__, 'meta_box' ), $post_type, 'side', 'low', $connection->name );
			}
		}
	}

	public static function meta_box( $post, $metabox ) {


		$connection_name = $metabox['args'];
		$connection = O2O_Connection_Factory::Get_Connection( $connection_name );
		
		$selected = $connection->get_connected_to_objects($post->ID);

		$args = array(
			'post_type' => $connection->to(),
			'selected' => $selected,
		);

		echo post_selection_ui( $connection_name, $args );
		wp_nonce_field('set_' . $connection->name . '_' . $post->ID, $connection->name . '_nonce');
		
	}

	public static function __action_save_post( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}
		$post_type = get_post_type($post_id);
		
		foreach ( O2O_Connection_Factory::Get_Connections() as $connection ) {
			if ( in_array( $post_type, $connection->from() ) && isset($_POST[ $connection->name . '_nonce']) && wp_verify_nonce( $_POST[ $connection->name . '_nonce'], 'set_' . $connection->name . '_' . $post_id ) ) {
				$from_ids = empty( $_POST[$connection->name] ) ? array( ) : array_map( 'intval', explode(',', $_POST[$connection->name] ));
				$connection->set_connected_to( $post_id, $from_ids );
			}
		}
	}

}