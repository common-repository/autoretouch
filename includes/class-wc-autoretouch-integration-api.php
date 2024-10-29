<?php

class ARExecutionStatus {
	public static $CREATED = 'CREATED';
	public static $ACTIVE = 'ACTIVE';
	public static $COMPLETED = 'COMPLETED';
	public static $PAYMENT_REQUIRED = 'PAYMENT_REQUIRED';
	public static $FAILED = 'FAILED';
}

class ARGetDeviceAuthResponse {
	public $response_code;
	public $device_code;
	public $verification_uri;
	public $user_code;
}

class ARGetAccessTokenResponse {
	public $response_code;
	public $success;
	public $access_token;
	public $refresh_token;
	public $token_expiry;
}


class Wc_Autoretouch_Integration_API {

	private static $instance = null;

	private $client_id;
	private $audience;
	private $auth_url;
	private $api_url;
	private $jwt_namespace;
	private $app_url;

	private $selected_organization_id;

	/**
	 * @return mixed
	 */
	public function get_selected_organization_id() {
		return $this->selected_organization_id;
	}

	private $access_token;
	private $refresh_token;
	private $token_expiry;
	private $is_connected = false;

	/**
	 * Wc_Autoretouch_Integration_API constructor.
	 */
	private function __construct() {
	}

	/**
	 * @param $client_id
	 * @param $audience
	 * @param $auth_url
	 * @param $api_url
	 * @param $jwt_namespace
	 * @param $app_url
	 *
	 * @return $this the configured api instance
	 */
	public function configure( $client_id, $audience, $auth_url, $api_url, $jwt_namespace, $app_url ) {
		$this->client_id     = $client_id;
		$this->audience      = $audience;
		$this->auth_url      = $auth_url;
		$this->api_url       = $api_url;
		$this->jwt_namespace = $jwt_namespace;
		$this->app_url       = $app_url;

		$this->access_token             = get_option( "ARI_accessToken" );
		$this->refresh_token            = get_option( "ARI_refreshToken" );
		$this->token_expiry             = get_option( "ARI_tokenExpiry" );
		$this->is_connected             = get_option( "ARI_isConnectedAccount" );
		$this->selected_organization_id = get_option( "ARI_selectedOrganization" );

		return $this;
	}

	/**
	 * @return ARGetDeviceAuthResponse
	 */
	public function get_device_auth_uri() {

		$headers = $this->get_headers( false, false );

		$body = array(
			'client_id' => $this->client_id,
			'audience'  => $this->audience,
			'scope'     => 'offline_access'
		);

		$payload = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $body
		);

		$response      = wp_remote_request( $this->auth_url . '/oauth/device/code', $payload );
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = wp_remote_retrieve_response_code( $response );

		$response                = new ARGetDeviceAuthResponse();
		$response->response_code = $response_code;
		if ( $response_code == 200 ) {
			$response->device_code      = $response_data['device_code'];
			$response->verification_uri = $response_data['verification_uri_complete'];
			$response->user_code        = $response_data['user_code'];
		}

		$response->response_code = $response_code;

		return $response;

	}

	/**
	 * @param $device_code the device code to be used for the access token request
	 *
	 * @return ARGetAccessTokenResponse
	 */
	public function get_access_token( $device_code ) {
		$headers = $this->get_headers( false, false );

		$body = array(
			'client_id'   => $this->client_id,
			'device_code' => $device_code,
			'grant_type'  => 'urn:ietf:params:oauth:grant-type:device_code'
		);

		$payload = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $body
		);

		$response      = wp_remote_request( $this->auth_url . '/oauth/token', $payload );
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = wp_remote_retrieve_response_code( $response );

		$result                = new ARGetAccessTokenResponse();
		$result->response_code = $response_code;

		if ( array_key_exists( 'access_token', $response_data ) ) {
			$result->success       = true;
			$result->access_token  = $response_data['access_token'];
			$result->refresh_token = $response_data['refresh_token'];
			$result->token_expiry  = $response_data['expires_in'] + time();

			$this->is_connected  = true;
			$this->access_token  = $result->access_token;
			$this->refresh_token = $result->refresh_token;
			$this->token_expiry  = $result->token_expiry;

			$jwt_object                     = json_decode( base64_decode( str_replace( '_', '/', str_replace( '-', '+', explode( '.', $result->access_token )[1] ) ) ) );
			$this->selected_organization_id = $jwt_object->{$this->jwt_namespace . 'selectedOrganization'};

			update_option( "ARI_isConnectedAccount", true );
			update_option( "ARI_accessToken", $this->access_token );
			update_option( "ARI_refreshToken", $this->refresh_token );
			update_option( "ARI_tokenExpiry", $this->token_expiry );
			update_option( "ARI_selectedOrganization", $this->selected_organization_id );

		} else {
			$result->success    = false;
			$this->is_connected = false;
		}

		return $result;
	}

	/**
	 * @return bool true, if new access token could be obtained by using the refresh token
	 */
	private function get_access_token_by_refresh_token() {
		$headers = $this->get_headers( false, false );

		$body = array(
			'client_id'     => $this->client_id,
			'refresh_token' => $this->refresh_token,
			'grant_type'    => 'refresh_token'
		);

		$payload = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $body
		);

		$response      = wp_remote_request( $this->auth_url . '/oauth/token', $payload );
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == 200 && array_key_exists( "access_token", $response_data ) ) {
			$this->access_token = $response_data['access_token'];
			$this->token_expiry = $response_data['expires_in'] + time();

			update_option( "ARI_isConnectedAccount", true );
			update_option( "ARI_accessToken", $this->access_token );
			update_option( "ARI_tokenExpiry", $this->token_expiry );

			return true;
		}

		return false;
	}

	/**
	 *
	 * @return false|mixed list of autoretouch orgs for connected account or false, if not available
	 */
	public function get_organizations() {
		if ( ! $this->validate_connection() ) {
			return false;
		}

		$headers = $this->get_headers();

		$payload = array(
			'method'  => 'GET',
			'headers' => $headers
		);

		$response      = wp_remote_request( $this->api_url . '/organization', $payload );
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code != 200 ) {
			return false;
		} else {
			return $response_data;
		}

	}

	/**
	 *
	 * @return false|mixed balance of selected autoretouch org
	 */
	public function get_balance() {
		if ( ! $this->validate_connection() ) {
			return false;
		}

		$headers = $this->get_headers( true, 'text' );

		$payload = array(
			'method'  => 'GET',
			'headers' => $headers
		);

		$response      = wp_remote_request( $this->api_url . '/organization/balance', $payload );
		$response_data = wp_remote_retrieve_body( $response );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code != 200 ) {
			return false;
		} else {
			return $response_data;
		}
	}


	/**
	 *
	 * @return false|mixed workflows for selected organization
	 */
	public function get_workflows() {
		if ( ! $this->validate_connection() ) {
			return false;
		}

		$headers = $this->get_headers();

		$payload = array(
			'method'  => 'GET',
			'headers' => $headers
		);

		$response      = wp_remote_request( $this->api_url . '/workflow', $payload );
		$response_data = json_decode( wp_remote_retrieve_body( $response ) );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code != 200 ) {
			return false;
		} else {
			return $response_data;
		}
	}

	/**
	 * @param bool $authenticated if true, add the bearer token to the header set
	 * @param bool $content_type
	 *
	 * @return string[] the http header set
	 */
	private function get_headers( $authenticated = true, $content_type = 'json' ) {
		$headers = array(
			'User-Agent' => 'ar-wordpress-integration:' . WC_AUTORETOUCH_INTEGRATION_VERSION
		);
		if ( $content_type == 'json' ) {
			$headers['Content-Type'] = 'application/json';
		} else if ( $content_type == 'text' ) {
			$headers['Content-Type'] = 'text/plain';
		} else {
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		}
		if ( $authenticated == true ) {
			$headers['Authorization'] = 'Bearer ' . $this->access_token;
		}

		return $headers;
	}

	/**
	 * validate the current api connection state and try to renew if possible
	 *
	 * @return bool is this api connection still valid?
	 */
	private function validate_connection() {
		if ( ! $this->is_connected ) {
			return false;
		}
		if ( $this->token_expiry < time() ) {
			return $this->get_access_token_by_refresh_token();
		}

		return true;
	}

	/**
	 * disconnect the currently connected autoretouch account
	 */
	public function disconnect_account() {
		update_option( "ARI_isConnectedAccount", false );
		update_option( "ARI_accessToken", "" );
		update_option( "ARI_refreshToken", "" );
		update_option( "ARI_tokenExpiry", 0 );
		update_option( "ARI_selectedOrganization", 0 );
	}

	/**
	 * @param $file_path
	 * @param $post_id
	 * @param $workflow_id
	 * @param $workflow_version
	 * @param $organization_id
	 * @param $mime_type
	 *
	 * @return false|string
	 */
	public function submit_image_to_autoretouch( $file_path, $post_id, $workflow_id, $workflow_version, $organization_id, $mime_type ) {
		if ( ! $this->validate_connection() ) {
			return false;
		}

		$headers  = $this->get_headers();
		$boundary = wp_generate_password( 24, false, false );

		$headers['Content-Type'] = "multipart/form-data; boundary=$boundary";

		$body = '';
		$body .= '--' . $boundary . "\r\n";
		$body .= 'Content-Disposition: form-data; name="file"; filename="' . basename( $file_path ) . "\"\r\n";
		$body .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
		$body .= file_get_contents( $file_path ) . "\r\n";
		$body .= '--' . $boundary . "--\r\n";

		$payload = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $body
		);

		$url = $this->api_url . '/workflow/execution/create?workflow=' . $workflow_id . '&version=' . $workflow_version . '&organization=' . $organization_id;

		$response      = wp_remote_request( $url, $payload );
		$execution_id  = wp_remote_retrieve_body( $response );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == 201 ) {
			return $execution_id;
		} else {
			return false;
		}
	}


	public function get_execution_status( $execution_id ) {

		if ( ! $this->validate_connection() ) {
			return false;
		}

		$headers = $this->get_headers();

		$payload = array(
			'method'  => 'GET',
			'headers' => $headers
		);

		$response = wp_remote_request( $this->api_url . '/workflow/execution/' . $execution_id, $payload );

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code != 200 ) {
			return false;
		} else {
			return $response_data;
		}
	}

	public function download_result_image( $url ) {
		if ( ! $this->validate_connection() ) {
			return false;
		}

		$headers = $this->get_headers();

		$payload = array(
			'method'  => 'GET',
			'headers' => $headers
		);

		$response = wp_remote_request( $url, $payload );

		$response_data         = wp_remote_retrieve_body( $response );
		$response_code         = wp_remote_retrieve_response_code( $response );
		$response_content_type = wp_remote_retrieve_header( $response, "content-type" );

		if ( $response_code != 200 ) {
			return false;
		} else {
			return array( "body" => $response_data, "content-type" => $response_content_type );
		}
	}

	public function get_app_url() {
		return $this->app_url;
	}

	public function get_api_url() {
		return $this->api_url;
	}

	public function is_connected() {
		return $this->is_connected;
	}


	/**
	 * @return singleton instance of the autoretouch api client
	 */
	public static function get_instance() {

		if ( self::$instance == null ) {
			self::$instance = new Wc_Autoretouch_Integration_API();
		}

		return self::$instance;
	}


}
