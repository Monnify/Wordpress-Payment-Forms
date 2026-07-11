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
 * Plugin class.
 */
final class Payment_Forms {

	/**
	 * Holds class isntance
	 *
	 * @var object \monnify\payment_forms\Payment_Forms
	 */
	protected static $instance = null;

	/**
	 * The package namespace for the plugin.
	 *
	 * @var string
	 */
	public $namespace = '\monnify\payment_forms\\';

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = MFF_PLUGIN_NAME;

	/**
	 * The plugin version number.
	 *
	 * @var string
	 */
	public $version = MFF_MONNIFY_VERSION;

	/**
	 * Holdes the array of classes key => object.
	 *
	 * @var array
	 */
	public $classes = array();

	/**
	 * Helpers functions for the custom payments.
	 *
	 * @var \monnify\payment_forms\Helpers
	 */
	public $helpers;

	/**
	 * Initialize the plugin by setting localization, filters, and
	 * administration functions.
	 *
	 * @access private
	 */
	private function __construct() {
		$this->set_variables();
		$this->include_classes();
		$this->init_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object \monnify\payment_forms\Payment_Forms
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets our plugin variables.
	 *
	 * @return void
	 */
	private function set_variables() {
		$this->classes = array(
			'activation'          => '',
			'setup'               => 'Setup',
			'helpers'             => '',
			'settings'            => 'Settings',
			'forms-list'          => 'Forms_List',
			'submissions'         => 'Submissions',
			'forms-update'        => 'Forms_Update',
			'tinymce-plugin'      => 'TinyMCE_Plugin',
			'form-shortcode'      => 'Form_Shortcode',
			'field-shortcodes'    => 'Field_Shortcodes',
			'api'                 => '',
			'transaction-verify'  => 'Transaction_Verify',
			'form-submit'         => 'Form_Submit',
			'confirm-payment'     => 'Confirm_Payment',
			'webhook'             => 'Webhook',
			'email'               => '',
			'email-invoice'       => 'Email_Invoice',
			'email-receipt'       => 'Email_Receipt',
			'email-receipt-owner' => 'Email_Receipt_Owner',
			'retry-submit'        => 'Retry_Submit',
		);
	}

	/**
	 * Includes our class files
	 *
	 * @return void
	 */
	private function include_classes() {
		foreach ( $this->classes as $key => $name ) {
			include_once MFF_MONNIFY_PLUGIN_PATH . '/includes/classes/class-' . $key . '.php';
			if ( '' !== $name ) {
				$className = $this->namespace . $name;
				$this->classes[$key] = new $className();
			}
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_activation_hook( MFF_MONNIFY_MAIN_FILE, array( '\monnify\payment_forms\activation', 'install' ) );
	}
}
