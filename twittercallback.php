<?php
             
       //echo dirname(__FILE__)."\src\utilities.php";
       include_once(dirname(__FILE__)."/src/utilities.php");
             
       if(!empty($_REQUEST['since'])) {$sinceUID=(integer)$_REQUEST['since'];}
       else {$sinceUID=77800;}
       $sql= "SELECT uid, tweetid, created_at, latitude, longitude, place, inserted, json from tweets where uid > :since order by uid asc limit 2";
       $prms=array(':since'=>$sinceUID);
       
       //$sql= "SELECT uid, tweetid, created_at, latitude, longitude, place, inserted, json from tweets ORDER BY uid DESC LIMIT 2";
       //$prms=array();
       
        //" and latitude between $minLat and $maxLat 
       //and longitude between $minLong and $maxLong";

       $results=db_execute($sql,$prms);
       $rows=array();
       foreach ($results as $row) {
          $rows[]=$row;
          $untilUID=$row['uid'];
       }
      
        $untilUID=(isset($untilUID))?$untilUID:$sinceUID;
        $xrw= !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : 'HTTP_X_REQUESTED_WITH not set'; 
        $tweets = array("xrw"=>$xrw, "since"=>$sinceUID, "until"=>$untilUID,"sql"=>$sql,"tweets"=>$rows);
       //echo "<br>error::".$db->error;

         //end tweet db code
	   //if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
           //{
              
	      $result = json_encode($tweets);
              header('Content-type: application/json');	      
              header('Access-Control-Allow-Origin: *');
              //header('Access-Control-Allow-Credentials: true');
              echo $result;
	   /*}
	   else 
           {
	      header("Location: ".$_SERVER["HTTP_REFERER"]);
	      echo "This is a callback page and cannot be requested directly<br>";
	      $str= (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '$_SERVER["HTTP_X_REQUESTED_WITH"] not set';
	      echo $str;
	   }
	   */
	   die();
?>