<?php

	$title = $_GET['title'];
	$author = $_GET['author'];

	$headers = 'From: wds-testing@unlv.edu' . "\r\n" .
    'Reply-To: wds-testing@unlv.edu' . "\r\n";


	$message = "A patron has requested that we purchase: " . $title . " by " . $author;

$referer = $_SERVER["HTTP_REFERER"];
$remote_addr = $_SERVER["REMOTE_ADDR"];
$message .= "\n\nReferer: $referer\n";
$message .= "\n\nRemote addr: $remote_addr\n";

//	echo $message;

	mail('hong.zhang@unlv.edu', 'Patron Initiated Book Request', $message, $headers);
	
?>
