<?php
/*
  Plugin Name:  Payment Forms for Monnify
  Plugin URI:   https://github.com/PaystackHQ/Wordpress-Payment-forms-for-Paystack
  Description:  Payment Forms for Monnify allows you create forms that will be used to bill clients for goods and services via Monnify.
  Version:      1.0.6
  Author:       Monnify
  Author URI:   https://monnify.com
  License:      GPL-2.0+
  License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
*/
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
define( 'MFF_MONNIFY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MFF_MONNIFY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MFF_MONNIFY_MAIN_FILE', __FILE__ );
define( 'MFF_MONNIFY_VERSION', '1.0.6' );
define( 'MFF_MONNIFY_TABLE', 'monnify_forms_payments' );
define( 'MFF_PLUGIN_BASENAME', plugin_basename(__FILE__) );
define( 'MFF_PLUGIN_NAME', 'mff-monnify' );

// Monnify API endpoints.
define( 'MFF_MONNIFY_SANDBOX_BASE_URL', 'https://sandbox.monnify.com' );
define( 'MFF_MONNIFY_LIVE_BASE_URL', 'https://api.monnify.com' );
define( 'MFF_MONNIFY_WEBHOOK_IP', '35.242.133.146' );

include_once MFF_MONNIFY_PLUGIN_PATH . '/includes/classes/class-monnify-forms.php';

/**
 * Returns an instance of the Monnify Payment forms Object
 *
 * @return object \monnify\payment_forms\Payment_Forms()
 */
function mff_monnify() {
	return \monnify\payment_forms\Payment_Forms::get_instance();
}
$GLOBALS['mff_monnify'] = mff_monnify();
