<?php

/**
 *
 * @link              https://www.wpseoplugins.org
 * @since             1.0.0
 * @package           seocopy
 *
 * @wordpress-plugin
 * Plugin Name:       SEO Copy
 * Plugin URI:        https://wpseoplugins.org/seo-copywriting/
 * Description:       Keyword research made easy!
 * Version:           2.3.5
 * Author:            WP SEO Plugins
 * Author URI:        https://www.wpseoplugins.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       seocopy
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
define( 'seocopy_VERSION', '2.3.5' );
define( 'seocopy_DOMAIN', 'seocopy' );
define( 'SEOCOPY_SERVER_NAME', $_SERVER['SERVER_NAME']);
define( 'SEOCOPY_SERVER_PORT', $_SERVER['SERVER_PORT']);
define( 'SEOCOPY_SITE_URL', ( SEOCOPY_SERVER_PORT == 80 ? 'http://' : 'https://' ) . SEOCOPY_SERVER_NAME );
if( !defined( 'WP_SEO_PLUGINS_BACKEND_URL' ) ) {
    define( 'WP_SEO_PLUGINS_BACKEND_URL', 'https://api.wpseoplugins.org/');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-seocopy-activator.php
 */
function activate_seocopy() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-seocopy-activator.php';
	seocopy_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-seocopy-deactivator.php
 */
function deactivate_seocopy() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-seocopy-deactivator.php';
	seocopy_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_seocopy' );
register_deactivation_hook( __FILE__, 'deactivate_seocopy' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-seocopy.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_seocopy() {

	$plugin = new seocopy();
	$plugin->run();

}
run_seocopy();
