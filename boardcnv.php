<?php /*

Boardcnv v1
```````````
About:    Converts a fuukaba-basic imageboard to a tinyib-sqlite database.
Usage:    Place boardcnv.php in the root of your fuukaba-basic installation, and
            view in a web browser to create a tinyib.db sqlite database.
Requires: PHP 5.3+
Config:
*/
define('CONV_TIMEZONE', 'Pacific/Auckland');
define('TINYIB_BOARD',  'b');
define('TINYIB_DBPOSTS', /*TINYIB_BOARD . "_*/"posts");
/*

==============================================================================*/

// Definitions

define('FB_ID', 		0);
define('FB_TIME_TXT', 	1);
define('FB_NAMEBLOCK',	2);
define('FB_LINK',		3);
define('FB_SUBJECT',	4);
define('FB_MESSAGE', 	5);
define('FB_IMGDETAIL',	6);
define('FB_IP',			7);
define('FB_PASSWORD',	8);
define('FB_EXTENSION',	9);
define('FB_THUMBW',		10);
define('FB_THUMBH',		11);
define('FB_FILE',		12);
define('FB_FILEHEX',	13);

define('TIB_ID',		0);
define('TIB_PARENT', 	1);
define('TIB_TIMESTAMP', 2);
define('TIB_BUMPED',	3);
define('TIB_IP',		4);
define('TIB_NAME',		5);
define('TIB_TRIPCODE',	6);
define('TIB_EMAIL',		7);
define('TIB_NAMEBLOCK',	8);
define('TIB_SUBJECT',	9);
define('TIB_MESSAGE',	10);
define('TIB_PASSWORD',	11);
define('TIB_FILE',		12);
define('TIB_FILEHEX',	13);
define('TIB_FILEORIG',	14);
define('TIB_FILESZ',	15);
define('TIB_FILESZFMT',	16);
define('TIB_IMGWIDTH',	17);
define('TIB_IMGHEIGHT',	18);
define('TIB_THUMB',		19);
define('TIB_THUMBW',	20);
define('TIB_THUMBH',	21);

define('MAX_FB',        FB_FILEHEX +2);

// Initialisation

$tree = '';
$img = array();
global $posts;
$posts = array();
$log = array();
date_default_timezone_set(CONV_TIMEZONE);
set_time_limit(60 * 2); // Two minutes

// Load FuukabaB posts

$tree = @file_get_contents('tree.log');
$img_file = @file_get_contents("img.log");
if ($tree === false || $img_file === false) {
	die("Couldn't load Fuukaba-basic's log files!");
}
$img_split = explode("\n", $img_file);
$img_file = null; $img = array();
foreach($img_split as $_img) $img[] = explode(",", $_img);
$_img = null;

// Pass 1: Copy over details

//$img = array_reverse($img);
foreach ($img as $old) {
	if (count($old) != MAX_FB) continue;

	$new = array();
	$oldimg = unserialize($old[FB_IMGDETAIL]);
	
	// Basic fields
	$new[TIB_ID]		= $old[FB_ID];
	$new[TIB_PARENT] 	= 0; // Do later
	$new[TIB_TIMESTAMP] = $old[FB_TIME_TXT]; // Unix timestamp
	$new[TIB_BUMPED] 	= 0; // Do later       (unix timestamp)
	$new[TIB_IP]		= $old[FB_IP];
	$new[TIB_EMAIL]		= $old[FB_LINK];
	$new[TIB_SUBJECT]	= $old[FB_SUBJECT];
	$new[TIB_MESSAGE]	= $old[FB_MESSAGE];
	$new[TIB_PASSWORD]	= $old[FB_PASSWORD];
	
	// Format image fields
	if (! strlen($old[FB_EXTENSION])) {
	
		// No image post
		$new[TIB_FILE]		= '';
		$new[TIB_FILEHEX]	= '';
		$new[TIB_FILEORIG]	= '';
		$new[TIB_FILESZ]	= 0;
		$new[TIB_FILESZFMT]	= 0;
		$new[TIB_IMGWIDTH]	= 0;
		$new[TIB_IMGHEIGHT]	= 0;
		$new[TIB_THUMB]		= '';
		$new[TIB_THUMBW]	= 0;
		$new[TIB_THUMBH]	= 0;
		
	} else {
	
		// Image post
		$new[TIB_FILE]		= $old[FB_FILE].$old[FB_EXTENSION];		
		$new[TIB_FILESZ]	= 1;
		$new[TIB_FILESZFMT]	= 'Unknown';
		$new[TIB_IMGWIDTH]	= 1;
		$new[TIB_IMGHEIGHT]	= 1;
		
		// Format convertible data		
		$new[TIB_FILEHEX]	= $old[FB_FILEHEX];
		$new[TIB_FILEORIG]	= $oldimg['upfile_name'];				
		$new[TIB_THUMB]		= $old[FB_FILE].'s.jpg'; //.$old[FB_EXTENSION];
		$new[TIB_THUMBW]	= $old[FB_THUMBW];
		$new[TIB_THUMBH]	= $old[FB_THUMBH];
		
		// Retrieve remaining details from filesystem
		$imagesize = @filesize('src/'.$new[TIB_FILE]);
		if ($imagesize !== FALSE) {
			$new[TIB_FILESZ]    = $imagesize;
			$new[TIB_FILESZFMT] = formatBytes($imagesize);
			
			$imageinfo = @getimagesize('src/'.$new[TIB_FILE]);
			if ($imageinfo !== FALSE) {
				$new[TIB_IMGWIDTH]	= $imageinfo[0];
				$new[TIB_IMGHEIGHT]	= $imageinfo[1];
			} else {			
				$log[] = "Couldn't find image dimensions for ".$new[TIB_FILE];
			}
		} else {
			$log[] = "Couldn't get filesize for ".$new[TIB_FILE];
		}		
		
	}
	
	// Format the timestamp field
	$new[TIB_TIMESTAMP] = date_timestamp_get(
		date_create_from_format(' d#m#Y * H#i#s', $old[FB_TIME_TXT])
	);
	
	// Format the name, tripcode and nameblock fields
	$nameBlock = '';
	$tripMarkPos = strpos($old[FB_NAMEBLOCK], '!');
	if ($tripMarkPos !== false) {
		$new[TIB_NAME] = substr($old[FB_NAMEBLOCK], 0, $tripMarkPos);
		if (! strlen($new[TIB_NAME])) $new[TIB_NAME] = 'Anonymous';
		$new[TIB_TRIPCODE] = substr($old[FB_NAMEBLOCK], $tripMarkPos + 1);
		$nameBlock = '<span class="postername">'.$new[TIB_NAME].'</span>'.
			'<span class="postertrip">!'.$new[TIB_TRIPCODE].'</span>';
	} else {
		$new[TIB_NAME] = strlen(trim($old[FB_NAMEBLOCK])) ? $old[FB_NAMEBLOCK] : 'Anonymous';
		$new[TIB_TRIPCODE] = '';
		$nameBlock = '<span class="postername">'.$new[TIB_NAME].'</span>';		
	}
	if (strlen($new[TIB_EMAIL])) {
		$emailPrefix = (strpos($new[TIB_EMAIL], '@') === false) ? '':'mailto:';
		$new[TIB_NAMEBLOCK] = '<a href="'.$emailPrefix.'">'.$nameBlock.'</a>';
	} else {
		$new[TIB_NAMEBLOCK]	= $nameBlock.' ';
	}
	$new[TIB_NAMEBLOCK] .= $old[FB_TIME_TXT];
	
	// Save and continue
	$posts[$old[FB_ID]] = $new;
}

// Pass 2: Fill in parents and bump order using tree log

$threads = explode("\n", $tree);
$bumpidx = count($threads) + 1;
foreach($threads as $thread) {
	$threadposts = explode(',', $thread);
	$parent = $threadposts[0];
	foreach($threadposts as $threadpost) {
		$posts[$threadpost][TIB_BUMPED] = $bumpidx;
		$posts[$threadpost][TIB_PARENT] = $parent;
	}
	$posts[$threadposts[0]][TIB_PARENT] = 0;
	$bumpidx--;
}

// Pass 3: Properly delete all hidden posts
$posts = array_filter($posts, 'testPost');

// Pass 4: Format all links
$post_keys = array_keys($posts);
for ($i=0, $e = count($post_keys); $i<$e; $i++) {
	if (!isset( $posts[$post_keys[$i]][TIB_MESSAGE] )) continue;
	$posts[$post_keys[$i]][TIB_MESSAGE] = colorQuote(postLink($posts[$post_keys[$i]][TIB_MESSAGE]));
}

// Finally, save to new sqlite database.
if (file_exists('tinyib.db')) {
	die('A Tinyib.db already exists. Please move it first.');
}

// Create a new DB and connect to it DB
if (!$db = sqlite_open('tinyib.db', 0666, $error)) {
	die("Couldn't connect to database: " . $error);
}

// Create the posts table
sqlite_query($db, "CREATE TABLE " . TINYIB_DBPOSTS . " (
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
	file_size INTEGER NOT NULL DEFAULT '0',
	file_size_formatted TEXT NOT NULL,
	image_width INTEGER NOT NULL DEFAULT '0',
	image_height INTEGER NOT NULL DEFAULT '0',
	thumb TEXT NOT NULL,
	thumb_width INTEGER NOT NULL DEFAULT '0',
	thumb_height INTEGER NOT NULL DEFAULT '0'
)");

sqlite_query($db, 'BEGIN;');
foreach($posts as $post) {
	if (! isset($post[TIB_ID])) continue;
	
	sqlite_query($db, "
		INSERT INTO ".TINYIB_DBPOSTS." (
			id,
			parent, timestamp, bumped, ip, name, tripcode, email, nameblock,
			subject, message, password, file, file_hex, file_original,
			file_size, file_size_formatted, image_width, image_height,
			thumb, thumb_width, thumb_height
		) VALUES (
		" . $post[TIB_ID] .",
		" . $post[TIB_PARENT] . ",
		" . $post[TIB_TIMESTAMP] . ", 
		" . $post[TIB_BUMPED] . ",
		'" .$post[TIB_IP] . "',
		'" .sqlite_escape_string($post[TIB_NAME]) . "',
		'" .sqlite_escape_string($post[TIB_TRIPCODE]) . "',
		'" .sqlite_escape_string($post[TIB_EMAIL]) . "',
		'" .sqlite_escape_string($post[TIB_NAMEBLOCK]) . "',
		'" .sqlite_escape_string($post[TIB_SUBJECT]) . "',
		'" .sqlite_escape_string($post[TIB_MESSAGE]) . "',
		'" .sqlite_escape_string($post[TIB_PASSWORD]) . "',
		'" .$post[TIB_FILE] . "',
		'" .$post[TIB_FILEHEX] . "',
		'" .sqlite_escape_string($post[TIB_FILEORIG]) . "',
		" . $post[TIB_FILESZ] . ",
		'" .$post[TIB_FILESZFMT] . "',
		" . $post[TIB_IMGWIDTH] . ", 
		" . $post[TIB_IMGHEIGHT] . ", 
		'" .$post[TIB_THUMB] . "', 
		" . $post[TIB_THUMBW] . ", 
		" . $post[TIB_THUMBH] . "
		)
	");
}
sqlite_query($db, 'COMMIT;');

sqlite_close($db);


// Helper functions
// ````````````````

function formatBytes($bytes) {
	static $suffix = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
	$ptr = $bytes;
	for ($i = 0, $e = count($suffix); $i < $e; $i++) {
		if ($ptr < 1024) return number_format($ptr, 2).$suffix[$i];
		$ptr /= $bytes;
	}
	return 'Large';
}

function testPost($post) {
	// Hidden posts still exist in FuB post database, but not in thread list.
	return ($post[TIB_BUMPED] != 0);
}

function showLink($id) {
	return '<a href="javascript:;" onClick="showArea(\''.$id.'\');">show</a>';
}

function getParentOfPost($id) {
	global $posts;
	if (isset($posts[$id])) return $posts[$id][TIB_PARENT];
	return -1;
}

function _postLink($matches) {
	$id = $matches[1];
	$parent = getParentOfPost($id);
	if ($parent >= 0) {
		return '<a href="res/' . ($parent == 0 ? $id : $parent) . '.html#' . $matches[1] . '">' . $matches[0] . '</a>';
	}
	return $matches[0];
}

function postLink($message) {
	return preg_replace_callback('/&gt;&gt;([0-9]+)/', '_postLink', $message);
}

function colorQuote($message) {
	if (substr($message, -1, 1) != "\n") { $message .= "\n"; }
	return preg_replace('/^(&gt;[^\>](.*))\n/m', '<span class="unkfunc">\\1</span>' . "\n", $message);
}

// HTML Template
// `````````````

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Boardcnv</title>
		<style type="text/css">
#datanew, #dataold, #log {
	display: none;
}
		</style>		
		<script language="javascript">
function showArea(id) {
	document.getElementById(id).style.display='block';
}
		</script>
		
	</head>
	<body>
		<h2>Boardcnv</h2>
		<hr />
		
		<fieldset>
			<legend>Warnings (<?=showlink('log')?>)</legend>
			<div id="log">
				<ul><?='<li>'.implode('</li><li>', $log).'</li>'; ?></ul>
			</div>
		</fieldset>
		
<? if (! isset($_GET['no_data_display'])) { ?>
		
		<fieldset>
			<legend>Replacement data (<?=showlink('datanew')?>)</legend>
			<div id="datanew">
				<table border="1" cellpadding="1" cellspacing="0">
					<thead>
						<tr>
							<td>id</td>
							<td>parent</td>
							<td>timestamp</td>
							<td>bumped</td>
							<td>ip</td>
							<td>name</td>
							<td>tripcode</td>
							<td>email</td>
							<td>nameblock</td>
							<td>subject</td>
							<td>message</td>
							<td>password</td>
							<td>file</td>
							<td>file_hex</td>
							<td>file_original</td>
							<td>file_size</td>
							<td>file_size_formatted</td>
							<td>image_width</td>
							<td>image_height</td>
							<td>thumb</td>
							<td>thumb_width</td>
							<td>thumb_height</td>
						</tr>
					</thead>
					<tbody>
			
<? foreach ($posts as $post) { ?>			
						<tr>
							<td><?=htmlspecialchars($post[TIB_ID])?></td>
							<td><?=htmlspecialchars($post[TIB_PARENT])?></td>
							<td><?=htmlspecialchars($post[TIB_TIMESTAMP])?></td>
							<td><?=htmlspecialchars($post[TIB_BUMPED])?></td>
							<td><?=htmlspecialchars($post[TIB_IP])?></td>
							<td><?=htmlspecialchars($post[TIB_NAME])?></td>
							<td><?=htmlspecialchars($post[TIB_TRIPCODE])?></td>
							<td><?=htmlspecialchars($post[TIB_EMAIL])?></td>
							<td><?=htmlspecialchars($post[TIB_NAMEBLOCK])?></td>
							<td><?=htmlspecialchars($post[TIB_SUBJECT])?></td>
							<td><?=htmlspecialchars($post[TIB_MESSAGE])?></td>
							<td><?=htmlspecialchars($post[TIB_PASSWORD])?></td>
							<td><?=htmlspecialchars($post[TIB_FILE])?></td>
							<td><?=htmlspecialchars($post[TIB_FILEHEX])?></td>
							<td><?=htmlspecialchars($post[TIB_FILEORIG])?></td>
							<td><?=htmlspecialchars($post[TIB_FILESZ])?></td>
							<td><?=htmlspecialchars($post[TIB_FILESZFMT])?></td>
							<td><?=htmlspecialchars($post[TIB_IMGWIDTH])?></td>
							<td><?=htmlspecialchars($post[TIB_IMGHEIGHT])?></td>
							<td><?=htmlspecialchars($post[TIB_THUMB])?></td>
							<td><?=htmlspecialchars($post[TIB_THUMBW])?></td>
							<td><?=htmlspecialchars($post[TIB_THUMBH])?></td>
						</tr>
<? } ?> 			
					</tbody>
				</table>
			</div>
		</fieldset>
				
		<fieldset>
			<legend>Original Data (<?=showlink('dataold')?>)</legend>
		
			<div id="dataold">
				<table border="1" cellpadding="1" cellspacing="0">

<? 
foreach($img_split as $_img) {
	echo '<tr><td>'.implode('</td><td>',explode(",", $_img))."</td></tr>\n";
}
$_img = null;
?>
		
				</table>
			</div>
		</fieldset>
		
<? } // no_data_display ?>		
		
	</body>
</html>