<?php
/**
 * Apache Status & Info analytics
 *
 * Handles info insights operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */

namespace Hsiss\Plugin\Feature;

use Hsiss\Plugin\Feature\Capture;
use Hsiss\System\Blog;
use Hsiss\System\Cache;
use Hsiss\System\Date;
use Hsiss\System\Conversion;
use Hsiss\System\Role;
use Hsiss\System\Logger;
use Hsiss\System\L10n;
use Hsiss\System\Http;
use Hsiss\System\Favicon;
use Hsiss\System\Timezone;
use Hsiss\System\UUID;
use Feather;
use Flagiconcss;


/**
 * Define the infos functionality.
 *
 * Handles all infos operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */
class InfoInsights {

	/**
	 * The page to dislay.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $page    The page to display.
	 */
	protected $page;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $page   Optional. The subpage to request.
	 * @since    2.3.0
	 */
	public function __construct( $page = '' ) {
		$this->page = $page;
	}

	/**
	 * Normalize an info page.
	 *
	 * @param   string  $html   The page content to normalize.
	 * @return string The normalized HTML.
	 * @since    2.3.0
	 */
	private function normalize( $html ) {
		$result = Capture::get_info();





		return $result;
	}

	/**
	 * Get the Apache infos.
	 *
	 * @return string The current info.
	 * @since    2.3.0
	 */
	private function get_info() {
		return $this->normalize( Capture::get_info() );
	}

	/**
	 * Get the Apache infos.
	 *
	 * @return string The current info.
	 * @since    2.3.0
	 */
	public function display() {
		return $this->get_info();
	}

}
