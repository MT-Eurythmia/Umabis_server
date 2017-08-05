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
 * 1: user is not authenticated
 * 2: the token does not match
 * 3: the session expired
 */
function get_session($nick, $token = NULL) {
	global $db;

	$req = $db->prepare('SELECT nick, token, UNIX_TIMESTAMP(expiration_time) as exp_time_UNIX FROM sessions WHERE nick = ?');
	$req->execute(array($nick));
	$session = $req->fetch();

	if (empty($session)) {
		return 1;
	}

	if ($token != NULL && $session['token'] !== $token) {
		return 2;
	}

	if ($session['exp_time_UNIX'] <= time()) {
		close_session($nick);
		return 3;
	}

	// Update the expiration time
	$req = $db->prepare('UPDATE sessions SET expiration_time = NOW() + ? WHERE nick = ?');
	$req->execute(array(SESSION_EXPIRATION_TIME, $nick));

	return $session;
}

function session_exists($nick) {
	return !is_int(get_session($nick));
}

function generate_token() {
	$token = "";
	$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
	$codeAlphabet.= "0123456789";
	$max = strlen($codeAlphabet);

	// The token length is 64 characters
	for ($i=0; $i < 64; $i++) {
		$token .= $codeAlphabet[random_int(0, $max-1)];
	}

	return $token;
}

function create_session($nick) {
	global $db;

	if (session_exists($nick)) {
		return 1;
	}

	$session = array(
		'nick' => $nick,
		'token' => generate_token(),
		'expiration_time' => SESSION_EXPIRATION_TIME
	);

	$token = generate_token();

	$req = $db->prepare('INSERT INTO sessions(nick, token, expiration_time) VALUES(:nick, :token, NOW() + :expiration_time)');
	$req->execute(array(
		'nick' => $nick,
		'token' => $token,
		'expiration_time' => SESSION_EXPIRATION_TIME
	));

	return $token;
}
