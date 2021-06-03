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


use Hsiss\System\User;
use Hsiss\System\Environment;
use Hsiss\System\Blog;
use Hsiss\System\IP;
use Hsiss\System\Option;
use Hsiss\System\Hash;
use function Automattic\Jetpack\load_3rd_party;

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
		$result   = [];
		$url      = site_url( 'server-status' ) . '?auto';
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$result['ServerVersion'] = 'HTTP ' . wp_remote_retrieve_response_code( $response ) . ' / ' . wp_remote_retrieve_response_message( $response );
			return $result;
		}
		foreach ( explode( PHP_EOL, $response['body'] ) as $line ) {
			$pair = explode( ': ', $line );
			if ( 2 === count( $pair ) ) {
				$result[ trim( $pair[0] ) ] = trim( $pair[1] );
			}
		}
		return $result;
	}

	/**
	 * Get the Apache infos.
	 *
	 * @param   string  $page   Optional. The subpage to request.
	 * @return  string The current infos as html.
	 * @since    2.3.0
	 */
	public static function get_info( $page = '' ) {
		$url      = site_url( 'server-info' ) . $page;
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = sprintf( esc_html__( 'Unable to retrieve data from %s (%s).', 'htaccess-server-info-server-status' ), $url, 'HTTP ' . wp_remote_retrieve_response_code( $response ) . ' / ' . wp_remote_retrieve_response_message( $response ) );
			return '<div class="notice notice-error"><p>' . $message . '</p></div>';
		}
		return $response['body'];
	}

}
