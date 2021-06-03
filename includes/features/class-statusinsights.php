<?php
/**
 * Apache Status & Info analytics
 *
 * Handles status insights operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */

namespace Hsiss\Plugin\Feature;

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
use Hsiss\Plugin\Feature\Capture;
use Feather;
use Flagiconcss;
use Hsiss\Plugin\Integration\Databeam;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */
class StatusInsights {

	/**
	 * Colors for graphs.
	 *
	 * @since  2.3.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#b2c326', '#BDC3C6' ];

	/**
	 * Main KPIs.
	 *
	 * @since  2.3.0
	 * @var    array    $kpis    The kpi ids.
	 */
	private $kpis = [ 'access', 'query', 'data', 'worker', 'cpu', 'uptime' ];

	/**
	 * Scoreboard elements.
	 *
	 * @since  2.3.0
	 * @var    array    $scoreboard    The scoreboard ids.
	 */
	private static $scoreboard = [ 'O', 'Z', 'W', 'R', 'K', 'C', 'G', 'S', 'D', 'L', 'I' ];

	/**
	 * Scoreboard names.
	 *
	 * @since  2.3.0
	 * @var    array    $sbnames    The scoreboard names.
	 */
	private static $sbnames = [];

	/**
	 * Details elements.
	 *
	 * @since  2.3.0
	 * @var    array    $details    The details ids.
	 */
	private static $details = [ 'server-version', 'server-mpm', 'server-built', 'ctime', 'rtime', 'configg', 'mpmg', 'uptime', 'load1', 'load5', 'load15' ];

	/**
	 * Details keys.
	 *
	 * @since  2.3.0
	 * @var    array    $keys    The details keys.
	 */
	private static $keys = [ 'ServerVersion', 'ServerMPM', 'Server Built', 'CurrentTime', 'RestartTime', 'ParentServerConfigGeneration', 'ParentServerMPMGeneration', 'ServerUptime', 'Load1', 'Load5', 'Load15' ];

	/**
	 * Details names.
	 *
	 * @since  2.3.0
	 * @var    array    $dnames    The details names.
	 */
	private static $dnames = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.3.0
	 */
	public function __construct() {
		self::$sbnames = [
			'O' => esc_html__( 'Open slot with no current process', 'htaccess-server-info-server-status' ),
			'Z' => esc_html__( 'Waiting for connection', 'htaccess-server-info-server-status' ),
			'W' => esc_html__( 'Sending reply', 'htaccess-server-info-server-status' ),
			'R' => esc_html__( 'Reading request', 'htaccess-server-info-server-status' ),
			'K' => esc_html__( 'Keepalive (read)', 'htaccess-server-info-server-status' ),
			'C' => esc_html__( 'Closing connection', 'htaccess-server-info-server-status' ),
			'G' => esc_html__( 'Gracefully finishing', 'htaccess-server-info-server-status' ),
			'S' => esc_html__( 'Starting up', 'htaccess-server-info-server-status' ),
			'D' => esc_html__( 'DNS lookup', 'htaccess-server-info-server-status' ),
			'L' => esc_html__( 'Logging', 'htaccess-server-info-server-status' ),
			'I' => esc_html__( 'Idle cleanup of worker', 'htaccess-server-info-server-status' ),
		];
		self::$dnames  = [
			'server-version' => esc_html__( 'Server version', 'htaccess-server-info-server-status' ),
			'server-mpm'     => esc_html__( 'Server MPM', 'htaccess-server-info-server-status' ),
			'server-built'   => esc_html__( 'Server built', 'htaccess-server-info-server-status' ),
			'ctime'          => esc_html__( 'Current time', 'htaccess-server-info-server-status' ),
			'rtime'          => esc_html__( 'Restart time', 'htaccess-server-info-server-status' ),
			'configg'        => esc_html__( 'Parent server config generation', 'htaccess-server-info-server-status' ),
			'mpmg'           => esc_html__( 'Parent server MPM generation', 'htaccess-server-info-server-status' ),
			'uptime'         => esc_html__( 'Uptime', 'htaccess-server-info-server-status' ),
			'load1'          => esc_html__( 'Server load over last 1 minute', 'htaccess-server-info-server-status' ),
			'load5'          => esc_html__( 'Server load over last 5 minutes', 'htaccess-server-info-server-status' ),
			'load15'         => esc_html__( 'Server load over last 15 minutes', 'htaccess-server-info-server-status' ),
		];
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    2.3.0
	 */
	public function get_title_bar() {
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<span class="hsiss-title" id="hsiss-insights-title">' . esc_html__( 'Apache Server Status', 'htaccess-server-info-server-status' ) . '</span>';
		$result .= '<span class="hsiss-subtitle" id="hsiss-insights-subtitle"></span>';
		$result .= '<span class="hsiss-switch">' . $this->get_switch_box( 'live' ) . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get a switch box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	private function get_switch_box( $switch ) {
		$enabled = true;
		$opacity = '';
		$checked = true;
		$result  = '<input type="checkbox" class="hsiss-input-' . $switch . '-switch"' . ( $checked ? ' checked' : '' ) . ' />';
		// phpcs:ignore
		$result .= '&nbsp;<span class="hsiss-text-' . $switch . '-switch"' . $opacity . '>' . esc_html__( $switch, 'htaccess-server-info-server-status' ) . '</span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var elem = document.querySelector(".hsiss-input-' . $switch . '-switch");';
		$result .= ' var params = {size: "small", color: "#5A738E", disabledOpacity:0.6 };';
		$result .= ' var ' . $switch . ' = new Switchery(elem, params);';
		if ( $enabled ) {
			$result .= ' ' . $switch . '.enable();';
		} else {
			$result .= ' ' . $switch . '.disable();';
		}
		$result .= ' elem.onchange = function() {';
		switch ( $switch ) {
			case 'live':
				$result .= '  running = elem.checked;';
				break;
		}
		$result .= ' };';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    2.3.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<div class="hsiss-kpi-bar">';
		foreach ( $this->kpis as $kpi ) {
			$result .= '<div class="hsiss-kpi-large">' . $this->get_large_kpi( $kpi ) . '</div>';
		}
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the detail box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_detail_box() {
		$result  = '<div class="hsiss-60-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Details', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-detail">' . $this->get_detail_placeholder() . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the scoreboard box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_scoreboard_box() {
		$result  = '<div class="hsiss-40-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Scoreboard', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content">' . $this->get_scoreboard_placeholder() . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'access':
				$icon  = Feather\Icons::get_base64( 'arrow-down-circle', 'none', '#73879C' );
				$title = esc_html__( 'Requests', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Rate and number of requests.', 'htaccess-server-info-server-status' );
				break;
			case 'query':
				$icon  = Feather\Icons::get_base64( 'loader', 'none', '#73879C' );
				$title = esc_html__( 'Avg. Request', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Average latency and size of requests.', 'htaccess-server-info-server-status' );
				break;
			case 'data':
				$icon  = Feather\Icons::get_base64( 'link-2', 'none', '#73879C' );
				$title = esc_html__( 'Data', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Rate and volume of transferred data.', 'htaccess-server-info-server-status' );
				break;
			case 'worker':
				$icon  = Feather\Icons::get_base64( 'refresh-cw', 'none', '#73879C' );
				$title = esc_html__( 'Workers', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Busy number and total number of workers.', 'htaccess-server-info-server-status' );
				break;
			case 'cpu':
				$icon  = Feather\Icons::get_base64( 'cpu', 'none', '#73879C' );
				$title = esc_html__( 'CPU Load', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'CPU load of the server.', 'htaccess-server-info-server-status' );
				break;
			case 'uptime':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html__( 'Uptime', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Uptime of the server.', 'htaccess-server-info-server-status' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="hsiss-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="hsiss-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="hsiss-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="hsiss-kpi-large-middle"><div class="hsiss-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="hsiss-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="hsiss-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    2.3.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . HSISS_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    2.3.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . HSISS_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get a placeholder for scoreboard.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    2.3.0
	 */
	private function get_scoreboard_placeholder() {
		$result = '';
		foreach ( self::$scoreboard as $line ) {
			$result .= '<div class="hsiss-top-line">';
			$result .= '<div class="hsiss-top-line-title">';
			$result .= '<span class="hsiss-top-line-title-text">' . self::$sbnames[ $line ] . '</a></span>';
			$result .= '</div>';
			$result .= '<div class="hsiss-top-line-content">';
			$result .= '<div class="hsiss-bar-graph"><div class="hsiss-bar-graph-value" id="hsiss-sb-pct-' . $line . '" style="width:0%"></div></div>';
			$result .= '<div class="hsiss-bar-detail" id="hsiss-sb-val-' . $line . '"></div>';
			$result .= '</div>';
			$result .= '</div>';
		}
		return $result;
	}

	/**
	 * Get a placeholder for detail.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    2.3.0
	 */
	private function get_detail_placeholder() {
		$result  = '<table class="hsiss-table">';
		$result .= '<tr>';
		$result .= '<th style="width: 48%;">&nbsp;</th>';
		$result .= '<th>' . esc_html__( 'Current value', 'htaccess-server-info-server-status' ) . '</th>';
		$result .= '</tr>';
		foreach ( self::$details as $line ) {
			$result .= '<tr>';
			$result .= '<td>' . self::$dnames[ $line ] . '</td>';
			$result .= '<td><span id="row-detail-' . $line . '"></span></td>';
			$result .= '</tr>';
		}
		$result .= '</table>';
		$result .= '</table>';
		$result .= '<div style="text-align: center;margin-top: 20px;"><img style="height: 140px;opacity:0.4;" src="' . Databeam::get_base64_banner() . '"/></div>';
		return $result;
	}

	/**
	 * Get the Apache status.
	 *
	 * @return array The current status.
	 * @since    2.3.0
	 */
	public static function get_status() {
		$result           = [];
		$result['kpi']    = [];
		$result['txt']    = [];
		$result['sboard'] = [];
		$status           = Capture::get_status();
		if ( array_key_exists( 'ServerVersion', $status ) ) {
			$result['txt'][] = [ 'hsiss-insights-subtitle', $status['ServerVersion'] ];
		}
		// ACCESSES
		if ( array_key_exists( 'ReqPerSec', $status ) ) {
			$result['kpi'][] = [ 'kpi-main-access', round( (float) $status['ReqPerSec'], 2 ) . '&nbsp;<span class="hsiss-kpi-large-bottom-sup">/sec</span>' ];
		}
		if ( array_key_exists( 'Total Accesses', $status ) ) {
			$result['kpi'][] = [ 'kpi-bottom-access', '<span class="hsiss-kpi-large-bottom-text">' . esc_html__( 'Total:', 'htaccess-server-info-server-status' ) . '&nbsp;' . Conversion::number_shorten( (float) $status['Total Accesses'], 2, false, '&nbsp;' ) . '</span>' ];
		}
		// QUERY AVG
		if ( array_key_exists( 'DurationPerReq', $status ) ) {
			$result['kpi'][] = [ 'kpi-main-query', round( (float) $status['DurationPerReq'], 0 ) . '&nbsp;ms' ];
		}
		if ( array_key_exists( 'BytesPerReq', $status ) ) {
			$result['kpi'][] = [ 'kpi-bottom-query', '<span class="hsiss-kpi-large-bottom-text">' . Conversion::data_shorten( (float) $status['BytesPerReq'], 2, false, '&nbsp;' ) . '</span>' ];
		}
		// DATA
		if ( array_key_exists( 'BytesPerSec', $status ) ) {
			$result['kpi'][] = [ 'kpi-main-data', Conversion::data_shorten( (float) $status['BytesPerSec'], 2, false, '&nbsp;' ) . '&nbsp;<span class="hsiss-kpi-large-bottom-sup">/sec</span>' ];
		}
		if ( array_key_exists( 'Total kBytes', $status ) ) {
			$result['kpi'][] = [ 'kpi-bottom-data', '<span class="hsiss-kpi-large-bottom-text">' . esc_html__( 'Total:', 'htaccess-server-info-server-status' ) . '&nbsp;' . Conversion::data_shorten( (float) $status['Total kBytes'] * 1024, 2, false, '&nbsp;' ) . '</span>' ];
		}
		// WORKERS
		if ( array_key_exists( 'BusyWorkers', $status ) ) {
			$result['kpi'][] = [ 'kpi-main-worker', round( (int) $status['BusyWorkers'], 2 ) ];
			if ( array_key_exists( 'IdleWorkers', $status ) ) {
				$result['kpi'][] = [ 'kpi-bottom-worker', '<span class="hsiss-kpi-large-bottom-text">' . esc_html__( 'Total:', 'htaccess-server-info-server-status' ) . '&nbsp;' . ( (int) $status['IdleWorkers'] + (int) $status['BusyWorkers'] ) . '</span>' ];
			}
		}
		// CPU
		if ( array_key_exists( 'CPULoad', $status ) ) {
			$result['kpi'][] = [ 'kpi-main-cpu', round( (float) $status['CPULoad'], 2 ) . '&nbsp;%' ];
		}
		// UPTIME
		if ( array_key_exists( 'Uptime', $status ) ) {
			$result['kpi'][] = [ 'kpi-main-uptime', Conversion::duration_shorten( (int) $status['Uptime'], 1, false, '&nbsp;' ) ];
			$result['kpi'][] = [ 'kpi-bottom-uptime', '<span class="hsiss-kpi-large-bottom-text">' . (int) round( (float) $status['Uptime'], 0 ) . '&nbsp;s</span>' ];
		}
		// Scoreboard
		if ( array_key_exists( 'Scoreboard', $status ) ) {
			$sb = $status['Scoreboard'];
			$sb = str_replace( '.', 'O', $sb );
			$sb = str_replace( '_', 'Z', $sb );
			if ( 0 < strlen( $sb ) ) {
				foreach ( self::$scoreboard as $item ) {
					$val = (int) substr_count( $sb, $item );
					$pct = round( $val * 100 / strlen( $sb ), 1 );
					if ( 0 === $val ) {
						$val = '';
					}
					$result['sboard'][] = [ $item, $val, $pct . '%' ];
				}
			}
		}
		// Details
		for ( $i = 0; $i < count( self::$keys ); $i++ ) {
			if ( array_key_exists( self::$keys[$i], $status ) ) {
				$result['txt'][] = [ 'row-detail-' . self::$details[ $i ], $status[ self::$keys[ $i ] ] ];
			} else {
				$result['txt'][] = [ 'row-detail-' . self::$details[ $i ], '' ];
			}
		}
		return $result;
	}

	/**
	 * Ajax callback.
	 *
	 * @since    2.3.0
	 */
	public static function get_status_callback() {
		check_ajax_referer( 'ajax_hsiss', 'nonce' );
		exit( wp_json_encode( self::get_status() ) );
	}

}
