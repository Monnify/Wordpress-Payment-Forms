<?php
/**
 * The functions to handle retrying an abandoned payment.
 *
 * @package monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retry Submit Class
 */
class Retry_Submit {

	/**
	 * The helpers class.
	 *
	 * @var \monnify\payment_forms\Helpers
	 */
	public $helpers;

	/**
	 * Holds the current form meta
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Holds the current retry meta from the DB
	 *
	 * @var object
	 */
	protected $retry_meta;

	/**
	 * Holds the current form id
	 *
	 * @var int
	 */
	protected $form_id = 0;

	/**
	 * Holds the current transaction reference.
	 *
	 * @var string
	 */
	public $code = '';

	/**
	 * Holds the new reference to use.
	 *
	 * @var string
	 */
	public $new_code = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_mff_monnify_retry_action', [ $this, 'retry_action' ] );
		add_action( 'wp_ajax_nopriv_mff_monnify_retry_action', [ $this, 'retry_action' ] );
	}

	/**
	 * Sets up the data needed to process the retry.
	 *
	 * @return void
	 */
	protected function setup_data() {
		$this->helpers  = new Helpers();
		$this->new_code = $this->generate_code() . '_2';
		$retry_record   = $this->helpers->get_db_record( $this->code );
		if ( false !== $retry_record ) {
			$this->retry_meta = $retry_record;
			$this->form_id    = $this->retry_meta->post_id;
			$this->meta       = $this->helpers->parse_meta_values( get_post( $this->form_id ) );
		}
	}

	/**
	 * Builds the Monnify income split config from the form's subaccount meta, or null if unset.
	 *
	 * @return array|null
	 */
	protected function build_income_split_config() {
		if ( 'yes' !== $this->meta['usesubaccount'] || '' === $this->meta['subaccountcode'] ) {
			return null;
		}

		$split = array(
			'subAccountCode' => $this->meta['subaccountcode'],
			'feeBearer'      => 'yes' === $this->meta['feebearer'],
		);

		if ( 'amount' === $this->meta['splittype'] ) {
			$split['splitAmount'] = floatval( $this->meta['splitvalue'] );
		} else {
			$split['splitPercentage'] = floatval( $this->meta['splitvalue'] );
		}

		return array( $split );
	}

	/**
	 * The action for the retry form.
	 *
	 * @return void
	 */
	public function retry_action() {
		if ( ! isset( $_POST['pf-nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pf-nonce'] ) ), 'mff-monnify-retry' ) ) {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Nonce verification is required.', 'payment-forms-for-monnify' ),
			);
			// Exit here, for not processing further because of the error.
			exit( wp_json_encode( $response ) );
		}

		if ( isset( $_POST['code'] ) && '' !== trim( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) ) {
			$this->code = sanitize_text_field( wp_unslash( $_POST['code'] ) );
		} else {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Code is required', 'payment-forms-for-monnify' ),
			);
			// Exit here, for not processing further because of the error.
			exit( wp_json_encode( $response ) );
		}

		/**
		 * Setup our data to be processed.
		 */
		$this->setup_data();

		$fixedmetadata = json_decode( $this->retry_meta->metadata );
		$quantity      = 1;
		$fullname      = '';
		foreach ( $fixedmetadata as $nvalue ) {
			if ( 'Quantity' === $nvalue->variable_name ) {
				$quantity = $nvalue->value;
			}
			if ( 'Full_Name' === $nvalue->variable_name ) {
				$fullname = $nvalue->value;
			}
		}

		$this->update_retry_code();

		$response = array(
			'result'             => 'success',
			'code'               => $this->new_code,
			'quantity'           => $quantity,
			'email'              => $this->retry_meta->email,
			'name'               => $fullname,
			'total'              => round( floatval( $this->retry_meta->amount ), 2 ),
			'custom_fields'      => $fixedmetadata,
			'currency'           => $this->meta['currency'],
			'incomeSplitConfig'  => $this->build_income_split_config(),
			'paymentDescription' => get_the_title( $this->form_id ),
		);

		// We create 2 nonces here
		// 1 incase the payment fails, and the user needs to try again.
		// 2 if the payment is successful and the confirmation ajax needs to run.
		$response['retryNonce']   = wp_create_nonce( 'mff-monnify-retry' );
		$response['confirmNonce'] = wp_create_nonce( 'mff-monnify-confirm' );

		echo wp_json_encode( $response );

		die();
	}

	/**
	 * Generate a unique Monnify reference that does not yet exist in the database.
	 *
	 * @return string Generated unique reference.
	 */
	public function generate_code() {
		do {
			$code = $this->helpers->generate_new_code();
			$check = $this->helpers->check_code( $code );
		} while ( $check );

		return $code;
	}

	/**
	 * Updates the DB row with the new transaction reference.
	 *
	 * @return void
	 */
	protected function update_retry_code() {
		global $wpdb;
		$table = $wpdb->prefix . MFF_MONNIFY_TABLE;

		// phpcs:disable WordPress.DB -- Table name interpolated, not user input.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET txn_code_2 = %s WHERE txn_code = %s",
				$this->new_code,
				$this->code
			)
		);
		// phpcs:enable
	}
}
