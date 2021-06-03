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
	 * @param   string  $mode   Optional. The mode of the normalization.
	 * @return string The normalized HTML.
	 * @since    2.3.0
	 */
	private function normalize( $html, $mode = 'base' ) {
		$result = $html;
		// Clean headers etc.
		$result = preg_replace_callback(
			'/<body>(.*)<\/body>/iU',
			function( $matches ) {
				return $matches[1];
			},
			$result
		);
		// Clean H1
		$result = preg_replace_callback(
			'/<h1.*>(.*)<\/h1>/iU',
			function( $matches ) {
				return '<h1>' . $matches[1] . '</h1>';
			},
			$result
		);
		// Clean H2
		$result = preg_replace_callback(
			'/<h2><a name="(.*)">(.*)<\/a><\/h2>/iU',
			function( $matches ) {
				return '<h2 id="' . $matches[1] . '">' . $matches[2] . '</h2>';
			},
			$result
		);
		// Transform modules names in H3
		$result = preg_replace_callback(
			'/<dt><a name="(.*)"><strong>(.*)<\/strong><\/a>.*<font size="\+1"><tt><a href="\?.*">(.*)<\/a><\/tt><\/font><\/dt>/iU',
			function( $matches ) {
				return '<h3 id="' . $matches[1] . '">' . $matches[2] . ' ' . $matches[3] . '</h3>';
			},
			$result
		);
		// Removes "subpages" section
		if ( 'base' === $mode ) {
			$result = preg_replace_callback(
				'/<\/h1>.*<hr \/>/iUs',
				function( $matches ) {
					return '</h1>';
				},
				$result
			);
		}
		// Decorates "sections" section
		if ( 'base' === $mode ) {
			$result = preg_replace_callback(
				'/<\/h1>.*<dl><dt><tt>(.*)<br \/>(.*)<\/tt><\/dt><\/dl>/iUs',
				function( $matches ) {
					$match = str_replace( [ ', ' ], ' | ', $matches[2] );
					return '</h1><p>' . $match . '</p>';
				},
				$result
			);
		}
		// Final cleaning
		$result = str_replace( [ '<hr />' ], '', $result );
		return $result;
	}

	/**
	 * Get the Apache infos.
	 *
	 * @return string The current info.
	 * @since    2.3.0
	 */
	private function get_info() {
		return $this->normalize( Capture::get_info(), 'base' );
	}
	/**
	 * Get the Apache config.
	 *
	 * @return string The current info.
	 * @since    2.3.0
	 */
	private function get_config() {
		return $this->normalize( Capture::get_info( '?config' ), 'config' );
	}

	/**
	 * Get the Apache infos.
	 *
	 * @return string The current info.
	 * @since    2.3.0
	 */
	public function display() {
		if ( 'config' === $this->page ) {
			return $this->get_config();
		}
		return $this->get_info();
	}

}
