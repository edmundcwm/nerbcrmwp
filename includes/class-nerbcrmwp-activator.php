<?php
/**
 * Plugin activation functionalities
 *
 * Class for handling plugin activation related functionalities
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_Activator Class
 */
class NerbCRMWP_Activator {
	/**
	 * Declare post types, taxonomies and plugin settings
	 * Flushes rewrite rules afterwards
	 */
	public static function activate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-nerbcrmwp-admin.php';
		NerbCRMWP_Admin::register_roles();
		NerbCRMWP_Admin::register_capabilities();

		flush_rewrite_rules();
	}
}
