<?php
/**
 * Linked Sites REST Routes
 *
 * Handles registration of custom REST routes for Linked Sites module
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_Linked_Sites_Controller Class
 */
class NerbCRMWP_Linked_Sites_Controller extends WP_REST_Controller {
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
		register_rest_route(
			$this->namespace,
			'/linked-sites',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_linked_sites' ),
			)
		);
	}

	/**
	 * Retrieve "Site" post type entries
	 *
	 * @return array An array of objects containing requested data.
	 */
	public function get_linked_sites() {
		$all_sites = array();

		$query = new WP_Query(
			array(
				'post_type' => 'site',
				'no_found_rows' => true,
				'update_post_term_cache' => false,
			)
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$all_sites[] = array(
					'id'    => get_the_id(),
					'title' => get_the_title(),
					'url'   => get_post_meta( get_the_ID(), 'nerbcrmwp_url', true ),
				);
			}
			wp_reset_postdata();
		}

		return rest_ensure_response( $all_sites );
	}
}
