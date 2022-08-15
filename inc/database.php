<?php

// ##########################################
// Database functions
// ##########################################

define('TINYIB_DBPOSTS','posts');
define('TINYIB_DBBANS',	'bans');
define('TINYIB_DBLOCKS','locked_threads');
define('TINYIB_DBPATH', 'tinyib.db');

// ##########################################
// Initialise DB connection
// ##########################################

try {
	$db = new \PDO('sqlite:'.TINYIB_DBPATH);
	validateDatabaseSchema();
} catch (PDOException $e) {
    fancyDie('Could not connect to database: '.  $e->getMessage());
}

// ##########################################
// Schema
// ##########################################

function validateDatabaseSchema() {
	global $db;
	$db->query('
	CREATE TABLE IF NOT EXISTS '.TINYIB_DBPOSTS.' (
		id INTEGER PRIMARY KEY,
		parent INTEGER NOT NULL,
		timestamp TIMESTAMP NOT NULL,
		bumped TIMESTAMP NOT NULL,
		ip TEXT NOT NULL,
		name TEXT NOT NULL,
		tripcode TEXT NOT NULL,
		email TEXT NOT NULL,
		nameblock TEXT NOT NULL,
		subject TEXT NOT NULL,
		message TEXT NOT NULL,
		password TEXT NOT NULL,
		file TEXT NOT NULL,
		file_hex TEXT NOT NULL,
		file_original TEXT NOT NULL,
		file_size INTEGER NOT NULL DEFAULT "0",
		file_size_formatted TEXT NOT NULL,
		image_width INTEGER NOT NULL DEFAULT "0",
		image_height INTEGER NOT NULL DEFAULT "0",
		thumb TEXT NOT NULL,
		thumb_width INTEGER NOT NULL DEFAULT "0",
		thumb_height INTEGER NOT NULL DEFAULT "0"
	)
	');
	$db->query('
	CREATE TABLE IF NOT EXISTS '.TINYIB_DBBANS.' (
		id INTEGER PRIMARY KEY,
		ip TEXT NOT NULL,
		timestamp TIMESTAMP NOT NULL,
		expire TIMESTAMP NOT NULL,
		reason TEXT NOT NULL
	)
	');
	$db->query('
	CREATE TABLE IF NOT EXISTS '.TINYIB_DBLOCKS.' (
		id INTEGER PRIMARY KEY,
		thread INTEGER NOT NULL		
	)
	');
}

// SQLite PDO Helper
function fetchAndExecute($sql, $parameters=array()) {
	$stmt = $GLOBALS['db']->prepare($sql);
	$stmt->execute($parameters);
	$results = $stmt->fetchAll();
	return $results;
}

// ##########################################
// Post Functions
// ##########################################

function uniquePosts() {
	$result = fetchAndExecute(
		'SELECT COUNT(ip) c FROM (SELECT DISTINCT ip FROM '.TINYIB_DBPOSTS.')',
		array()
	);
	return $result[0]['c'];
}

function postByID($id) {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE id=? LIMIT 1',
		array(intval($id))
	);
	if (count($result)) return $result[0];
}

function insertPost($post) {
	$result = fetchAndExecute('
		INSERT INTO '.TINYIB_DBPOSTS.' (
			parent, timestamp, bumped, ip, name, tripcode, email, nameblock,
			subject, message, password, file, file_hex, file_original,
			file_size, file_size_formatted, image_width, image_height,
			thumb, thumb_width, thumb_height
		) VALUES (
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
		)',
		array(
			$post['parent'], time(), time(), $_SERVER['REMOTE_ADDR'],
			$post['name'], $post['tripcode'], $post['email'], $post['nameblock'],
			$post['subject'], $post['message'], $post['password'],
			$post['file'], $post['file_hex'], $post['file_original'],
			$post['file_size'], $post['file_size_formatted'],
			$post['image_width'], $post['image_height'], $post['thumb'],
			$post['thumb_width'], $post['thumb_height']
		)
	);
	return $GLOBALS['db']->lastInsertId();
}

function countPosts() {
	$result = fetchAndExecute(
		'SELECT COUNT(*) c FROM '.TINYIB_DBPOSTS.'',
		array()
	);
	return $result[0]['c'];
}

function latestPosts($count) {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBPOSTS.' ORDER BY id DESC LIMIT '.intval($count),
		array()
	);
	return $result;
}

function postsByHex($hex) {	
	$result = fetchAndExecute(
		'SELECT id,parent FROM '.TINYIB_DBPOSTS.' WHERE file_hex=? LIMIT 1',
		array($hex)
	);
	return $result;
}

function deletePostByID($id) {	
	$posts = postsInThreadByID($id);
	foreach ($posts as $post) {
		if ($post['id'] != $id) {
			deletePostImages($post);
			fetchAndExecute('DELETE FROM '.TINYIB_DBPOSTS.' WHERE id = ?', array($post['id']));
		} else {
			$thispost = $post;
		}
	}
	if (isset($thispost)) {
		/*if ($thispost['parent'] == 0) {
			@unlink('res/' . $thispost['id'] . '.html');
		}*/
		deletePostImages($thispost);
		fetchAndExecute('DELETE FROM '.TINYIB_DBPOSTS.' WHERE id = ?', array($thispost['id']));
	}
}

function postsInThreadByID($id) {	
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE id=? OR parent=? ORDER BY id ASC',
		array($id, $id)
	);
	return $result;
}

function latestRepliesInThreadByID($id) {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE parent = ? ORDER BY id DESC LIMIT '.TINYIB_REPLIESTOSHOW,
		array(intval($id))
	);
	return $result;
}

function lastPostByIP() {	
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE ip=? ORDER BY id DESC LIMIT 1',
		array($_SERVER['REMOTE_ADDR'])
	);
	if (count($result)) return $result[0];
}

// ##########################################
// Thread functions
// ##########################################

function threadExistsByID($id) {
	$result = fetchAndExecute(
		'SELECT COUNT(id) c FROM '.TINYIB_DBPOSTS.' WHERE id=? AND parent=? LIMIT 1',
		array(intval($id), 0)
	);
	return $result[0]['c'];
}

function bumpThreadByID($id) {
	fetchAndExecute(
		'UPDATE '.TINYIB_DBPOSTS.' SET bumped = ? WHERE id = ?',
		array( time(), intval($id) )
	);
}

function countThreads() {
	$result = fetchAndExecute(
		'SELECT COUNT(id) c FROM '.TINYIB_DBPOSTS .' WHERE parent = ?',
		array(0)
	);
	return $result[0]['c'];
}

function getThreadRange($count, $offset) {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE parent = ? ORDER BY bumped DESC LIMIT '.intval($offset).','.intval($count),
		array(0)
	);
	return $result;
}

function trimThreads() {
	if (TINYIB_MAXTHREADS > 0) {
		$result = fetchAndExecute(
			'SELECT id FROM '.TINYIB_DBPOSTS.' WHERE parent = ? ORDER BY bumped DESC LIMIT '.TINYIB_MAXTHREADS.',10',
			array(0)
		);
		foreach ($result as $post) {
			deletePostByID($post['id']);
		}
	}
}

// ##########################################
// Ban Functions
// ##########################################

function banByID($id) {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBBANS.' WHERE id=? LIMIT 1',
		array($id)
	);
	if (count($result)) return $result[0];
}

function banByIP($ip) {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBBANS.' WHERE ip=? LIMIT 1',
		array($ip)
	);
	if (count($result)) return $result[0];
}

function allBans() {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBBANS.' ORDER BY timestamp DESC',
		array()
	);
	return $result;
}

function insertBan($ban) {
	$result = fetchAndExecute(
		'INSERT INTO '.TINYIB_DBBANS.' (ip, timestamp, expire, reason) VALUES (?, ?, ?, ?)',
		array($ban['ip'], time(), $ban['expire'], $ban['reason'])
	);
	return $GLOBALS['db']->lastInsertId();
}

function clearExpiredBans() {
	$result = fetchAndExecute(
		'SELECT * FROM '.TINYIB_DBBANS.' WHERE expire > 0 AND expire <= ?',
		array(time())
	);
	foreach ($result as $ban) deleteBanByID($ban['id']);
}

function deleteBanByID($id) {
	fetchAndExecute('DELETE FROM '.TINYIB_DBBANS.' WHERE id=?', array($id));
}

// ##########################################
// Locking functions
// ##########################################

function isLocked($thread) {
	$result = fetchAndExecute(
		'SELECT COUNT(*) c FROM '.TINYIB_DBLOCKS.' WHERE thread=? LIMIT 1',
		array($thread)
	);
	return $result[0]['c'];
}

function lockThread($thread) {
	if (isLocked($thread)) return;
	fetchAndExecute('INSERT INTO '.TINYIB_DBLOCKS.' (thread) VALUES (?)', array($thread));
}

function unlockThread($thread) {
	if (! isLocked($thread)) return;
	fetchAndExecute('DELETE FROM '.TINYIB_DBLOCKS.' WHERE thread=?', array($thread));
}

function getAllLocks() {
	$result = fetchAndExecute(
		'SELECT thread FROM '.TINYIB_DBLOCKS.';',
		array()
	);
	$ret = array();
	foreach($result as $r) $ret[] = $r['thread'];
	return $ret;
}

