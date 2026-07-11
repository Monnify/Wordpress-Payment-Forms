<?php
/**
 * The main plugin class, this will return the and instance of the class.
 *
 * @package    \monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Activation class.
 */
class Activation {

	/**
	 * Install Monnify DB Table
	 */
	public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . MFF_MONNIFY_TABLE;
        $table_name = sanitize_text_field( $table_name );

		// Include the DB Functions.
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		Activation::create_tables( $table_name );
		Activation::seed_default_settings();
		update_option( 'mff_monnify_db_version', '1.0' );
    }

	/**
	 * Install Monnify DB Table
	 */
	public static function create_tables( $table_name ) {
		global $wpdb;
        $query = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				post_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				email varchar(100) DEFAULT '' NOT NULL,
				metadata longtext,
				paid tinyint(1) NOT NULL DEFAULT '0',
				txn_code varchar(191) DEFAULT '' NOT NULL,
				txn_code_2 varchar(191) DEFAULT '' NULL,
				amount decimal(12,2) NOT NULL DEFAULT '0.00',
				ip varchar(45) NOT NULL,
				deleted_at datetime NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				paid_at datetime NULL,
				modified datetime NULL,
				PRIMARY KEY  (id),
				KEY post_id (post_id),
				KEY txn_code (txn_code),
				KEY txn_code_2 (txn_code_2)
			) {$wpdb->get_charset_collate()};";
		dbDelta( $query );
	}

	/**
	 * Seeds the default plugin settings, if not already present.
	 *
	 * @return void
	 */
	public static function seed_default_settings() {
		$existing = get_option( 'mff_monnify_settings', false );
		if ( false === $existing ) {
			update_option(
				'mff_monnify_settings',
				array(
					'mode'               => 'test',
					'test_api_key'       => '',
					'test_secret_key'    => '',
					'test_contract_code' => '',
					'live_api_key'       => '',
					'live_secret_key'    => '',
					'live_contract_code' => '',
				)
			);
		}
	}
}
