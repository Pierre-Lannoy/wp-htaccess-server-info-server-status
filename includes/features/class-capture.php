<?php
/**
 * Apache status capture
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */

namespace Hsiss\Plugin\Feature;

use Hsiss\System\Logger;
use Hsiss\System\User;
use Hsiss\System\Environment;
use Hsiss\System\Blog;
use Hsiss\System\IP;
use Hsiss\System\Option;
use Hsiss\System\Hash;

/**
 * Define the captures functionality.
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */
class Capture {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.3.0
	 */
	public function __construct() {
	}

	/**
	 * Get the Apache status.
	 *
	 * @return array The current status.
	 * @since    2.3.0
	 */
	public static function get_status() {
		$result = [];
		$result['ServerVersion'] = 'Apache/2.4.43 (Unix)';
		return $result;
	}

}
