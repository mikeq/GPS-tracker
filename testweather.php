<?php
    $content = file_get_contents('http://www.worldweatheronline.com/feed/weather.ashx?key=[youweatherkey]&q=55.9439,-3.20383&fx=no&format=json');
?><pre><?php
    print_r(json_decode($content));
?>
</pre>