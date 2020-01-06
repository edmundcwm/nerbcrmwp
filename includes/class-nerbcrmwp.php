<?php
/**
 * Core plugin class
 *
 * Class for defining public and admin-facing hooks
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP Class
 */
class NerbCRMWP {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The instance of the EDM_Product_Gallery_Slider_Dependencies Class
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      obj    $dependency_check    instance of the EDM_Product_Gallery_Slider_Dependencies Class
	 */
	protected $dependency_check;

	/**
	 * The collection of action hooks to be registered
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array   $actions    The collection of action hooks to be registered
	 */
	protected $actions;

	/**
	 * The collection of filter hooks to be registered
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array   $filters    The collection of filter hooks to be registered
	 */
	protected $filters;

	/**
	 * Constructor method
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$this->plugin_name = __( 'Nerb CRM WP', 'edm' );
		$this->version     = '1.0.0';
		$this->actions     = array();
		$this->filters     = array();
	}

	/**
	 * Execute registered hooks
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function run() {
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_cmb2_hooks();
		$this->register_hooks();
	}

	/**
	 * Require all necessary files
	 *
	 * @since   1.0.0
	 * @access  private
	 */
	private function load_dependencies() {
		// handles admin-facing functionalities.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-nerbcrmwp-admin.php';

		// handles linked sites endpoints.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/endpoints/class-nerbcrmwp-linked-sites-controller.php';

		// handles company profile endpoints.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/endpoints/class-nerbcrmwp-company-profile-controller.php';

		// handles portal orders endpoints.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/endpoints/class-nerbcrmwp-orders-controller.php';

		// handles CMB2 related functionalities.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-nerbcrmwp-cmb2.php';
	}

	/**
	 * Define all admin facing hooks
	 *
	 * @since   1.0.0
	 * @access  private
	 */
	private function define_admin_hooks() {
		$plugin_admin  = new NerbCRMWP_Admin( $this->plugin_name, $this->version );
		$this->actions = $this->add_hook_to_collection( $this->actions, 'init', $plugin_admin, 'register_cpt' );
		$this->actions = $this->add_hook_to_collection( $this->actions, 'init', $plugin_admin, 'register_ctx' );
		$this->actions = $this->add_hook_to_collection( $this->actions, 'rest_after_insert_portal_order', $plugin_admin, 'assign_orders_to_categories', 10, 3 );
		$this->filters = $this->add_hook_to_collection( $this->filters, 'jwt_auth_token_before_dispatch', $plugin_admin, 'jwt_auth_function', 10, 2 );
		$this->filters = $this->add_hook_to_collection( $this->filters, 'upload_mimes', $plugin_admin, 'restrict_file_type' );

		$portal_orders_endpoints = new NerbCRMWP_Orders_Controller();
		$this->actions           = $this->add_hook_to_collection( $this->actions, 'rest_api_init', $portal_orders_endpoints, 'register_routes' );

		$linked_sites_endpoints = new NerbCRMWP_Linked_Sites_Controller();
		$this->actions          = $this->add_hook_to_collection( $this->actions, 'rest_api_init', $linked_sites_endpoints, 'register_routes' );

		$company_profile_endpoints = new NerbCRMWP_Company_Profile_Controller();
		$this->actions             = $this->add_hook_to_collection( $this->actions, 'rest_api_init', $company_profile_endpoints, 'register_routes' );
	}

	/**
	 * Define CMB2 hooks
	 *
	 * @since   1.0.0
	 * @access  private
	 */
	private function define_cmb2_hooks() {
		$cmb2_plugin   = new NerbCRMWP_CMB2( $this->plugin_name, $this->version );
		$this->actions = $this->add_hook_to_collection( $this->actions, 'cmb2_init', $cmb2_plugin, 'nerbcrmwp_register_rest_api_box' );
	}

	/**
	 * Register all hooks with WordPress
	 *
	 * @since   1.0.0
	 * @access  private
	 */
	private function register_hooks() {
		// register the collection of actions.
		foreach ( $this->actions as $action_hook ) {
			add_action( $action_hook['hook'], array( $action_hook['component'], $action_hook['callback'] ), $action_hook['priority'], $action_hook['args'] );
		}
		// register the collection of filters.
		foreach ( $this->filters as $filter_hook ) {
			add_filter( $filter_hook['hook'], array( $filter_hook['component'], $filter_hook['callback'] ), $filter_hook['priority'], $filter_hook['args'] );
		}
	}

	/**
	 * Utility function to add all filters and actions into its respective collection.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array  $hooks The collection of hooks that is being registered (that is, actions or filters).
	 * @param    string $hook The name of the WordPress filter that is being registered.
	 * @param    object $component A reference to the instance of the object on which the filter is defined.
	 * @param    string $callback The name of the function definition on the $component.
	 * @param    int    $priority The priority at which the function should be fired.
	 * @param    int    $args The number of arguments that should be passed to the $callback.
	 * @return   array The collection of actions and filters registered with WordPress
	 */
	private function add_hook_to_collection( $hooks, $hook, $component, $callback, $priority = 10, $args = 1 ) {
		$hooks[] = array(
			'hook'      => $hook,
			'component' => $component,
			'callback'  => $callback,
			'priority'  => $priority,
			'args'      => $args,
		);

		return $hooks;
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_plugin_version() {
		return $this->version;
	}
}
