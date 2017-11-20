<?php
namespace ServerAuth;

function authenticate($name, $password) {
	global $db;

	$req = $db->prepare('SELECT IP, password, token FROM servers WHERE name = ?');
	$req->execute(array($name));
	$server_entry = $req->fetch();

	if (!$server_entry)
		return 17; // name not registered

	// if ($server_entry['token'])
	// 	return 20; // already authenticated

	if ($server_entry['IP'] != $_SERVER['REMOTE_ADDR'])
		return 19; // server IP does not match

	if ($server_entry['password'] !== hash('sha256', $password))
		return 18; // server password does not match

	$token = \Session\generate_token(SERVER_TOKEN_LENGTH);

	$req = $db->prepare('UPDATE servers SET token = :token, expiration_time = DATE_ADD(NOW(), INTERVAL :expiration_delay SECOND) WHERE name = :name');
	$req->execute(array(
		'token' => $token,
		'expiration_delay' => SERVER_EXPIRATION_DELAY,
		'name' => $name
	));

	return $token;
}

function check_auth($name, $token) {
	global $db;

	$req = $db->prepare('SELECT IP, token, UNIX_TIMESTAMP(expiration_time) as exp_time_UNIX FROM servers WHERE name = ?');
	$req->execute(array($name));
	$server_entry = $req->fetch();

	if (!$server_entry || !$server_entry['token'])
		return 21; // server is not authenticated

	if ($server_entry['IP'] != $_SERVER['REMOTE_ADDR'])
		return 19; // server IP does not match

	if ($server_entry['token'] !== $token)
		return 22; // server token does not match

	if ($server_entry['exp_time_UNIX'] <= time()) {
		close_session($name);
		return 20; // server session expired
	}

	$req = $db->prepare('UPDATE servers SET expiration_time = DATE_ADD(NOW(), INTERVAL :expiration_delay SECOND) WHERE name = :name');
	$req->execute(array(
		'expiration_delay' => SERVER_EXPIRATION_DELAY,
		'name' => $name
	));
}

function close_session($name) {
	global $db;

	$req = $db->prepare('UPDATE servers SET token = NULL, expiration_time = NOW() WHERE name = ?');
	$req->execute(array($name));
}
