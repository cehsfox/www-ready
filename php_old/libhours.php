<?php

    $hours = file_get_contents_curl ("https://api3.libcal.com/api_hours_today.php?iid=446&format=json&systemTime=0&lid=0");
    echo $hours;



// https://stackoverflow.com/questions/26148701/file-get-contents-ssl-operation-failed-with-code-1-failed-to-enable-crypto

function file_get_contents_curl( $url )
{
  $ch = curl_init();

  curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
  curl_setopt( $ch, CURLOPT_HEADER, 0 );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt( $ch, CURLOPT_URL, $url );
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );

  $data = curl_exec( $ch );
  curl_close( $ch );

  return $data;
}

?>
