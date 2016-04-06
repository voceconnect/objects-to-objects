<?php

// Use Composer's autoloader if it exists
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/vendor/autoload.php' );
}
// Otherwise, load classes manually
else {
	if ( ! class_exists( 'O2O' ) ) {
		require_once ( __DIR__ . '/src/o2o.php' );
	}

	if ( ! class_exists( 'O2O_Connection_Factory' ) ) {
		require_once ( __DIR__ . '/src/factory.php' );
	}

	if ( ! class_exists( 'O2O_Query' ) ) {
		require_once ( __DIR__ . '/src/query.php' );
	}

	if ( ! class_exists( 'O2O_Rewrites' ) ) {
		require_once ( __DIR__ . '/src/rewrites.php' );
	}

	if ( ! class_exists( 'O2O_Connection_Taxonomy' ) ) {
		require_once ( __DIR__ . '/src/connection-types/taxonomy/taxonomy.php' );
	}
}

$o2oInstance = O2O::GetInstance();
if ( ! has_action( 'init', array( $o2oInstance, 'init' ) ) ) {
	add_action( 'init', array( O2O::GetInstance(), 'init' ), 20 );
}
