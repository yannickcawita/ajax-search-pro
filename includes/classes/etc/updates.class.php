<?php
class asp_updates {

	private static $_instance;

	private $url = "http://update.wp-dreams.com/version/asp.txt";

	// 2 seconds of timeout, no need to hold up the back-end
	private $timeout = 2;

	private $interval = 1800;

	private $option_name = "asp_updates";

	private $data = false;

	private $version = "";

	private $version_string = "";

	private $requires_version = "3.5";

	private $tested_version = "5.5";

	private $downloaded_count = 0;

	private $last_updated = "2020-10-01";

	// -------------------------------------------- Auto Updater Stuff here---------------------------------------------
	public $title = "Ajax Search Pro";

	function __construct() {
		if (
			defined('ASP_BLOCK_EXTERNAL') ||
			( defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL )
		)
			return false;

		// Redundant: Let's make sure, that the version check is not executed during Ajax requests, by any chance
		if (  !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$this->getData();
			$this->processData();
		}
	}

	function getData($force_update = false) {
		$last_checked = get_option($this->option_name . "_lc", time() - $this->interval - 500);

		if ($this->data != "" && $force_update != true) return;

		if (
			((time() - $this->interval) > $last_checked) ||
			$force_update
		) {
			$response = wp_remote_get( $this->url . "?t=" . time(), array( 'timeout' => $this->timeout ) );
			if ( is_wp_error( $response ) ) {
				$this->data = get_option($this->option_name, false);
			} else {
				$this->data = $response['body'];
				update_option($this->option_name, $this->data);
			}
			/**
			 * Any case, success or failure, the last checked timer should be updated. Otherwise if the remote server
			 * is offline, it will block each back-end page load every time for 'timeout' seconds
			 */
			update_option($this->option_name . "_lc", time());
		} else {
			$this->data = get_option($this->option_name, false);
		}
	}

	function processData() {
		if ($this->data === false) return false;

		// Version
		preg_match("/VERSION:(.*?)[\r\n]/s", $this->data, $m);
		$this->version = isset($m[1]) ? (trim($m[1]) + 0) : $this->version;

		// Version string
		preg_match("/VERSION_STRING:(.*?)[\r\n]/s", $this->data, $m);
		$this->version_string = isset($m[1]) ? trim($m[1]) : $this->version_string;

		// Requires version string
		preg_match("/REQUIRES:(.*?)[\r\n]/s", $this->data, $m);
		$this->requires_version = isset($m[1]) ? trim($m[1]) : $this->requires_version;

		// Tested version string
		preg_match("/TESTED:(.*?)[\r\n]/s", $this->data, $m);
		$this->tested_version = isset($m[1]) ? trim($m[1]) : $this->tested_version;

		// Downloaded count
		preg_match("/DOWNLOADED:(.*?)[\r\n]/s", $this->data, $m);
		$this->downloaded_count = isset($m[1]) ? trim($m[1]) : $this->downloaded_count;

		// Last updated date
		preg_match("/LAST_UPDATED:(.*?)$/s", $this->data, $m);
		$this->last_updated = isset($m[1]) ? trim($m[1]) : $this->last_updated;

		return true;
	}

	public function refresh() {
		$this->getData(true );
		$this->processData();
	}

	public function getVersion() {
		return $this->version;
	}

	public function getVersionString() {
		return $this->version_string;
	}

	public function needsUpdate( $refresh = false ) {
		if ( $refresh )
			$this->refresh();

		if ($this->version != "")
			if ($this->version > ASP_CURR_VER)
				return true;

		return false;
	}

	public function getRequiresVersion() {
		return $this->requires_version;
	}

	public function getTestedVersion() {
		return $this->tested_version;
	}

	public function getDownloadedCount() {
		return $this->downloaded_count;
	}

	public function getLastUpdated() {
		return $this->last_updated;
	}

	/**
	 * Get the instane of VC_Manager
	 *
	 * @return self
	 */
	public static function getInstance() {
		if ( ! ( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}