<?php
   


function cgets($ch, $string) {

    $funcname="consume.php::cgets";
    global $i;
    //var_dump($string);
    $i=$i+1;
    $tweet=json_decode($string);
    $length = strlen($string);
    
    if (isset($tweet->{'id'}))
    {writeToLog($funcname,"($i) tweet id_str: ".$tweet->{'id_str'});}
    else
    {
      var_dump($string);
      writeToLog($funcname,"($i) non-tweet returned: ");
      return $length;
    }
   
    //var_dump($tweet);
            
    //FROM Sat Dec 08 21:27:45 +0000 2012
    //TO      YYYY-MM-DD HH:mm:SS
    $ca=explode(" ",$tweet->{'created_at'});
    $d=$ca[2];
    $m=$ca[1];
    $y=$ca[5];
    $time=$ca[3];
    $dt=strtotime("$y-$m-$d $time");
    $created_at=date('Y-m-d H:i:s',$dt);
    $latitude=null;
    $longitude=null;
    if (isset($tweet->{'coordinates'})) 
    {
        $coords=$tweet->{'coordinates'}->{'coordinates'};
        $latitude=$coords[1];
        $longitude=$coords[0];
    }
    
    $tweetid = $tweet->{'id_str'};
    $place=null;
    //if (isset($tweet->{'place'})){
    //  $place= $tweet->{'place'}->{'id'};
    //  }
    $created_at=$created_at;
    $inserted = date("Y-m-d H:i:s");

    //echo "inserted $inserted";
    
    //We store the new post in the database, to be able to read it later
    $sql= "INSERT INTO tweets (tweetid, latitude, longitude, place, created_at, json, inserted) "
          ."VALUES (:tweetid, :latitude, :longitude, :place, :created_at, :json, :inserted) "
          ."ON DUPLICATE KEY UPDATE latitude=:latitude2, longitude=:longitude2, place=:place2, created_at=:created_at2, json=:json2, inserted=:inserted2";
    $prms = array(
                  ":tweetid"=>$tweetid,
                  ":latitude"=>$latitude,
                  ":longitude"=>$longitude,
                  ":place"=>$place,
                  ":created_at"=>$created_at,
                  ":json"=>$string,
                  ":inserted"=>$inserted,
                  ":latitude2"=>$latitude,
                  ":longitude2"=>$longitude,
                  ":place2"=>$place,
                  ":created_at2"=>$created_at,
                  ":json2"=>$string,
                  ":inserted2"=>$inserted,
                  );
     $result=db_execute($sql,$prms);
    
    if ($i % 10 === 0 ) {

      $sql= "DELETE FROM tweets where inserted < :inserted - INTERVAL 1 HOUR";
      $prms = array(':inserted'=>$inserted);
      $result=db_execute($sql,$prms);
      writeToLog($funcname,"deleted ".$result." tweets older than $inserted - INTERVAL 1 HOUR");

       $result=db_execute("select config_value from config_table where config_key = 'CONTINUETWITTER'");
      if (strtolower($result[0]['config_value']) !== 'true')
      {
        writeToLog($funcname,"ContinueTwitter set to false.  Exiting after $i tweets");
        $sql="INSERT INTO config_table (config_key, config_value) values (:config_key, :config_value) "
              ."ON DUPLICATE KEY UPDATE config_value = :config_value2";
        $prms=array(':config_key' => 'CONSUMINGTWITTER',':config_value'=> 'false',':config_value2'=> 'false'); 
  
        $result=db_execute($sql,$prms);
        $length=0;
      }
      else
      {
        writeToLog($funcname,"ContinueTwitter set to true.  Continuing after $i tweets");
      }
    }
    
    flush();
    return $length;
}

  $funcname="consume.php";
  date_default_timezone_set('UTC');
  $start=time();

  //include_once($_SERVER['DOCUMENT_ROOT']."/twitteroauth-master/twitteroauth/twitteroauth.php");
  include_once(__DIR__."/twitteroauth-master/twitteroauth/twitteroauth.php");
  include_once(__DIR__."/src/utilities.php");

  writeToLog($funcname,"Starting ".date('hh:mm:ss',$start));
  $i=0;
  $val=getConfigValue('CONSUMINGTWITTER');
  if ($val !== 'false')
  {
    writeToLog($funcname,"Already running (CONSUMINGTWITTER = $val).  Exiting.");
  }
  else
  {
  
    saveConfigValue('CONSUMINGTWITTER','true');
      
    $oauth_token='747336924-dL6y56IwSw1WU9I4JLejpfUAxinAo2SnsOP5He22';
    $oauth_token_secret='i1pYzSBghe659C1GsZZGEZecLttMxPXNeegVIWYQac';
    $consumer_key='VpgFiEiS8inZRESXkuKag';
    $consumer_token_secret='EuZ9wjA1rXRVkRHoWYr6kGkbIa1gGCYATQq0AwODnbA';
     
    // create new instance                    
    $tweet = new TwitterOAuth($consumer_key, $consumer_token_secret, $oauth_token, $oauth_token_secret);
    $tweet->callback='cgets'; 
    $tweet->decode_json=false;
    $tweet->connecttimeout=10;
    $tweet->timeout=120;
    
    $poststart=time();
    
    writeToLog($funcname,"poststart ".date('h:n:s',$poststart));
    
    // TWITTER TAKES LONG-LAT NOT LAT-LONG!!!! //
    $locs='-58.924,-34.915,-58.133,-34.396'; //BA
    //$locs='-0.489,51.28,0.236,51.686'; //LDN 
    $tweet->post('statuses/filter', array('locations' => $locs));
    
    $postend=time();
    writeToLog($funcname,"postend ".date('h:n:s',$postend));
    $curlruntime=$postend-$poststart;
    writeToLog($funcname,"Finished. Curl ran for: $curlruntime seconds (timeout set at ".$tweet->timeout.")");
   
    saveConfigValue('CONSUMINGTWITTER','false');
    $val=getConfigValue('CONTINUETWITTER');
    if (strtolower($val) !== 'true')
    {
      writeToLog($funcname,"ContinueTwitter set to false.  Exiting after $i tweets");
    }
    else
    {
       writeToLog($funcname,"ContinueTwitter set to true.  Continuing");
       start_twitter_feed($_SERVER["REQUEST_URI"]);
    }
   
  }
?>