<?php

WP_CLI::add_command( 'o2o', 'O2O_CLI' );

class O2O_CLI extends WP_CLI_Command {

	public function db_version() {
		WP_CLI::line( sprintf( 'Current version: %s', get_option( 'o2o_db_version' ) ) );
		WP_CLI::line( sprintf( 'Latest DB version: %s', O2O::DB_VERSION ) );
	}

	public function db_update() {

		$current_db_version = get_option( 'o2o_db_version' );
		if( version_compare( $current_db_version, O2O::DB_VERSION, '<' ) ) {
			WP_CLI::line( sprintf( 'Current DB version: %s', O2O::DB_VERSION ) );
			WP_CLI::line( 'Updating...' );

			$o2o = O2O::GetInstance();
			$o2o->db_update();

			WP_CLI::success( 'Update complete.' );
		} else {
			WP_CLI::success( 'Already up to date.' );
		}
	}
}