<?php

/* This script takes a bib number, then looks it up in webpac system
 * for its marc record display.
 *
 * Each YBP marc record should have a 984 field that contains string 'YBP'.
 * If that's found, then return 'yes', else return 'no'.
 */


$base_webpac = 'http://webpac.library.unlv.edu/search~S1?/';
$pattern_ybp = '/984.*YBP/';

    // recordnum is: /record=b1234567~S1
    // Extract b number: b1234567.
$record = $_GET['recordnum'];
//$record = '/record=b3415779~S1';

$pieces = explode ('=', $record);

$number = $pieces[1];
$pieces_bnum = explode ('~', $number);
$bnum = $pieces_bnum[0];


$url_record_marc = $base_webpac . '.' . $bnum . '/.' . $bnum . '/1,1,1,B/marc~' . $bnum;


    // Retrieve webpage that contains marc display.
$info_rec = file_get_contents ($url_record_marc);


if ( preg_match ($pattern_ybp, $info_rec) )
    $return = 'yes';
else
    $return = 'no';

$length = strlen ($return);


    // Return response.
header ("Status: 200 OK");
header ("Content-type: text/html; charset: UTF-8");
header ("Content-Length: $length");

echo $return;
exit(0);

?>

