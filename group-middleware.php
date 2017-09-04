<?php
	require "vendor/autoload.php";

	$app = new \Slim\App();

	$app->group('/utils', function() use ($app) {

		$app->get('/date', function($request, $response) {
			return $response->write(date('Y-m-d H:i:s'));
		});

		$app->get('/time', function($request, $response) {
			return $response->write(time());
		});
	})->add(function ($request, $response, $next) {
		$response->write('It is now ');
		$response = $next($request, $response);
		$response->write('. Enjoy!');

		return $response;
	});

	$app->run();
?>