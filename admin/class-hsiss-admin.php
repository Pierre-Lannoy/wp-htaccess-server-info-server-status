<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Hsiss\Plugin;

use Hsiss\Plugin\Feature\Analytics;
use Hsiss\Plugin\Feature\AnalyticsFactory;
use Hsiss\System\Assets;
use Hsiss\System\Logger;
use Hsiss\System\Role;
use Hsiss\System\Option;
use Hsiss\System\Form;
use Hsiss\System\Blog;
use Hsiss\System\Date;
use Hsiss\System\Timezone;
use Hsiss\System\GeoIP;
use Hsiss\System\Environment;
use PerfOpsOne\AdminMenus;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Hsiss_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( HSISS_ASSETS_ID, HSISS_ADMIN_URL, 'css/htaccess-server-info-server-status.min.css' );
		$this->assets->register_style( 'hsiss-daterangepicker', HSISS_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'hsiss-switchery', HSISS_ADMIN_URL, 'css/switchery.min.css' );
		$this->assets->register_style( 'hsiss-tooltip', HSISS_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'hsiss-chartist', HSISS_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'hsiss-chartist-tooltip', HSISS_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( HSISS_ASSETS_ID, HSISS_ADMIN_URL, 'js/htaccess-server-info-server-status.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'hsiss-moment-with-locale', HSISS_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'hsiss-daterangepicker', HSISS_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'hsiss-switchery', HSISS_ADMIN_URL, 'js/switchery.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'hsiss-chartist', HSISS_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'hsiss-chartist-tooltip', HSISS_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'hsiss-chartist' ] );
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfops_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['settings'][] = [
				'name'          => HSISS_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \Hsiss\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'hsiss-settings',
				/* translators: as in the sentence "Apache Status & Info Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'htaccess-server-info-server-status' ), HSISS_PRODUCT_NAME ),
				'menu_title'    => HSISS_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'position'      => 50,
				'plugin'        => HSISS_SLUG,
				'version'       => HSISS_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Hsiss\System\Statistics', 'sc_get_raw' ],
			];
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'API Apache Status & Info', 'htaccess-server-info-server-status' ),
				/* translators: as in the sentence "Find out inbound and outbound API calls made to/from your network." or "Find out inbound and outbound API calls made to/from your website." */
				'description'   => sprintf( esc_html__( 'Find out inbound and outbound API calls made to/from your %s.', 'htaccess-server-info-server-status' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'htaccess-server-info-server-status' ) : esc_html__( 'website', 'htaccess-server-info-server-status' ) ),
				'icon_callback' => [ \Hsiss\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'hsiss-viewer',
				/* translators: as in the sentence "DecaLog Viewer" */
				'page_title'    => sprintf( esc_html__( 'API Apache Status & Info', 'htaccess-server-info-server-status' ), HSISS_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'API Apache Status & Info', 'htaccess-server-info-server-status' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_viewer_page' ],
				'position'      => 50,
				'plugin'        => HSISS_SLUG,
				'activated'     => true,
				'remedy'        => '',
			];
		}
		return $perfops;
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfops_admin_menus', [ $this, 'init_perfops_admin_menus' ] );
		AdminMenus::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'hsiss_inbound_options_section', esc_html__( 'Inbound APIs', 'htaccess-server-info-server-status' ), [ $this, 'inbound_options_section_callback' ], 'hsiss_inbound_options_section' );
		add_settings_section( 'hsiss_outbound_options_section', esc_html__( 'Outbound APIs', 'htaccess-server-info-server-status' ), [ $this, 'outbound_options_section_callback' ], 'hsiss_outbound_options_section' );
		add_settings_section( 'hsiss_plugin_features_section', esc_html__( 'Plugin features', 'htaccess-server-info-server-status' ), [ $this, 'plugin_features_section_callback' ], 'hsiss_plugin_features_section' );
		add_settings_section( 'hsiss_plugin_options_section', esc_html__( 'Plugin options', 'htaccess-server-info-server-status' ), [ $this, 'plugin_options_section_callback' ], 'hsiss_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=hsiss-settings' ) ), esc_html__( 'Settings', 'htaccess-server-info-server-status' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=hsiss-viewer' ) ), esc_html__( 'Statistics', 'htaccess-server-info-server-status' ) );
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, HSISS_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . HSISS_SLUG . '/">' . __( 'Support', 'htaccess-server-info-server-status' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status">' . __( 'GitHub repository', 'htaccess-server-info-server-status' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics();
		include HSISS_ADMIN_DIR . 'partials/htaccess-server-info-server-status-admin-view-analytics.php';
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
			}
		}
		include HSISS_ADMIN_DIR . 'partials/htaccess-server-info-server-status-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'hsiss-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'hsiss_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_plugin_options_usecdn' ) : false );
				Option::network_set( 'download_favicons', array_key_exists( 'hsiss_plugin_options_favicons', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_plugin_options_favicons' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'hsiss_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_plugin_options_nag' ) : false );
				Option::network_set( 'inbound_capture', array_key_exists( 'hsiss_inbound_options_capture', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_inbound_options_capture' ) : false );
				Option::network_set( 'outbound_capture', array_key_exists( 'hsiss_outbound_options_capture', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_outbound_options_capture' ) : false );
				Option::network_set( 'inbound_cut_path', array_key_exists( 'hsiss_inbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'hsiss_inbound_options_cut_path' ) : Option::network_get( 'hsiss_inbound_options_cut_path' ) );
				Option::network_set( 'outbound_cut_path', array_key_exists( 'hsiss_outbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'hsiss_outbound_options_cut_path' ) : Option::network_get( 'hsiss_outbound_options_cut_path' ) );
				Option::network_set( 'history', array_key_exists( 'hsiss_plugin_features_history', $_POST ) ? (string) filter_input( INPUT_POST, 'hsiss_plugin_features_history', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'history' ) );
				$message = esc_html__( 'Plugin settings have been saved.', 'htaccess-server-info-server-status' );
				$code    = 0;
				add_settings_error( 'hsiss_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'htaccess-server-info-server-status' );
				$code    = 2;
				add_settings_error( 'hsiss_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'hsiss-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'htaccess-server-info-server-status' );
				$code    = 0;
				add_settings_error( 'hsiss_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'htaccess-server-info-server-status' );
				$code    = 2;
				add_settings_error( 'hsiss_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'hsiss_plugin_options_favicons',
			__( 'Favicons', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_plugin_options_section',
			'hsiss_plugin_options_section',
			[
				'text'        => esc_html__( 'Download and display', 'htaccess-server-info-server-status' ),
				'id'          => 'hsiss_plugin_options_favicons',
				'checked'     => Option::network_get( 'download_favicons' ),
				'description' => esc_html__( 'If checked, Apache Status & Info will download favicons of websites to display them in reports.', 'htaccess-server-info-server-status' ) . '<br/>' . esc_html__( 'Note: This feature uses the (free) Google Favicon Service.', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_plugin_options_section', 'hsiss_plugin_options_favicons' );
		$geo_ip = new GeoIP();
		if ( $geo_ip->is_installed() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'htaccess-server-info-server-status' ), '<em>' . $geo_ip->get_full_name() .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any IP geographic information plugin. To take advantage of the geographical distribution of calls in Apache Status & Info, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'htaccess-server-info-server-status' ), '<a href="https://wordpress.org/plugins/geoip-detect/">GeoIP Detection</a>' );
		}
		add_settings_field(
			'hsiss_plugin_options_geoip',
			__( 'IP information', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_simple_text' ],
			'hsiss_plugin_options_section',
			'hsiss_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'hsiss_plugin_options_section', 'hsiss_plugin_options_geoip' );
		
		if ( defined( 'DECALOG_VERSION' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'htaccess-server-info-server-status' ), '<em>DecaLog v' . DECALOG_VERSION .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in Apache Status & Info, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'htaccess-server-info-server-status' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'hsiss_plugin_options_logger',
			__( 'Logging', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_simple_text' ],
			'hsiss_plugin_options_section',
			'hsiss_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'hsiss_plugin_options_section', 'hsiss_plugin_options_logger' );
		add_settings_field(
			'hsiss_plugin_options_usecdn',
			__( 'Resources', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_plugin_options_section',
			'hsiss_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'htaccess-server-info-server-status' ),
				'id'          => 'hsiss_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, Apache Status & Info will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_plugin_options_section', 'hsiss_plugin_options_usecdn' );
		add_settings_field(
			'hsiss_plugin_options_nag',
			__( 'Admin notices', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_plugin_options_section',
			'hsiss_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'htaccess-server-info-server-status' ),
				'id'          => 'hsiss_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows Apache Status & Info to display admin notices throughout the admin dashboard.', 'htaccess-server-info-server-status' ) . '<br/>' . esc_html__( 'Note: Apache Status & Info respects DISABLE_NAG_NOTICES flag.', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_plugin_options_section', 'hsiss_plugin_options_nag' );
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form = new Form();
		add_settings_field(
			'hsiss_plugin_features_history',
			esc_html__( 'Historical data', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_select' ],
			'hsiss_plugin_features_section',
			'hsiss_plugin_features_section',
			[
				'list'        => $this->get_retentions_array(),
				'id'          => 'hsiss_plugin_features_history',
				'value'       => Option::network_get( 'history' ),
				'description' => esc_html__( 'Maximum age of data to keep for statistics.', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_plugin_features_section', 'hsiss_plugin_features_history' );
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  3.2.0
	 */
	protected function get_retentions_array() {
		$result = [];
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 30 * $i ), esc_html( sprintf( _n( '%d month', '%d months', $i, 'htaccess-server-info-server-status' ), $i ) ) ];
		}
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 365 * $i ), esc_html( sprintf( _n( '%d year', '%d years', $i, 'htaccess-server-info-server-status' ), $i ) ) ];
		}
		return $result;
	}

	/**
	 * Callback for inbound APIs section.
	 *
	 * @since 1.0.0
	 */
	public function inbound_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'hsiss_inbound_options_capture',
			__( 'Analytics', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_inbound_options_section',
			'hsiss_inbound_options_section',
			[
				'text'        => esc_html__( 'Activated', 'htaccess-server-info-server-status' ),
				'id'          => 'hsiss_inbound_options_capture',
				'checked'     => Option::network_get( 'inbound_capture' ),
				'description' => esc_html__( 'If checked, Apache Status & Info will analyze inbound API calls (the calls made by external sites or apps to your site).', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_inbound_options_section', 'hsiss_inbound_options_capture' );
		add_settings_field(
			'hsiss_inbound_options_cut_path',
			__( 'Path cut', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_input_integer' ],
			'hsiss_inbound_options_section',
			'hsiss_inbound_options_section',
			[
				'id'          => 'hsiss_inbound_options_cut_path',
				'value'       => Option::network_get( 'inbound_cut_path' ),
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Allows to keep only the first most significative elements of the endpoint path.', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_inbound_options_section', 'hsiss_inbound_options_cut_path' );
	}

	/**
	 * Callback for outbound APIs section.
	 *
	 * @since 1.0.0
	 */
	public function outbound_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'hsiss_outbound_options_capture',
			__( 'Analytics', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_outbound_options_section',
			'hsiss_outbound_options_section',
			[
				'text'        => esc_html__( 'Activated', 'htaccess-server-info-server-status' ),
				'id'          => 'hsiss_outbound_options_capture',
				'checked'     => Option::network_get( 'outbound_capture' ),
				'description' => esc_html__( 'If checked, Apache Status & Info will analyze outbound API calls (the calls made by your site to external services).', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_outbound_options_section', 'hsiss_outbound_options_capture' );
		add_settings_field(
			'hsiss_outbound_options_cut_path',
			__( 'Path cut', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_input_integer' ],
			'hsiss_outbound_options_section',
			'hsiss_outbound_options_section',
			[
				'id'          => 'hsiss_outbound_options_cut_path',
				'value'       => Option::network_get( 'outbound_cut_path' ),
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Allows to keep only the first most significative elements of the endpoint path.', 'htaccess-server-info-server-status' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_outbound_options_section', 'hsiss_outbound_options_cut_path' );
	}

}
