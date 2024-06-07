<?php

    $hours = file_get_contents ("https://api3.libcal.com/api_hours_today.php?iid=446&format=json&systemTime=0&lid=0");
    echo $hours;

?>
