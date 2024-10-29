<?php

/**
 *
 * @link              https://autoretouch.com
 * @since             1.0.0
 * @package           Wc_Autoretouch_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       autoRetouch
 * Description:       Easily post-process images using the AI-powered autoRetouch platform! Remove backgrounds, retouch skin, apply custom backgrounds and more!
 * Version:           1.0.2
 * Author:            autoRetouch GmbH
 * Author URI:        https://www.autoretouch.com
 * Plugin URI:        https://www.autoretouch.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-autoretouch-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_AUTORETOUCH_INTEGRATION_VERSION', '1.0.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-autoretouch-integration-activator.php
 */
function activate_wc_autoretouch_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'config/ar-integration-configuration.php';
	Wc_Autoretouch_Integration_Configuration::get_instance();
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-autoretouch-integration-activator.php';
	Wc_Autoretouch_Integration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-autoretouch-integration-deactivator.php
 */
function deactivate_wc_autoretouch_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-autoretouch-integration-deactivator.php';
	Wc_Autoretouch_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_autoretouch_integration' );
register_deactivation_hook( __FILE__, 'deactivate_wc_autoretouch_integration' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-autoretouch-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_autoretouch_integration() {

	$plugin = new Wc_Autoretouch_Integration();
	$plugin->run();

}
run_wc_autoretouch_integration();
