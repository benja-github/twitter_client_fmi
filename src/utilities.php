<?php

require("db.php");

function writeToLog($source, $message, $debug=true) {

  $sql= "INSERT INTO log_table (source, message) VALUES (:source,:message)";
  $prms=array(':source'=>$source,':message'=>$message);
  if ($debug) echo "<br>$source - $message";
  $result=db_execute($sql,$prms);

}

function db_execute ($sql, $prms = array()) {
  
  $db = feedmyinsight\DB::getConnection();
  $stmt = $db->prepare($sql);
  
  try
  {
    $stmt->execute($prms);
  }
  catch(\PDOException $e)
  {
    echo '<br>Caught exception: '.$e->getMessage();
  }
  $count = $stmt->rowCount();  
  
  try{
    $rows = $stmt->fetchAll();
  }
  catch(\PDOException $e)
  {
    $rows=null;
    //echo '<br>Caught exception: '.$e->getMessage();
    
  }
  
  $stmt->closeCursor();

  if (isset($rows)) {return $rows;} 
  else {return $count;}
  
}

function get_timezone_offset($remote_tz, $origin_tz = null) {
    if($origin_tz === null) {
        if(!is_string($origin_tz = date_default_timezone_get())) {
            return false; // A UTC timestamp was returned -- bail out!
        }
    }
    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime("now", $origin_dtz);
    $remote_dt = new DateTime("now", $remote_dtz);
    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
    return $offset;
}

function fmiTimeStamp($float=true)
{
  return round(microtime($float),3)*1000;
}

function date_to_fmiTimeStamp($date)
{
  $dt=null;
  $type=(get_class($date)?get_class($date):gettype($date));
  switch ($type)
  {
    case "Date":
      $dt = new DateTime($date);
      break;
    case "DateTime":
      $dt=$date;
      break;
    case "string":
      $dt= new DateTime($date);
      break;  
  }
  $ts=(string)$dt->getTimestamp().".".substr($dt->format('u'),0,3);
  $ts=$ts*1000;
  //$ts=(int)$ts;
  //$ts=(int)substr($dt->format('YmdHisu'),0,18);
  return $ts;
}

function fmiTimeStamp_to_date($ts)
{ 
  $ts2=$ts/1000;
  $s=floor($ts2);
  $ms=str_pad(substr(round($ts2-$s,3),2),3,'0');
  $md=date('Y-m-d H:i:s',$s);
  $md.=".$ms";
  $dt=new DateTime($md);
  //echo "<br>date: ".$dt->format('Y-m-d H:i:s.u');
  return $dt;
}

function get_earliest_tweet_id($timelaginseconds)
{
  $tweetid=null;
  $since= new datetime("now");
  $since->sub(new DateInterval('PT'.$timelaginseconds.'S'));
  $tzUTC = new DateTimeZone('UTC');
  $since->setTimezone($tzUTC); 
  $sql = 'SELECT max(a.uid) uid from (select max(uid) uid from tweets where inserted <= :since union select min(uid) uid from tweets where inserted > :since2) a';
  $prms = array(':since'=>$since->format('Y-m-d H:i:s'),':since2'=>$since->format('Y-m-d H:i:s'));
  
  $rows=db_execute($sql,$prms);
  if (count($rows)>0)
  {
    $tweetid=$rows[0]['uid'];
  } 
  return $tweetid;
}

function saveSessionValue($key, $value, $sid=null)
{
  $sql='INSERT INTO sessions (uid, id, value) VALUES (:uid, :key, :value) '
    .'ON DUPLICATE KEY UPDATE id=:key2, value=:value2';
  
  if ($sid==null) {$sid=session_id();}
  $val=base64_encode(serialize($value));
  
  $prms=array(':uid'=>$sid,
              ':key'=>$key,
              ':value'=>$val,
              ':key2'=>$key,
              ':value2'=>$val);
  
  
  db_execute($sql, $prms);
  
}

function getSessionValue($key, $sid=null)
{
  $sql='SELECT value FROM sessions WHERE id = :key and UID = :uid';
  if ($sid==null) {$sid=session_id();}
 
  $prms=array(':uid'=>$sid,
              ':key'=>$key);
  $value=null;
  $rows=db_execute($sql,$prms);
  if (count($rows)>0)
  {
    $val=$rows[0]['value'];
    $value=unserialize(base64_decode($val));
  } 
 
  return $value; 
}

function saveConfigValue($key, $value)
{
  $sql='INSERT INTO config_table (config_key, config_value) VALUES (:key, :value) '
    .'ON DUPLICATE KEY UPDATE config_value=:value2';
  
  $prms=array(':key'=>$key,
              ':value'=>$value,
              ':value2'=>$value);
  
  
  db_execute($sql, $prms);
  
}

function getConfigValue($key)
{
  $sql='SELECT config_value FROM config_table WHERE config_key = :key';
 
  $prms=array(':key'=>$key);
  $value=null;
  $rows=db_execute($sql,$prms);
  if (count($rows)>0)
  {
    $value=$rows[0]['config_value'];
  } 
 
  return $value; 
}

function curl_post_async($url, $params)
{
    // source here: http://petewarden.typepad.com/searchbrowser/2008/06/how-to-post-an.html
    $funcname='curl_post_async';
    writeToLog($funcname,"calling $url");
    foreach ($params as $key => &$val) {
      if (is_array($val)) $val = implode(',', $val);
        $post_params[] = $key.'='.urlencode($val);
    }
    $post_string = implode('&', $post_params);
    
    $parts=parse_url($url);
    echo "<br>opening socket with::<br>";
      var_dump($parts);      
    
    $fp = fsockopen($parts['host'],
        isset($parts['port'])?$parts['port']:80,
        $errno, $errstr, 30);

    if (!$fp) {
    echo "<br>$funcname::failed to open socket to $url::$errstr ($errno)<br>";
      }

    echo "<br>fp:$fp/".($fp!=0);
    //if ($fp!=0) writeToLog($funcname, "Couldn't open a socket to ".$url." (".$errno."::".$errstr.")");
    
    stream_set_blocking ( $fp, 0);

    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
    $out.= "Host: ".$parts['host']."\r\n";
    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out.= "Content-Length: ".strlen($post_string)."\r\n";
    $out.= "Connection: Close\r\n\r\n";
    if (isset($post_string)) $out.= $post_string;
    
    echo "<br>out:$out/";
    
    $bytes=fwrite($fp, $out);
    echo "<br>done:";
    while (!feof($fp)) {
        echo fgets($fp, 128);
    }
    //echo "<br>bytes written::$bytes";
    //$ret=fgets($fp);
    echo "<br>done::";
    fclose($fp);
    
}

function start_twitter_feed($url)

{
  
  $funcname="start_twitter_feed";
  $result=getConfigValue('CONSUMINGTWITTER');
  if (strtolower($result) !== 'true')
  {
    writeToLog($funcname,"Starting Twitter Feed");
    $prms= array('since'=>'2012-12-13 09:09:09');
    $url="http://".$_SERVER['SERVER_NAME'].$url;
    curl_post_async($url,$prms);

  }
 else
  {
    writeToLog($funcname,"Already Consuming. Feed not started.");
  }

}

function stop_twitter_feed()
{
  $funcname="stop_twitter_feed";
  $result=db_execute("insert into config_table (config_key, config_value) values ('CONTINUETWITTER','false') "
    ."ON DUPLICATE KEY UPDATE config_value = 'false' ");
  writeToLog($funcname,"Twitter feed stopped.");
}


?>