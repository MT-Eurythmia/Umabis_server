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

function session_exists($nick) {
	return !is_int(get_session($nick));
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

function create_session($nick) {
	global $db;

	if (session_exists($nick)) {
		//return 1;
		close_session($nick);
	}

	$token = generate_token(SESSION_TOKEN_LENGTH);

	$req = $db->prepare('INSERT INTO sessions(nick, token, expiration_time) VALUES(:nick, :token, DATE_ADD(NOW(), INTERVAL :expiration_time SECOND))');
	$req->execute(array(
		'nick' => $nick,
		'token' => $token,
		'expiration_time' => SESSION_EXPIRATION_DELAY
	));

	return $token;
}
