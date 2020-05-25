<?php
/**
 * DataBeam integration
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Hsiss\Plugin\Integration;

use Hsiss\System\Option;
use Hsiss\System\Role;
use Hsiss\Plugin\Core;

/**
 * Define the DataBeam integration.
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.1.1
 */
class Databeam {

	/**
	 * Init the class.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		if ( Option::network_get( 'status' ) ) {
			add_filter( 'databeam_source_register', [ static::class, 'register_status' ] );
		}
		if ( Option::network_get( 'info' ) ) {
			add_filter( 'databeam_source_register', [ static::class, 'register_info' ] );
		}
	}

	/**
	 * Register server status endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_status( $integrations ) {
		$integrations[ HSISS_SLUG . '::kpi' ] = [
			'name'         => HSISS_PRODUCT_NAME,
			'version'      => HSISS_VERSION,
			'subname'      => __( 'KPIs', 'htaccess-server-info-server-status' ),
			'description'  => __( 'Allows to integrate, as a DataBeam source, all KPIs & indexes collected from the <code>server-status</code> page of this server.', 'htaccess-server-info-server-status' ),
			'instruction'  => __( 'Just activate-it and use it as source in your favorite visualizers and publishers.', 'htaccess-server-info-server-status' ),
			'note'         => __( 'Adding this type of data source is reserved to site admins in single site environments and network admins in multisite environments.', 'htaccess-server-info-server-status' ),
			'legal'        =>
				[
					'author'  => 'Pierre Lannoy',
					'url'     => 'https://github.com/Pierre-Lannoy',
					'license' => 'gpl3',
				],
			'icon'         =>
				[
					'static' => [
						'class'  => '\Hsiss\Plugin\Core',
						'method' => 'get_base64_logo',
					],
				],
			'picture'      =>
				[
					'static' => [
						'class'  => '\Hsiss\Plugin\Integration\Databeam',
						'method' => 'get_base64_banner',
					],
				],
			'type'         => 'kpi_collection',
			'roles'        => [ 'network_admin', 'admin' ],
			'restrictions' => [ 'only_network' ],
			'ttl'          => '15-600',
			'caching'      => [],
			'data_call'    =>
				[
					'static' => [
						'class'  => '\Hsiss\Plugin\Integration\Databeam',
						'method' => 'get_status_kpi_collection',
					],
				],
			'data_args'    => [],
		];
		return $integrations;
	}

	/**
	 * Get a raw (SVG) icon.
	 *
	 * @param string $name Optional. The name of the file.
	 * @return  string  The raw value of the SVG file.
	 * @since   1.0.0
	 */
	private static function get_raw( $name = 'banner.svg' ) {
		$filename = __DIR__ . '/' . $name;
		if ( ! file_exists( $filename ) ) {
			$content = '';
		} else {
			$content = file_get_contents( $filename );
		}
		// phpcs:ignore
		$id = Cache::id( serialize( [ 'name' => $name, 'squared' => $squared ] ), 'flags/' );
		if ( Cache::is_memory() ) {
			$flag = Cache::get_shared( $id );
			if ( isset( $flag ) ) {
				return $flag;
			}
		} else {
			if ( array_key_exists( $fname, self::$flags ) ) {
				return self::$flags[ $fname ];
			}
		}
		if ( ! file_exists( $filename ) ) {
			return ( 'fr' === $name ? '' : self::get_raw() );
		}
		if ( Cache::is_memory() ) {
			// phpcs:ignore
			Cache::set_shared( $id, file_get_contents( $filename ), 'infinite' );
		} else {
			// phpcs:ignore
			self::$flags[ $fname ] = file_get_contents( $filename );
		}

		return ( self::get_raw( $name ) );
	}

	/**
	 * Returns a base64 svg resource for the banner.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_banner() {
		$filename = __DIR__ . '/banner.svg';
		if ( file_exists( $filename ) ) {
			// phpcs:ignore
			$content = @file_get_contents( $filename );
		} else {
			$content = '';
		}
		if ( $content ) {
			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $content );
		}
		return '';
	}

	/**
	 * Register server infos endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_info( $integrations ) {
		return $integrations;
	}

	/**
	 * Get the server status data.
	 *
	 * @param   array   $args   Optional. The needed args.
	 * @return  array   The full transformed data.
	 * @since    1.0.0
	 */
	public static function get_status_kpi_collection( $args = [] ) {
		return [];  // status + meta + data
	}

}
