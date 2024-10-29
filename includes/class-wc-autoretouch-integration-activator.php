<?php

/**
 * Fired during plugin activation
 *
 * @link       https://autoretouch.com
 * @since      1.0.0
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/includes
 * @author     autoRetouch GmbH <integrations@autoretouch.com>
 */
class Wc_Autoretouch_Integration_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( ! get_option( "ARI_isConnectedAccount" ) ) {
			update_option( "ARI_isConnectedAccount", false );
		}
		if ( ! get_option( "ARI_accessToken" ) ) {
			update_option( "ARI_accessToken", "" );
		}
		if ( ! get_option( "ARI_refreshToken" ) ) {
			update_option( "ARI_refreshToken", "" );
		}
		if ( ! get_option( "ARI_tokenExpiry" ) ) {
			update_option( "ARI_tokenExpiry", 0 );
		}
		if ( ! get_option( "ARI_selectedOrganization" ) ) {
			update_option( "ARI_selectedOrganization", 0 );
		}

		if ( ! get_option( "ARI_isProcessingQueue" ) ) {
			update_option( "ARI_isProcessingQueue", false );
		}

		if ( ! get_option( "ARI_lastSelectedWorkflowId" ) ) {
			update_option( "ARI_lastSelectedWorkflowId", false );
		}

		return Wc_Autoretouch_Integration_DB::get_instance()->setup_db();
	}
}
