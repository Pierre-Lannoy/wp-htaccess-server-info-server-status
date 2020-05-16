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
		if ( Option::network_get( 'htaccess-server-info-server-status' ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) ) {
			if ( Option::network_get( 'status' ) ) {
				add_filter( 'databeam_source_register', [ static::class, 'register_status' ] );
			}
			if ( Option::network_get( 'info' ) ) {
				add_filter( 'databeam_source_register', [ static::class, 'register_info' ] );
			}
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
		$integrations['hsiss_status-kpi'] = [
			'name'        => HSISS_PRODUCT_NAME,
			'subname'     => __( 'KPIs', 'htaccess-server-info-server-status' ),
			'description' => __( 'All KPIs & indexes collected from the <code>server-status</code> page of this server.', 'htaccess-server-info-server-status' ),
			'icon'        => Core::get_base64_logo(),
			'type'        => 'kpi_collection',
			'properties'  => [ 'network' ],
			'ttl'         => '15-600',
			'data_call'   =>
				[
					'static' => [
						'class'  => '\Hsiss\Plugin\Integration\Databeam',
						'method' => 'get_status_kpi_collection',
					],
				],
			'data_args'   => [],
		];
		return $integrations;
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
	 * @param   array   $args   The needed args.
	 * @return  array   The full transformed data.
	 * @since    1.0.0
	 */
	public static function get_status_kpi_collection( $args = [] ) {
		return [];  // status + meta + data
	}

}
