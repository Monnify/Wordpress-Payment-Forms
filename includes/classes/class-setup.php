<?php
/**
 * The setup plugin class, this will register the post type and other needed items.
 *
 * @package monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Setup class.
 */
class Setup {

	/**
	 * Constructor: Registers the custom post type on WordPress 'init' action.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'plugin_action_links_' . MFF_PLUGIN_BASENAME, [ $this, 'add_action_links' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_action( 'admin_head', [ $this, 'menu_icon_style' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Keeps the full-color Monnify brand mark in the admin menu at full
	 * opacity, overriding WP core's default 60% dimming meant for monochrome
	 * dashicons. Printed on every admin page (not just our own screens),
	 * since the sidebar menu itself is present everywhere.
	 *
	 * @return void
	 */
	public function menu_icon_style() {
		echo '<style>#adminmenu .menu-icon-monnify_form .wp-menu-image img { opacity: 1; }</style>';
	}

    /**
     * Registers the custom post type 'monnify_form'.
     */
    public function register_post_type() {
        $labels = [
            'name'                  => esc_html__( 'Monnify Forms', 'payment-forms-for-monnify' ),
            'singular_name'         => esc_html__( 'Monnify Form', 'payment-forms-for-monnify' ),
            'add_new'               => esc_html__( 'Add New', 'payment-forms-for-monnify' ),
            'add_new_item'          => esc_html__( 'Add Monnify Form', 'payment-forms-for-monnify' ),
            'edit_item'             => esc_html__( 'Edit Monnify Form', 'payment-forms-for-monnify' ),
            'new_item'              => esc_html__( 'Monnify Form', 'payment-forms-for-monnify' ),
            'view_item'             => esc_html__( 'View Monnify Form', 'payment-forms-for-monnify' ),
            'all_items'             => esc_html__( 'All Forms', 'payment-forms-for-monnify' ),
            'search_items'          => esc_html__( 'Search Monnify Forms', 'payment-forms-for-monnify' ),
            'not_found'             => esc_html__( 'No Monnify Forms found', 'payment-forms-for-monnify' ),
            'not_found_in_trash'    => esc_html__( 'No Monnify Forms found in Trash', 'payment-forms-for-monnify' ),
            'parent_item_colon'     => esc_html__( 'Parent Monnify Form:', 'payment-forms-for-monnify' ),
            'menu_name'             => esc_html__( 'Monnify Forms', 'payment-forms-for-monnify' ),
		];

        $args = [
            'labels'                => $labels,
            'hierarchical'          => true,
            'description'           => esc_html__( 'Monnify Forms', 'payment-forms-for-monnify' ),
            'supports'              => array( 'title', 'editor' ),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
			'show_in_rest'          => false,
            'menu_position'         => 5,
            'menu_icon'             => MFF_MONNIFY_PLUGIN_URL . '/assets/images/monnify-icon.png',
            'show_in_nav_menus'     => true,
            'publicly_queryable'    => true,
            'exclude_from_search'   => false,
            'has_archive'           => false,
            'query_var'             => true,
            'can_export'            => true,
            'rewrite'               => false,
            'comments'              => false,
            'capability_type'       => 'post',
		];
        register_post_type( 'monnify_form', $args );
    }

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'payment-forms-for-monnify', false, MFF_MONNIFY_PLUGIN_PATH . '/languages/' );
	}

	/**
	 * Add a link to our settings page in the plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'edit.php?post_type=monnify_form&page=settings') . '">' . esc_html__( 'Settings', 'payment-forms-for-monnify' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}

	/**
	 * Enqueues our admin css.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function admin_enqueue_styles( $hook ) {
		if ( $hook != 'monnify_form_page_submissions' && $hook != 'monnify_form_page_settings' ) {
			return;
		}
		wp_enqueue_style( MFF_PLUGIN_NAME,  MFF_MONNIFY_PLUGIN_URL . '/assets/css/monnify-admin.css', array(), MFF_MONNIFY_VERSION, 'all' );
	}

	/**
	 * Enqueue the Administration scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( MFF_PLUGIN_NAME, MFF_MONNIFY_PLUGIN_URL . '/assets/js/monnify-admin.js', array( 'jquery' ), MFF_MONNIFY_VERSION, false );
	}

	/**
	 * Enques our frontend styles
	 *
	 * @return void
	 */
	public function enqueue_styles() {
        wp_enqueue_style( MFF_PLUGIN_NAME . '-style', MFF_MONNIFY_PLUGIN_URL . '/assets/css/monnify-forms.css', array(), MFF_MONNIFY_VERSION, 'all' );
        wp_enqueue_style( MFF_PLUGIN_NAME . '-font-awesome', MFF_MONNIFY_PLUGIN_URL . '/assets/css/font-awesome.min.css', array(), MFF_MONNIFY_VERSION, 'all' );
    }

}
