<?php
define('VERSION', '0.0.0');

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

function command_wrapper($request, $action) {
	$name = $request->post('name');
	$token = $request->post('token');
	if (!$name || !$token)
		return '012';
	$session = Session\get_session($name, $token);
	if (is_int($session))
		return sprintf('%03d', $session);

	$ret = $action($name);
	if ($ret)
		return (string) $ret;

	return '000';
}

$app->path('api', function($request) use($app, $db) {
	$app->path('register', function($request) use($app, $db) {
		$app->post(function($request) use($db) {
			$name = $request->post('name');
			$hash = $request->post('hash');
			$email = $request->post('e-mail');
			$is_email_public = (int) $request->post('is_email_public', '1');
			$ip_address = $request->post('ip_address');

			// Check request validity
			if (!$hash || !$name || !$email || !$ip_address)
				return '012';

			// Make sure that the account does not already exists
			$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
			$req->execute(array($name));
			if ($req->rowCount() != 0)
				return '015';

			// Make sure that the IP is not blacklisted
			$blacklist_entry_ip = Blacklist\get_entry_ip($ip_address);
			if (!empty($blacklist_entry_ip))
				return '005' . json_encode($blacklist_entry_ip);

			// Register the user
			$req = $db->prepare('INSERT INTO users(nick, email, is_email_public, password_hash) VALUES(:nick, :email, :is_email_public, :hash)');
			$req->execute(array(
				'nick' => $name,
				'email' => $email,
				'is_email_public' => $is_email_public,
				'hash' => $hash
			));

			$req = $db->prepare('INSERT INTO user_IPs(nick, IP_address) VALUES(?, ?)');
			$req->execute(array($name, $ip_address));

			return '000';
		});
	});
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
				return '002';
			}

			// TODO: check the number of failing authentication attempts

			// TODO: add the IP to the user IPs

			// Create the session
			$token = Session\create_session($user['nick']);
			if ($token === 1) {
				// User is already authenticated
				return '003';
			}

			return '000' . $token;
		});
	});
	$app->path('close_session', function($request) use($app, $db) {
		$app->post(function($request) use($db) {
			return command_wrapper($request, function($name) {
				Session\close_session($name);
			});
		});
	});
	$app->path('ping', function($request) use ($app, $db) {
		$app->post(function($request) use($db) {
			return command_wrapper($request, function($name) {
				// Do nothing more :p
			});
		});
	});
	$app->path('hello', function($request) use ($app) {
		$app->get(function($request) {
			return '000' . json_encode(array(
				'SESSION_EXPIRATION_TIME' => SESSION_EXPIRATION_TIME,
				'VERSION' => VERSION
			));
		});
	});
});

echo $app->run(new Bullet\Request());
