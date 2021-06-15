<?php
/**
 * WP-CLI for Apache Status & Info.
 *
 * Adds WP-CLI commands to Apache Status & Info
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.5.0
 */

namespace Hsiss\Plugin\Feature;

use Hsiss\System\Markdown;

/**
 * -.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.5.0
 */
class Wpcli {

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 2.5.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'WP-CLI.md', $attributes );
	}

}

add_shortcode( 'hsiss-wpcli', [ 'Hsiss\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );
