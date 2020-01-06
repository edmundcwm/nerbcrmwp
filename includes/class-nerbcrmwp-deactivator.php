<?php
/**
 * Plugin deactivation functionalities
 *
 * Class for handling plugin deactivation related functionalities
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * NerbCRMWP_Deactivator Class
 */
class NerbCRMWP_Deactivator {
	/**
	 * Deregister custom capabilities and roles
	 */
	public static function deactivate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-nerbcrmwp-admin.php';
		// remove custom capabilities.
		NerbCRMWP_Admin::deregister_capabilities();
		// remove custom roles.
		NerbCRMWP_Admin::deregister_roles();
	}
}
