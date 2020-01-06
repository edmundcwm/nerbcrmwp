<?php
/**
 * Company Profile REST routes
 *
 * Handles registration of custom REST routes for Company Profile Module
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_Company_Profile_Controller Class
 */
class NerbCRMWP_Company_Profile_Controller extends WP_REST_Controller {
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
	 * List of allowed editable fields
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $version   List of editable fields
	 */
	protected $editable_fields;

	/**
	 * Constructor method
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$this->version         = '1';
		$this->namespace       = 'nerbcrmwp/v' . $this->version;
		$this->editable_fields = array(
			'shareholders' => array(),
		);
	}

	/**
	 * Register all custom REST routes
	 */
	public function register_routes() {
		// All company profiles.
		register_rest_route(
			$this->namespace,
			'/company-profile',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_company_profiles' ),
					'permission_callback' => array( $this, 'all_company_profiles_permissions_check' ),
				),
			)
		);

		// Single company profile.
		register_rest_route(
			$this->namespace,
			'/company-profile/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_company_profile' ),
					'permission_callback' => array( $this, 'company_profile_permissions_check' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => function( $param ) {
								return is_numeric( $param ) ? $param : new WP_Error( 'rest_invalid_param', esc_html__( 'The filter argument must be a number.', 'nerb' ), array( 'status' => 400 ) );
							},
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_company_profile' ),
					'permission_callback' => array( $this, 'company_profile_update_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Retrieve Company Profile info for all portal customers
	 *
	 * @return array $response An array of objects containing requested data.
	 */
	public function get_all_company_profiles() {
		$user_meta = array();

		// Create the WP_User_Query object.
		$user_query = new WP_User_Query( array( 'role' => 'portal_customer' ) );

		// Get the results.
		$users = $user_query->get_results();

		foreach ( $users as $user ) {
			$user_meta[] = array(
				'id'           => $user->ID,
				'first_name'   => $user->first_name,
				'last_name'    => $user->last_name,
				'email'        => $user->user_email,
				'company_name' => get_user_meta( $user->ID, 'nerbcrm_user_company_name', true ),
				'designation'  => get_user_meta( $user->ID, 'nerbcrm_user_designation', true ),
			);
		}

		return rest_ensure_response( $user_meta );
	}

	/**
	 * Retrieve Company Profile info for a specific portal customer
	 *
	 * @param obj $data REST Request data.
	 */
	public function get_company_profile( $data ) {
		$user_id           = $data['id'];
		$shareholders_info = get_user_meta( $user_id, 'nerbcrm_user_shareholders', true );
		/**
		 * By explicitly specifying the individual input fields,
		 * it will be easy for us to remove them from the frontend.
		 *
		 * To do that, we can just comment out the input field inside the for loop
		 *
		 * -----Refactoring required------
		 */

		if ( is_array( $shareholders_info ) && ! empty( $shareholders_info ) ) {
			$shareholders_info_count = count( $shareholders_info );
			for ( $i = 0; $i < $shareholders_info_count; ++ $i ) {
				$shareholder_arr[] = array(
					'shareholder_name'       => $shareholders_info[ $i ]['shareholder_name'],
					'shareholder_percentage' => $shareholders_info[ $i ]['shareholder_percentage'],
				);
			}
		} else {
			$shareholder_arr = array();
		}

		$user_meta[] = array(
			'shareholders' => $shareholder_arr,
		);

		return rest_ensure_response( $user_meta );
	}

	/**
	 * Callback for updating Company profile
	 *
	 * @param obj $data REST Request data.
	 */
	public function update_company_profile( $data ) {
		$data_array = $data->get_json_params(); // Retrieve all request parameters.
		$user_id    = isset( $data->get_url_params()['id'] ) ? $data->get_url_params()['id'] : '';
		if ( empty( $data_array ) ) {
			return __( 'No data available for update', 'nerb' );
		}

		if ( ! $user_id ) {
			return __( 'Error. UserID is missing', 'nerb' );
		}

		// Check if user submitted fields match the allowed $editable_fields.
		foreach ( $data_array as $key => $value ) {
			if ( ! array_key_exists( $key, $this->editable_fields ) ) {
				return new WP_Error( 'invalid_key', esc_html__( 'Submitted Data is invalid', 'nerb' ), array( 'status' => 400 ) );
			} else {
				update_user_meta( absint( $user_id ), 'nerbcrm_user_' . $key, $this->sanitize_inputs( $key, $value ) );
			}
		}

		return __( 'Update complete', 'nerb' );
	}

	/**
	 * Utility function that handles sanitization of inputs
	 *
	 * @param string $key Field group label.
	 * @param array  $array Data for updating.
	 */
	public function sanitize_inputs( $key, $array ) {
		$new_arr = array();

		if ( ! is_array( $array ) ) {
			return $new_arr;
		}

		// Shareholders.
		if ( 'shareholders' === $key ) {
			foreach ( $array as $val ) {
				$val['shareholder_name']       = sanitize_text_field( $val['shareholder_name'] );
				$val['shareholder_percentage'] = is_numeric( $val['shareholder_percentage'] ) ? number_format( $val['shareholder_percentage'], 1 ) : 0;
				$new_arr[]                     = $val;
			}
			return $new_arr;
		}

		return $new_arr;
	}

	/****************
	 * PERMISSIONS
	 ****************/

	/**
	 * Permission callback for retrieving all Company Profiles
	 */
	public function all_company_profiles_permissions_check() {
		return current_user_can( 'read_all_company_profiles' );
	}

	/**
	 * Permission callback for retrieving single Company Profile
	 *
	 * @param obj $request The Request object.
	 */
	public function company_profile_permissions_check( $request ) {
		$requested_user_id = $request->get_params()['id'];

		// Users without read_others_company_profile capability can only view his/her own profile.
		if ( get_current_user_id() !== absint( $requested_user_id ) && ! current_user_can( 'read_others_company_profile' ) ) {
			return false;
		}

		// If user is requesting to view his own profile, he must have the read_company_profile capability.
		if ( ! current_user_can( 'read_company_profile' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Permission callback for updating single Company Profile
	 *
	 * @param obj $request The Request object.
	 */
	public function company_profile_update_permissions_check( $request ) {
		$requested_user_id = $request->get_params()['id'];

		// Users without edit_others_company_profile capability cannot edit other users profile.
		if ( get_current_user_id() !== absint( $requested_user_id ) && ! current_user_can( 'edit_others_company_profile' ) ) {
			return false;
		}

		// If user is requesting to edit his own profile, he must have the edit_company_profile capability.
		if ( ! current_user_can( 'edit_company_profile' ) ) {
			return false;
		}

		return true;
	}
}
