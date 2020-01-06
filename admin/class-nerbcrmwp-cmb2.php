<?php
/**
 * CMB2 functionality
 *
 * Handles CMB2-related functionalities
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_CMB2 Class
 */
class NerbCRMWP_CMB2 {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of the plugin.
	 */
	private $version;

	/**
	 * Constructor method
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $plugin_name Plugin Name.
	 * @param string $plugin_version Plugin version.
	 */
	public function __construct( $plugin_name, $plugin_version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $plugin_version;
		$this->load_dependencies();
	}

	/**
	 * Require all necessary files
	 */
	public function load_dependencies() {
		if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/cmb2/init.php';
		} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/CMB2/init.php';
		}
	}

	/**
	 * Only show this box in the CMB2 REST API if the user is logged in.
	 *
	 * @param  bool                 $is_allowed     Whether this box and its fields are allowed to be viewed.
	 * @param  CMB2_REST_Controller $cmb_controller The controller object.
	 *                                              CMB2 object available via `$cmb_controller->rest_box->cmb`.
	 *
	 * @return bool                 Whether this box and its fields are allowed to be viewed.
	 */
	public function nerbcrmwp_limit_rest_view_to_logged_in_users( $is_allowed, $cmb_controller ) {
		if ( ! is_user_logged_in() ) {
			$is_allowed = false;
		}

		return $is_allowed;
	}

	/**
	 * Hook in and add a box to be available in the CMB2 REST API. Can only happen on the 'cmb2_init' hook.
	 * More info: https://github.com/CMB2/CMB2/wiki/REST-API
	 */
	public function nerbcrmwp_register_rest_api_box() {
		$prefix = 'nerbcrmwp_';

		$cmb_rest = new_cmb2_box(
			array(
				'id'                           => $prefix . 'metabox',
				'title'                        => esc_html__( 'Nerb CRM WP', 'nerbcrmwp' ),
				'object_types'                 => array( 'site' ), // Post type
				'show_in_rest'                 => WP_REST_Server::ALLMETHODS, // WP_REST_Server::READABLE|WP_REST_Server::EDITABLE, // Determines which HTTP methods the box is visible in.
			// Optional callback to limit box visibility.
			// See: https://github.com/CMB2/CMB2/wiki/REST-API#permissions
				'get_box_permissions_check_cb' => 'nerbcrmwp_limit_rest_view_to_logged_in_users',
			)
		);

		$cmb_rest->add_field(
			array(
				'name'      => esc_html__( 'URL(must be using the https protocol)', 'nerbcrmwp' ),
				'id'        => $prefix . 'url',
				'type'      => 'text_url',
				'protocols' => array( 'https' ),
			)
		);
	}
}
