<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Hsiss\Plugin;

use Hsiss\System\Assets;

use Hsiss\System\Role;
use Hsiss\System\Option;
use Hsiss\System\Form;
use Hsiss\System\Blog;
use Hsiss\System\Date;
use Hsiss\System\Timezone;
use Hsiss\System\GeoIP;
use Hsiss\System\Environment;
use Hsiss\Plugin\Feature\StatusInsights;
use Hsiss\Plugin\Feature\InfoInsights;
use PerfOpsOne\Menus;
use PerfOpsOne\AdminBar;

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
		$this->assets->register_script( HSISS_LIVESTATUS_ID, HSISS_ADMIN_URL, 'js/insights-status.min.js', [ 'jquery' ] );
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
	public function init_perfopsone_admin_menus( $perfops ) {
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
				'plugin'        => HSISS_SLUG,
				'version'       => HSISS_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Hsiss\System\Statistics', 'sc_get_raw' ],
			];
			if ( Option::network_get( 'info' ) ) {
				$perfops['insights'][] = [
					'name'          => esc_html__( 'Apache Configuration', 'htaccess-server-info-server-status' ),
					'description'   => esc_html__( 'Effective configuration files for this Apache server.', 'htaccess-server-info-server-status' ),
					'icon_callback' => [ \Hsiss\Plugin\Core::class, 'get_base64_logo' ],
					'slug'          => 'hsiss-config',
					'page_title'    => esc_html__( 'Apache Configuration', 'htaccess-server-info-server-status' ),
					'menu_title'    => esc_html__( 'Apache Configuration', 'htaccess-server-info-server-status' ),
					'capability'    => 'manage_options',
					'callback'      => [ $this, 'get_conf_page' ],
					'plugin'        => HSISS_SLUG,
					'version'       => HSISS_VERSION,
					'activated'     => true,
				];
				$perfops['insights'][] = [
					'name'          => esc_html__( 'Apache Information', 'htaccess-server-info-server-status' ),
					'description'   => esc_html__( 'Full information pages for this Apache server.', 'htaccess-server-info-server-status' ),
					'icon_callback' => [ \Hsiss\Plugin\Core::class, 'get_base64_logo' ],
					'slug'          => 'hsiss-info',
					'page_title'    => esc_html__( 'Apache Information', 'htaccess-server-info-server-status' ),
					'menu_title'    => esc_html__( 'Apache Information', 'htaccess-server-info-server-status' ),
					'capability'    => 'manage_options',
					'callback'      => [ $this, 'get_info_page' ],
					'plugin'        => HSISS_SLUG,
					'version'       => HSISS_VERSION,
					'activated'     => true,
				];
			}
			if ( Option::network_get( 'status' ) ) {
				$perfops['insights'][] = [
					'name'          => esc_html__( 'Apache Status', 'htaccess-server-info-server-status' ),
					'description'   => esc_html__( 'Simple report, displaying live current Apache status for this server.', 'htaccess-server-info-server-status' ),
					'icon_callback' => [ \Hsiss\Plugin\Core::class, 'get_base64_logo' ],
					'slug'          => 'hsiss-status',
					'page_title'    => esc_html__( 'Apache Status', 'htaccess-server-info-server-status' ),
					'menu_title'    => esc_html__( 'Apache Status', 'htaccess-server-info-server-status' ),
					'capability'    => 'manage_options',
					'callback'      => [ $this, 'get_status_page' ],
					'plugin'        => HSISS_SLUG,
					'version'       => HSISS_VERSION,
					'activated'     => true,
				];
			}
		}
		return $perfops;
	}

	/**
	 * Dispatch the items in the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function finalize_admin_menus() {
		Menus::finalize();
	}

	/**
	 * Removes unneeded items from the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function normalize_admin_menus() {
		Menus::normalize();
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfopsone_admin_menus', [ $this, 'init_perfopsone_admin_menus' ] );
		Menus::initialize();
		AdminBar::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
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
		}
		return $links;
	}

	/**
	 * Get the content of the status page.
	 *
	 * @since 2.3.0
	 */
	public function get_status_page() {
		$insights = new StatusInsights();
		include HSISS_ADMIN_DIR . 'partials/htaccess-server-info-server-status-admin-insights-status.php';
	}

	/**
	 * Get the content of the info page.
	 *
	 * @since 2.3.0
	 */
	public function get_info_page() {
		$insights = new InfoInsights();
		include HSISS_ADMIN_DIR . 'partials/htaccess-server-info-server-status-admin-insights-info.php';
	}

	/**
	 * Get the content of the info page.
	 *
	 * @since 2.3.0
	 */
	public function get_conf_page() {
		$insights = new InfoInsights( 'config' );
		include HSISS_ADMIN_DIR . 'partials/htaccess-server-info-server-status-admin-insights-info.php';
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
		$nonce = filter_input( INPUT_GET, 'nonce' );
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
						case 'install-decalog':
							if ( class_exists( 'PerfOpsOne\Installer' ) && $nonce && wp_verify_nonce( $nonce, $action ) ) {
								$result = \PerfOpsOne\Installer::do( 'decalog', true );
								if ( '' === $result ) {
									add_settings_error( 'hsiss_no_error', '', esc_html__( 'Plugin successfully installed and activated with default settings.', 'htaccess-server-info-server-status' ), 'info' );
								} else {
									add_settings_error( 'hsiss_install_error', '', sprintf( esc_html__( 'Unable to install or activate the plugin. Error message: %s.', 'htaccess-server-info-server-status' ), $result ), 'error' );
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
				Option::network_set( 'display_nag', array_key_exists( 'hsiss_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_plugin_options_nag' ) : false );
				Option::network_set( 'status', array_key_exists( 'hsiss_plugin_features_status', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_plugin_features_status' ) : false );
				Option::network_set( 'info', array_key_exists( 'hsiss_plugin_features_info', $_POST ) ? (bool) filter_input( INPUT_POST, 'hsiss_plugin_features_info' ) : false );
				flush_rewrite_rules();
				$message = esc_html__( 'Plugin settings have been saved.', 'htaccess-server-info-server-status' );
				$code    = 0;
				add_settings_error( 'hsiss_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( HSISS_SLUG )->info( 'Plugin settings updated.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'htaccess-server-info-server-status' );
				$code    = 2;
				add_settings_error( 'hsiss_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( HSISS_SLUG )->warning( 'Plugin settings not updated.', [ 'code' => $code ] );
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
				\DecaLog\Engine::eventsLogger( HSISS_SLUG )->info( 'Plugin settings reset to defaults.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'htaccess-server-info-server-status' );
				$code    = 2;
				add_settings_error( 'hsiss_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( HSISS_SLUG )->warning( 'Plugin settings not reset to defaults.', [ 'code' => $code ] );
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
		if ( \DecaLog\Engine::isDecalogActivated() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'htaccess-server-info-server-status' ), '<em>' . \DecaLog\Engine::getVersionString() .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in Apache Status & Info, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'htaccess-server-info-server-status' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
			if ( class_exists( 'PerfOpsOne\Installer' ) && ! Environment::is_wordpress_multisite() ) {
				$help .= '<br/><a href="' . wp_nonce_url( admin_url( 'admin.php?page=hsiss-settings&tab=misc&action=install-decalog' ), 'install-decalog', 'nonce' ) . '" class="poo-button-install"><img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'download-cloud', 'none', '#FFFFFF', 3 ) . '" />&nbsp;&nbsp;' . esc_html__('Install It Now', 'htaccess-server-info-server-status' ) . '</a>';
			}
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
				'full_width'  => false,
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
				'full_width'  => false,
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
			'hsiss_plugin_features_status',
			__( 'Server status', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_plugin_features_section',
			'hsiss_plugin_features_section',
			[
				'text'        => sprintf( esc_html__( 'Activate .htaccess rule for %s', 'htaccess-server-info-server-status' ), 'mod_status' ),
				'id'          => 'hsiss_plugin_features_status',
				'checked'     => Option::network_get( 'status' ),
				'description' => sprintf( esc_html__( 'If checked, Apache server status will be served via the url %s.', 'htaccess-server-info-server-status' ), site_url( 'server-status') ) . '<br/>' . esc_html__( 'Note: this only sets up your .htaccess file. For this to work, the module must be activated in your Apache configuration.', 'htaccess-server-info-server-status' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_plugin_features_section', 'hsiss_plugin_features_status' );
		add_settings_field(
			'hsiss_plugin_features_info',
			__( 'Server info', 'htaccess-server-info-server-status' ),
			[ $form, 'echo_field_checkbox' ],
			'hsiss_plugin_features_section',
			'hsiss_plugin_features_section',
			[
				'text'        => sprintf( esc_html__( 'Activate .htaccess rule for %s', 'htaccess-server-info-server-status' ), 'mod_info' ),
				'id'          => 'hsiss_plugin_features_info',
				'checked'     => Option::network_get( 'info' ),
				'description' => sprintf( esc_html__( 'If checked, Apache server info will be served via the url %s.', 'htaccess-server-info-server-status' ), site_url( 'server-info') ) . '<br/>' . esc_html__( 'Note: this only sets up your .htaccess file. For this to work, the module must be activated in your Apache configuration.', 'htaccess-server-info-server-status' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'hsiss_plugin_features_section', 'hsiss_plugin_features_info' );
	}

}
