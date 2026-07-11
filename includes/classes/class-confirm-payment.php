<?php
/**
 * The functions to handle the confirm payment action.
 *
 * @package monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Confirm Payment Class
 */
class Confirm_Payment {

	/**
	 * The helpers class.
	 *
	 * @var \monnify\payment_forms\Helpers
	 */
	public $helpers;

	/**
	 * The transaction column to update.
	 * Defaults to 'txn_code' and 'txn_code_2' when a payment retry is triggered.
	 *
	 * @var string
	 */
	protected $txn_column = 'txn_code';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_mff_monnify_confirm_action', [ $this, 'confirm_payment' ] );
		add_action( 'wp_ajax_nopriv_mff_monnify_confirm_action', [ $this, 'confirm_payment' ] );
	}

	/**
	 * Confirm Payment Functionality.
	 */
	public function confirm_payment() {

		if ( ! isset( $_POST['nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mff-monnify-confirm' ) ) {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Nonce verification is required.', 'payment-forms-for-monnify' ),
			);

			exit( wp_json_encode( $response ) );
		}

		if ( ! isset( $_POST['code'] ) || '' === trim( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) ) {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Did you make a payment?', 'payment-forms-for-monnify' ),
			);

			exit( wp_json_encode( $response ) );
		}

		// If this is a retry payment then set the column accordingly.
		if ( isset( $_POST['retry'] ) ) {
			$this->txn_column = 'txn_code_2';
		}

		$quantity = 1;
		if ( isset( $_POST['quantity'] ) ) {
			$quantity = (int) sanitize_text_field( wp_unslash( $_POST['quantity'] ) );
		}

		$this->helpers = new Helpers();
		$code          = sanitize_text_field( wp_unslash( $_POST['code'] ) );
		$record        = $this->helpers->get_db_record( $code, $this->txn_column );

		if ( false === $record ) {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Payment Verification Failed', 'payment-forms-for-monnify' ),
			);
			echo wp_json_encode( $response );
			die();
		}

		// Verify our transaction with the Monnify API, using OUR OWN stored reference
		// (never the client-supplied transactionReference) as the sole source of truth.
		$transaction = mff_monnify()->classes['transaction-verify']->verify_transaction( $code );

		if ( 'success' !== $transaction['result'] ) {
			$response = array(
				'result'  => 'failed',
				'message' => $transaction['message'],
			);
			echo wp_json_encode( $response );
			die();
		}

		$response = $this->helpers->finalize_successful_payment( $record, $transaction['data'], $quantity );

		if ( 'success' === $response['result'] ) {
			$meta = $this->helpers->parse_meta_values( get_post( $record->post_id ) );
			if ( '' !== $meta['redirect'] ) {
				$response['result'] = 'success2';
				$response['link']   = $this->add_param_to_url( $meta['redirect'], $code );
			}
		}

		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Adds parameters to a URL.
	 *
	 * @param string $url The original URL.
	 * @param string $ref The reference value to add as a parameter.
	 * @return string The modified URL with added parameters.
	 */
	public function add_param_to_url( $url, $ref ) {
		// Parse the URL.
		$parsed_url = wp_parse_url( $url );

		// Parse query parameters into an array.
		parse_str( isset( $parsed_url['query'] ) ? $parsed_url['query'] : '', $query_params );

		// Add the payment reference parameter to the query parameters.
		$query_params['paymentReference'] = $ref;

		// Rebuild the query string.
		$query_string = http_build_query( $query_params );

		// Construct the new URL.
		$new_url  = ( isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '' );
		$new_url .= ( isset( $parsed_url['user'] ) ? $parsed_url['user'] . ( isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '' ) . '@' : '' );
		$new_url .= ( isset( $parsed_url['host'] ) ? $parsed_url['host'] : '' );
		$new_url .= ( isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '' );
		$new_url .= ( isset( $parsed_url['path'] ) ? $parsed_url['path'] : '' );
		$new_url .= ( ! empty( $query_string ) ? '?' . $query_string : '' );
		$new_url .= ( isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '' );

		return $new_url;
	}
}
