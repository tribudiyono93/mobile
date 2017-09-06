<?php
	require "vendor/autoload.php";
	include "config.php";
	include "api_validation.php";
	include "crud_data.php";

	$app = new \Slim\App(["settings" => $config]);

	$container = $app->getContainer();

	$container['db'] = function ($c) {
		try {
			$db = $c['settings']['db'];
			$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,);
			$pdo = new PDO("mysql:host=" . $db['servername'] . ";dbname=" . $db['dbname'], $db['username'], $db['password'], $options);

			return $pdo;
		} catch (\Exception $ex) {
			return $ex->getMessage();
		}
	};


	$app->get('/bonus/member', function($request, $response) {

		$response_data = null;
		$status_code = null;

		try {
			
			$request_parameters = $request->getParams();

			if (request_parameter_validation($request_parameters)) {
				
				$client_id = $request->getParam("client_id");
				
				if (client_id_validation($client_id)) {


					if (signature_validation($request_parameters)) {

						if (timestamp_validation($request_parameters['timestamp'])) {
							
							$period = $request->getParam("period");
							$mbr_code = $request->getParam("mbr_code");

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, "http://202.137.7.177:4280/json/bonus/member?period=" . $period . "&mbr_code=" . $mbr_code);
							curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result 
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result  
							//set apiKey
							curl_setopt($ch, CURLOPT_HTTPHEADER, array('apiKey: value_api_key')); 

							// Fetch and return content, save it.
							$raw_data = curl_exec($ch);
							curl_close($ch);

							// If the API is JSON, use json_decode.
							$data = json_decode($raw_data);
							//var_dump($data);

							$response_data['status'] = "200";
							$response_data['message'] = "success";
							$status_code = 200;
							$response_data['result'] = $data;

							//insert data for history

							$url = $request->getUri();
							$sql_query = "INSERT INTO url_histories (`client_id`, `url`, `created_by`) VALUES ('" . $client_id . "','" . $url . "','" . $GLOBALS['config']['db']['username'] . "')";
							insert_data($sql_query);

						} else {
							$response_data['status'] = "204";
							$response_data['message'] = "timestamp expired.";
							$status_code = 203;
						}

					} else {
						$response_data['status'] = "203";
						$response_data['message'] = "signature tidak valid.";
						$status_code = 203;
					}
					
				} else {
					$response_data['status'] = "202";
					$response_data['message'] = "client id tidak ada.";
					$status_code = 202;
				}

			} else {
				$response_data['status'] = "201";
				$response_data['message'] = "request parameter tidak lengkap.";
				$status_code = 201;
			}


		} catch (\Exception $ex) {
			return $response->withJson(array('error' => $ex->getMessage()), 422);
		}

		return $response->withJson($response_data, $status_code);
	});

	$app->run();
?>