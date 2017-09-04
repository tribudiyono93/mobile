<?php
	require "vendor/autoload.php";

	$app = new \Slim\App();

	$app->add(function($request, $response, $next) {
		$response->getBody()->write('SEBELUM');
		$response = $next($request, $response);
		$response->getBody()->write('SESUDAH');

		return $response;
	});

	$app->get('/', function($request, $response, $args) {
		$response->getBody()->write(' Azuwir ');

		return $response;
	});	

	$app->run();
?>