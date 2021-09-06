<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Hsiss\Plugin;

/**
 * Fired after 'plugins_loaded' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		\Hsiss\System\Cache::init();
		\Hsiss\System\Sitehealth::init();
		\Hsiss\Plugin\Feature\Rules::init( true );
		\Hsiss\System\APCu::init();
		//if ( 'en_US' !== determine_locale() ) {
			unload_textdomain( HSISS_SLUG );
			load_plugin_textdomain( HSISS_SLUG );
		//}
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function late_initialize() {
		require_once HSISS_PLUGIN_DIR . 'perfopsone/init.php';
	}

}
