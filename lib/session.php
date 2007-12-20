<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
function getMicrotimeAsFloat() {
	list($usec, $sec) = explode(" ", microtime());
	return ($usec + $sec);
}
$sessionMicrotime = getMicrotimeAsFloat();

function getRemoteAddress() {
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
	else return $_SERVER['REMOTE_ADDR'];
}

function openSession($savePath, $sessionName) {
	return true;
}

function closeSession() {
	return true;
}

function readSession($id) {
	global $database, $service;
	if ($result = sessionQuery("SELECT data FROM {$database['prefix']}Sessions 
		WHERE id = '$id' AND address = '".getRemoveAddress()."' AND updated >= (UNIX_TIMESTAMP() - {$service['timeout']})")) {
		return $result;
	}
	return '';
}

function writeSession($id, $data) {
	global $database;
	global $sessionMicrotime;
	if (strlen($id) < 32)
		return false;
	$userid = Acl::getIdentity('textcube');
	if( empty($userid) ) $userid = 'null';
	$data = POD::escapeString($data);
	$server = POD::escapeString($_SERVER['HTTP_HOST']);
	$request = POD::escapeString($_SERVER['REQUEST_URI']);
	$referer = isset($_SERVER['HTTP_REFERER']) ? POD::escapeString($_SERVER['HTTP_REFERER']) : '';
	$timer = getMicrotimeAsFloat() - $sessionMicrotime;
	$result = POD::queryCount("UPDATE {$database['prefix']}Sessions 
			SET userid = $userid, data = '$data', server = '$server', request = '$request', referer = '$referer', timer = $timer, updated = UNIX_TIMESTAMP() 
			WHERE id = '$id' AND address = '".getRemoveAddress()."'");
	if ($result && $result == 1)
		return true;
	return false;
}

function destroySession($id, $setCookie = false) {
	global $database;
	@POD::query("DELETE FROM {$database['prefix']}Sessions 
		WHERE id = '$id' AND address = '".getRemoveAddress()."'");
	gcSession();
}

function gcSession($maxLifeTime = false) {
	global $database, $service;
	@POD::query("DELETE FROM {$database['prefix']}Sessions 
		WHERE updated < (UNIX_TIMESTAMP() - {$service['timeout']})");
	$result = @sessionQueryAll("SELECT DISTINCT v.id, v.address 
		FROM {$database['prefix']}SessionVisits v 
		LEFT JOIN {$database['prefix']}Sessions s ON v.id = s.id AND v.address = s.address 
		WHERE s.id IS NULL AND s.address IS NULL");
	if ($result) {
		$gc = array();
		foreach ($result as $g)
			array_push($gc, $g);
		foreach ($gc as $g)
			@POD::query("DELETE FROM {$database['prefix']}SessionVisits WHERE id = '{$g[0]}' AND address = '{$g[1]}'");
	}
	return true;
}

function getAnonymousSession() {
	global $database;
	$result = sessionQuery("SELECT id FROM {$database['prefix']}Sessions WHERE address = '".getRemoveAddress()."' AND userid IS NULL AND preexistence IS NULL");
	if ($result)
		return $result;
	return false;
}

function newAnonymousSession() {
	global $database;
	for ($i = 0; $i < 100; $i++) {
		if (($id = getAnonymousSession()) !== false)
			return $id;
		$id = dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF));
		$result = POD::queryCount("INSERT INTO {$database['prefix']}Sessions(id, address, created, updated) VALUES('$id', '".getRemoveAddress()."', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
		if ($result > 0)
			return $id;
	}
	return false;
}

function setSessionAnonymous($currentId) {
	$id = getAnonymousSession();
	if ($id !== false) {
		if ($id != $currentId)
			session_id($id);
		return true;
	}
	$id = newAnonymousSession();
	if ($id !== false) {
		session_id($id);
		return true;
	}
	return false;
}

function newSession() {
	global $database;
	for ($i = 0; ($i < 100) && !setSessionAnonymous(); $i++) {
		$id = dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF));
		$result = POD::queryCount("INSERT INTO {$database['prefix']}Sessions(id, address, created, updated) SELECT DISTINCT '$id', '".getRemoveAddress()."', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
		if ($result && $result > 0) {
			session_id($id);
			return true;
		}
	}
	return false;
}

function isSessionAuthorized($id) {
	global $database;
	$result = POD::queryCell("SELECT id 
		FROM {$database['prefix']}Sessions 
		WHERE id = '$id' 
			AND address = '".getRemoveAddress()."' 
			AND (userid IS NOT NULL OR preexistence IS NOT NULL)");
	if ($result)
		return true;
	return false;
}

function setSession() {
	$id = empty($_COOKIE[session_name()]) ? '' : $_COOKIE[session_name()];
	if ((strlen($id) < 32) || !isSessionAuthorized($id))
		setSessionAnonymous($id);
}

// Teamblog : insert userid to variable admin when member logins.
function authorizeSession($blogid, $userid) {
	global $database, $service;
	$session_cookie_path = "/";
	if( !empty($service['session_cookie_path']) ) {
		$session_cookie_path = $service['session_cookie_path'];
	}
	if (!is_numeric($userid))
		return false;
	$_SESSION['userid'] = $userid;
	if (isSessionAuthorized(session_id()))
		return true;
	for ($i = 0; $i < 100; $i++) {
		$id = dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF));
		$result = POD::execute("INSERT INTO {$database['prefix']}Sessions
			(id, address, userid, created, updated) 
			VALUES('$id', '".getRemoveAddress()."', $userid, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
		if ($result) {
			@session_id($id);
			//$service['domain'] = $service['domain'].':8888';
			setcookie('TSSESSION', $id, 0, $session_cookie_path, $service['domain']);
			return true;
		}
	}
	return false;
}

function sessionQuery($sql) {
	global $database, $sessionDBRepair;
	$result = POD::queryCell($sql);
	if($result === false) {
		if (!isset($sessionDBRepair)) {		
			POD::query("REPAIR TABLE {$database['prefix']}Sessions");
			$result = POD::queryCell($sql);
			$sessionDBRepair = true;
		}
	}
	return $result;
}

function sessionQueryAll($sql) {
	global $database, $sessionDBRepair;
	$result = POD::queryAll($sql);
	if($result === false) {
		if (!isset($sessionDBRepair)) {		
			POD::query("REPAIR TABLE {$database['prefix']}Sessions");
			$result = POD::queryAll($sql);
			$sessionDBRepair = true;
		}
	}
	return $result;
}
session_name('TSSESSION');
setSession();
session_set_save_handler('openSession', 'closeSession', 'readSession', 'writeSession', 'destroySession', 'gcSession');
session_cache_expire(1);
session_set_cookie_params(0, '/', $service['domain']);
if (session_start() !== true) {
	header('HTTP/1.1 503 Service Unavailable');
}
?>
