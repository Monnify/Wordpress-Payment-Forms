<?php
/**
 * The class that controls the output of the list of forms in the backend.
 *
 * @package monnify\payment_forms
 */

namespace monnify\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the additional functions for the WP Dashboard Forms List
 */
class Forms_List {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'page_row_actions', [ $this, 'quick_edit_links' ], 10, 2 );
		add_filter( 'manage_edit-monnify_form_columns', [ $this, 'register_columns' ], 10, 1 );
		add_action( 'manage_monnify_form_posts_custom_column', [ $this, 'column_data' ], 10, 2 );
	}

	/**
	 * Adds the "View Payments" link to the quick edit and disables others.
	 *
	 * @param array $actions
	 * @param \WP_Post $post
	 * @return array
	 */
	public function quick_edit_links( $actions, $post ) {
		if ( get_post_type( $post ) === 'monnify_form' ) {
			unset( $actions['view'] );
			unset( $actions['quick edit'] );
			$actions['export'] = '<a href="' . admin_url( 'edit.php?post_type=monnify_form&page=submissions&form=' . $post->ID ) . '" >' . esc_html__( 'View Payments', 'payment-forms-for-monnify' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Registers our column names.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function register_columns( $columns ) {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => esc_html__( 'Name', 'payment-forms-for-monnify' ),
			'shortcode' => esc_html__( 'Shortcode', 'payment-forms-for-monnify' ),
			'payments'  => esc_html__( 'Payments', 'payment-forms-for-monnify' ),
			'date'      => esc_html__( 'Date', 'payment-forms-for-monnify' )
		);
		return $columns;
	}

	/**
	 * Renders the custom column data.
	 *
	 * @param string $column
	 * @param int $post_id
	 * @return void
	 */
	public function column_data( $column, $post_id ) {
		$helpers = Helpers::get_instance();
		switch ( $column ) {
			case 'shortcode':
				echo wp_kses_post( '<span class="shortcode"><code>[monnify_form id=&quot;' . $post_id . '&quot;]</code></span>' );
				break;
			case 'payments':
				$num = $helpers->get_payments_count( $post_id );
				echo wp_kses_post( '<u><a href="' . admin_url( 'edit.php?post_type=monnify_form&page=submissions&form=' . $post_id ) . '">' . $num . '</a></u>' );
				break;
			default:
				break;
		}
	}
}
