<?php
namespace Session;

function close_session($nick) {
	global $db;

	$req = $db->prepare('DELETE FROM sessions WHERE nick = ?');
	if ($req->execute(array($nick)) == 0) {
		return 1;
	}

	return 0;
}

/* Return values:
 * 14: user is not authenticated
 * 6: the token does not match
 * 13: the session expired
 */
function get_session($nick, $token = NULL) {
	global $db;

	$req = $db->prepare('SELECT nick, token, UNIX_TIMESTAMP(expiration_time) as exp_time_UNIX FROM sessions WHERE nick = ?');
	$req->execute(array($nick));
	$session = $req->fetch();

	if (empty($session)) {
		return 14;
	}

	if ($token != NULL && $session['token'] !== $token) {
		return 6;
	}

	if ($session['exp_time_UNIX'] <= time()) {
		close_session($nick);
		return 13;
	}

	// Update the expiration time
	$req = $db->prepare('UPDATE sessions SET expiration_time = DATE_ADD(NOW(), INTERVAL :expiration_time SECOND) WHERE nick = :nick');
	$req->execute(array(
		'expiration_time' => SESSION_EXPIRATION_DELAY,
		'nick' => $nick
	));

	return $session;
}

function generate_token($length) {
	$token = "";
	$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
	$codeAlphabet.= "0123456789";
	$max = strlen($codeAlphabet);

	for ($i=0; $i < $length; $i++) {
		$token .= $codeAlphabet[random_int(0, $max-1)];
	}

	return $token;
}

// This function is not designed to be used outside this namespace.
function session_exists($nick, $server_name) {
	global $db;

	$req = $db->prepare('SELECT nick, token, UNIX_TIMESTAMP(expiration_time) as exp_time_UNIX, server FROM sessions WHERE nick = ?');
	$req->execute(array($nick));
	$session = $req->fetch();

	if (empty($session))
		return 0;

	if ($session['exp_time_UNIX'] <= time()) {
		// Do not close the session so the "session expired" error can be sent
		return 0;
	}

	if ($session['server'] === $server_name) {
		// This assumes that the function is called from create_session.
		close_session($nick);
		return 0;
	}

	return 1;
}

function create_session($nick, $server_name) {
	global $db;

	// Check if session already exists
	if (session_exists($nick, $server_name))
		return 1;

	$token = generate_token(SESSION_TOKEN_LENGTH);

	$req = $db->prepare('INSERT INTO sessions(nick, token, expiration_time, server) VALUES(:nick, :token, DATE_ADD(NOW(), INTERVAL :expiration_time SECOND), :server)');
	$req->execute(array(
		'nick' => $nick,
		'token' => $token,
		'expiration_time' => SESSION_EXPIRATION_DELAY,
		'server' => $server_name
	));

	return $token;
}
