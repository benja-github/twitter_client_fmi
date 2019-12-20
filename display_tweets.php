<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/jquery-ui.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCKx3rOPLHtvrcyFpqzxeQjSZ_X9A6a9A8&sensor=false" type="text/javascript"></script>
    <script src="http://www.feedmyinsight.com/scripts/jstz-1.0.4.min.js" type="text/javascript"></script>
    <script src='http://www.feedmyinsight.com/wp-content/themes/mosaic-fmi/gmaps.js?x=<?php echo rand(0,100) ?>' type="text/javascript"></script>
    <script src='http://www.feedmyinsight.com/wp-content/themes/mosaic-fmi/my_get_tweets.js?x=<?php echo rand(0,100) ?>' type="text/javascript"></script>
 </head>
<body>
<?php
  echo '<div id="tweets" data-since="0"></div>';
  echo '<table>';
  echo '<tr><td>';
  echo '<div id="map_canvas"  style="height: 600px; width: 700px; position: relative; margin-left: auto; margin-right: auto;"></div>';
  echo '</td><td>';
  echo '<div id="list_canvas"  style="height: 600px; width: 225px; position: relative; margin-left: auto; margin-right: auto; overflow:auto;"></div>';
  echo '</td><tr>';
  echo '</table>';
?>
</body>
</html> 