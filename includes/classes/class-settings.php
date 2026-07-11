<?php
/**
 * The Settings page class.
 *
 * @package    \monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Settings class.
 */
class Settings {

	/**
	 * Holdes the array of settings fields.
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$this->fields = array(
			'general' => array(
				'mode' => array(
					'title'   => esc_html__( 'Mode', 'payment-forms-for-monnify' ),
					'type'    => 'select',
					'default' => 'test',
				),
				'test_api_key' => array(
					'title'   => esc_html__( 'Test API Key', 'payment-forms-for-monnify' ),
					'type'    => 'text',
					'default' => '',
				),
				'test_secret_key' => array(
					'title'   => esc_html__( 'Test Secret Key', 'payment-forms-for-monnify' ),
					'type'    => 'password',
					'default' => '',
				),
				'test_contract_code' => array(
					'title'   => esc_html__( 'Test Contract Code', 'payment-forms-for-monnify' ),
					'type'    => 'text',
					'default' => '',
				),
				'live_api_key' => array(
					'title'   => esc_html__( 'Live API Key', 'payment-forms-for-monnify' ),
					'type'    => 'text',
					'default' => '',
				),
				'live_secret_key' => array(
					'title'   => esc_html__( 'Live Secret Key', 'payment-forms-for-monnify' ),
					'type'    => 'password',
					'default' => '',
				),
				'live_contract_code' => array(
					'title'   => esc_html__( 'Live Contract Code', 'payment-forms-for-monnify' ),
					'type'    => 'text',
					'default' => '',
				),
			),
		);
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );
	}

	/**
	 * Registers our settings sub page under the Monnify Forms menu item.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_submenu_page( 'edit.php?post_type=monnify_form', esc_html__( 'Settings', 'payment-forms-for-monnify' ), esc_html__( 'Settings', 'payment-forms-for-monnify' ), 'manage_options', 'settings', [ $this, 'output_settings_page' ] );
	}

	/**
	 * Registers our Settings option with the WP Settings API.
	 *
	 * @return void
	 */
	public function register_settings_fields() {
		register_setting( 'mff-monnify-settings-group', 'mff_monnify_settings', [ $this, 'sanitise_settings' ] );
	}

	/**
	 * Outputs the settings page markup.
	 *
	 * @return void
	 */
	public function output_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Helpers::get_instance()->get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Monnify Forms Settings', 'payment-forms-for-monnify' ); ?></h1>
			<h2><?php esc_html_e( 'API Keys Settings', 'payment-forms-for-monnify' ); ?></h2>

			<span><?php echo wp_kses_post( __( 'Get your API Keys <a href="https://app.monnify.com/settings/api" target="_blank">here</a>', 'payment-forms-for-monnify' ) ); ?> </span>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'mff-monnify-settings-group' );
					$settings_fields = $this->get_settings_fields();
				?>
				<table class="form-table monnify_setting_page">
				<?php
					foreach ( $settings_fields['general'] as $key => $field ) {
						?>
						<tr valign="top">
							<th scope="row"><?php echo wp_kses_post( $field['title'] ); ?></th>
							<td>
							<?php if ( 'mode' === $key ) { ?>
								<select class="form-control" name="mff_monnify_settings[mode]" id="parent_id">
									<option value="test" <?php selected( $settings['mode'], 'test' ); ?>><?php esc_html_e( 'Test Mode', 'payment-forms-for-monnify' ); ?></option>
									<option value="live" <?php selected( $settings['mode'], 'live' ); ?>><?php esc_html_e( 'Live Mode', 'payment-forms-for-monnify' ); ?></option>
								</select>
							<?php } else { ?>
								<input type="<?php echo esc_attr( $field['type'] ); ?>" name="mff_monnify_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ?? $field['default'] ); ?>" class="regular-text" />
							<?php } ?>
							</td>
						</tr>
						<?php
					}
				?>
				</table>
				<hr>
				<table class="form-table monnify_setting_page" id="monnify_setting_webhook">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Webhook URL', 'payment-forms-for-monnify' ); ?></th>
						<td>
							<input type="text" readonly="readonly" onfocus="this.select();" class="regular-text code" value="<?php echo esc_url( admin_url( 'admin-ajax.php?action=mff_monnify_webhook' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Paste this URL into the Webhook URL field of your Monnify dashboard.', 'payment-forms-for-monnify' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>

			</form>
		</div>
		<?php
	}

	/**
	 * Returns the array of settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return apply_filters( 'mff_monnify_settings_fields', $this->fields );
	}

	/**
	 * Sanitises the settings array before it is saved.
	 *
	 * @param array $value
	 * @return array
	 */
	public function sanitise_settings( $value ) {
		$value = (array) $value;
		return array(
			'mode'               => in_array( $value['mode'] ?? 'test', array( 'test', 'live' ), true ) ? $value['mode'] : 'test',
			'test_api_key'       => sanitize_text_field( $value['test_api_key'] ?? '' ),
			'test_secret_key'    => sanitize_text_field( $value['test_secret_key'] ?? '' ),
			'test_contract_code' => sanitize_text_field( $value['test_contract_code'] ?? '' ),
			'live_api_key'       => sanitize_text_field( $value['live_api_key'] ?? '' ),
			'live_secret_key'    => sanitize_text_field( $value['live_secret_key'] ?? '' ),
			'live_contract_code' => sanitize_text_field( $value['live_contract_code'] ?? '' ),
		);
	}
}
