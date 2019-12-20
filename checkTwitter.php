<?php

     include_once($_SERVER['DOCUMENT_ROOT']."/scripts/feedmyinsightConfiguration.php");
    $config = feedmyinsightConfiguration::getInstance();
   $running=false;
   $running = $config->get('CONSUMINGTWITTER'); 
   echo "<br>ConsumingTwitter set to ".$running;
   exit;

?>