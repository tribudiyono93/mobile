<?php
	require "vendor/autoload.php";
	include "config.php";
	include "api_validation-mobile.php";
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

	$app->post('/user', function ($request, $response) {
		try {
			$con = $this->db;
			$sql = "INSERT INTO `users`(`username`, `email`, `password`) VALUES (:username, :email, :password)";
			$pre = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

			$values = array (
				':username' => $request->getParam('username'),
				':email' => $request->getParam('email'),
				':password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT),
			);

			$result = $pre->execute($values);
			return $response->withJson(array('status' => 'User Created'),200);
		} catch (\Exception $ex) {
			return $response->withJson(array('error' => $ex->getMessage()), 422);
		}
	});

	$app->get('/user/{id}', function($request, $response) {
		try {
			$id = $request->getAttribute('id');
			$con = $this->db;
			$sql = "SELECT * FROM users WHERE id = :id";
			$pre = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

			$values = array (
				':id' => $id
			);

			$pre->execute($values);
			$result = $pre->fetch();

			if ($result) {
				return $response->withJson(array('status' => 'true', 'result' => $result), 200);
			} else {
				return $response->withJson(array('status' => 'User not found'), 422);
			}

		} catch (\Exception $ex) {
			return $response->withJson(array('error' => $ex->getMessage()), 422);
		}
	});

	$app->get('/users', function($request, $response) {
		try {
			$con = $this->db;
			$sql = "SELECT * FROM users";
			$result = null;

			foreach ($con->query($sql) as $row) {
				$result[] = $row;
			}

			if ($result) {
				return $response->withJson(array($result), 200);
			} else {
				return $response->withJson(array('status' => 'User not found'), 422);
			}
		} catch (\Exception $ex) {
			return $response->withJson(array('error' => $ex->getMessage()), 422);
		}
	});

	$app->get('/testApi', function($request, $response) {

		$response_data = null;
		$status_code = null;

		try {
			
			$request_parameters = $request->getParams();

			if (request_parameter_validation($request_parameters)) {
				
				$client_id = $request->getParam("client_id");
				
				if (client_id_validation($client_id)) {


					if (signature_validation($request_parameters)) {

						if (timestamp_validation($request_parameters['timestamp'])) {

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, "http://localhost/mobile/users");
							curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result 
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result   

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