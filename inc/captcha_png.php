<?php
/*
Miniature PHP captcha
`````````````````````
Usage:
 captcha_png.php?key={a key}
 Displays a .png showing substr(md5(key),0,4)

Example captcha:
 $key = {random key}
 define SALT
 
 <img src="captcha_png.php?key=".$key 
 <input type="text" name="verification" />
 <input type="hidden" name="expected" val=" md5(SALT . substr(md5(key),0,4) )

 To validate, test to see if md5(SALT. verification) == expected.
*/
	
$phrase = 'invalid';
if (isset($_GET['key'])) $phrase = substr(md5($_GET['key']),0,4);

$im = @imagecreate(30, 12);
imagefill($im, 0, 0, imagecolorallocate($im, 255,255,255) );
imagestring($im, 2, 2, 0, $phrase, imagecolorallocate($im, 192, 0, 0)); 

header('Content-type: image/png'); 
imagepng($im); 

imagedestroy($im);
