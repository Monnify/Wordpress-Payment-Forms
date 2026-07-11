<?php
/**
 * Handles inbound Monnify webhook notifications, so that payments completed
 * asynchronously (e.g. bank transfer, USSD) after the browser tab closes are
 * still marked as paid.
 *
 * @package monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Webhook Class
 */
class Webhook {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_mff_monnify_webhook', [ $this, 'handle_webhook' ] );
		add_action( 'wp_ajax_mff_monnify_webhook', [ $this, 'handle_webhook' ] );
	}

	/**
	 * Handles the inbound webhook request.
	 *
	 * @return void
	 */
	public function handle_webhook() {
		$helpers = Helpers::get_instance();

		if ( ! $this->is_from_monnify_ip() ) {
			status_header( 403 );
			exit;
		}

		$raw_body  = file_get_contents( 'php://input' );
		$signature = isset( $_SERVER['HTTP_MONNIFY_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_MONNIFY_SIGNATURE'] ) ) : '';

		if ( '' === $signature || ! $this->is_signature_valid( $raw_body, $signature, $helpers ) ) {
			status_header( 401 );
			exit;
		}

		$payload = json_decode( $raw_body, true );

		if ( empty( $payload['eventType'] ) || empty( $payload['eventData'] ) ) {
			status_header( 200 );
			exit;
		}

		if ( 'SUCCESSFUL_TRANSACTION' !== $payload['eventType'] ) {
			status_header( 200 );
			exit;
		}

		$event_data         = $payload['eventData'];
		$payment_reference  = isset( $event_data['paymentReference'] ) ? sanitize_text_field( $event_data['paymentReference'] ) : '';

		if ( '' === $payment_reference ) {
			status_header( 200 );
			exit;
		}

		$record       = $helpers->get_db_record( $payment_reference, 'txn_code' );
		if ( false === $record ) {
			$record = $helpers->get_db_record( $payment_reference, 'txn_code_2' );
		}

		if ( false !== $record ) {
			$verify_data = array(
				'paymentReference'     => $event_data['paymentReference'] ?? '',
				'transactionReference' => $event_data['transactionReference'] ?? '',
				'amountPaid'           => $event_data['amountPaid'] ?? 0,
				'paidOn'               => $event_data['paidOn'] ?? current_time( 'mysql' ),
				'paymentStatus'        => 'PAID',
			);
			$helpers->finalize_successful_payment( $record, $verify_data );
		}

		status_header( 200 );
		exit;
	}

	/**
	 * Verifies the request originated from Monnify's published IP, as an extra
	 * layer of defense on top of signature verification.
	 *
	 * @return boolean
	 */
	protected function is_from_monnify_ip() {
		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return false;
		}
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		return hash_equals( MFF_MONNIFY_WEBHOOK_IP, $ip );
	}

	/**
	 * Verifies the Monnify-Signature header against an HMAC-SHA512 hash of the
	 * raw request body, keyed with the current mode's secret key.
	 *
	 * @param string  $raw_body
	 * @param string  $signature
	 * @param Helpers $helpers
	 * @return boolean
	 */
	protected function is_signature_valid( $raw_body, $signature, $helpers ) {
		$credentials = $helpers->get_credentials();
		if ( empty( $credentials['secret_key'] ) ) {
			return false;
		}
		$expected = hash_hmac( 'sha512', $raw_body, $credentials['secret_key'] );
		return hash_equals( $expected, $signature );
	}
}
