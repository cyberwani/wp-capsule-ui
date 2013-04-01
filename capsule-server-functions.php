<?php 

class Capsule_Server {
	
	public $api_meta_key;
	public $user_api_key;

	protected $api_endpoint;

	function __construct($user_id = null) {
		
		$this->user_id = $user_id === null ? get_current_user_id() : $user_id;
		
		$this->api_endpoint = home_url();

		$this->api_meta_key = capsule_server_api_meta_key();

		$this->user_api_key = get_user_meta($this->user_id, $this->api_meta_key, true);
	}

	public function add_actions() {
		add_action('user_register', array(&$this, 'user_register'));
		add_action('show_user_profile', array(&$this, 'user_profile'));
	}

	public function user_register($user_id) {
		$cap_server = new Capsule_Server($user_id);
		// This sets a new api key and returns it
		$cap_server->user_api_key();
	}

	public function user_profile($user_data) {
		// Add API Key to User's Profile
		$cap_server = new Capsule_Server($user_data->ID);		
		$api_key = $cap_server->user_api_key();

		// Just a request handler
		$api_endpoint = $cap_server->api_endpoint;
?>
<h3><?php _e('Capsule Credentials', 'capsule-server'); ?></h3>
<table class="form-table">
	<tr>
		<th></th>
		<td><span class="description">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Velit incidunt soluta adipisci officia autem sint doloremque odit corporis optio natus nam unde aperiam saepe vel odio ducimus animi quisquam numquam dolor explicabo veritatis quaerat fugiat! </span>&nbsp;<a href="#">Learn more.</a></td>
	</tr>
	<tr id="capsule-endpoint">
		<th><label for="cap-endpoint"><?php _e('Capsule API Endpoint', 'capsule-server'); ?></label></th>
		<td><span id="cap-endpoint"><?php echo $api_endpoint; ?><span/></td>
	</tr>
	<tr id="capsule-api-key">
		<th><label for="cap-api-key"><?php _e('Capsule API Key', 'capsule-server'); ?></label></th>
		<td><span id="cap-api-key"><?php echo esc_attr($api_key); ?></span></td>
	</tr>
	<tr>
		<th></th>
		<td><a href="#" class="button">Reset Capsule API Key</a></td>
	</tr>

</table>
<?php 
	}

	protected function get_api_key() {
		// Generate unique keys on a per blog basis
		if (is_multisite()) {
			global $blog_id;
			$key = AUTH_KEY.$blog_id;
		}
		else {
			$key = AUTH_KEY;
		}

		return sha1($this->user_id.$key);
	}

	protected function set_api_key() {
		update_user_meta($this->user_id, $this->api_meta_key, $this->user_api_key);
	}

	// Gets an api key for a user, generates a new one and sets it if the user doesn't have a key
	protected function user_api_key() {
		if (empty($this->user_api_key)) {
			$this->user_api_key = $this->get_api_key();
			$this->set_api_key();
		}

		return $this->user_api_key;
	}
}

$cap_server = new Capsule_Server();
$cap_server->add_actions();


/** 
 * Generate the user meta key for the api key value.
 * This generates a different key for each blog if it is a multisite install
 *
 * @return string meta key
 **/
function capsule_server_api_meta_key() {
	if (is_multisite()) {
		global $blog_id;
		$api_meta_key = ($blog_id == 1) ? '_capsule_api_key' : '_capsule_api_key_'.$blog_id;
	}
	else {
		$api_meta_key = '_capsule_api_key';
	}
	
	return $api_meta_key;
}

/**
 * Validates a user's existance in the db against an api key.
 * 
 * @param string $api_key The api key to use for the validation
 * @return int|null user ID or null if none can be found. 
 */
function capsule_server_validate_user($api_key) {
	global $wpdb;

	$meta_key = capsule_server_api_meta_key();
	$sql = $wpdb->prepare("
		SELECT `user_id`
		FROM $wpdb->usermeta
		WHERE `meta_key` = %s
		AND `meta_value` = %s", 
		$meta_key,
		$api_key
	);

	return $wpdb->get_var($sql);
}