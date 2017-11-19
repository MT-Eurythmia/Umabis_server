<?php
define('VERSION', '0.0.0');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config.php';

try {
	$db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
} catch (Exception $e) {
	die('Error while getting access to database: ' . $e->getMessage());
}

require __DIR__ . '/../src/whitelist.php';
require __DIR__ . '/../src/blacklist.php';

$app = new Bullet\App();

require __DIR__ . '/../src/session.php';

function command_wrapper($request, $action) {
	$name = strtolower($request->get('name', ''));
	$token = $request->get('token');
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

function privileged_command_wrapper($request, $action) {
	global $db;

	return command_wrapper($request, function($name) use ($db, $action) {
		// Check privileges
		if (!Whitelist\is_whitelisted($name))
			return '008';

		$ret = $action($name);
		if ($ret)
			return (string) $ret;
	});
}

$app->path('api', function($request) use($app, $db) {
	$app->path('register', function($request) use($app, $db) {
		$app->post(function($request) use($db) {
			$name = strtolower($request->post('name', ''));
			$hash = $request->post('hash');
			$email = $request->post('e-mail');
			$is_email_public = (int) $request->post('is_email_public', '1');
			$ip_address = $request->post('ip_address');
			$language_main = $request->post('language_main');
			$language_fallback_1 = $request->post('language_fallback_1', NULL);
			$language_fallback_2 = $request->post('language_fallback_2', NULL);

			// Check request validity
			if (!$hash || !$name || !$email || !$ip_address || !$language_main)
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
			$req = $db->prepare('INSERT INTO users(nick, email, is_email_public, language_main, language_fallback_1, language_fallback_2, password_hash) VALUES(:nick, :email, :is_email_public, :language_main, :language_fallback_1, :language_fallback_2, :hash)');
			$req->execute(array(
				'nick' => $name,
				'email' => $email,
				'is_email_public' => $is_email_public,
				'language_main' => $language_main,
				'language_fallback_1' => $language_fallback_1,
				'language_fallback_2' => $language_fallback_2,
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
			$name = strtolower($request->post('name', ''));
			$ip_address = $request->post('ip_address');

			// Check request validity
			if (!$hash || !$name || !$ip_address)
				return '012';

			// Make sure that the user is not blacklisted
			$blacklist_entry_nick = Blacklist\get_entry_nick($name);
			if (!empty($blacklist_entry_nick))
				return '005' . json_encode($blacklist_entry_nick);

			$blacklist_entry_ip = Blacklist\get_entry_ip($ip_address);
			if (!empty($blacklist_entry_ip))
				return '005' . json_encode($blacklist_entry_ip);

			$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
			$req->execute(array($name));

			// Make sure that the user is registered
			if ($req->rowCount() == 0)
				return '001';

			$user = $req->fetch();

			// Check that the hash matches
			if ($hash != $user['password_hash']) {
				return '002';
			}

			// TODO: check the number of failing authentication attempts

			// Add the IP to the user IPs
			$req = $db->prepare('SELECT * FROM user_IPs WHERE nick = ? AND IP_address = ?');
			$req->execute(array($name, $ip_address));
			if ($req->rowCount() == 0) {
				$req = $db->prepare('INSERT INTO user_IPs(nick, IP_address) VALUES(?, ?)');
				$req->execute(array($name, $ip_address));
			}

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
	$app->path('hello', function($request) use ($app, $db) {
		$app->get(function($request) use ($db) {
			$available_categories = array();
			$req = $db->query('SELECT * FROM blacklisting_categories');
			while ($entry = $req->fetch())
				$available_categories[$entry['category']] = $entry['description'];

			return '000' . json_encode(array(
				'SESSION_EXPIRATION_TIME' => SESSION_EXPIRATION_TIME,
				'VERSION' => VERSION,
				'NAME' => NAME,
				'AVAILABLE_BLACKLIST_CATEGORIES' => $available_categories
			));
		});
	});
	$app->path('is_registered', function($request) use ($app, $db) {
		$app->get(function($request) use ($db) {
			$name = strtolower($request->query('name', ''));
			$ip_address = $request->query('ip_address');

			if (!$name || !$ip_address)
				return '012';

			$req = $db->prepare('SELECT nick FROM users WHERE nick = ?');
			$req->execute(array($name));

			if ($req->rowCount() == 0) {
				return '000' . '0'; // Not registered
			} else {
				$req = $db->prepare('SELECT nick FROM user_IPs WHERE nick = ? AND IP_address = ?');
				$req->execute(array($name, $ip_address));

				if ($req->rowCount() == 0)
					return '000' . '1'; // Registered but new IP
				else
					return '000' . '2'; // Registered and known IP
			}
		});
	});
	$app->path('is_blacklisted', function($request) use ($app, $db) {
		$app->get(function($request) use ($db) {
			$name = strtolower($request->query('name', ''));
			$ip_address = $request->query('ip_address');

			if (!$name && !$ip_address)
				return '012';

			if ($name) {
				$blacklist_entry_nick = Blacklist\get_entry_nick($name);
				if (!empty($blacklist_entry_nick))
					return '000' . '1' . json_encode($blacklist_entry_nick);
			}

			if ($ip_address) {
				$blacklist_entry_ip = Blacklist\get_entry_ip($ip_address);
				if (!empty($blacklist_entry_ip))
					return '000' . '2' . json_encode($blacklist_entry_ip);
			}

			return '000' . '0';
		});
	});
	$app->path('ping', function($request) use ($app, $db) {
		$app->post(function($request) use($db) {
			return command_wrapper($request, function($name) {
				// Do nothing more :p
			});
		});
	});
	$app->path('set_pass', function($request) use ($app, $db) {
		$app->post(function($request) use ($db) {
			return command_wrapper($request, function($name) use ($request, $db) {
				$hash = $request->post('hash');
				if (!$hash)
					return '012';

				$req = $db->prepare('UPDATE users SET password_hash = ? WHERE nick = ?');
				$req->execute(array($hash, $name));

				return '000';
			});
		});
	});
	$app->path('set_info', function($request) use ($app, $db) {
		$app->post(function($request) use ($db) {
			return command_wrapper($request, function($name) use ($request, $db) {
				$email = $request->post('e-mail');
				$language_main = $request->post('language_main');
				$language_fallback_1 = $request->post('language_fallback_1');
				$language_fallback_2 = $request->post('language_fallback_2');

				if (!$email && !$language_main && !$language_fallback_1 && !$language_fallback_2)
					return '012';

				if ($email) {
					$req = $db->prepare('UPDATE users SET email = ? WHERE nick = ?');
					$req->execute(array($email, $name));
				}
				if ($language_main) {
					$req = $db->prepare('UPDATE users SET language_main = ? WHERE nick = ?');
					$req->execute(array($language_main, $name));
				}
				if ($language_fallback_1) {
					$req = $db->prepare('UPDATE users SET language_fallback_1 = ? WHERE nick = ?');
					$req->execute(array($language_fallback_1, $name));
				}
				if ($language_fallback_2) {
					$req = $db->prepare('UPDATE users SET language_fallback_2 = ? WHERE nick = ?');
					$req->execute(array($language_fallback_2, $name));
				}
			});
		});
	});
	$app->path('get_user_info', function($request) use ($app, $db) {
		$app->get(function($request) use ($db) {
			return command_wrapper($request, function($name) use ($request, $db) {
				$requested_name = strtolower($request->query('requested_name', ''));
				if (!$requested_name)
					return '012';

				$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
				$req->execute(array($requested_name));

				$user_entry = $req->fetch();
				if (!$user_entry)
					return '009';

				$info_table = array(
					'language_main' => $user_entry['language_main'],
					'language_fallback_1' => $user_entry['language_fallback_1'],
					'language_fallback_2' => $user_entry['language_fallback_2'],
					'is_email_public' => $user_entry['is_email_public']
				);

				$is_whitelisted = Whitelist\is_whitelisted($name);

				if ($is_whitelisted || $user_entry['is_email_public'] == 1)
					$info_table['email'] = $user_entry['email'];

				if ($is_whitelisted) {
					$info_table['IPs'] = array();

					$req = $db->prepare('SELECT IP_address FROM user_IPs WHERE nick = ?');
					$req->execute(array($requested_name));

					while($entry = $req->fetch())
						array_push($info_table['IPs'], $entry['IP_address']);
				}

				return '000' . json_encode($info_table);
			});
		});
	});
	$app->path('blacklist_user', function($request) use ($app, $db) {
		$app->post(function($request) use ($db) {
			return privileged_command_wrapper($request, function($name) use ($request) {
				$blacklisted_name = strtolower($request->post('blacklisted_name', ''));
				$reason = $request->post('reason');
				$category = $request->post('category');
				$time = $request->post('time');

				if (!$blacklisted_name || !$reason || !$category)
					return '012';

				$ret = Blacklist\blacklist_user($blacklisted_name, $name, $reason, $category, $time);
				if ($ret)
					return sprintf('%03d', $ret);
			});
		});
	});
	$app->path('unblacklist_user', function($request) use ($app, $db) {
		$app->post(function($request) use ($db) {
			return privileged_command_wrapper($request, function($name) use ($request) {
				$blacklisted_name = $request->post('blacklisted_name');

				if (!$blacklisted_name)
					return '012';

				$ret = Blacklist\unblacklist_user($blacklisted_name);
				if ($ret)
					return sprintf('%03d', $ret);
			});
		});
	});
	$app->path('whitelist_user', function($request) use ($app, $db) {
		$app->post(function($request) use ($db) {
			return privileged_command_wrapper($request, function($name) use ($request) {
				$whitelisted_name = $request->post('whitelisted_name');

				if (!$whitelisted_name)
					return '012';

				$ret = Whitelist\whitelist_user($whitelisted_name);
				if ($ret)
					return sprintf('%03d', $ret);
			});
		});
	});
	$app->path('unwhitelist_user', function($request) use ($app, $db) {
		$app->post(function($request) use ($db) {
			return privileged_command_wrapper($request, function($name) use ($request) {
				$whitelisted_name = $request->post('whitelisted_name');

				if (!$whitelisted_name)
					return '012';

				$ret = Whitelist\unwhitelist_user($whitelisted_name);
				if ($ret)
					return sprintf('%03d', $ret);
			});
		});
	});
});

echo $app->run(new Bullet\Request());
