<?php
/**
 * A request to verify the current transaction reference against Monnify.
 *
 * @package    \monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transaction_Verify
 */
class Transaction_Verify extends API {

	/**
	 * Query Monnify for the status of a transaction, keyed by OUR OWN
	 * previously generated payment reference (never a client-supplied value).
	 *
	 * @param string $payment_reference
	 * @return array
	 */
	public function verify_transaction( $payment_reference = '' ) {
		if ( '' === $payment_reference || ! $this->api_ready() ) {
			return array(
				'message' => esc_html__( 'Payment Verification Failed', 'payment-forms-for-monnify' ),
				'result'  => 'failed',
			);
		}

		$response = $this->request( 'GET', '/api/v2/merchant/transactions/query?paymentReference=' . rawurlencode( $payment_reference ) );

		return $this->verify_response( $response );
	}

	/**
	 * Reviews the transaction and returns success or an error and a message.
	 * Only a PAID status is treated as a successful payment; OVERPAID and
	 * PARTIALLY_PAID are left for manual admin review, never auto-completed.
	 *
	 * @param object|false $response
	 * @return array
	 */
	public function verify_response( $response ) {
		if ( false === $response || empty( $response->requestSuccessful ) || empty( $response->responseBody ) ) {
			return array(
				'message' => esc_html__( 'Payment Verification Failed', 'payment-forms-for-monnify' ),
				'result'  => 'failed',
			);
		}

		$body = $response->responseBody;

		if ( isset( $body->paymentStatus ) && 'PAID' === $body->paymentStatus ) {
			return array(
				'message' => esc_html__( 'Payment Verification Passed', 'payment-forms-for-monnify' ),
				'result'  => 'success',
				'data'    => array(
					'paymentReference'     => $body->paymentReference ?? '',
					'transactionReference' => $body->transactionReference ?? '',
					'amountPaid'           => $body->amountPaid ?? 0,
					'paidOn'               => $body->paidOn ?? '',
					'paymentStatus'        => $body->paymentStatus,
				),
			);
		}

		return array(
			'message' => esc_html__( 'Transaction Failed/Invalid Reference', 'payment-forms-for-monnify' ),
			'result'  => 'failed',
		);
	}
}
