<?php
// 'View source' in a web browser.
// Add ?apply to apply changes.

$db = new \PDO('sqlite2:tinyib.db');
$posts = $db->query('SELECT * FROM posts ORDER BY id ASC')->fetchAll();

foreach($posts as $post) {
	
	$message = preg_replace(
		'/<a href="res\/(\d+)\.html\#(\d+)">/',
		'<a href="?do=thread&id=\1#\2">',
		$post['message']
	);
	if ($message != $post['message']) {
		echo "Post #".$post['id']."\n";	
		echo "SOURCE\n";
		echo $post['message']."\n";
		echo "CHANGED\n";
		echo $message."\n\n";
		
		if (isset($_GET['apply'])) {
			$update = $db->prepare('UPDATE posts SET message=? WHERE id=?');
			$update->execute(array(
				$message,
				$post['id']
			));
		}
	}
}