<?php
/**
 * Plugin Name: Nerb CRM WP
 * Description: Plugin that powers the various functionalities required for the CRM
 * Version: 1.0.0
 * Plugin URI: https://www.nerb.com.sg
 * Author: Edmundcwm
 * Author URI: https://www.edmundcwm.com
 * License: GPL2 or later
 * Text Domain: nerb
 *
 * @package NerbCRMWP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation
 */
function deactivate_nerbcrmwp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nerbcrmwp-deactivator.php';
	NerbCRMWP_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_nerbcrmwp' );

/**
 * Handles plugin activation
 */
function activate_nerbcrmwp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nerbcrmwp-activator.php';
	NerbCRMWP_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_nerbcrmwp' );

// include main plugin file.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-nerbcrmwp.php';
$nerbcrmwp_plugin = new NerbCRMWP();
$nerbcrmwp_plugin->run();

