<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://autoretouch.com
 * @since      1.0.0
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/includes
 * @author     autoRetouch GmbH <integrations@autoretouch.com>
 */
class Wc_Autoretouch_Integration {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wc_Autoretouch_Integration_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WC_AUTORETOUCH_INTEGRATION_VERSION' ) ) {
			$this->version = WC_AUTORETOUCH_INTEGRATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wc-autoretouch-integration';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();


	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wc_Autoretouch_Integration_Loader. Orchestrates the hooks of the plugin.
	 * - Wc_Autoretouch_Integration_i18n. Defines internationalization functionality.
	 * - Wc_Autoretouch_Integration_Admin. Defines all hooks for the admin area.
	 * - Wc_Autoretouch_Integration_Public. Defines all hooks for the public side of the site.
	 * - Wc_Autoretouch_Integration_API. Defines the autoretouch client funtionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-autoretouch-integration-loader.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-autoretouch-integration-i18n.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-autoretouch-integration-admin.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-autoretouch-integration-api.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-autoretouch-integration-db.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-autoretouch-integration-cron.php';

		$this->intialize_autoretouch();


		$this->loader = new Wc_Autoretouch_Integration_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wc_Autoretouch_Integration_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wc_Autoretouch_Integration_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wc_Autoretouch_Integration_Admin( $this->get_plugin_name(), $this->get_version() );

		$ar_cron = Wc_Autoretouch_Integration_Cron::get_instance();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_entry_autoretouch_integration', 100);

        // autoretouch auth ajax
		$this->loader->add_action( 'wp_ajax_ar_get_device_auth', $plugin_admin, 'ar_get_device_auth' );
		$this->loader->add_action( 'wp_ajax_ar_probe_auth_token', $plugin_admin, 'ar_probe_auth_token' );
		$this->loader->add_action( 'wp_ajax_ar_disconnect_account', $plugin_admin, 'ar_disconnect_account' );

		// autoretouch admin
		$this->loader->add_action( 'wp_ajax_ar_reset_database', $plugin_admin, 'ar_reset_database' );

		// autoretouch api ajax
		$this->loader->add_action( 'wp_ajax_ar_get_organizations', $plugin_admin, 'ar_get_organizations' );
		$this->loader->add_action( 'wp_ajax_ar_get_balance', $plugin_admin, 'ar_get_balance' );
		$this->loader->add_action( 'wp_ajax_ar_get_workflows', $plugin_admin, 'ar_get_workflows' );
		$this->loader->add_action( 'wp_ajax_ar_media_submit_to_service', $plugin_admin, 'ar_media_submit_to_service' );

		// custom media form fields
		$this->loader->add_filter('attachment_fields_to_edit', $plugin_admin, 'add_custom_media_form_fields', 10, 2 );

		// wp executions
		$this->loader->add_action( 'wp_ajax_ar_get_executions', $plugin_admin, 'ar_get_executions' );

		// cron
		$this->loader->add_action( "ar_check_for_updates_from_service_hook", $ar_cron, 'ar_check_for_updates_from_service_exec', 10, 1 );

		// bulk actions (table view)
		$this->loader->add_action("bulk_actions-upload", $plugin_admin, 'ar_setup_table_bulk_actions', 10, 1);
		$this->loader->add_action("handle_bulk_actions-upload", $plugin_admin, 'ar_handle_submit_multiple_images', 10, 3);


	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wc_Autoretouch_Integration_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}


	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * startup init of autoretouch api client
	 */
	private function intialize_autoretouch() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'config/ar-integration-configuration.php';

		$config = Wc_Autoretouch_Integration_Configuration::get_instance();
		Wc_Autoretouch_Integration_API::get_instance()->configure(
			$config->client_id,
			$config->audience,
			$config->auth_url,
			$config->api_url,
			$config->jwt_namespace,
			$config->app_url
		);

		Wc_Autoretouch_Integration_DB::get_instance()->configure(
			$config->table_suffix
		);

		Wc_Autoretouch_Integration_Cron::get_instance();
	}


}
