<?php
require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../src/config.php';

try {
	$db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
} catch (Exception $e) {
	die('Error while getting access to database: ' . $e->getMessage());
}

require __DIR__ . '/../src/blacklist.php';

$app = new Bullet\App();

require __DIR__ . '/../src/session.php';

$app->path('api', function($request) use($app, $db) {
	$app->path('authenticate', function($request) use($app, $db) {
		$app->post(function($request) use($db) {
			$hash = $request->post('hash');
			$name = $request->post('name');
			$ip_address = $request->post('ip_address');

			// Check request validity
			if (!$hash || !$name || !$ip_address) {
				return '012';
			}

			// Make sure that the user is not blacklisted
			$blacklist_entry_nick = Blacklist\get_entry_nick($name);
			if (!empty($blacklist_entry_nick)) {
				return '005' . json_encode($blacklist_entry_nick);
			}
			$blacklist_entry_ip = Blacklist\get_entry_ip($ip_address);
			if (!empty($blacklist_entry_ip)) {
				return '005' . json_encode($blacklist_entry_ip);
			}

			$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
			$req->execute(array($name));

			// Make sure that the user is registered
			if ($req->rowCount() == 0) {
				return '001';
			}

			$user = $req->fetch();

			// Check that the hash matches
			if ($hash != $user['password_hash']) {
				return 002;
			}

			// TODO: check the number of failing authentication attempts

			// Create the session
			$token = Session\create_session($user['nick']);
			if ($token === 1) {
				// User is already authenticated
				return '003';
			}

			return '000' . $token;
		});
	});
});

echo $app->run(new Bullet\Request());
