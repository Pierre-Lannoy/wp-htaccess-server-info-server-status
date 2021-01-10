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
	 * Initialize the class and set its properties.
	 *
	 * @since    2.3.0
	 */
	public function __construct() {
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
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	private function query_map() {
		$uuid   = UUID::generate_unique_id( 5 );
		$data   = Schema::get_grouped_list( 'country', [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$series = [];
		foreach ( $data as $datum ) {
			if ( array_key_exists( 'country', $datum ) && ! empty( $datum['country'] ) ) {
				$series[ strtoupper( $datum['country'] ) ] = $datum['sum_hit'];
			}
		}
		$plus    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'plus-square', 'none', '#73879C' ) . '"/>';
		$minus   = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'minus-square', 'none', '#73879C' ) . '"/>';
		$result  = '<div class="hsiss-map-handler">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var mapdata' . $uuid . ' = ' . wp_json_encode( $series ) . ';';
		$result .= ' $(".hsiss-map-handler").vectorMap({';
		$result .= ' map: "world_mill",';
		$result .= ' backgroundColor: "#FFFFFF",';
		$result .= ' series: {';
		$result .= '  regions: [{';
		$result .= '   values: mapdata' . $uuid . ',';
		$result .= '   scale: ["#BDC7D1", "#73879C"],';
		$result .= '   normalizeFunction: "polynomial"';
		$result .= '  }]';
		$result .= ' },';
		$result .= '  regionStyle: {';
		$result .= '   initial: {fill: "#EEEEEE", "fill-opacity": 0.7},';
		$result .= '   hover: {"fill-opacity": 1,cursor: "default"},';
		$result .= '   selected: {},';
		$result .= '   selectedHover: {},';
		$result .= ' },';
		$result .= ' onRegionTipShow: function(e, el, code){if (mapdata' . $uuid . '[code]){el.html(el.html() + " (" + mapdata' . $uuid . '[code] + " ' . esc_html__( 'calls', 'htaccess-server-info-server-status' ) . ')")};},';
		$result .= ' });';
		$result .= ' $(".jvectormap-zoomin").html(\'' . $plus . '\');';
		$result .= ' $(".jvectormap-zoomout").html(\'' . $minus . '\');';
		$result .= '});';
		$result .= '</script>';
		return [ 'hsiss-map' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	private function query_chart() {
		$uuid           = UUID::generate_unique_id( 5 );
		$data_total     = Schema::get_time_series( $this->filter, ! $this->is_today, '', [], false );
		$data_uptime    = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_failure_codes, true );
		$data_error     = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', array_diff( Http::$http_error_codes, Http::$http_quota_codes ), false );
		$data_success   = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_success_codes, false );
		$data_quota     = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_quota_codes, false );
		$series_uptime  = [];
		$suc            = [];
		$err            = [];
		$quo            = [];
		$series_success = [];
		$series_error   = [];
		$series_quota   = [];
		$call_max       = 0;
		$kbin           = [];
		$kbout          = [];
		$series_kbin    = [];
		$series_kbout   = [];
		$data_max       = 0;
		$start          = '';
		foreach ( $data_total as $timestamp => $total ) {
			if ( '' === $start ) {
				$start = $timestamp;
			}
			$ts = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
			// Calls.
			if ( array_key_exists( $timestamp, $data_success ) ) {
				$val = $data_success[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$suc[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$suc[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			if ( array_key_exists( $timestamp, $data_error ) ) {
				$val = $data_error[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$err[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$err[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			if ( array_key_exists( $timestamp, $data_quota ) ) {
				$val = $data_quota[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$quo[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$quo[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			// Data.
			$val = $total['sum_kb_in'] * 1024;
			if ( $val > $data_max ) {
				$data_max = $val;
			}
			$kbin[] = [
				'x' => $ts,
				'y' => $val,
			];
			$val    = $total['sum_kb_out'] * 1024;
			if ( $val > $data_max ) {
				$data_max = $val;
			}
			$kbout[] = [
				'x' => $ts,
				'y' => $val,
			];
			// Uptime.
			if ( array_key_exists( $timestamp, $data_uptime ) ) {
				if ( 0 !== $total['sum_hit'] ) {
					$val             = round( $data_uptime[ $timestamp ]['sum_hit'] * 100 / $total['sum_hit'], 2 );
					$series_uptime[] = [
						'x' => $ts,
						'y' => $val,
					];
				} else {
					$series_uptime[] = [
						'x' => $ts,
						'y' => 100,
					];
				}
			} else {
				$series_uptime[] = [
					'x' => $ts,
					'y' => 100,
				];
			}
		}
		$before = [
			'x' => 'new Date(' . (string) ( strtotime( $start ) - 86400 ) . '000)',
			'y' => 'null',
		];
		$after  = [
			'x' => 'new Date(' . (string) ( strtotime( $timestamp ) + 86400 ) . '000)',
			'y' => 'null',
		];
		// Calls.
		$short     = Conversion::number_shorten( $call_max, 2, true );
		$call_max  = 0.5 + floor( $call_max / $short['divisor'] );
		$call_abbr = $short['abbreviation'];
		foreach ( $suc as $item ) {
			$item['y']        = $item['y'] / $short['divisor'];
			$series_success[] = $item;
		}
		foreach ( $err as $item ) {
			$item['y']      = $item['y'] / $short['divisor'];
			$series_error[] = $item;
		}
		foreach ( $quo as $item ) {
			$item['y']      = $item['y'] / $short['divisor'];
			$series_quota[] = $item;
		}
		array_unshift( $series_success, $before );
		array_unshift( $series_error, $before );
		array_unshift( $series_quota, $before );
		$series_success[] = $after;
		$series_error[]   = $after;
		$series_quota[]   = $after;
		$json_call        = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Success', 'htaccess-server-info-server-status' ),
						'data' => $series_success,
					],
					[
						'name' => esc_html__( 'Error', 'htaccess-server-info-server-status' ),
						'data' => $series_error,
					],
					[
						'name' => esc_html__( 'Quota Error', 'htaccess-server-info-server-status' ),
						'data' => $series_quota,
					],
				],
			]
		);
		$json_call        = str_replace( '"x":"new', '"x":new', $json_call );
		$json_call        = str_replace( ')","y"', '),"y"', $json_call );
		$json_call        = str_replace( '"null"', 'null', $json_call );
		// Data.
		$short     = Conversion::data_shorten( $data_max, 2, true );
		$data_max  = (int) ceil( $data_max / $short['divisor'] );
		$data_abbr = $short['abbreviation'];
		foreach ( $kbin as $kb ) {
			$kb['y']       = $kb['y'] / $short['divisor'];
			$series_kbin[] = $kb;
		}
		foreach ( $kbout as $kb ) {
			$kb['y']        = $kb['y'] / $short['divisor'];
			$series_kbout[] = $kb;
		}
		array_unshift( $series_kbin, $before );
		array_unshift( $series_kbout, $before );
		$series_kbin[]  = $after;
		$series_kbout[] = $after;
		$json_data      = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Incoming Data', 'htaccess-server-info-server-status' ),
						'data' => $series_kbin,
					],
					[
						'name' => esc_html__( 'Outcoming Data', 'htaccess-server-info-server-status' ),
						'data' => $series_kbout,
					],
				],
			]
		);
		$json_data      = str_replace( '"x":"new', '"x":new', $json_data );
		$json_data      = str_replace( ')","y"', '),"y"', $json_data );
		$json_data      = str_replace( '"null"', 'null', $json_data );
		// Uptime.
		array_unshift( $series_uptime, $before );
		$series_uptime[] = $after;
		$json_uptime     = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Perceived Uptime', 'htaccess-server-info-server-status' ),
						'data' => $series_uptime,
					],
				],
			]
		);
		$json_uptime     = str_replace( '"x":"new', '"x":new', $json_uptime );
		$json_uptime     = str_replace( ')","y"', '),"y"', $json_uptime );
		$json_uptime     = str_replace( '"null"', 'null', $json_uptime );
		// Rendering.
		if ( 4 < $this->duration ) {
			if ( 1 === $this->duration % 2 ) {
				$divisor = 6;
			} else {
				$divisor = 5;
			}
		} else {
			$divisor = $this->duration + 1;
		}
		$result  = '<div class="hsiss-multichart-handler">';
		$result .= '<div class="hsiss-multichart-item active" id="hsiss-chart-calls">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var call_data' . $uuid . ' = ' . $json_call . ';';
		$result .= ' var call_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var call_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [call_tooltip' . $uuid . '],';
		$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $call_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $call_abbr . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#hsiss-chart-calls", call_data' . $uuid . ', call_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="hsiss-multichart-item" id="hsiss-chart-data">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data_data' . $uuid . ' = ' . $json_data . ';';
		$result .= ' var data_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var data_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [data_tooltip' . $uuid . '],';
		$result .= '  axisX: {type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $data_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $data_abbr . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#hsiss-chart-data", data_data' . $uuid . ', data_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="hsiss-multichart-item" id="hsiss-chart-uptime">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var uptime_data' . $uuid . ' = ' . $json_uptime . ';';
		$result .= ' var uptime_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var uptime_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [uptime_tooltip' . $uuid . '],';
		$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#hsiss-chart-uptime", uptime_data' . $uuid . ', uptime_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '</div>';
		return [ 'hsiss-main-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    2.3.0
	 */
	private function query_kpi( $queried ) {
		$result = [];
		if ( 'call' === $queried ) {
			$data     = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata    = Schema::get_std_kpi( $this->previous );
			$current  = 0.0;
			$previous = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$current = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$previous = (float) $pdata['sum_hit'];
			}
			$result[ 'kpi-main-' . $queried ] = Conversion::number_shorten( $current, 1, false, '&nbsp;' );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			if ( is_array( $data ) && array_key_exists( 'avg_latency', $data ) && ! empty( $data['avg_latency'] ) ) {
				$result[ 'kpi-bottom-' . $queried ] = '<span class="hsiss-kpi-large-bottom-text">' . sprintf( esc_html__( 'avg latency: %s ms.', 'htaccess-server-info-server-status' ), (int) $data['avg_latency'] ) . '</span>';
			}
		}
		if ( 'data' === $queried ) {
			$data         = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata        = Schema::get_std_kpi( $this->previous );
			$current_in   = 0.0;
			$current_out  = 0.0;
			$previous_in  = 0.0;
			$previous_out = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_kb_in', $data ) && ! empty( $data['sum_kb_in'] ) ) {
				$current_in = (float) $data['sum_kb_in'] * 1024;
			}
			if ( is_array( $data ) && array_key_exists( 'sum_kb_out', $data ) && ! empty( $data['sum_kb_out'] ) ) {
				$current_out = (float) $data['sum_kb_out'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_in', $pdata ) && ! empty( $pdata['sum_kb_in'] ) ) {
				$previous_in = (float) $pdata['sum_kb_in'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_out', $pdata ) && ! empty( $pdata['sum_kb_out'] ) ) {
				$previous_out = (float) $pdata['sum_kb_out'] * 1024;
			}
			$current                          = $current_in + $current_out;
			$previous                         = $previous_in + $previous_out;
			$result[ 'kpi-main-' . $queried ] = Conversion::data_shorten( $current, 1, false, '&nbsp;' );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			$in                                 = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="hsiss-kpi-large-bottom-text">' . Conversion::data_shorten( $current_in, 2, false, '&nbsp;' ) . '</span>';
			$out                                = '<span class="hsiss-kpi-large-bottom-text">' . Conversion::data_shorten( $current_out, 2, false, '&nbsp;' ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$result[ 'kpi-bottom-' . $queried ] = $in . ' &nbsp;&nbsp; ' . $out;
		}
		if ( 'server' === $queried || 'quota' === $queried || 'pass' === $queried || 'uptime' === $queried ) {
			$not = false;
			if ( 'server' === $queried ) {
				$codes = Http::$http_error_codes;
			} elseif ( 'quota' === $queried ) {
				$codes = Http::$http_quota_codes;
			} elseif ( 'pass' === $queried ) {
				$codes = Http::$http_effective_pass_codes;
			} elseif ( 'uptime' === $queried ) {
				$codes = Http::$http_failure_codes;
				$not   = true;
			}
			$base        = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pbase       = Schema::get_std_kpi( $this->previous );
			$data        = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'code', $codes, $not );
			$pdata       = Schema::get_std_kpi( $this->previous, true, 'code', $codes, $not );
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $base ) && ! empty( $base['sum_hit'] ) ) {
				$base_value = (float) $base['sum_hit'];
			}
			if ( is_array( $pbase ) && array_key_exists( 'sum_hit', $pbase ) && ! empty( $pbase['sum_hit'] ) ) {
				$pbase_value = (float) $pbase['sum_hit'];
			}
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$data_value = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$pdata_value = (float) $pdata['sum_hit'];
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, 1 ) . '&nbsp;%';
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = '100&nbsp;%';
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = '0&nbsp;%';
				} else {
					$result[ 'kpi-main-' . $queried ] = '-';
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			switch ( $queried ) {
				case 'server':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="hsiss-kpi-large-bottom-text">' . sprintf( esc_html__( '%s calls in error', 'htaccess-server-info-server-status' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'quota':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="hsiss-kpi-large-bottom-text">' . sprintf( esc_html__( '%s blocked calls', 'htaccess-server-info-server-status' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'pass':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="hsiss-kpi-large-bottom-text">' . sprintf( esc_html__( '%s successful calls', 'htaccess-server-info-server-status' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'uptime':
					if ( 0.0 !== $base_value ) {
						$duration = implode( ', ', Date::get_age_array_from_seconds( $this->duration * DAY_IN_SECONDS * ( 1 - ( $data_value / $base_value ) ), true, true ) );
						if ( '' === $duration ) {
							$duration = esc_html__( 'no downtime', 'htaccess-server-info-server-status' );
						} else {
							$duration = sprintf( esc_html__( 'down %s', 'htaccess-server-info-server-status' ), $duration );
						}
						$result[ 'kpi-bottom-' . $queried ] = '<span class="hsiss-kpi-large-bottom-text">' . $duration . '</span>';
					}
					break;
			}
		}
		return $result;
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
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    2.3.0
	 */
	public function get_main_chart() {
		if ( 1 < $this->duration ) {
			$help_calls  = esc_html__( 'Responses types distribution.', 'htaccess-server-info-server-status' );
			$help_data   = esc_html__( 'Data volume distribution.', 'htaccess-server-info-server-status' );
			$help_uptime = esc_html__( 'Uptime variation.', 'htaccess-server-info-server-status' );
			$detail      = '<span class="hsiss-chart-button not-ready left" id="hsiss-chart-button-calls" data-position="left" data-tooltip="' . $help_calls . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
			$detail     .= '&nbsp;&nbsp;&nbsp;<span class="hsiss-chart-button not-ready left" id="hsiss-chart-button-data" data-position="left" data-tooltip="' . $help_data . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'link-2', 'none', '#73879C' ) . '" /></span>&nbsp;&nbsp;&nbsp;';
			$detail     .= '<span class="hsiss-chart-button not-ready left" id="hsiss-chart-button-uptime" data-position="left" data-tooltip="' . $help_uptime . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'activity', 'none', '#73879C' ) . '" /></span>';
			$result      = '<div class="hsiss-row">';
			$result     .= '<div class="hsiss-box hsiss-box-full-line">';
			$result     .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Metrics Variations', 'htaccess-server-info-server-status' ) . '<span class="hsiss-module-more">' . $detail . '</span></span></div>';
			$result     .= '<div class="hsiss-module-content" id="hsiss-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result     .= '</div>';
			$result     .= '</div>';
			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    2.3.0
	 */
	public function get_sites_list() {
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Sites Breakdown', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-sites">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    2.3.0
	 */
	public function get_domains_list() {
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'All Domains', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the authorities list.
	 *
	 * @return string  The table ready to print.
	 * @since    2.3.0
	 */
	public function get_authorities_list() {
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'All Subdomains', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the endpoints list.
	 *
	 * @return string  The table ready to print.
	 * @since    2.3.0
	 */
	public function get_endpoints_list() {
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'All Endpoints', 'htaccess-server-info-server-status' ) . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the extra list.
	 *
	 * @return string  The table ready to print.
	 * @since    2.3.0
	 */
	public function get_extra_list() {
		switch ( $this->extra ) {
			case 'codes':
				$title = esc_html__( 'All HTTP Codes', 'htaccess-server-info-server-status' );
				break;
			case 'schemes':
				$title = esc_html__( 'All Protocols', 'htaccess-server-info-server-status' );
				break;
			case 'methods':
				$title = esc_html__( 'All Methods', 'htaccess-server-info-server-status' );
				break;
			case 'countries':
				$title = esc_html__( 'All Countries', 'htaccess-server-info-server-status' );
				break;
			default:
				$title = esc_html__( 'All Endpoints', 'htaccess-server-info-server-status' );
		}
		$result  = '<div class="hsiss-box hsiss-box-full-line">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . $title . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-' . $this->extra . '">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the top domains box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_top_domain_box() {
		$url     = $this->get_url( [ 'domain' ], [ 'type' => 'domains' ] );
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all domains.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-40-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Top Domains', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-top-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the top authority box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_top_authority_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'authorities',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all subdomains.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-40-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Top Subdomains', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-top-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the top endpoint box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_top_endpoint_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'endpoints',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all endpoints.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-40-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Top Endpoints', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-top-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_map_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'countries',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all countries.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-60-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Countries', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-map">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_codes_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'codes',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all codes.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-33-module hsiss-33-left-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'HTTP codes', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-code">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_security_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'schemes',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of protocols breakdown.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-33-module hsiss-33-center-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Protocols', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-security">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    2.3.0
	 */
	public function get_method_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'methods',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all methods.', 'htaccess-server-info-server-status' );
		$result  = '<div class="hsiss-33-module hsiss-33-right-module">';
		$result .= '<div class="hsiss-module-title-bar"><span class="hsiss-module-title">' . esc_html__( 'Methods', 'htaccess-server-info-server-status' ) . '</span><span class="hsiss-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="hsiss-module-content" id="hsiss-method">' . $this->get_graph_placeholder( 90 ) . '</div>';
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
	 * Get the Apache status.
	 *
	 * @return array The current status.
	 * @since    2.3.0
	 */
	public static function get_status() {
		$result        = [];
		$result['kpi'] = [];
		$result['txt'] = [];
		$status        = Capture::get_status();
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
			$result['kpi'][] = [ 'kpi-bottom-uptime', '<span class="hsiss-kpi-large-bottom-text">' . (int) round( (float) $status['Uptime'], 0 ) . '&nbsp;s</span>' ];
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
