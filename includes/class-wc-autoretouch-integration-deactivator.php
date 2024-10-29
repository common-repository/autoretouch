<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://autoretouch.com
 * @since      1.0.0
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/includes
 * @author     autoRetouch GmbH <integrations@autoretouch.com>
 */
class Wc_Autoretouch_Integration_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		update_option( "ARI_isConnectedAccount", false );
		update_option( "ARI_accessToken", "" );
		update_option( "ARI_refreshToken", "" );
		update_option( "ARI_tokenExpiry", 0 );
		update_option( "ARI_selectedOrganization", 0 );
		update_option( "ARI_isProcessingQueue", false );
		update_option( "ARI_lastSelectedWorkflowId", false );

		if ( wp_next_scheduled( "ar_check_for_updates_from_service_hook" ) ) {
			wp_unschedule_hook("ar_check_for_updates_from_service_hook" );
		}

	}

}
