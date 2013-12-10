<?php
if( ! class_exists( 'O2O', false ) ) {
	require_once ( __DIR__ . '/src/o2o.php' );
}

if( ! class_exists( 'O2O_Connection_Factory', false ) ) {
	require_once ( __DIR__ . '/src/factory.php' );
}

if( ! class_exists( 'O2O_Query', false ) ) {
	require_once ( __DIR__ . '/src/query.php' );
}

if( ! class_exists( 'O2O_Rewrites', false ) ) {
require_once ( __DIR__ . '/src/rewrites.php' );
}

add_action( 'init', array( 'O2O', 'init' ), 20 );