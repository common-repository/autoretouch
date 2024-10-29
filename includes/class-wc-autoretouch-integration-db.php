<?php

class Wc_Autoretouch_Integration_DB {

	private static $instance = null;

	private $wpdb = null;
	private $table_suffix = "";

	/**
	 * @return singleton instance of the autoretouch db connector
	 */
	public static function get_instance() {

		if ( self::$instance == null ) {
			self::$instance = new Wc_Autoretouch_Integration_DB();
		}

		return self::$instance;
	}

	/**
	 * Wc_Autoretouch_Integration_DB constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @param $table_suffix
	 */
	public function configure( $table_suffix ) {
		$this->table_suffix = $table_suffix;
	}

	/**
	 * setup the database table for autoretouch
	 *
	 * @return bool true, if db table setup was successful
	 */
	public function setup_db() {

		if ( $this->wpdb->get_var( "SHOW TABLES LIKE '" . $this->wpdb->prefix . $this->table_suffix . "'" ) == $this->wpdb->prefix . $this->table_suffix ) {
			return true;
		}

		$table_query = "
					create table " . $this->wpdb->prefix . $this->table_suffix . "
					(
						id bigint unsigned auto_increment,
						post_id bigint unsigned not null,
						result_post_id bigint unsigned default 0,
						input_file_name varchar(255) not null,
						input_guid varchar(255) not null,
						output_file_name varchar(255),
						output_url varchar(500),
						job_status varchar(50) not null,
						execution_id varchar(36) not null,
						workflow_id varchar(36) not null,
						workflow_version varchar(36) not null,
						organization_id varchar(36) not null,
						started_at datetime default current_timestamp() not null,
						was_updated bool default false,
						primary key (id)
					);
				";
		$this->wpdb->query( $table_query );

		if ( $this->wpdb->get_var( "SHOW TABLES LIKE '" . $this->wpdb->prefix . $this->table_suffix . "'" ) == $this->wpdb->prefix . $this->table_suffix ) {
			return true;
		}

		return false;
	}

	/**
	 * resets the database tables by dropping and recreating
	 *
	 * @return bool
	 */
	public function reset_db() {
		if ( $this->wpdb->get_var( "SHOW TABLES LIKE '" . $this->wpdb->prefix . $this->table_suffix . "'" ) == $this->wpdb->prefix . $this->table_suffix ) {
			$table_query = "
					drop table " . $this->wpdb->prefix . $this->table_suffix . ";
				";
			$this->wpdb->query( $table_query );
		}

		return $this->setup_db();
	}

	/**
	 * add a job execution to the local database
	 *
	 * @param $file_name
	 * @param $guid
	 * @param $post_id
	 * @param $execution_id
	 * @param $workflow_id
	 * @param $workflow_version
	 * @param $organization_id
	 * @param $status
	 *
	 * @return bool|int
	 */
	public function add_execution(
		$file_name,
		$guid,
		$post_id,
		$execution_id,
		$workflow_id,
		$workflow_version,
		$organization_id,
		$status
	) {
		$query     = "INSERT INTO " . $this->wpdb->prefix . $this->table_suffix . " (post_id, input_file_name, input_guid, job_status, execution_id, workflow_id, workflow_version, organization_id) VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
		$statement = $this->wpdb->prepare( $query, $post_id, $file_name, $guid, $status, $execution_id, $workflow_id, $workflow_version, $organization_id );

		return $this->wpdb->query( $statement );
	}


	/**
	 * @param $execution_id
	 * @param $status
	 * @param null $output_file_name
	 * @param null $output_url
	 *
	 * @return bool|int
	 */
	public function update_execution(
		$execution_id,
		$status,
		$output_file_name = null,
		$output_url = null
	) {
		if ( $status !== "COMPLETED" ) {
			$query     = "UPDATE " . $this->wpdb->prefix . $this->table_suffix . " SET job_status = '%s' WHERE execution_id = '%s'";
			$statement = $this->wpdb->prepare( $query, $status, $execution_id );

			return $this->wpdb->query( $statement );
		} else {
			$query     = "UPDATE " . $this->wpdb->prefix . $this->table_suffix . " SET job_status = '%s', output_file_name = '%s', output_url = '%s' WHERE execution_id = '%s'";
			$statement = $this->wpdb->prepare( $query, $status, $output_file_name, $output_url, $execution_id );

			return $this->wpdb->query( $statement );
		}
	}

	/**
	 * @param $execution_id
	 * @param $result_post_id
	 *
	 * @return bool|int true, if execution could be updated with result post id
	 */
	public function set_result_post_id(
		$execution_id,
		$result_post_id
	) {
		$query     = "UPDATE " . $this->wpdb->prefix . $this->table_suffix . " SET result_post_id = %d WHERE execution_id = '%s'";
		$statement = $this->wpdb->prepare( $query, $result_post_id, $execution_id );

		return $this->wpdb->query( $statement );
	}

	/**
	 * @return array|object|null complete list of executions submitted
	 */
	public function get_executions() {
		$query = "SELECT * FROM " . $this->wpdb->prefix . $this->table_suffix . " ORDER BY id DESC";

		return $this->wpdb->get_results( $query );
	}


	/**
	 * @return bool true, if any submitted executions are still in progress
	 */
	public function has_active_executions() {
		$query = "SELECT * FROM " . $this->wpdb->prefix . $this->table_suffix . " WHERE job_status IN ('CREATED', 'ACTIVE')";

		$rows_affected = $this->wpdb->query( $query );
		if ( ! $rows_affected ) {
			return false;
		}

		return $rows_affected > 0;
	}

	/**
	 * @param $post_id
	 *
	 * @return bool true, if image associated with given post id is in a non-completed stage
	 */
	public function post_is_processing( $post_id ) {
		$query = "SELECT * FROM " . $this->wpdb->prefix . $this->table_suffix . " WHERE job_status IN ('CREATED', 'ACTIVE', 'PAYMENT_REQUIRED', 'FAILED') AND post_id = " . $post_id;

		$rows_affected = $this->wpdb->query( $query );
		if ( ! $rows_affected ) {
			return false;
		}

		return $rows_affected > 0;
	}

	/**
	 * @return array|object|null
	 */
	public function get_active_executions() {
		$query  = "SELECT * FROM " . $this->wpdb->prefix . $this->table_suffix . " WHERE job_status IN ('CREATED', 'ACTIVE', 'PAYMENT_REQUIRED','FAILED')";
		$result = $this->wpdb->get_results( $query );

		return $result;
	}

}
