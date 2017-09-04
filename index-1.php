<?php
	require "vendor/autoload.php";

	$app = new \Slim\App();

	$app->get('/', function() {
		echo "<h1>Hello, Slim</h1>";

	});

	$app->get('/show', function() {
		$db = DBConnection();
		$query = $db->query("SELECT * FROM buku");
		$result = $query->fetchAll();
		foreach ($result as $res) {
			echo $res['judul']." | ".$res['pengarang']."<br />";
		}
	});

	function DBConnection() {
		return new PDO('mysql:dbhost=localhost;dbname=db_slim','slim','slim');
	}

	$app->run();
?>