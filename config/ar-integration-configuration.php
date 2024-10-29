<?php

class Wc_Autoretouch_Integration_Configuration {

	private static $instance = null;

	// API configuration
	public $client_id = 'V8EkfbxtBi93cAySTVWAecEum4d6pt4J';
	public $audience = 'https://api.autoretouch.com';
	public $auth_url = 'https://auth.autoretouch.com';
	public $api_url = 'https://api.autoretouch.com';
	public $jwt_namespace = 'https://autoretouch.com/';
	public $app_url = 'https://app.autoretouch.com';

	// DB configuration
	public $table_suffix = "autoretouch_jobs";

	/**
	 * ARIntegrationConfiguration constructor.
	 */
	private function __construct() {
		$this->client_id = $this->override($this->client_id, 'ARI_DEV_CLIENT_ID');
		$this->audience = $this->override($this->audience, 'ARI_DEV_AUDIENCE');
		$this->auth_url = $this->override($this->auth_url, 'ARI_DEV_AUTH_URL');
		$this->api_url = $this->override($this->api_url, 'ARI_DEV_API_URL');
		$this->jwt_namespace = $this->override($this->jwt_namespace, 'ARI_DEV_JWT_NAMESPACE');
		$this->app_url = $this->override($this->app_url, 'ARI_DEV_APP_URL');
		$this->table_suffix = $this->override($this->table_suffix, 'ARI_DEV_TABLE_SUFFIX');
	}

	private function override($value, $definition) {
		if(defined($definition)) {
			return constant($definition);
		} else {
			return $value;
		}
	}

	public static function get_instance() {

		if ( self::$instance == null ) {
			self::$instance = new Wc_Autoretouch_Integration_Configuration();
		}

		return self::$instance;
	}

}
