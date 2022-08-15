<?php
/*
boardcnv / import_locks.php
```````````````````````````

If you are using an extended TinyIB (e.g. tinyib-mappy), this script will create
 a threadstates table in tinyib.db to be used to hold the IDs of locked threads.

Place it in the root of a fuukaba-basic installation, and it will take the
 threadstates from _DAT/ as normal.
*/

// Configuration
define('PATH_TO_THREADSTATES',	'_DAT/threadstates.dat');
define('TINYIB_DBLOCKS',		'locked_threads');

// Load file
$states_file = @file_get_contents(PATH_TO_THREADSTATES);
if ($states_file === false) die('Couldn\'t read threadstates.dat');
$states = explode(',', file_get_contents(PATH_TO_THREADSTATES));

if (!$db = sqlite_open('tinyib.db', 0666, $error)) {
	die("Couldn't connect to database: " . $error);
}

// Create the locks table if it does not exist
$result = sqlite_query($db, "SELECT name FROM sqlite_master WHERE type='table' AND name='" . TINYIB_DBLOCKS . "'");
if (sqlite_num_rows($result) == 0) {
	sqlite_query($db, "CREATE TABLE " . TINYIB_DBLOCKS . " (
		id INTEGER PRIMARY KEY,
		thread INTEGER NOT NULL		
	)");
}

// Add a row for each lock
sqlite_query($db, 'BEGIN;');
foreach($states as $state) {
	sqlite_query($db, "INSERT INTO ".TINYIB_DBLOCKS.' (thread) VALUES ('.intval($state).');');
}
sqlite_query($db, 'COMMIT;');

// Finished
die('Completed OK.');
?>