<?php
require __DIR__ . '/../vendor/autoload.php';

try {
	$db = new PDO('mysql:host=localhost;dbname=umabis;charset=utf8', 'umabis', 'iuR03JPGFaLsayL5');
} catch (Exception $e) {
	die('Error while getting access to database: ' . $e->getMessage());
}

$app = new Bullet\App();

// This associative array contains the user sessions tokens
$sessions = array();

$app->path('api', function($request) use($app) {
	$app->path('authenticate', function() use($app) {
		$app->post(function($request) {
			//return 'Authentication: ' . $request->post('name') . ' - ' . $request->post('hash');
		});
	});
});

echo $app->run(new Bullet\Request());
