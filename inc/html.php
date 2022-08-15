<?php

function pageHeader() {
	$page_title = TINYIB_PAGETITLE;
	$return = <<<EOF
<!DOCTYPE html>
<html>
	<head>
		<title>{$page_title}</title>
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="stylesheet" type="text/css" href="inc/style.css" />
		<link rel="alternate" type="application/rss+xml" href="rss.php" />
		<meta http-equiv="content-type" content="text/html;charset=UTF-8">
		<meta http-equiv="pragma" content="no-cache">
		<meta http-equiv="expires" content="-1">
		<script src="inc/script.js" type="text/javascript"></script>
	</head>
EOF;
	return $return;
}

function pageFooter() {
	return <<<EOF
	</body>
</html>
EOF;
}

function buildPost($post, $isrespage) {
	$return = "";
	$threadid = ($post['parent'] == 0) ? $post['id'] : $post['parent'];
	$postlink = '?do=thread&id='.$threadid.'#'.$post['id'];
	
	$image_desc = '';
	if ($post['file'] != '') {
		$image_desc =
			cleanString($post['file_original']) .' ('.$post["image_width"].'x'.
			$post["image_height"].', '.$post["file_size_formatted"].')'
		;
	}

	if ($post['parent'] == 0 && !$isrespage) {
		$note = isLocked($threadid) ? '<em>(locked)</em>' : ''; //&#x1f512;
		$return .=
			"<span class=\"replylink\">${note}&nbsp;[<a href=\"?do=thread&id=${post["id"]}\">".
			"View thread</a>]&nbsp;</span>"
		;
	}
	
	if ($post["parent"] != 0) {
		$return .= <<<EOF
<table>
	<tbody>
		<tr>
			<td class="doubledash">&gt;&gt;</td>
			<td class="reply" id="reply${post["id"]}">
			
EOF;
	} elseif ($post["file"] != "") {
		$return .= <<<EOF
<a target="_blank" href="src/${post["file"]}">
	<span id="thumb${post['id']}"><img title="$image_desc" src="thumb/${post["thumb"]}" alt="${post["id"]}" class="thumb" width="${post["thumb_width"]}" height="${post["thumb_height"]}"></span>
</a>

EOF;
	}
	
	$return .= <<<EOF
<a name="${post['id']}"></a>

EOF;

	if ($post["subject"] != "") {
		$return .= "	<span class=\"filetitle\">${post["subject"]}</span> ";
	}
	
	$return .= <<<EOF
<a href="?do=delpost&id={$post['id']}" title="Delete" />X</a>
${post["nameblock"]}
EOF;

	if (IS_ADMIN) {
		$return .= ' [<a href="?do=manage&p=bans&bans='.urlencode($post['ip']).'" title="Ban poster">'.htmlspecialchars($post['ip']).'</a>]';
	}

	$return .= <<<EOF
<span class="reflink">
	<a href="$postlink">No.</a><a href="javascript:quote('${post["id"]}')">${post['id']}</a>
</span>

EOF;
	
	
	if ($post['parent'] != 0 && $post["file"] != "") {
		$return .= <<<EOF
<br>
<a target="_blank" href="src/${post["file"]}">
	<span id="thumb${post["id"]}"><img title="$image_desc" src="thumb/${post["thumb"]}" alt="${post["id"]}" class="thumb" width="${post["thumb_width"]}" height="${post["thumb_height"]}"></span>
</a>

EOF;
	}

	$return .= <<<EOF
<blockquote>
{$post['message']}
</blockquote>

EOF;

	if ($post['parent'] == 0) {
		if (!$isrespage && $post["omitted"] > 0) {
			$return .=
				'<span class="omittedposts">'.$post['omitted'].' post(s) omitted. '.
				'<a href="?do=thread&id='.$post["id"].'">Click here</a> to view.</span>'
			;
		}
	} else {
		$return .= <<<EOF
			</td>
		</tr>
	</tbody>
</table>

EOF;
	}
	
	return $return;
}

function buildPostBlock($parent) {
	$body = '
		<div class="postarea">
			<form name="postform" id="postform" action="?do=post" method="post" enctype="multipart/form-data">
			
			<input type="hidden" name="parent" value="'.$parent.'">
			<table class="postform">
				<tbody>
					<tr>
						<td class="postblock" title="Optional [!password]">Name</td>
						<td>
							<input type="text" name="name" size="28" maxlength="75">
						</td>
					</tr>
	';
	if (! $parent) {
		$body .= '
					<tr>
						<td class="postblock" title="Optional">Subject</td>
						<td>
							<input type="text" name="subject" size="40" maxlength="75">
						</td>
					</tr>
		';
	}
	$body .= '
					<tr>
						<td class="postblock">Message</td>
						<td>
							<textarea name="message" cols="48" rows="4"></textarea>
						</td>
					</tr>
	';
	
	if (TINYIB_USECAPTCHA && !LOGGED_IN) {
		$captcha_key = md5(mt_rand());
		$captcha_expect = md5(TINYIB_CAPTCHASALT.substr(md5($captcha_key),0,4));
		$body .= '
					<tr>
						<td class="postblock" title="Please copy the text to show you\'re a human.">
							Verification
						</td>
						<td>
							<input type="hidden" name="captcha_ex" value="'.$captcha_expect.'" />
							<input type="text" name="captcha_out" size="8" />
							<img src="inc/captcha_png.php?key='.$captcha_key.'" />
						</td>
					</tr>
		';
	}
	
	$body .= '
					<tr>
						<td class="postblock">Image</td>
						<td>
							<input type="file" name="file" size="35" title="Images may be GIF, JPG or PNG up to 2 MB.">
						</td>
					</tr>
	';
	
	$post_button_name = ($parent) ? 'Post Reply' : 'Create Thread';
	$opt_bump_thread = ($parent) ? '<label><input type="checkbox" name="bump" id="bump" checked>Bump</label>' : '';
	$opt_modpost = LOGGED_IN ? '<label><input type="checkbox" name="modpost" id="modpost">Modpost</label>' : '';
	$opt_rawhtml = LOGGED_IN ? '<label><input type="checkbox" name="rawhtml" id="rawhtml">RawHTML</label>' : '';
	$body .= '
					<tr>
						<td class="postblock">&nbsp;</td>
						<td>
							<input type="submit" value="'.$post_button_name.'">
							'.$opt_bump_thread.'
							'.$opt_modpost.'
							'.$opt_rawhtml.'
						</td>
					</tr>
				</tbody>
			</table>
			</form>
		</div>
		<hr>
	';
	return $body;
}

function buildPage($htmlposts, $parent, $pages=0, $thispage=0) {
	$locked = $parent ? isLocked($parent) : false;
	$returnlink = ''; $pagelinks = '';
	
	if ($parent == 0) {
		$pages = max($pages, 0);
		
		$pagelinks =
			($thispage == 0) ?
			"[ Previous ]" :
			'[ <a href="?do=page&p=' .($thispage-1). '">Previous</a> ]'
		;		
		for ($i = 0;$i <= $pages;$i++) {
			$pagelinks .= ($thispage == $i) ? "[ $i ]" : "[ <a href=\"?do=page&p=$i\">$i</a> ]";
		}		
		$pagelinks .= ($pages <= $thispage) ?
			"[ Next ]" :
			'[ <a href="?do=page&p='.($thispage+1). '">Next</a> ]'
		;
		
	} else {
		$returnlink = '<span class="replylink">[<a href="?">Return</a>';
		if (LOGGED_IN) {
			if ($locked) {
				$returnlink .= ' | <a href="?do=lock&id='.$parent.'">Unlock Thread</a>';
			} else {
				$returnlink .= ' | <a href="?do=lock&id='.$parent.'">Lock Thread</a>';				
			}
		}
		$returnlink .= ']</span>';
	}
	
	$body = '
	<body onLoad="onFirstLoad();">
		<div class="logo">
'.TINYIB_LOGO.'
		</div>
		<hr size="1">
	';
	if ($locked) {
		$body .= '<div class="replymode">This thread is locked. You can\'t reply any more.</div>';
	}
	if ($parent) {
		$body .= $returnlink . "\n" . $htmlposts;
	}
	if (!$locked) {
		$body .= buildPostBlock($parent);
	}
	if (!$parent) {
		$body .= $returnlink . "\n" . $htmlposts;
	}

	$body .= <<<EOF
		<div class="adminbar">
			[<a href="?">Home</a> | <a href="?do=manage">Admin</a>]
		</div>
		<div class="pagelinks">
			$pagelinks
		</div>
		<br>
EOF;

	return pageHeader() . $body . pageFooter();
}

function viewPage($pagenum) {
	$page = intval($pagenum);
	$pagecount = max(0, ceil(countThreads() / TINYIB_THREADSPERPAGE) - 1);
	if (!is_numeric($pagenum) || $page < 0 || $page > $pagecount) fancyDie('Invalid page number.');
	
	$htmlposts = array();
	
	$threads = getThreadRange(TINYIB_THREADSPERPAGE, $pagenum * TINYIB_THREADSPERPAGE );
	
	foreach ($threads as $thread) {
	
		$replies = latestRepliesInThreadByID($thread['id']);
		
		$htmlreplies = array();
		foreach ($replies as $reply) {
			$htmlreplies[] = buildPost($reply, False);
		}
		
		$thread["omitted"] = (count($htmlreplies) == 3) ? (count(postsInThreadByID($thread['id'])) - 4) : 0;
		
		$htmlposts[] = buildPost($thread, false) . implode("", array_reverse($htmlreplies)) . "<br clear=\"left\">\n<hr>";
	}
	
	return buildPage(implode('', $htmlposts), 0, $pagecount, $page);
}

function viewThread($id) {
	$htmlposts = array();
	$posts = postsInThreadByID($id);
	foreach ($posts as $post) $htmlposts[] = buildPost($post, True);
	$htmlposts[] = "<br clear=\"left\">\n<hr>";
	
	return buildPage(implode('',$htmlposts), $id);
}

function adminBar() {
	if (! LOGGED_IN) { return '[<a href="?">Return</a>]'; }
	$text = IS_ADMIN ? '[<a href="?do=manage&p=bans">Bans</a>] ' : '';
	$text .=
		'[<a href="?do=manage&p=threads">Thread list</a>] '.
		'[<a href="?do=manage&p=moderate">Moderate Post</a>] '.
		'[<a href="?do=manage&p=logout">Log Out</a>] '.
		'[<a href="?">Return</a>]'
	;
	return $text;
}

function managePage($text) {
	$adminbar = adminBar();
	$body = <<<EOF
	<body>
		<div class="adminbar">
			$adminbar
		</div>
		<div class="logo">
EOF;
	$body .= TINYIB_LOGO . <<<EOF
		</div>
		<hr width="90%" size="1">
		<div class="replymode">Manage mode</div>
		$text
		<hr>
EOF;
	return pageHeader() . $body . pageFooter();
}

function manageLogInForm() {
	return <<<EOF
	<form id="tinyib" name="tinyib" method="post" action="?do=manage&p=home">
		<fieldset>
			<legend align="center">Please enter an administrator or moderator password</legend>
			<div class="login">
				<input type="password" id="password" name="password" autofocus><br>
				<input type="submit" value="Submit" class="managebutton">
			</div>
		</fieldset>
	</form>
	<br/>
EOF;
}

function manageBanForm() {
	$banstr = isset($_GET['bans']) ? $_GET['bans'] : '';

	return <<<EOF
	<form id="tinyib" name="tinyib" method="post" action="?do=manage&p=bans">
		<fieldset>
			<legend>Ban an IP address from posting</legend>
			<label for="ip">IP Address:</label>
			<input type="text" name="ip" id="ip" value="$banstr" autofocus>
			<input type="submit" value="Submit" class="managebutton">
			<br/>
			<label for="expire">Expire(sec):</label>
			<input type="text" name="expire" id="expire" value="0">&nbsp;&nbsp;
			<small>
				<a href="#" onclick="document.tinyib.expire.value='3600';return false;">1hr</a>&nbsp;
				<a href="#" onclick="document.tinyib.expire.value='86400';return false;">1d</a>&nbsp;
				<a href="#" onclick="document.tinyib.expire.value='172800';return false;">2d</a>&nbsp;
				<a href="#" onclick="document.tinyib.expire.value='604800';return false;">1w</a>&nbsp;
				<a href="#" onclick="document.tinyib.expire.value='1209600';return false;">2w</a>&nbsp;
				<a href="#" onclick="document.tinyib.expire.value='2592000';return false;">30d</a>&nbsp;
				<a href="#" onclick="document.tinyib.expire.value='0';return false;">never</a>
			</small>
			<br/>
			<label for="reason">Reason:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
			<input type="text" name="reason" id="reason">&nbsp;&nbsp;<small>(optional)</small>
		</fieldset>
	</form>
	<br/>
EOF;
}

function manageBansTable() {
	$text = '';
	$allbans = allBans();
	if (count($allbans) > 0) {
		$text .= '<table border="1"><tr><th>IP Address</th><th>Set At</th><th>Expires</th><th>Reason Provided</th><th>&nbsp;</th></tr>';
		foreach ($allbans as $ban) {
			$expire = ($ban['expire'] > 0) ? date('y/m/d(D)H:i:s', $ban['expire']) : 'Never';
			$reason = ($ban['reason'] == '') ? '&nbsp;' : htmlentities($ban['reason']);
			$text .= '<tr><td>' . $ban['ip'] . '</td><td>' . date('y/m/d(D)H:i:s', $ban['timestamp']) . '</td><td>' . $expire . '</td><td>' . $reason . '</td><td><a href="?do=manage&p=bans&lift=' . $ban['id'] . '">lift</a></td></tr>';
		}
		$text .= '</table>';
	}
	return $text;
}

function manageModeratePostForm() {
	return <<<EOF
	<form id="tinyib" name="tinyib" method="get" action="?">
		<input type="hidden" name="manage" value="">
		<fieldset>
			<legend>Moderate a post</legend>
			<input type="hidden" name="do" value="manage">
			<input type="hidden" name="p" value="moderate">
			<label for="moderate">Post ID:</label>
			<input type="text" name="moderate" id="moderate" autofocus>
			<input type="submit" value="Submit" class="managebutton">
			<br/>
		</fieldset>
	</form>
	<br/>
EOF;
}

function manageModeratePost($post) {
	$ban = banByIP($post['ip']);
	$ban_disabled = (!$ban && IS_ADMIN) ? '' : ' disabled';
	$ban_disabled_info = (!$ban) ? '' : (' A ban record already exists for ' . $post['ip']);
	$post_html = buildPost($post, true);
	$post_or_thread = ($post['parent'] == 0) ? 'Thread' : 'Post';
	return <<<EOF
	<fieldset>
		<legend>Moderating post No.${post['id']}</legend>		
		<div class="floatpost">
			<fieldset>
				<legend>$post_or_thread</legend>	
				$post_html
			</fieldset>
		</div>		
		<fieldset>
			<legend>Action</legend>					
			<form method="get" action="?">
				<input type="hidden" name="do" value="manage" />
				<input type="hidden" name="p" value="delete" />
				<input type="hidden" name="delete" value="${post['id']}" />
				<input type="submit" value="Delete $post_or_thread" class="managebutton" />
			</form>
			<br/>
			<form method="get" action="?">
				<input type="hidden" name="do" value="manage" />
				<input type="hidden" name="p"  value="bans" />
				<input type="hidden" name="bans" value="${post['ip']}" />
				<input type="submit" value="Ban Poster" class="managebutton"$ban_disabled />$ban_disabled_info
			</form>
		</fieldset>	
	</fieldset>
	<br />
EOF;
}

function manageAllThreads() {
	$threads = getThreadRange(10000, 0);
	$locks   = getAllLocks();
	
	$ret = '
		<table style="width:100%;border:0px;border-collapse:collapse;margin:2px;">
			<thead style="background-color:darkred;color:white;text-align:left;">
				<tr>					
					<th>#</th>
					<th>Subject</th>
					<th>First post</th>
					<th style="width:160px;">Created</th>
					<th style="width:160px;">Last Bump</th>
					<th>Locked</th>
				</tr>
			</thead>
			<tbody>
	';
	foreach($threads as $thread) {
		$locked = in_array($thread['id'], $locks);
		// Workaround for incorrectly imported history
		$bump = ($thread['bumped'] > 1000 ? date(TINYIB_DATEFORMAT,$thread['bumped']) : '-');
		$ret .= '
				<tr>
					<td><a href="?do=thread&id='.$thread['id'].'">#'.$thread['id'].'</a></td>
					<td>'.$thread['subject'].'</td>
					<td>'.htmlspecialchars(substr($thread['message'], 0, 60)).'</td>
					<td>'.date(TINYIB_DATEFORMAT, $thread['timestamp']).'</td>
					<td><a href="?do=manage&p=bump&id='.$thread['id'].'" title="Bump this thread">'.$bump.'</a></td>
					<td>'.($locked ? 'Locked' : '-').'</td>
				</tr>
		';
	}
	$ret .= '
			</tbody>
		</table>
	';
	return $ret;
}

