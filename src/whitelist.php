<?php
namespace Whitelist;

function get_entry($nick) {
	global $db;

	$req = $db->prepare('SELECT * FROM global_moderators WHERE nick = ?');
	$req->execute(array($nick));
	return $req->fetchAll();
}

function is_whitelisted($nick) {
	global $db;

	$req = $db->prepare('SELECT * FROM global_moderators WHERE nick = ?');
	$req->execute(array($nick));
	return $req->rowCount() > 0;
}

function whitelist_user($nick) {
	global $db;

	// Check that the user exists
	$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() == 0)
		return 9;

	// Check that the user is not already whitelisted
	$req = $db->prepare('SELECT * FROM global_moderators WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() != 0)
		return 10;

	// Check that the user is not blacklisted
	$blacklist_entry_nick = Blacklist\get_entry_nick($name);
	if (!empty($blacklist_entry_nick))
		return 16;

	$req = $db->prepare('INSERT INTO global_moderators(nick) VALUES(?)');
	$req->execute(array($nick));
}

function unwhitelist_user($nick) {
	global $db;

	// Check that the user exists
	$req = $db->prepare('SELECT * FROM users WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() == 0)
		return 9;

	// Check that the user is whitelisted
	$req = $db->prepare('SELECT * FROM global_moderators WHERE nick = ?');
	$req->execute(array($nick));
	if ($req->rowCount() == 0)
		return 10;

	$req = $db->prepare('DELETE FROM global_moderators where nick = ?');
	$req->execute(array($nick));
}
