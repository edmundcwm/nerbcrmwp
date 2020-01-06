<?php
/**
 * Admin-facing functionality
 *
 * The admin-facing functionality of the plugin
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_Admin Class
 */
class NerbCRMWP_Admin {

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
	 * The list of custom roles
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $roles    The list of custom roles.
	 */
	private const ROLES = array(
		array(
			'role_id'   => 'portal_manager',
			'role_name' => 'Portal Manager',
		),
		array(
			'role_id'   => 'portal_customer',
			'role_name' => 'Portal Customer',
		),
	);

	/**
	 * The list of custom post type labels
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $cpt_labels    The list of custom post type labels.
	 */
	private $cpt_labels = array();

	/**
	 * The list of custom post types
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $cpts    The list of custom post types.
	 */
	private $cpts = array();

	/**
	 * The settings for all custom post types
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $cpt_settings    The settings for all custom post types
	 */
	private $cpt_settings = array();

	/**
	 * The list of custom taxonomies
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $ctx    The list of custom taxonomies
	 */
	private $ctx = array();

	/**
	 * The list of custom taxonomy labels
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $ctx_labels    The list of custom taxonomy labels
	 */
	private $ctx_labels = array();

	/**
	 * The settings for all custom taxonomies
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $ctx_settings    The settings for all custom taxonomies
	 */
	private $ctx_settings = array();

	/**
	 * The list of custom capabilities
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $roles    The list of custom capabilities.
	 */
	private const CAPABILITIES = array(
		'read_others_company_profile' => array( 'portal_manager', 'administrator' ), // for single company profile endpoint.
		'read_all_company_profiles'   => array( 'portal_manager', 'administrator' ),
		'read_company_profile'        => array( 'portal_customer', 'portal_manager', 'administrator' ),
		'edit_others_company_profile' => array( 'portal_manager', 'administrator' ),
		'edit_company_profile'        => array( 'portal_customer', 'portal_manager', 'administrator' ),
		'upload_files'                => array( 'portal_customer', 'portal_manager' ),
		'read_others_portal_order'    => array( 'portal_manager', 'administrator' ),
		'read_all_portal_orders'      => array( 'portal_manager', 'administrator' ),
		'read_portal_order'           => array( 'portal_customer', 'portal_manager', 'administrator' ),
	);

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
		$this->set_cpt();
		$this->set_cpt_labels();
		$this->cpt_settings = array(
			'site'         => array(
				'labels'       => isset( $this->cpt_labels['site'] ) ? $this->cpt_labels['site'] : '',
				'public'       => true,
				'has_archive'  => true,
				'show_in_menu' => true,
				'show_ui'      => true,
				'hierarchical' => false,
				'show_in_rest' => true,
				'rest_base'    => 'sites',
				'supports'     => array( 'title', 'editor' ),
				'menu_icon'    => 'dashicons-portfolio',
			),
			'portal_order' => array(
				'labels'       => isset( $this->cpt_labels['portal_order'] ) ? $this->cpt_labels['portal_order'] : '',
				'public'       => true,
				'has_archive'  => true,
				'show_in_menu' => true,
				'show_ui'      => true,
				'hierarchical' => false,
				'show_in_rest' => true,
				'rest_base'    => 'portal_orders',
				'supports'     => array( 'title', 'editor' ),
				'menu_icon'    => 'dashicons-portfolio',
			),
		);
		$this->set_ctx();
		$this->set_ctx_labels();
		$this->ctx_settings = array(
			'order_cat' => array(
				'hierarchical'          => true,
				'labels'                => isset( $this->ctx_labels['order_cat'] ) ? $this->ctx_labels['order_cat'] : '',
				'show_ui'               => true,
				'show_admin_column'     => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
				'rewrite'               => array( 'slug' => 'order_cat' ),
				'show_in_rest'          => true,
			),
		);
	}

	/**
	 * Prepare the list of custom post types to be registered
	 */
	public function set_cpt() {
		$this->cpts = array(
			'site'         => array(
				'singular' => 'Site',
				'plural'   => 'Sites',
			),
			'portal_order' => array(
				'singular' => 'Portal Order',
				'plural'   => 'Portal Orders',
			),
		);
	}

	/**
	 * Prepare the list of custom taxonomies to be registered
	 */
	public function set_ctx() {
		$this->ctx = array(
			'order_cat' => array(
				'singular'  => 'Category',
				'plural'    => 'Categories',
				'post_type' => 'portal_order',
			),
		);
	}

	/**
	 * Prepare an array of labels for the 'labels' property in the CPT args array
	 */
	public function set_cpt_labels() {
		foreach ( $this->cpts as $cpt => $label ) {
			if ( ! is_array( $label ) || empty( $label ) || ! isset( $label['singular'] ) || ! isset( $label['plural'] ) ) {
				continue;
			}
			$this->cpt_labels[ $cpt ] = array(
				'name'               => _x( $label['plural'], 'post type general name', 'nerb' ),
				'singular_name'      => _x( $label['singular'], 'post type singular name', 'nerb' ),
				'menu_name'          => _x( $label['plural'], 'admin menu', 'nerb' ),
				'name_admin_bar'     => _x( $label['singular'], 'add new on admin bar', 'nerb' ),
				'add_new'            => _x( 'Add New', 'Site', 'nerb' ),
				'add_new_item'       => __( 'Add New ' . $label['singular'], 'nerb' ),
				'new_item'           => __( 'New ' . $label['singular'], 'nerb' ),
				'edit_item'          => __( 'Edit ' . $label['singular'], 'nerb' ),
				'view_item'          => __( 'View ' . $label['singular'], 'nerb' ),
				'all_items'          => __( 'All ' . $label['plural'], 'nerb' ),
				'search_items'       => __( 'Search ' . $label['plural'], 'nerb' ),
				'parent_item_colon'  => __( 'Parent ' . $label['plural'] . ': ', 'nerb' ),
				'not_found'          => __( 'No ' . $label['plural'] . ' found.', 'nerb' ),
				'not_found_in_trash' => __( 'No ' . $label['plural'] . ' found in Trash.', 'nerb' ),
			);
		}
	}

	/**
	 * Prepare an array of labels for the 'labels' property in the CTX args array
	 */
	public function set_ctx_labels() {
		foreach ( $this->ctx as $ctx => $label ) {
			if ( ! is_array( $label ) || empty( $label ) || ! isset( $label['singular'] ) || ! isset( $label['plural'] ) ) {
				continue;
			}
			$this->ctx_labels[ $ctx ] = array(
				'name'                       => _x( $label['plural'], 'taxonomy general name', 'nerb' ),
				'singular_name'              => _x( $label['singular'], 'taxonomy singular name', 'nerb' ),
				'search_items'               => __( 'Search ' . $label['plural'], 'nerb' ),
				'popular_items'              => __( 'Popular ' . $label['plural'], 'nerb' ),
				'all_items'                  => __( 'All ' . $label['plural'], 'nerb' ),
				'parent_item'                => null,
				'parent_item_colon'          => null,
				'edit_item'                  => __( 'Edit ' . $label['singular'], 'nerb' ),
				'update_item'                => __( 'Update ' . $label['singular'], 'nerb' ),
				'add_new_item'               => __( 'Add New ' . $label['singular'], 'nerb' ),
				'new_item_name'              => __( 'New ' . $label['singular'] . ' Name', 'nerb' ),
				'separate_items_with_commas' => __( 'Separate ' . $label['plural'] . ' with commas', 'nerb' ),
				'add_or_remove_items'        => __( 'Add or remove ' . $label['plural'], 'nerb' ),
				'choose_from_most_used'      => __( 'Choose from the most used ' . $label['plural'], 'nerb' ),
				'not_found'                  => __( 'No ' . $label['plural'] . ' found.', 'nerb' ),
				'menu_name'                  => __( $label['plural'], 'nerb' ),
			);
		}
	}

	/**
	 * Register Custom Post Types
	 */
	public function register_cpt() {
		foreach ( $this->cpt_settings as $cpt => $args ) {
			// skip CPT if its labels are empty.
			if ( empty( $args['labels'] ) ) {
				continue;
			}
			register_post_type( $cpt, $args );
		}
	}

	/**
	 * Register Custom Taxonomies
	 */
	public function register_ctx() {
		foreach ( $this->ctx_settings as $ctx => $args ) {
			$post_type = '';
			if ( array_key_exists( $ctx, $this->ctx ) ) {
				$post_type = $this->ctx[ $ctx ]['post_type'];
			}

			// skip custom taxonomy if its labels are empty.
			if ( empty( $args['labels'] ) ) {
				continue;
			}
			register_taxonomy( $ctx, $post_type, $args );
		}
	}

	/**
	 * Register Custom Roles
	 */
	public static function register_roles() {
		foreach ( self::ROLES as $role ) {
			add_role( esc_html( $role['role_id'] ), esc_html__( $role['role_name'], 'nerb' ) );
		}
	}

	/**
	 * Deregister Custom Roles
	 */
	public static function deregister_roles() {
		foreach ( self::ROLES as $role ) {
			if ( get_role( $role['role_id'] ) ) {
				remove_role( $role['role_id'] );
			}
		}
	}

	/**
	 * Register Custom Capabilities
	 */
	public static function register_capabilities() {
		foreach ( self::CAPABILITIES as $cap => $roles ) {
			foreach ( $roles as $role ) {
				$role_obj = get_role( $role );
				if ( $role_obj ) {
					$role_obj->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Deregister Custom Capabilities
	 */
	public static function deregister_capabilities() {
		foreach ( self::CAPABILITIES as $cap => $roles ) {
			foreach ( $roles as $role ) {
				$role_obj = get_role( $role );
				if ( $role_obj ) {
					$role_obj->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Allow assigning of orders to categories when updating orders
	 *
	 * @param WP_POST         $post The Post Object.
	 * @param WP_REST_REQUEST $request The Request Object.
	 * @param bool            $creating Whether post is being created or updated.
	 */
	public function assign_orders_to_categories( $post, $request, $creating ) {
		// categories can only be assigned when updating orders.
		if ( $creating ) {
			return;
		}

		$categories_by_slug = wp_get_post_terms( $post->ID, 'order_cat', array( 'fields' => 'slugs' ) );

		update_post_meta( $post->ID, 'order_category', $categories_by_slug );
	}

	/**
	 * Add user_role property to JWT Auth validation response
	 *
	 * @param obj $data The User Object after token is signed.
	 * @param obj $user The User Object.
	 */
	public function jwt_auth_function( $data, $user ) {
		$data['user_role'] = $user->roles;
		$data['user_id']   = $user->id;
		return $data;
	}

	/**
	 * Add upload file restriction to specific roles
	 *
	 * @param array $mimes Mime types keyed by the file extension regex corresponding to
	 *                     those types. 'swf' and 'exe' removed from full list. 'htm|html' also
	 *                     removed depending on '$user' capabilities.
	 */
	public function restrict_file_type( $mimes ) {
		$current_user = wp_get_current_user();
		foreach ( self::ROLES as $role ) {
			if ( in_array( $role['role_id'], $current_user->roles ) ) {
				$mimes = array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'pdf'          => 'application/pdf',
				);
				break;
			}
		}
		return $mimes;
	}
}
