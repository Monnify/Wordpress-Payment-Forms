<?php
/**
 * A class of API functions used to send requests to and from Monnify.
 *
 * @package    \monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin API Class
 */
class API {

	/**
	 * The current mode, 'test' or 'live'.
	 *
	 * @var string
	 */
	protected $mode = 'test';

	/**
	 * The API Key, Secret Key and Contract Code for the current mode.
	 *
	 * @var array
	 */
	protected $credentials = array();

	/**
	 * The Monnify API base URL for the current mode.
	 *
	 * @var string
	 */
	protected $base_url = '';

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$helpers           = Helpers::get_instance();
		$this->mode        = $helpers->get_mode();
		$this->credentials = $helpers->get_credentials( $this->mode );
		$this->base_url    = 'live' === $this->mode ? MFF_MONNIFY_LIVE_BASE_URL : MFF_MONNIFY_SANDBOX_BASE_URL;
	}

	/**
	 * Determines if all the settings have been entered.
	 *
	 * @return boolean
	 */
	public function api_ready() {
		return '' !== $this->credentials['api_key'] && '' !== $this->credentials['secret_key'];
	}

	/**
	 * Gets a valid access token, requesting/caching a new one if needed.
	 *
	 * @param boolean $force_refresh
	 * @return string|false
	 */
	protected function get_access_token( $force_refresh = false ) {
		if ( ! $this->api_ready() ) {
			return false;
		}

		$transient_key = 'mff_monnify_token_' . $this->mode;

		if ( ! $force_refresh ) {
			$cached = get_transient( $transient_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$auth = base64_encode( $this->credentials['api_key'] . ':' . $this->credentials['secret_key'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$response = wp_remote_post(
			$this->base_url . '/api/v1/auth/login',
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $auth,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body->requestSuccessful ) || empty( $body->responseBody->accessToken ) ) {
			return false;
		}

		$expires_in = isset( $body->responseBody->expiresIn ) ? (int) $body->responseBody->expiresIn : 3600;
		set_transient( $transient_key, $body->responseBody->accessToken, max( 60, $expires_in - 60 ) );

		return $body->responseBody->accessToken;
	}

	/**
	 * Sends an authenticated request to the Monnify API, retrying once on a 401
	 * with a freshly refreshed access token.
	 *
	 * @param string     $method  GET|POST|PUT.
	 * @param string     $path    Path beginning with /api/..., may include a query string.
	 * @param array|null $body    Request body for POST/PUT requests.
	 * @param boolean    $retrying Internal flag used for the single retry.
	 * @return object|false Decoded JSON response, or false on failure.
	 */
	protected function request( $method, $path, $body = null, $retrying = false ) {
		if ( ! $this->api_ready() ) {
			return false;
		}

		$token = $this->get_access_token( $retrying );
		if ( false === $token ) {
			return false;
		}

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 60,
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->base_url . $path, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $code && ! $retrying ) {
			return $this->request( $method, $path, $body, true );
		}

		if ( $code < 200 || $code >= 300 ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}
}
