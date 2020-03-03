<?php
/**
 * .htaccess rules
 *
 * Handles all rules processes.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Hsiss\Plugin\Feature;

/**
 * Define the rules functionality.
 *
 * Handles all rules processes.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Rules {

	/**
	 * Initialization state.
	 *
	 * @since  1.0.0
	 * @var    boolean    $initialized    Maintain the initialization state.
	 */
	private static $initialized = false;

	/**
	 * Init the class.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$initialized = true;
	}

	/**
	 * Shutdown (de-init) the class.
	 *
	 * @since    1.0.0
	 */
	public static function shutdown() {
		self::$initialized = false;
	}

}
