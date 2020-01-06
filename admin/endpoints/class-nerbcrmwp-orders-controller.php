<?php
/**
 * Orders REST Routes
 *
 * Handles registration of custom REST routes for Orders Module
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_Orders_Controller Class
 */
class NerbCRMWP_Orders_Controller extends WP_REST_Controller {
	/**
	 * Custom Route version
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version   Custom route version
	 */
	protected $version;

	/**
	 * Custom Route Namespace
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version   Custom route namespace
	 */
	protected $namespace;

	/**
	 * Constructor method
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$this->version   = '1';
		$this->namespace = 'nerbcrmwp/v' . $this->version;
	}

	/**
	 * Register all custom REST routes
	 */
	public function register_routes() {
		// All orders.
		register_rest_route(
			$this->namespace,
			'/orders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_orders' ),
					'permission_callback' => array( $this, 'all_orders_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_order' ),
					'permission_callback' => array( $this, 'all_orders_permissions_check' ),
				),
			)
		);

		// Orders by Email.
		register_rest_route(
			$this->namespace,
			'/orders/(?P<email>\S+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_customer_orders' ),
					'permission_callback' => array( $this, 'order_permissions_check' ),
					'args'                => array(
						'email' => array(
							'validate_callback' => function ( $param ) {
								return is_email( $param ) ? $param : new WP_Error( 'rest_invalid_param', esc_html__( 'The filter argument must be an email.', 'nerb' ), array( 'status' => 400 ) );
							},
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_customer_orders' ),
					'permission_callback' => array( $this, 'order_permissions_check' ),
					'args'                => array(
						'email' => array(
							'validate_callback' => function ( $param ) {
								return is_email( $param ) ? $param : new WP_Error( 'rest_invalid_param', esc_html__( 'The filter argument must be an email.', 'nerb' ), array( 'status' => 400 ) );
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Retrieve all portal orders
	 *
	 * @return array An array of objects containing requested data
	 */
	public function get_all_orders() {
		$order_info = array();
		$args       = array(
			'post_type'              => 'portal_order',
			'posts_per_page'         => 1000,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);
		$query      = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id           = get_the_ID();
				$order_info[] = array(
					'id'             => $id,
					'title'          => get_the_title(),
					'date'           => get_post_meta( $id, 'order_date', true ),
					'amount'         => get_post_meta( $id, 'order_amount', true ),
					'category'       => get_post_meta( $id, 'order_category', true ),
					'attachment'     => get_post_meta( $id, 'order_attachment', true ),
					'customer_email' => get_post_meta( $id, 'order_email', true ),
				);
			}
			wp_reset_postdata();
		}
		return rest_ensure_response( $order_info );
	}

	/**
	 * Retrieve term ids of an array of terms from the order_cat taxonomy
	 *
	 * @param  array $categories     An array of categories tagged to the product.
	 * @return array    $term_ids       An array of term ids of the categories that exist
	 */
	public function get_category_ids( array $categories ) {
		// Since order_cat is hierarchical, we need to retrieve the term ID and use that to assign the order to the category.
		$term_ids = array();

		foreach ( $categories as $category ) {
			$term_id    = term_exists( $category, 'order_cat' );
			$term_ids[] = $term_id['term_id'];
		}

		return $term_ids;
	}

	/**
	 * Create a new portal order and tag it to its respective category based on products inside the order
	 *
	 * @param  WP_REST_Request $data   Full data about the request.
	 * @return string                  Either the success or error message
	 */
	public function create_order( $data ) {
		$order_data = array(
			'post_type'   => 'portal_order',
			'post_title'  => $data['order_title'],
			'post_status' => 'publish',
			'meta_input'  => array(
				'order_date'     => $data['order_date'],
				'order_amount'   => $data['order_amount'],
				'order_category' => $data['order_category'],
				'order_email'    => $data['order_email'],
			),
		);

		$post_id = wp_insert_post( $order_data, true );

		// Error handling if failed to create order/post.
		if ( is_wp_error( $post_id ) ) {
			// The error message will be sent to the linked site for them to use in the error email notification.
			return $post_id->get_error_message();
		}

		if ( ! empty( $data['order_category'] ) ) {
			$term_ids = $this->get_category_ids( $data['order_category'] );
			// Tag order to respective category based on order_category.
			$terms = wp_set_post_terms( $post_id, $term_ids, 'order_cat', true );

			// Error handling if post id is not an integer.
			if ( is_bool( $terms ) && ! $terms ) {
				return __( 'The Post ID is not an integer', 'nerb' );
			}

			// Error handling if term ids do not belong to any existing term.
			if ( is_array( $terms ) && empty( $terms ) ) {
				return __( 'Order is created but categories do not exist', 'nerb' );
			}

			// Error handling if taxonomy is invalid.
			if ( is_wp_error( $terms ) ) {
				return $terms->get_error_message();
			}

			// This 'success' message will inform the linked site whether the order is created successfully.
			// The Linked site can then determine whether to send the error email notification.
			return 'success';
		} else {
			return 'Order is created but product has no categories tagged to it';
		}
	}

	/**
	 * Retrieve portal orders based on email
	 *
	 * @param  WP_REST_Request $data           Full data about the request.
	 * @return array            $order_info     An array of objects containing requested data
	 */
	public function get_customer_orders( $data ) {
		$order_info = array();
		$category   = isset( $data['cat'] ) ? $data['cat'] : '';
		$email      = $data['email'];
		$args       = array(
			'post_type'              => 'portal_order',
			'posts_per_page'         => 1000,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_key'               => 'order_email',
			'meta_value'             => $email,
		);

		if ( $category ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'order_cat',
					'field'    => 'slug',
					'terms'    => $category,
				),
			);
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id           = get_the_ID();
				$submitted_on = get_post_meta( $id, 'lc_submitted_on', true );
				// Format submitted_on date to dd-mm-yyyy format.
				$format_date  = date( 'd-m-Y', $submitted_on / 1000 );
				$order_info[] = array(
					'id'             => $id,
					'title'          => get_the_title(),
					'date'           => get_post_meta( $id, 'order_date', true ),
					'amount'         => get_post_meta( $id, 'order_amount', true ),
					'category'       => get_post_meta( $id, 'order_category', true ),
					'attachment'     => get_post_meta( $id, 'lc_order_attachments', true ),
					'customer_email' => get_post_meta( $id, 'order_email', true ),
					'legal_counsel'  => array(
						'company_uen'     => get_post_meta( $id, 'lc_company_uen', true ),
						'other_party'     => get_post_meta( $id, 'lc_other_party', true ),
						'full_name_nric'  => get_post_meta( $id, 'lc_full_name_nric', true ),
						'validity'        => get_post_meta( $id, 'lc_validity', true ),
						'bizprofile'      => get_post_meta( $id, 'lc_bizprofile', true ),
						'bizprofile_link' => get_post_meta( $id, 'lc_bizprofile_link', true ),
						'scanned_ic'      => get_post_meta( $id, 'lc_scanned_ic', true ),
						'scanned_ic_link' => get_post_meta( $id, 'lc_scanned_ic_link', true ),
						'submitted_on'    => $format_date,
					),
				);
			}
			wp_reset_postdata();
		}
		return rest_ensure_response( $order_info );
	}

	/**
	 * Update orders based on customer email
	 *
	 * @param  WP_REST_Request $request           Full data about the request.
	 * @return array            $order_info     An array of objects containing requested data
	 */
	public function update_customer_orders( $request ) {
		$order_id = $request->get_json_params()['id'];
		if ( ! $order_id ) {
			return new WP_Error( 'missing order id', __( 'Unable to update order due to missing ID.' ), array( 'status' => 400 ) );
		}

		// Get current value.
		$current_attachments = get_post_meta( $order_id, 'lc_order_attachments', true );

		$allowed_attachment_formats = array( 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx' );

		// Handle legal counsel form submission.
		if ( ! empty( $request['legal_counsel_data'] ) ) {
			foreach ( $request['legal_counsel_data'] as $key => $val ) {
				update_post_meta( $order_id, 'lc_' . $key, $val );
			}
		}

		// Handle removing of specific attachment.
		if ( isset( $request['attachment_to_remove'] ) && ! empty( $request['attachment_to_remove'] ) ) {
			$attachment_to_remove = $request['attachment_to_remove']['file_name'];
			foreach ( $current_attachments as $key => $value ) {
				if ( $value['file_name'] === $attachment_to_remove ) {
					// Remove attachment from current attachment list.
					unset( $current_attachments[ $key ] );
					// Rebase array.
					$current_attachments = array_values( $current_attachments );
					// Update order attachments with latest attachments less removed file.
					return update_post_meta( $order_id, 'lc_order_attachments', $current_attachments );
				}
			}
		}

		// Handle order attachments submission.
		if ( ! empty( $request['order_attachments'] ) ) {
			// File type validation.
			foreach ( $request['order_attachments'] as $attachment ) {
				$file_extension = pathinfo( $attachment['file_url'], PATHINFO_EXTENSION );
				if ( ! in_array( $file_extension, $allowed_attachment_formats, true ) ) {
					return new WP_Error( 'invalid_format', __( 'Invalid file format', 'nerb' ), array( 'status' => 400 ) );
				}
			}

			// Update attachments.
			if ( $current_attachments && is_array( $current_attachments ) ) {
				// Merge both current and new attachments.
				$merged_attachments = array_merge( $current_attachments, $request['order_attachments'] );
				update_post_meta( $order_id, 'lc_order_attachments', $merged_attachments );
			} else {
				update_post_meta( $order_id, 'lc_order_attachments', $request['order_attachments'] );
			}
		}
	}

	/****************
	 * PERMISSIONS
	 ****************/

	/**
	 * Permission callback for retrieving all orders
	 */
	public function all_orders_permissions_check() {
		return current_user_can( 'read_all_portal_orders' );
	}

	/**
	 * Permission callback for retrieving and editing orders by email
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 */
	public function order_permissions_check( $request ) {
		$current_user_email   = wp_get_current_user()->user_email;
		$requested_user_email = $request->get_params()['email'];

		// Users without read_others_portal_order capability can only view his/her own orders.
		if ( $current_user_email !== $requested_user_email && ! current_user_can( 'read_all_portal_orders' ) ) {
			return false;
		}

		// Users must have the read_portal_order capability to view order by email.
		if ( ! current_user_can( 'read_portal_order' ) ) {
			return false;
		}

		return true;
	}
}
