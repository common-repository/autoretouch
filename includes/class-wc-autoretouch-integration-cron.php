<?php

class Wc_Autoretouch_Integration_Cron {

	private static $instance = null;

	private $ar_db;
	private $ar_api;

	/**
	 * @return singleton instance of the autoretouch db connector
	 */
	public static function get_instance() {

		if ( self::$instance == null ) {
			self::$instance = new Wc_Autoretouch_Integration_Cron();
		}

		return self::$instance;
	}

	/**
	 * Wc_Autoretouch_Integration_DB constructor.
	 */
	private function __construct() {
		$this->ar_db  = Wc_Autoretouch_Integration_DB::get_instance();
		$this->ar_api = Wc_Autoretouch_Integration_API::get_instance();
	}

	/**
	 * check, if a background job needs to be scheduled
	 */
	public function check_cron_schedule() {
		if ( $this->ar_db->has_active_executions() ) {
			if ( ! wp_next_scheduled( "ar_check_for_updates_from_service_hook" ) ) {
				wp_schedule_single_event( time() + 5, "ar_check_for_updates_from_service_hook", array( uniqid() ) );
			}
		}
	}

	/**
	 * @param $seed random seed to prevent 10 minute event lockout, not used otherwise
	 */
	public function ar_check_for_updates_from_service_exec( $seed ) {
		$executions = $this->ar_db->get_active_executions();
		if ( count( $executions ) > 0 ) {
			$this->check_executions_to_update( $executions );
		}
	}

	public function check_executions_to_update( &$executions ) {
		$execution = array_pop( $executions );
		$this->handle_single_execution_update( $execution );
		if ( count( $executions ) > 0 ) {
			$this->check_executions_to_update( $executions );
		} else {
			$this->check_cron_schedule();
		}
	}

	public function handle_single_execution_update( &$execution ) {
		$result = $this->ar_api->get_execution_status( $execution->execution_id );

		if ( $result['status'] == "COMPLETED" ) {
			$was_updated = $this->transfer_result_to_wordpress( $execution, $result );

			if ( $was_updated ) {
				$this->ar_db->update_execution( $execution->execution_id, $result['status'], $result['resultFileName'], $this->ar_api->get_app_url() . $result['resultPath'] );

				return;
			} else {
				$this->ar_db->update_execution( $execution->execution_id, "WORDPRESS_ERROR", $result['resultFileName'], $this->ar_api->get_app_url() . $result['resultPath'] );

				return;
			}
		}

		$this->ar_db->update_execution( $execution->execution_id, $result['status'], $result['resultFileName'], $this->ar_api->get_app_url() . $result['resultPath'] );
	}

	private function transfer_result_to_wordpress( $execution, $result ) {

		$original_path = str_replace(
			wp_get_upload_dir()['baseurl'],
			wp_get_upload_dir()['basedir'],
			$execution->input_guid
		);

		$downloaded_image = $this->ar_api->download_result_image( $this->ar_api->get_api_url() . $result['resultPath'] );

		if ( ! $downloaded_image ) {
			return false;
		}

		$target_folder                = dirname( $original_path );
		$target_path_parts            = pathinfo( $result['resultFileName'] );
		$cache_busted_target_filename = $target_path_parts['filename'] . "." . uniqid() . "." . $target_path_parts['extension'];
		$target_path                  = $target_folder . "/" . $cache_busted_target_filename;

		$fp = fopen( $target_path, "wb" );

		if ( ! $fp ) {
			return false;
		}

		if ( fwrite( $fp, $downloaded_image['body'] ) === false ) {
			return false;
		};

		if ( fclose( $fp ) === false ) {
			return false;
		};

		$post = get_post( $execution->post_id );

		$upload_path       = str_replace(
			wp_get_upload_dir()['baseurl'],
			"",
			$execution->input_guid
		);
		$upload_path_parts = pathinfo( $upload_path );
		$new_guid          = wp_get_upload_dir()['baseurl'] . $upload_path_parts['dirname'] . "/" . $cache_busted_target_filename;

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$description = "This image was autoRetouched at " . date(DATE_RSS, time()) . ".\n\n" . $post->post_content;

		$new_attachment_data = array(
			"file"           => $target_path,
			"post_title"     => $post->post_title,
			"post_parent"    => $post->post_parent,
			"post_content"   => $description,
			"post_author"    => $post->post_author,
			"post_mime_type" => $downloaded_image['content-type'],
			"guid"           => $new_guid
		);

		$new_attachment_id = wp_insert_attachment( $new_attachment_data );
		$featured_image_id = get_post_thumbnail_id( $post->post_parent );

		$metadata = wp_generate_attachment_metadata( $new_attachment_id, $target_path );
		wp_update_attachment_metadata( $new_attachment_id, $metadata );

		$this->ar_db->set_result_post_id( $execution->execution_id, $new_attachment_id );

		if ( $post->post_parent != 0 && $execution->post_id == $featured_image_id ) {
			set_post_thumbnail( $post->post_parent, $new_attachment_id );
		}

		return true;
	}
}