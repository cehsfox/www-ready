<?php
// This program strips off style from an xml file.

include_once ("env.inc");

$str_pattern = '/style=".*"/i';

$file_default = "http://innopac.library.unlv.edu/feeds/sustainable.xml";

    // Get parameter.
$target = _GetGPCData_("target");
if ( '' == $target )
    $target = $file_default;

    // Need to tell browser the content type, in order to render properly.
$now_local = time ();       // local time in seconds, of unix stamp.

$lastmod = $now_local - 540; // minus 9 minutes.
$lastmod = gmdate ('D, d M Y H:i:s', $lastmod) . ' GMT';
header ("Last-Modified: $lastmod");
header ("Content-Type: text/xml");

$xmlfile = file_get_contents ($target);

echo preg_replace ($str_pattern, '', $xmlfile);

?>
