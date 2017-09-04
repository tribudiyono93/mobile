<?php

	$required_parameters = array("client_id", "signature", "timestamp", "member_id");
	$required_data = array("client_id","timestamp", "member_id");
	
	
	function DBConnection() {

		//diambil dari config.php
		$db = $GLOBALS['config']['db'];

		return new PDO("mysql:host=" . $db['servername'] . ";dbname=" . $db['dbname'], $db['username'], $db['password']);
	}

	function request_parameter_validation($request_parameters) {

		$is_exist = TRUE;

		foreach ($GLOBALS['required_parameters'] as $value) {
		    if (!array_key_exists($value, $request_parameters)) {
				$is_exist = FALSE;
			} 
		}

		return $is_exist;

	}

	function client_id_validation($client_id) {

		$is_exist = FALSE;
		try {

			$conn = DBConnection();
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$query = $conn->query("SELECT * FROM clients WHERE client_id = '".$client_id."' AND (end_time IS NULL OR end_time > CURDATE())");
			$result = $query->fetch();

			if (count($result) > 1) {
				$is_exist = TRUE;	
			}

			$conn = null;

		} catch (\PDOException $ex) {
			echo "CLIENT ID VALIDATION. ERROR : " . $ex->getMessage();
		}

		return $is_exist;
	}

	function get_secret_key_by_client_id($client_id) {

		$secret_key = "";

		try {

			$conn = DBConnection();
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$query = $conn->query("SELECT * FROM clients WHERE client_id = '".$client_id."'");
			$result = $query->fetch(PDO::FETCH_ASSOC);

			$secret_key = $result['secret_key'];

			$conn = null;

		} catch (\PDOException $ex) {
			echo "GET SECRET KEY BY CLIENT ID. ERROR : " . $ex->getMessage();
		}

		return $secret_key;
	}

	function insert_data($sql_query) {
		try {
			$conn = DBConnection();
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$conn->exec($sql_query);

			$conn = null;
			
		} catch (\PDOException $ex) {
			echo "SAVE DATA. ERROR : " . $ex->getMessage();
		}
	}

	function signature_validation ($request_parameters) {

		$is_valid = FALSE;

		$padding = "";
		$string_data = "";

		foreach ($GLOBALS['required_data'] as $value) {
			if (array_key_exists($value, $request_parameters)) {
				$string_data .= $value . "=" . $request_parameters[$value] . "&";
			} 
		}

		for ($i = strlen($string_data)%32; $i<32; $i++) {
		    $padding .= '0';
		}

		$secret_key = get_secret_key_by_client_id($request_parameters['client_id']);

		$new_signature = md5($secret_key . $padding . $string_data . $secret_key);

		$old_signature = $request_parameters['signature'];

		if ($old_signature == $new_signature) {
			$is_valid = TRUE;
		}

		return $is_valid;
	}

	function timestamp_validation($timestamp) {
		$is_valid = FALSE;
		
		$minutes = 30;

		$api_timestamp = new DateTime($timestamp);
		
		$add_current_datetime = new DateTime();

		$sub_current_datetime = new DateTime();

		$add_current_datetime->add(new DateInterval('PT' . $minutes . 'M'));
		$sub_current_datetime->sub(new DateInterval('PT' . $minutes . 'M'));

		if ( ($api_timestamp > $sub_current_datetime) && ($api_timestamp < $add_current_datetime) ) {
			$is_valid = TRUE;
		}

		return $is_valid;
	}
?>