<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://autoretouch.com
 * @since      1.0.0
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/admin
 * @author     autoRetouch GmbH <integrations@autoretouch.com>
 */
class Wc_Autoretouch_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;


	/**
	 * @var Wc_Autoretouch_Integration_API $ar_api The autoRetouch API client instance
	 */
	private $ar_api;

	/**
	 * @var Wc_Autoretouch_Integration_DB $ar_db the autoRetouch DB client wrapper
	 */
	private $ar_db;

	/**
	 * @var Wc_Autoretouch_Integration_Cron $ar_cron the autoRetouch background processor
	 */
	private $ar_cron;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->ar_api      = Wc_Autoretouch_Integration_API::get_instance();
		$this->ar_db       = Wc_Autoretouch_Integration_DB::get_instance();
		$this->ar_cron     = Wc_Autoretouch_Integration_Cron::get_instance();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name . '-wc-admin', plugin_dir_url( __FILE__ ) . 'css/wc-autoretouch-integration-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-autoretouch-integration-admin.js', array( 'jquery' ), $this->version, false );

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( $_GET['page'] == 'wc-autoretouch-integration-sub-settings' ) {
			wp_enqueue_script( $this->plugin_name . "-settings", plugin_dir_url( __FILE__ ) . 'js/wc-autoretouch-integration-admin-settings.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name . "-settings", 'arConfig', array(
				'isConnected'            => get_option( 'ARI_isConnectedAccount' ),
				'selectedOrganization'   => get_option( 'ARI_selectedOrganization' ),
				'appURL'                 => $this->ar_api->get_app_url()
			) );
		}

		if ( $_GET['page'] == 'wc-autoretouch-integration-sub-history' ) {
			wp_enqueue_script( $this->plugin_name . "-history", plugin_dir_url( __FILE__ ) . 'js/wc-autoretouch-integration-admin-history.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name . "-history", 'arConfig', array(
				'isConnected'            => get_option( 'ARI_isConnectedAccount' ),
				'selectedOrganization'   => get_option( 'ARI_selectedOrganization' ),
				'appURL'                 => $this->ar_api->get_app_url()
			) );
		}

		if ( $_GET['page'] == 'wc-autoretouch-integration-submit-multiple' ) {
			wp_enqueue_script( $this->plugin_name . "-submit-multiple", plugin_dir_url( __FILE__ ) . 'js/wc-autoretouch-integration-admin-submit-multiple.js', array( 'jquery' ), $this->version, false );
		}
	}

	public function render_autoretouch_menu_entry_settings() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wc-autoretouch-integration-admin-display-settings.php';
	}

	public function render_autoretouch_menu_entry_history() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wc-autoretouch-integration-admin-display-history.php';
	}

	public function render_autoretouch_view_submit_multiple() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wc-autoretouch-integration-admin-submit-multiple.php';
	}


	public function add_menu_entry_autoretouch_integration() {
		require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/assets/wc-autoretouch-logo-svg.php' );

		add_menu_page( __( 'autoRetouch', 'wc-autoretouch-integration' ), __( 'autoRetouch', 'wc-autoretouch-integration' ), 'unknown', 'wc-autoretouch-integration-main-menu', array(
			$this,
			'render_autoretouch_menu_entry_home'
		), autorwci_get_logo_svg() );

		add_submenu_page( 'wc-autoretouch-integration-main-menu', 'Settings', 'Settings', 'manage_options', 'wc-autoretouch-integration-sub-settings', array(
			$this,
			'render_autoretouch_menu_entry_settings'
		) );

		add_submenu_page( 'wc-autoretouch-integration-main-menu', 'Job History', 'Job History', 'manage_options', 'wc-autoretouch-integration-sub-history', array(
			$this,
			'render_autoretouch_menu_entry_history'
		) );

		add_submenu_page( null, 'Submit multiple', 'Submit multiple', 'manage_options', 'wc-autoretouch-integration-submit-multiple', array(
			$this,
			'render_autoretouch_view_submit_multiple'
		) );

	}

	/**
	 * get device auth verification url
	 */
	public function ar_get_device_auth() {

		$result = $this->ar_api->get_device_auth_uri();

		if ( $result->response_code != 200 ) {
			wp_die();
		}
		wp_send_json( $result );
	}

	/**
	 * probe for an access token
	 */
	public function ar_probe_auth_token() {
		$result = $this->ar_api->get_access_token( sanitize_text_field( $_POST['device_code'] ) );
		wp_send_json( $result );
	}

	/**
	 * disconnect autoretouch
	 */
	public function ar_disconnect_account() {
		$this->ar_api->disconnect_account();
		wp_die();
	}

	/**
	 * get org list from autoretouch
	 */
	public function ar_get_organizations() {
		$result = $this->ar_api->get_organizations();
		if ( ! $result ) {
			wp_die( '', '', array( 'response' => 500 ) );
		} else {
			wp_send_json( $result );
		}
	}

	/**
	 * get balance from autoretouch
	 */
	public function ar_get_balance() {
		$result = $this->ar_api->get_balance();
		if ( ! $result ) {
			wp_die( '', '', array( 'response' => 500 ) );
		} else {
			wp_send_json( array( 'balance' => $result ) );
		}
	}

	/**
	 * get workflows list from autoretouch
	 */
	public function ar_get_workflows() {
		$result = $this->ar_api->get_workflows();
		if ( ! $result ) {
			wp_die( '', '', array( 'response' => 500 ) );
		} else {
			wp_send_json( $result );
		}
	}

	/**
	 * build the menu extension for the autoretouch integration
	 *
	 * @param $form_fields
	 * @param $post
	 *
	 * @return mixed
	 */
	public function add_custom_media_form_fields( $form_fields, $post ) {

		if ( ! $this->ar_api->is_connected() ) {
			return $form_fields;
		}

		if ( $post->post_mime_type !== 'image/png'
		     && $post->post_mime_type !== 'image/jpeg'
		     && $post->post_mime_type !== 'image/jpg'
		) {
			return $form_fields;
		}

		$workflows = $this->ar_api->get_workflows();
		if ( ! $workflows ) {
			return $form_fields;
		}

		$workflows_options_string = '';
		$last_selected_workflow = get_option( 'ARI_lastSelectedWorkflowId' );

		foreach ( $workflows->entries as $workflow ) {
			$workflows_options_string = $workflows_options_string . '<option value="' . $workflow->id . '::' . $workflow->version . '"'.($last_selected_workflow == $workflow->id?' selected':'').'>' . $workflow->name . '</option>';
		}

		$is_processing = $this->ar_db->post_is_processing( $post->ID );

		$form_class          = $is_processing ? " ar-media-handler-container-content-invisible" : "";
		$is_processing_class = $is_processing ? "" : " ar-media-handler-container-content-invisible";

		$form_fields['ar-submit-autoretouch'] = array(
			'tr' => "<div class='ar-media-handler-container'>
							<div class='ar-media-handler-title-row'>
							<img id='ar-mh-title-icon' src='" . plugin_dir_url( __FILE__ ) . "/assets/menu-icon.svg' />
							<span id='ar-mh-title-label'>auto</span><span>Retouch Integration</span>
							</div>
							<div id='ar-media-handler-container-form' class='ar-media-handler-container-content" . $form_class . "'>
								<div class='ar-media-handler-form-notice-row'>
									<p>To have this image autoRetouched, simply select the workflow you would like to be used and click &quot;Submit&quot;.
									</p>							
								</div>
								<div class='ar-media-handler-form-label-row'>select workflow:</div>
								<div class='ar-media-handler-form-select-row'>
								<select id='ar-media-handler-form-workflow-select' class='ar-media-handler-form-select'>
								" . $workflows_options_string . "
								</select>
								</div>
								<div class='ar-media-handler-form-submit-row'>
								<a id='ar-media-handler-form-workflow-submit-button' class='ar-media-handler-form-submit-button' onclick='aRonMediaSubmissionHandler(" . $post->ID . ")'>submit image</a>
								</div>
							</div>
							<div id='ar-media-handler-container-is-error' class='ar-media-handler-container-content ar-media-handler-container-content-invisible'>
								<div class='ar-media-handler-form-notice-row'>
									<p>There has been an error with the upload.<br />Please try again later.<br /></p>
								</div>
							</div>
							<div id='ar-media-handler-container-is-processing' class='ar-media-handler-container-content" . $is_processing_class . "'>
								<div class='ar-media-handler-form-notice-row'>
									<p>Image has been submitted and is in process.<br />You can follow the process <a href='" . admin_url() . "admin.php?page=wc-autoretouch-integration-sub-history'>here</a>.<br /></p>
								</div>
							</div>
							
							<div class='ar-media-handler-title-row'>
						</div>"
		);

		return $form_fields;
	}


	/**
	 * submit image process to autoretouch
	 */
	public function ar_media_submit_to_service() {
		$workflow_id      = sanitize_text_field( $_POST['workflow_id'] );
		$workflow_version = sanitize_text_field( $_POST['workflow_version'] );
		$post_id          = sanitize_text_field( $_POST['post_id'] );

		$post = get_post( $post_id );

		update_option( "ARI_lastSelectedWorkflowId", $workflow_id );

		$file_path = str_replace(
			wp_get_upload_dir()['baseurl'],
			wp_get_upload_dir()['basedir'],
			$post->guid
		);

		$execution_id = $this->ar_api->submit_image_to_autoretouch(
			$file_path,
			$post_id,
			$workflow_id,
			$workflow_version,
			$this->ar_api->get_selected_organization_id(),
			$post->post_mime_type
		);

		if ( ! $execution_id ) {
			wp_die( '', '', array( 'response' => 500 ) );
		} else {

			$this->ar_db->add_execution(
				basename( $file_path ),
				$post->guid,
				$post_id,
				$execution_id,
				$workflow_id,
				$workflow_version,
				$this->ar_api->get_selected_organization_id(),
				ARExecutionStatus::$CREATED
			);

			$this->ar_cron->check_cron_schedule();

			wp_die( '', '', array( 'response' => 200 ) );
		}
	}

	/**
	 * resets the database
	 */
	public function ar_reset_database() {
		$result = $this->ar_db->reset_db();
		if ( ! $result ) {
			wp_die( '', '', array( 'response' => 500 ) );
		} else {
			wp_die( '', '', array( 'response' => 200 ) );
		}
	}


	/**
	 * get currently active executions
	 */
	public function ar_get_executions() {

		$result = $this->ar_db->get_executions();
		if ( is_array( $result ) ) {
			wp_send_json( $result );
		} else {
			wp_die( '', '', array( 'response' => 500 ) );
		}
	}

	/**
	 * augment upload table with option to bulk process images
	 *
	 * @param $actions
	 */
	public function ar_setup_table_bulk_actions( $actions ) {

		$actions['ar_submit_multiple_images_to_autoretouch'] = "Submit to autoRetouch";

		return $actions;
	}


	/**
	 * upon submission of multiple images, redirect to workflow selection page
	 *
	 * @param $redirect_to
	 * @param $doaction
	 * @param $post_ids
	 *
	 * @return mixed
	 */
	function ar_handle_submit_multiple_images( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction == 'ar_submit_multiple_images_to_autoretouch' ) {
			if ( count( $post_ids ) > 0 ) {
				$redirect_to = admin_url( 'admin.php?page=wc-autoretouch-integration-submit-multiple' );
				$redirect_to = add_query_arg( 'post_ids', implode( ",", $post_ids ), $redirect_to );
			}
		}

		return $redirect_to;
	}

}
