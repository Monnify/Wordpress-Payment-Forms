<?php
/**
 * A class of helper functions that are used in many places.
 *
 * @package    \monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Helper class.
 */
class Helpers {

	/**
	 * Holds class isntance
	 *
	 * @var object \monnify\payment_forms\Helpers
	 */
	protected static $instance = null;

	/**
	 * The array of meta keys and their default values.
	 *
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * An array of the allowed HTML tags
	 *
	 * @var array
	 */
	protected $allowed_html = [];

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$this->defaults = [
			'amount'            => 0,
			'merchant'          => '',
			'paybtn'            => esc_html__( 'Pay', 'payment-forms-for-monnify' ),
			'successmsg'        => esc_html__( 'Thank you for paying!', 'payment-forms-for-monnify' ),
			'loggedin'          => 'no',
			'currency'          => 'NGN',
			'filelimit'         => 2,
			'redirect'          => '',
			'minimum'           => 0,
			'usevariableamount' => 0,
			'variableamount'    => 'Please configure your options:0,None:0',
			'hidetitle'         => 0,
			'subject'           => esc_html__( 'Thank you for your payment', 'payment-forms-for-monnify' ),
			'heading'           => esc_html__( 'We\'ve received your payment', 'payment-forms-for-monnify' ),
			'message'           => esc_html__( 'Your payment was received and we appreciate it.', 'payment-forms-for-monnify' ),
			'sendreceipt'       => 'yes',
			'sendinvoice'       => 'yes',
			'usequantity'       => 'no',
			'useinventory'      => 'no',
			'inventory'         => 0,
			'sold'              => 0,
			'quantity'          => 10,
			'quantityunit'      => esc_html__( 'Quantity', 'payment-forms-for-monnify' ),
			'useagreement'      => 'no',
			'agreementlink'     => '',
			'usesubaccount'     => 'no',
			'subaccountcode'    => '',
			'splittype'         => 'percentage',
			'splitvalue'        => '',
			'feebearer'         => 'no',
		];

		$this->allowed_html = array(
			'small' => array(
				'href' => true,
				'target' => true
			),
			'a' => array(
				'href' => true,
				'target' => true
			),
			'p' => array(),
			'input' => array(
				'type' => true,
				'name' => true,
				'value' => true,
				'class' => true,
				'checked' => true
			),
			'br' => array(),
			'label' => array(
				'for' => true
			),
			'code' => array(),
			'select' => array(
				'class' => true,
				'name' => true,
				'id' => true,
				'style' => true
			),
			'option' => array(
				'value' => true,
				'selected' => true
			),
			'textarea' => array(
				'rows' => true,
				'name' => true,
				'class' => true
			)
			);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object \monnify\payment_forms\Helpers
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	// GETTERS

	/**
	 * Returns the whole plugin settings array, merged with sensible defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = array(
			'mode'               => 'test',
			'test_api_key'       => '',
			'test_secret_key'    => '',
			'test_contract_code' => '',
			'live_api_key'       => '',
			'live_secret_key'    => '',
			'live_contract_code' => '',
		);
		$settings = get_option( 'mff_monnify_settings', array() );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Gets the current mode, 'test' or 'live'.
	 *
	 * @return string
	 */
	public function get_mode() {
		$settings = $this->get_settings();
		return 'live' === $settings['mode'] ? 'live' : 'test';
	}

	/**
	 * Gets the API credentials for the given (or current) mode.
	 *
	 * @param string|null $mode
	 * @return array
	 */
	public function get_credentials( $mode = null ) {
		$settings = $this->get_settings();
		if ( null === $mode ) {
			$mode = $this->get_mode();
		}
		if ( 'live' === $mode ) {
			return array(
				'api_key'       => $settings['live_api_key'],
				'secret_key'    => $settings['live_secret_key'],
				'contract_code' => $settings['live_contract_code'],
			);
		}
		return array(
			'api_key'       => $settings['test_api_key'],
			'secret_key'    => $settings['test_secret_key'],
			'contract_code' => $settings['test_contract_code'],
		);
	}

	/**
	 * Returns the client-side credentials needed to initialize the Monnify SDK.
	 *
	 * @return array
	 */
	public function get_client_credentials() {
		$credentials = $this->get_credentials();
		return array(
			'apiKey'       => $credentials['api_key'],
			'contractCode' => $credentials['contract_code'],
		);
	}

	/**
	 * Fetch an array of the payments by the form ID.
	 *
	 * @param integer $form_id
	 * @param array $args
	 * @return array
	 */
	public function get_payments_by_id( $form_id = 0, $args = array() ) {
        global $wpdb;
		$results = array();
		if ( 0 === (int) $form_id ) {
			return $results;
		}

		$defaults = array(
			'paid'     => '1',
			'order'    => 'desc',
			'orderby'  => 'created_at',
		);
		$args  = wp_parse_args( $args, $defaults );
        $table = $wpdb->prefix . MFF_MONNIFY_TABLE;
		$order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$allowed_orderby = array( 'created_at', 'email', 'amount' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';

		// phpcs:disable WordPress.DB -- Table/column names cannot be parameterised with %s/%i safely across all supported WP versions.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM `{$table}`
				WHERE post_id = %d
				AND paid = %s
				ORDER BY `{$orderby}` {$order}",
				$form_id,
				$args['paid']
			)
		);
		// phpcs:enable

		return $results;
	}

	/**
	 * Gets the payments count for the current form.
	 *
	 * @param int|string $form_id
	 * @return int
	 */
	public function get_payments_count( $form_id ) {
		global $wpdb;
		$table = $wpdb->prefix . MFF_MONNIFY_TABLE;
		$num   = wp_cache_get( 'form_payments_' . $form_id, 'mff_monnify' );
		if ( false === $num ) {
			// phpcs:disable WordPress.DB -- Table name interpolated, not user input.
			$num = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM `{$table}`
					WHERE post_id = %d
					AND paid = '1'",
					$form_id
				)
			);
			// phpcs:enable

			wp_cache_set( 'form_payments_' . $form_id, $num, 'mff_monnify', 60 * 5 );
		}
		return $num;
	}

	/**
	 * Returns an array | string of the countries
	 *
	 * @param boolean $implode
	 * @return array|string
	 */
	public function get_countries( $implode = false ) {
		$countries = [
			esc_html__( 'Nigeria', 'payment-forms-for-monnify' ),
			esc_html__( 'Ghana', 'payment-forms-for-monnify' ),
			esc_html__( 'Kenya', 'payment-forms-for-monnify' ),
			esc_html__( 'South Africa', 'payment-forms-for-monnify' ),
			esc_html__( 'United Kingdom', 'payment-forms-for-monnify' ),
			esc_html__( 'United States', 'payment-forms-for-monnify' ),
		];
		if ( $implode ) {
			$countries = implode( ',', $countries );
		}
		return $countries;
	}

	/**
	 * Returns the states available.
	 *
	 * @param boolean $implode
	 * @return array|string
	 */
	public function get_states( $implode = false ) {
		$states = [
			esc_html__( 'Abia', 'payment-forms-for-monnify' ),
			esc_html__( 'Adamawa', 'payment-forms-for-monnify' ),
			esc_html__( 'Akwa Ibom', 'payment-forms-for-monnify' ),
			esc_html__( 'Anambra', 'payment-forms-for-monnify' ),
			esc_html__( 'Bauchi', 'payment-forms-for-monnify' ),
			esc_html__( 'Bayelsa', 'payment-forms-for-monnify' ),
			esc_html__( 'Benue', 'payment-forms-for-monnify' ),
			esc_html__( 'Borno', 'payment-forms-for-monnify' ),
			esc_html__( 'Cross River', 'payment-forms-for-monnify' ),
			esc_html__( 'Delta', 'payment-forms-for-monnify' ),
			esc_html__( 'Ebonyi', 'payment-forms-for-monnify' ),
			esc_html__( 'Edo', 'payment-forms-for-monnify' ),
			esc_html__( 'Ekiti', 'payment-forms-for-monnify' ),
			esc_html__( 'Enugu', 'payment-forms-for-monnify' ),
			esc_html__( 'FCT', 'payment-forms-for-monnify' ),
			esc_html__( 'Gombe', 'payment-forms-for-monnify' ),
			esc_html__( 'Imo', 'payment-forms-for-monnify' ),
			esc_html__( 'Jigawa', 'payment-forms-for-monnify' ),
			esc_html__( 'Kaduna', 'payment-forms-for-monnify' ),
			esc_html__( 'Kano', 'payment-forms-for-monnify' ),
			esc_html__( 'Katsina', 'payment-forms-for-monnify' ),
			esc_html__( 'Kebbi', 'payment-forms-for-monnify' ),
			esc_html__( 'Kogi', 'payment-forms-for-monnify' ),
			esc_html__( 'Kwara', 'payment-forms-for-monnify' ),
			esc_html__( 'Lagos', 'payment-forms-for-monnify' ),
			esc_html__( 'Nasarawa', 'payment-forms-for-monnify' ),
			esc_html__( 'Niger', 'payment-forms-for-monnify' ),
			esc_html__( 'Ogun', 'payment-forms-for-monnify' ),
			esc_html__( 'Ondo', 'payment-forms-for-monnify' ),
			esc_html__( 'Osun', 'payment-forms-for-monnify' ),
			esc_html__( 'Oyo', 'payment-forms-for-monnify' ),
			esc_html__( 'Plateau', 'payment-forms-for-monnify' ),
			esc_html__( 'Rivers', 'payment-forms-for-monnify' ),
			esc_html__( 'Sokoto', 'payment-forms-for-monnify' ),
			esc_html__( 'Taraba', 'payment-forms-for-monnify' ),
			esc_html__( 'Yobe', 'payment-forms-for-monnify' ),
			esc_html__( 'Zamfara', 'payment-forms-for-monnify' ),
		];
		if ( $implode ) {
			$states = implode( ',', $states );
		}
		return $states;
	}

	/**
	 * Returns the meta fields and their default values.
	 *
	 * @return array
	 */
	public function get_meta_defaults() {
		return $this->defaults;
	}

	/**
	 * Returns the allowed HTML for wp_kses()
	 *
	 * @return array
	 */
	public function get_allowed_html() {
		return $this->allowed_html;
	}

	/**
	 * Retrieve the user's IP address.
	 *
	 * @return string User's IP address.
	 */
	public function get_the_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Get the DB records by the transaction code supplied.
	 *
	 * @param string $code
	 * @param string $column
	 * @return object|false
	 */
	public function get_db_record( $code, $column = 'txn_code' ) {
		global $wpdb;
		$return = false;
		$table  = $wpdb->prefix . MFF_MONNIFY_TABLE;
		$column = in_array( $column, array( 'txn_code', 'txn_code_2' ), true ) ? $column : 'txn_code';

		// phpcs:disable WordPress.DB -- Table/column names interpolated, not user input.
		$record = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM `{$table}`
				WHERE `{$column}` = %s",
				$code
			),
			'OBJECT'
		);
		// phpcs:enable

		if ( ! empty( $record ) && isset( $record[0] ) ) {
			$return = $record[0];
		}
		return $return;
	}

	// FUNCTIONS

	/**
	 * Gets the current forms meta fields values and set the defaults if needed.
	 *
	 * @param \WP_Post $post
	 * @return array
	 */
	public function parse_meta_values( $post ) {
		$new_values = [];
		foreach ( $this->defaults as $key => $default ) {
			$value = get_post_meta( $post->ID, '_' . $key, true );
			if ( '' !== $value && false !== $value ) {
				$new_values[ $key ] = $value;
			}
		}

		$meta = wp_parse_args( $new_values, $this->defaults );
		if ( empty( $meta['inventory'] ) ) {
			if ( ! empty( $meta['sold'] ) ) {
				$meta['inventory'] = $meta['sold'];
			} else {
				$meta['inventory'] = '1';
			}
		}

		// Strip any text from the variable amount field.
		if ( isset( $meta['usevariableamount'] ) && is_string( $meta['usevariableamount'] ) ) {
			$meta['usevariableamount'] = (int) $meta['usevariableamount'];
		}

		$meta['minimum'] = (int) $meta['minimum'];
		return $meta;
	}

	/**
	 * Take an array of the submitted form values and formats it for a Monnify request.
	 *
	 * @param array $metadata
	 * @return array
	 */
	public function format_meta_as_custom_fields( $metadata ) {
		$fields = array();

		foreach ( $metadata as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			switch ( $key ) {
				case 'pf-fname':
					$fields[] = array(
						'display_name'  => esc_html__( 'Full Name', 'payment-forms-for-monnify' ),
						'variable_name' => 'Full_Name',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-vname':
					$fields[] = array(
						'display_name'  => esc_html__( 'Payment Option', 'payment-forms-for-monnify' ),
						'variable_name' => 'Payment Option',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-quantity':
					$fields[] = array(
						'display_name'  => esc_html__( 'Quantity', 'payment-forms-for-monnify' ),
						'variable_name' => 'Quantity',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				default:
					$display_name = ucwords( str_replace( array( '_', '-', 'pf' ), ' ', $key ) );
					$fields[] = array(
						'display_name'  => $display_name,
						'variable_name' => $key,
						'type'          => 'text',
						'value'         => (string) $value,
					);
					break;
			}
		}
		return $fields;
	}

	/**
	 * Formats the metadata for output on the retry form page.
	 *
	 * @param string $data
	 * @return string
	 */
	public function format_meta_as_display_fields( $data ) {
		$new  = json_decode( $data );
		$text = '';

		if ( is_array( $new ) && array_key_exists( 0, $new ) ) {
			foreach ( $new as $item ) {
				if ( 'text' === $item->type ) {
					$text .= sprintf(
						'<div class="span12 unit">
							<label class="label inline">%s:</label>
							<strong>%s</strong>
						</div>',
						esc_html( $item->display_name ),
						esc_html( $item->value )
					);
				} else {
					$text .= sprintf(
						'<div class="span12 unit">
							<label class="label inline">%s:</label>
							<strong><a target="_blank" href="%s">%s</a></strong>
						</div>',
						esc_html( $item->display_name ),
						esc_url( $item->value ),
						esc_html__( 'link', 'payment-forms-for-monnify' )
					);
				}
			}
		} elseif ( is_object( $new ) ) {
			if ( count( get_object_vars( $new ) ) > 0 ) {
				foreach ( $new as $key => $item ) {
					$text .= sprintf(
						'<div class="span12 unit">
							<label class="label inline">%s:</label>
							<strong>%s</strong>
						</div>',
						esc_html( $key ),
						esc_html( $item )
					);
				}
			}
		}
		return $text;
	}

	/**
	 * Generate a new, cryptographically random payment reference.
	 *
	 * @param int $length Byte length used to seed the reference. Default 16.
	 * @return string Generated reference.
	 */
	public function generate_new_code( $length = 16 ) {
		return 'MFF_' . bin2hex( random_bytes( $length ) );
	}

	/**
	 * Check if the given code exists in the database, in either transaction column.
	 *
	 * @param string $code The code to check.
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return bool True if the code exists, false otherwise.
	 */
	public function check_code( $code ) {
		global $wpdb;
		$table = $wpdb->prefix . MFF_MONNIFY_TABLE;

		// phpcs:disable WordPress.DB -- Table name interpolated, not user input.
		$o_exist = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE txn_code = %s OR txn_code_2 = %s",
				$code,
				$code
			)
		);
		// phpcs:enable

		return ( count( $o_exist ) > 0 );
	}

	/**
	 * Finalizes a successful payment. This is the single source of truth for marking a
	 * payment as paid, called from both the ajax confirm handler and the webhook handler
	 * so the amount-check/inventory/email logic never has to be duplicated.
	 *
	 * @param object $record      The DB payment row.
	 * @param array  $verify_data The Monnify transaction data (paymentStatus, amountPaid, etc).
	 * @param int    $quantity    The quantity purchased, used to update inventory.
	 * @return array {result: 'success'|'failed', message: string}
	 */
	public function finalize_successful_payment( $record, $verify_data, $quantity = 1 ) {
		global $wpdb;
		$table = $wpdb->prefix . MFF_MONNIFY_TABLE;
		$meta  = $this->parse_meta_values( get_post( $record->post_id ) );

		if ( 1 === (int) $record->paid ) {
			return array(
				'result'  => 'success',
				'message' => $meta['successmsg'],
			);
		}

		$amount_paid = isset( $verify_data['amountPaid'] ) ? floatval( $verify_data['amountPaid'] ) : 0;
		$paid_at     = isset( $verify_data['paidOn'] ) ? $verify_data['paidOn'] : current_time( 'mysql' );
		$oamount     = floatval( $record->amount );

		if ( 0 === (int) $oamount || 1 === (int) $meta['usevariableamount'] ) {
			$new_amount = $amount_paid;
		} else {
			if ( (int) round( $oamount ) !== (int) round( $amount_paid ) ) {
				return array(
					'result'  => 'failed',
					// translators: %1$s: currency, %2$s: formatted amount required
					'message' => sprintf( esc_html__( 'Invalid amount Paid. Amount required is %1$s%2$s', 'payment-forms-for-monnify' ), $meta['currency'], number_format( $oamount ) ),
				);
			}
			$new_amount = $amount_paid;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET paid = 1, amount = %f, paid_at = %s WHERE id = %d AND paid = 0",
				$new_amount,
				$paid_at,
				$record->id
			)
		);

		if ( ! $updated ) {
			return array(
				'result'  => 'success',
				'message' => $meta['successmsg'],
			);
		}

		$this->update_sold_inventory( $record->post_id, $meta, $quantity );

		$decoded  = json_decode( $record->metadata );
		$fullname = '';
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $item ) {
				if ( isset( $item->variable_name ) && 'Full_Name' === $item->variable_name ) {
					$fullname = $item->value;
				}
			}
		}

		if ( 'yes' === $meta['sendreceipt'] ) {
			/**
			 * Allow 3rd Party Plugins to hook into the email sending.
			 *
			 * 10: Email_Receipt::send_receipt();
			 * 11: Email_Receipt_Owner::send_receipt_owner();
			 */
			do_action( 'mff_monnify_send_receipt', $record->post_id, $meta['currency'], $new_amount, $fullname, $record->email, $record->txn_code, $record->metadata );
			do_action( 'mff_monnify_send_receipt_owner', $record->post_id, $meta['currency'], $new_amount, $fullname, $record->email, $record->txn_code, $record->metadata );
		}

		return array(
			'result'  => 'success',
			'message' => $meta['successmsg'],
		);
	}

	/**
	 * Update the sold inventory with the amount of payments made.
	 *
	 * @param int   $form_id
	 * @param array $meta
	 * @param int   $quantity
	 * @return void
	 */
	protected function update_sold_inventory( $form_id, $meta, $quantity = 1 ) {
		$usequantity = $meta['usequantity'];
		$sold        = '' !== $meta['sold'] ? (int) $meta['sold'] : 0;

		if ( 'yes' === $usequantity ) {
			$sold += (int) $quantity;
		} else {
			$sold++;
		}

		if ( $meta['sold'] ) {
			update_post_meta( $form_id, '_sold', $sold );
		} else {
			add_post_meta( $form_id, '_sold', $sold, true );
		}
	}
}
