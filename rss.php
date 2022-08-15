<?php

// RSS support for TinyIB
// ``````````````````````

// Initialisation

require 'inc/settings.php';
include 'inc/functions.php';
require 'inc/database.php';

function getBaseUrl() {
	$base = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
	if ($base[strlen($base)-1] != '/') $base .= '/';
	return $base;
}

$base_url = getBaseUrl();
$home_url = $base_url.'index.html';

// Retrieve latest posts

$posts = latestPosts(RSS_POSTS_PER_REQUEST);

// RSS Template

header("Content-Type: application/rss+xml");
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?> 
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"> 
	<channel>
		<title><?php echo TINYIB_PAGETITLE; ?></title> 
		<link><?php echo htmlspecialchars($home_url); ?></link> 
		<description>Posts</description> 
		<language><?php echo RSS_LANGUAGE; ?></language>
		<atom:link href="<?php echo $base_url; ?>rss.php" rel="self" type="application/rss+xml" />
<?php
	foreach ($posts as $post) {
	
		$post_title = '&gt;&gt;'.$post['id'];
		if (strlen($post['subject'])) $post_title .= ' - '.$post['subject'];
		
		$post_author = $post['name'];
		if (strlen($post['tripcode'])) $post_author .= '!'.$post['tripcode'];
		if (! strlen($post_author)) $post_author = "Anonymous";
		$post_author = RSS_AUTHOR_EMAIL. ' ('.$post_author.')';
		
		if ($post['parent'] == 0) {
			//$post_link = $base_url.'res/'.$post['id'].'.html';
			$post_link = $base_url.'?do=thread&id='.$post['id'];
		} else {
			//$post_link = $base_url.'res/'.$post['parent'].'.html#'.$post['id'];
			$post_link = $base_url.'?do=thread&id='.$post['parent'].'#'.$post['id'];
		}
		
		$post_desc = '';
		if (strlen($post['file'])) {
			$post_desc = '
				<a href="'.$base_url.'src/'.$post['file'].'">'.
					'<img src="'.$base_url.'thumb/'.$post['thumb'].'" width="'.$post['thumb_width'].'" height="'.$post['thumb_height'].'" />'.
				'</a><br/>'
			;
		}
		$post_desc .= 
			str_replace(
				array('<br>',  '<a href="res/'), 
				array('<br/>', '<a href="'.$base_url.'res/"'),
				$post['message']
		);

		$post_date = date(DATE_RSS, $post['timestamp']);
?> 
		<item>
			<title><?php echo $post_title; ?></title>
			<link><?php echo htmlspecialchars($post_link); ?></link>
			<guid><?php echo htmlspecialchars($post_link); ?></guid>
			<author><?php echo $post_author; ?></author>
			<pubDate><?php echo $post_date; ?></pubDate>
			<description>
				<![CDATA[
<?php echo $post_desc; ?>
				]]></description>
		</item>
<?php
	}
?> 
	</channel>
</rss>
