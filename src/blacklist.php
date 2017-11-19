<?php
namespace Blacklist;

function get_entry_ip($ip) {
	global $db;

	$req = $db->prepare('SELECT nick FROM user_IPs WHERE IP_address = ? AND nick IN (SELECT nick FROM blacklist_entries)');
	$req->execute(array($ip));

	$ret = array();
	while ($entry = $req->fetch()) {
		array_push($ret, get_entry_nick($entry['nick']));
	}

	return $ret;
}

function get_entry_nick($nick) {
	global $db;

	$req = $db->prepare('SELECT ID, nick, date, source_moderator, reason, category, UNIX_TIMESTAMP(expiration_time) as exp_time_UNIX FROM blacklist_entries WHERE nick = ?');
	$req->execute(array($nick));
	$entry = $req->fetch();

	if (!$entry)
		return null;

	if ($entry['exp_time_UNIX'] && $entry['exp_time_UNIX'] <= time()) {
		unblacklist_user($nick);
		return null;
	}
	return $entry;
}

function blacklist_user($nick, $source_moderator, $reason, $category, $time) {
	global $db;

	// Check that the user exists
	$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() == 0)
		return 9;

	// Check that the user is not already blacklisted
	$req = $db->prepare('SELECT * FROM blacklist_entries WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() != 0)
		return 10;

	// Check that the user is not whitelisted
	if (\Whitelist\is_whitelisted($nick))
		return 16;

	// Check that the category is valid
	$req = $db->prepare('SELECT * FROM blacklisting_categories WHERE category = ?');
	$req->execute(array($category));
	if ($req->rowCount() == 0)
		return 11;

	if ($time) {
		$req = $db->prepare('INSERT INTO blacklist_entries(nick, date, source_moderator, reason, category, expiration_time) VALUES(:nick, NOW(), :source_moderator, :reason, :category, DATE_ADD(NOW(), INTERVAL :time SECOND))');
		$req->execute(array(
			'nick' => $nick,
			'source_moderator' => $source_moderator,
			'reason' => $reason,
			'category' => $category,
			'time' => $time
		));
	} else {
		$req = $db->prepare('INSERT INTO blacklist_entries(nick, date, source_moderator, reason, category) VALUES(:nick, NOW(), :source_moderator, :reason, :category)');
		$req->execute(array(
			'nick' => $nick,
			'source_moderator' => $source_moderator,
			'reason' => $reason,
			'category' => $category
		));
	}
}

function unblacklist_user($nick) {
	global $db;

	$req = $db->prepare('DELETE FROM blacklist_entries WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() == 0)
		return 10;
}
