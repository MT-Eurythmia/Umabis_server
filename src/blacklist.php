<?php
namespace Blacklist;

function get_entry_ip($ip) {
	global $db;

	$req = $db->prepare('SELECT nick FROM blacklist_entries INTERSECT SELECT nick FROM user_IPs WHERE IP_address = ?');
	$req->execute(array($ip));

	$ret = array();
	while ($entry = $req->fetch()) {
		array_push($ret, get_entry_nick($entry['nick']));
	}

	return $ret;
}

function get_entry_nick($nick) {
	global $db;

	$req = $db->prepare('SELECT * FROM blacklist_entries WHERE nick = ?');
	$req->execute(array($nick));
	return $req->fetchAll();
}
