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
use Hsiss\System\Logger;
use Hsiss\System\L10n;
use Hsiss\System\Http;
use Hsiss\System\Favicon;
use Hsiss\System\Timezone;
use Hsiss\System\UUID;
use Hsiss\Plugin\Feature\Capture;
use Feather;
use Flagiconcss;


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
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'main-chart':
				return $this->query_chart();
			case 'map':
				return $this->query_map();
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'top-domains':
				return $this->query_top( 'domains', (int) $queried );
			case 'top-authorities':
				return $this->query_top( 'authorities', (int) $queried );
			case 'top-endpoints':
				return $this->query_top( 'endpoints', (int) $queried );
			case 'sites':
				return $this->query_list( 'sites' );
			case 'domains':
				return $this->query_list( 'domains' );
			case 'authorities':
				return $this->query_list( 'authorities' );
			case 'endpoints':
				return $this->query_list( 'endpoints' );
			case 'codes':
				return $this->query_list( 'codes' );
			case 'schemes':
				return $this->query_list( 'schemes' );
			case 'methods':
				return $this->query_list( 'methods' );
			case 'countries':
				return $this->query_list( 'countries' );
			case 'code':
				return $this->query_pie( 'code', (int) $queried );
			case 'security':
				return $this->query_pie( 'security', (int) $queried );
			case 'method':
				return $this->query_pie( 'method', (int) $queried );
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	private function query_pie( $type, $limit ) {
		$extra_field = '';
		$extra       = [];
		$not         = false;
		$uuid        = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'code':
				$group       = 'code';
				$follow      = 'authority';
				$extra_field = 'code';
				$extra       = [ 0 ];
				$not         = true;
				break;
			case 'security':
				$group       = 'scheme';
				$follow      = 'endpoint';
				$extra_field = 'scheme';
				$extra       = [ 'http', 'https' ];
				$not         = false;
				break;
			case 'method':
				$group  = 'verb';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, $extra_field, $extra, $not, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		$labels = [];
		$series = [];
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$meta = strtoupper( $data[ $cpt ][ $group ] );
			if ( 'code' === $type ) {
				$meta = $data[ $cpt ][ $group ] . ' ' . Http::$http_status_codes[ (int) $data[ $cpt ][ $group ] ];
			}
			$labels[] = strtoupper( $data[ $cpt ][ $group ] );
			$series[] = [
				'meta'  => $meta,
				'value' => (float) $percent,
			];
			++$cpt;
		}
		if ( 0 < $other ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $other / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$labels[] = esc_html__( 'Other', 'htaccess-server-info-server-status' );
			$series[] = [
				'meta'  => esc_html__( 'Other', 'htaccess-server-info-server-status' ),
				'value' => (float) $percent,
			];
		}
		$result  = '<div class="hsiss-pie-box">';
		$result .= '<div class="hsiss-pie-graph">';
		$result .= '<div class="hsiss-pie-graph-handler" id="hsiss-pie-' . $group . '"></div>';
		$result .= '</div>';
		$result .= '<div class="hsiss-pie-legend">';
		foreach ( $labels as $key => $label ) {
			$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
			$result .= '<div class="hsiss-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
		}
		$result .= '';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
			[
				'labels' => $labels,
				'series' => $series,
			]
		) . ';';
		$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
		$result .= ' var option' . $uuid . ' = {width: 120, height: 120, showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
		$result .= ' new Chartist.Pie("#hsiss-pie-' . $group . '", data' . $uuid . ', option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		return [ 'hsiss-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	private function query_top( $type, $limit ) {
		switch ( $type ) {
			case 'authorities':
				$group  = 'authority';
				$follow = 'authority';
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			default:
				$group  = 'id';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			$url = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $data[ $cpt ][ $group ],
					'domain' => $data[ $cpt ]['id'],
				]
			);
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			$result .= '<div class="hsiss-top-line">';
			$result .= '<div class="hsiss-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $data[ $cpt ]['id'] ) . '" />&nbsp;&nbsp;<span class="hsiss-top-line-title-text"><a href="' . esc_url( $url ) . '">' . $data[ $cpt ][ $group ] . '</a></span>';
			$result .= '</div>';
			$result .= '<div class="hsiss-top-line-content">';
			$result .= '<div class="hsiss-bar-graph"><div class="hsiss-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="hsiss-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'], 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="hsiss-top-line hsiss-minor-data">';
		$result .= '<div class="hsiss-top-line-title">';
		$result .= '<span class="hsiss-top-line-title-text">' . esc_html__( 'Other', 'htaccess-server-info-server-status' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="hsiss-top-line-content">';
		$result .= '<div class="hsiss-bar-graph"><div class="hsiss-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="hsiss-bar-detail">' . Conversion::number_shorten( $other, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'hsiss-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	private function query_list( $type ) {
		$follow     = '';
		$has_detail = false;
		$detail     = '';
		switch ( $type ) {
			case 'domains':
				$group      = 'id';
				$follow     = 'domain';
				$has_detail = true;
				break;
			case 'authorities':
				$group      = 'authority';
				$follow     = 'authority';
				$has_detail = true;
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			case 'codes':
				$group = 'code';
				break;
			case 'schemes':
				$group = 'scheme';
				break;
			case 'methods':
				$group = 'verb';
				break;
			case 'countries':
				$group = 'country';
				break;
			case 'sites':
				$group  = 'site';
				break;
		}
		$data         = Schema::get_grouped_list( $group, [ 'authority', 'endpoint' ], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$detail_name  = esc_html__( 'Details', 'htaccess-server-info-server-status' );
		$calls_name   = esc_html__( 'Calls', 'htaccess-server-info-server-status' );
		$data_name    = esc_html__( 'Data Volume', 'htaccess-server-info-server-status' );
		$latency_name = esc_html__( 'Latency', 'htaccess-server-info-server-status' );
		$result       = '<table class="hsiss-table">';
		$result      .= '<tr>';
		$result      .= '<th>&nbsp;</th>';
		if ( $has_detail ) {
			$result .= '<th>' . $detail_name . '</th>';
		}
		$result   .= '<th>' . $calls_name . '</th>';
		$result   .= '<th>' . $data_name . '</th>';
		$result   .= '<th>' . $latency_name . '</th>';
		$result   .= '</tr>';
		$other     = false;
		$other_str = '';
		foreach ( $data as $key => $row ) {
			$url         = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $row[ $group ],
					'domain' => $row['id'],
				]
			);
			$name        = $row[ $group ];
			$other       = ( 'countries' === $type && ( empty( $name ) || 2 !== strlen( $name ) ) );
			$authorities = sprintf( esc_html( _n( '%d subdomain', '%d subdomains', $row['cnt_authority'], 'htaccess-server-info-server-status' ) ), $row['cnt_authority'] );
			$endpoints   = sprintf( esc_html( _n( '%d endpoint', '%d endpoints', $row['cnt_endpoint'], 'htaccess-server-info-server-status' ) ), $row['cnt_endpoint'] );
			switch ( $type ) {
				case 'sites':
					if ( 0 === (int) $row['sum_hit'] ) {
						break;
					}
					if ( 'summary' === $this->type ) {
						$url = $this->get_url(
							[],
							[
								'site' => $row['site'],
							]
						);
					} else {
						$url = $this->get_url(
							[],
							[
								'site'   => $row['site'],
								'domain' => $row['id'],
							]
						);
					}
					$site = Blog::get_blog_url( $row['site'] );
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $site ) . '" />&nbsp;&nbsp;<span class="hsiss-table-text"><a href="' . esc_url( $url ) . '">' . $site . '</a></span>';
					break;
				case 'domains':
					$detail = $authorities . ' - ' . $endpoints;
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="hsiss-table-text"><a href="' . esc_url( $url ) . '">' . $name . '</a></span>';
					break;
				case 'authorities':
					$detail = $endpoints;
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="hsiss-table-text"><a href="' . esc_url( $url ) . '">' . $name . '</a></span>';
					break;
				case 'endpoints':
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="hsiss-table-text"><a href="' . esc_url( $url ) . '">' . $name . '</a></span>';
					break;
				case 'codes':
					if ( '0' === $name ) {
						$name = '000';
					}
					$code = (int) $name;
					if ( 100 > $code ) {
						$http = '0xx';
					} elseif ( 200 > $code ) {
						$http = '1xx';
					} elseif ( 300 > $code ) {
						$http = '2xx';
					} elseif ( 400 > $code ) {
						$http = '3xx';
					} elseif ( 500 > $code ) {
						$http = '4xx';
					} elseif ( 600 > $code ) {
						$http = '5xx';
					} else {
						$http = 'nxx';
					}
					$name  = '<span class="hsiss-http hsiss-http-' . $http . '">' . $name . '</span>&nbsp;&nbsp;<span class="hsiss-table-text">' . Http::$http_status_codes[ $code ] . '</span>';
					$group = 'code';
					break;
				case 'schemes':
					$icon = Feather\Icons::get_base64( 'unlock', 'none', '#E74C3C' );
					if ( 'HTTPS' === strtoupper( $name ) ) {
						$icon = Feather\Icons::get_base64( 'lock', 'none', '#18BB9C' );
					}
					$name  = '<img style="width:14px;vertical-align:text-top;" src="' . $icon . '" />&nbsp;&nbsp;<span class="hsiss-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'scheme';
					break;
				case 'methods':
					$name  = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'code', 'none', '#73879C' ) . '" />&nbsp;&nbsp;<span class="hsiss-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'verb';
					break;
				case 'countries':
					if ( $other ) {
						$name = esc_html__( 'Other', 'htaccess-server-info-server-status' );
					} else {
						$country_name = L10n::get_country_name( $name );
						if ( $country_name === $name ) {
							$country_name = '';
						}
						$name = '<img style="width:16px;vertical-align:baseline;" src="' . Flagiconcss\Flags::get_base64( strtolower( $name ) ) . '" />&nbsp;&nbsp;<span class="hsiss-table-text" style="vertical-align: text-bottom;">' . $country_name . '</span>';
					}
					$group = 'country';
					break;
			}
			$calls = Conversion::number_shorten( $row['sum_hit'], 2, false, '&nbsp;' );
			$in    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="hsiss-table-text">' . Conversion::data_shorten( $row['sum_kb_in'] * 1024, 2, false, '&nbsp;' ) . '</span>';
			$out   = '<span class="hsiss-table-text">' . Conversion::data_shorten( $row['sum_kb_out'] * 1024, 2, false, '&nbsp;' ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$data  = $in . ' &nbsp;&nbsp; ' . $out;
			if ( 1 < $row['sum_hit'] ) {
				$min = Conversion::number_shorten( $row['min_latency'], 0 );
				if ( false !== strpos( $min, 'K' ) ) {
					$min = str_replace( 'K', esc_html_x( 's', 'Unit symbol - Stands for "second".', 'htaccess-server-info-server-status' ), $min );
				} else {
					$min = $min . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'htaccess-server-info-server-status' );
				}
				$max = Conversion::number_shorten( $row['max_latency'], 0 );
				if ( false !== strpos( $max, 'K' ) ) {
					$max = str_replace( 'K', esc_html_x( 's', 'Unit symbol - Stands for "second".', 'htaccess-server-info-server-status' ), $max );
				} else {
					$max = $max . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'htaccess-server-info-server-status' );
				}
				$latency = (int) $row['avg_latency'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'htaccess-server-info-server-status' ) . '&nbsp;<small>(' . $min . '→' . $max . ')</small>';
			} else {
				$latency = (int) $row['avg_latency'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'htaccess-server-info-server-status' );
			}
			if ( 'codes' === $type && '0' === $row[ $group ] ) {
				$latency = '-';
			}
			$row_str  = '<tr>';
			$row_str .= '<td data-th="">' . $name . '</td>';
			if ( $has_detail ) {
				$row_str .= '<td data-th="' . $detail_name . '">' . $detail . '</td>';
			}
			$row_str .= '<td data-th="' . $calls_name . '">' . $calls . '</td>';
			$row_str .= '<td data-th="' . $data_name . '">' . $data . '</td>';
			$row_str .= '<td data-th="' . $latency_name . '">' . $latency . '</td>';
			$row_str .= '</tr>';
			if ( $other ) {
				$other_str = $row_str;
			} else {
				$result .= $row_str;
			}
		}
		$result .= $other_str . '</table>';
		return [ 'hsiss-' . $type => $result ];
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
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Countries', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-detail">' . $this->get_graph_placeholder( 200 ) . '</div>';
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
				$title = esc_html__( 'Accesses', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Rate and number of accesses.', 'htaccess-server-info-server-status' );
				break;
			case 'query':
				$icon  = Feather\Icons::get_base64( 'loader', 'none', '#73879C' );
				$title = esc_html__( 'Avg. Request', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Average latency and size for last requests.', 'htaccess-server-info-server-status' );
				break;
			case 'data':
				$icon  = Feather\Icons::get_base64( 'link-2', 'none', '#73879C' );
				$title = esc_html__( 'Data', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Rate and volume of transferred data.', 'htaccess-server-info-server-status' );
				break;
			case 'worker':
				$icon  = Feather\Icons::get_base64( 'refresh-cw', 'none', '#73879C' );
				$title = esc_html__( 'Workers', 'htaccess-server-info-server-status' );
				$help  = esc_html__( 'Workers activity.', 'htaccess-server-info-server-status' );
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
			$result['kpi'][] = [ 'kpi-bottom-data', '<span class="hsiss-kpi-large-bottom-text">' . esc_html__( 'Total:', 'htdata-server-info-server-status' ) . '&nbsp;' . Conversion::data_shorten( (float) $status['Total kBytes'] * 1024, 2, false, '&nbsp;' ) . '</span>' ];
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
