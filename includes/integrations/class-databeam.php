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
			'instruction'  => __( 'Just add this and use it as source in your favorite visualizers and publishers.', 'htaccess-server-info-server-status' ),
			'note'         => __( 'In multisite environments, this source is available for all network sites.', 'htaccess-server-info-server-status' ),
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
			'type'         => 'collection::kpi',
			'restrictions' => [ 'only_network' ],
			'ttl'          => '15-600:15',
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
		$result['meta']   = [];
		$result['data']   = [];
		$result['assets'] = [];

		$result['data'] = [
			'hit' => [
				'name'        => 'Hits per second',
				'description' => 'The number of hits per second',
				'values'      => [
					'hps'     => [
						'value' => 2254,
						'unit'  => 'H/s',
					],
				],
			],
			'temp' => [
				'name'        => 'Temperature',
				'description' => 'Outdoor temperature',
				'values'      => [
					'imperial'     => [
						'value' => 87,
						'unit'  => '°F',
					],
					'mksi'     => [
						'value' => 26,
						'unit'  => '°C',
					],
				],
			],
		];

		return $result;
	}

}
