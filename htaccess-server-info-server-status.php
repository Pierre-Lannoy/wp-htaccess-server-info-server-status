<?php

/**
 * Main plugin file.
 *
 * @package -
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       htaccess Server-Info & Server-Status
 * Plugin URI:        https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status
 * Description:       Automatically add rules to .htaccess file to support server-info and server-status Apache mod.
 * Version:           1.2.0
 * Author:            Pierre Lannoy
 * Author URI:        https://pierre.lannoy.fr
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       htaccess-server-info-server-status
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main class of the plugin.
 *
 * @since 1.0.0
 */
class HtaccessServerInfoStatus {


	private $output_rules = true;

	public static function init() {
		static $instance = null;
		if ( ! $instance ) {
			$instance = new HtaccessServerInfoStatus();
		}
		return $instance;
	}

	/**
	 * Initializes the instance.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		load_plugin_textdomain( 'htaccess-server-info-server-status' );
		register_activation_hook( __FILE__, [ $this, 'plugin_activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'plugin_deactivate' ] );
		add_filter( 'mod_rewrite_rules', [ $this, 'modify_rules' ], 10, 1 );
		add_filter( 'plugin_row_meta', [ $this, 'add_row_meta' ], 10, 2 );
	}

	/**
	 * Set up the plugin environment upon activation.
	 *
	 * @since 1.0.0
	 */
	public function plugin_activate() {
		$this->output_rules = true;
		flush_rewrite_rules();
	}

	/**
	 * Cleans the plugin environment upon deactivation.
	 *
	 * @since 1.0.0
	 */
	public function plugin_deactivate() {
		$this->output_rules = false;
		flush_rewrite_rules();
	}

	/**
	 * Add links in the "Description" column on the Plugins page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Name of the plugin.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( array $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/htaccess-server-info-server-status">' . __( 'Support', 'htaccess-server-info-server-status' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status">' . __( 'GitHub Project', 'htaccess-server-info-server-status' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Modify rewrite rules if needed.
	 *
	 * @param string $rules mod_rewrite Rewrite rules formatted for .htaccess.
	 * @return string Modified (if needed) mod_rewrite Rewrite rules formatted for .htaccess.
	 * @since 1.0.0
	 */
	public function modify_rules( $rules ) {
		if ( $this->output_rules ) {
			$rules = preg_replace( '/^(RewriteBase \/.*)$/miU', "$1\nRewriteRule ^(server-info|server-status) - [L]", $rules, 1 );
			do_action( 'hsiss_rewrite_rules_added' );
		}
		return $rules;
	}
}

// Init the plugin

HtaccessServerInfoStatus::init();
