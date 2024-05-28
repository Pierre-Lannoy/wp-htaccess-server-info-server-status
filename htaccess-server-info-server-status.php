<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Apache Status & Info
 * Plugin URI:        https://perfops.one/htaccess-server-info-server-status
 * Description:       Apache server-info and server-status monitoring right in your WordPress admin.
 * Version:           3.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       htaccess-server-info-server-status
 * Domain Path:       /languages
 * Network:           true
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function hsiss_activate() {
	Hsiss\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function hsiss_deactivate() {
	Hsiss\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function hsiss_uninstall() {
	Hsiss\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function hsiss_run() {
	\DecaLog\Engine::initPlugin( HSISS_SLUG, HSISS_PRODUCT_NAME, HSISS_VERSION, \Hsiss\Plugin\Core::get_base64_logo() );
	require_once __DIR__ . '/includes/features/class-wpcli.php';
	$plugin = new Hsiss\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'hsiss_activate' );
register_deactivation_hook( __FILE__, 'hsiss_deactivate' );
register_uninstall_hook( __FILE__, 'hsiss_uninstall' );
hsiss_run();
