<?php
	
	$secret_key = "secretmobile";
	$data = array(
		"client_id" => "mobile",
		"timestamp" => "2001-03-10 17:16:18",
		"member_id" => "2",
	);
	
	function generate_signature () {
		$padding = "";

		$string_data = "";

		foreach ($GLOBALS['data'] as $key => $value) {
			$string_data .= $key . "=" . $value . "&";
		}

		for ($i = strlen($string_data)%32; $i<32; $i++) {
		    $padding .= '0';
		}

		$signature = md5($GLOBALS['secret_key'] . $padding . $string_data . $GLOBALS['secret_key']);

		echo $signature;     

	}

?>