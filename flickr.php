<?php

  include_once(__DIR__."/src/utilities.php");
  include_once(__DIR__."/src/flickrOauth.php");
  
  if (isset($_REQUEST['oauth_verifier']))
  {
    $fo=getSessionValue('flickrOauth',$_COOKIE['PHPSESSID']);
    $fo->oauth_verifier=$_REQUEST['oauth_verifier'];
    $fo->getAccessToken();
   
  }
  else
  {
    session_start();
    $sid = session_id();
    $fo = new flickrOauth;  
    //$fo->callbackURL = "http://localhost/feedmyinsight/cgi-bin/flickr.php";
    $fo->callbackURL = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $fo->getRequestToken();
    saveSessionValue('flickrOauth',$fo,$sid);
    header("Location: http://www.flickr.com/services/oauth/authorize?oauth_token=$fo->oauth_token&perms=read");
  } 
  
  saveSessionValue('flickrOauth',$fo);
  
  if (isset($_REQUEST['oauth_verifier']))
  {
    $fo->authQuery('http://api.flickr.com/services/rest','flickr.test.login','json','1');
    /*
    http://api.flickr.com/services/rest/?
      method=flickr.photos.search&
      api_key=d059a3c5aef4abb5f88ab344815bce0c&
      bbox=-0.489%2C51.28%2C0.236%2C51.686&
      format=rest&
      api_sig=bd2f94d85254a758ca1f3e71b8f11453
    */
    //BA -34.915,-58.924,-34.396,-58.133
    //LDN -0.489,51.28,0.236,51.686
    $bbox='-34.915,-58.924,-34.396,-58.133'; //BA
    $bbox='-0.489,51.28,0.236,51.686'; //LDN
    
    $prms=array('bbox'=>$bbox,
          'extras'=>'geo,media,media_status,owner_name,icon_server');
    
    $json=$fo->publicQuery('http://api.flickr.com/services/rest','flickr.photos.search','json','1',$prms);
    $response=json_decode($json);
    $photos=$response->{'photos'}->{'photo'};
  
    /*
    http://farm{farm-id}.staticflickr.com/{server-id}/{id}_{secret}.jpg
    	or
    http://farm{farm-id}.staticflickr.com/{server-id}/{id}_{secret}_[mstzb].jpg
    	or
    http://farm{farm-id}.staticflickr.com/{server-id}/{id}_{o-secret}_o.(jpg|gif|png)
    
    see here for more details:
        http://code.flickr.net/2008/08/19/standard-photos-response-apis-for-civilized-age/ 
        http://www.flickr.com/services/api/misc.urls.html
        http://www.flickr.com/services/api/misc.buddyicons.html - for buddyicon info
    
    { 
      ["id"]=> string(10) "8404718893" 
      ["owner"]=> string(12) "59358584@N06" 
      ["secret"]=> string(10) "cd34f7332b" 
      ["server"]=> string(4) "8221" 
      ["farm"]=> int(9) 
      ["title"]=> string(6) "upload" 
      ["ispublic"]=> int(1) 
      ["isfriend"]=> int(0) 
      ["isfamily"]=> int(0) 
      ["latitude"]=> float(51.511622)  - geo
      ["longitude"]=> float(-0.077297) - geo 
      ["accuracy"]=> string(2) "16"    - geo 
      ["context"]=> int(0) 
      ["place_id"]=> string(18) "v70Y8qlTUrrLiiTZAg" - geo
      ["woeid"]=> string(8) "20094363" - geo
      ["geo_is_family"]=> int(0)       - geo 
      ["geo_is_friend"]=> int(0)       - geo
      ["geo_is_contact"]=> int(0)      - geo
      ["geo_is_public"]=> int(1)       - geo
      ["media"]=> string(5) "photo"    - media
      ["media_status"]=> string(5) "ready" - media_status   
      ["ownername"]=> string(13) "Damian Corbet" - ownername 
      ["iconserver"]=> string(4) "8340"  - icon_server
      ["iconfarm"]=> int(9) - icon_server
    }
    
    */
    foreach($photos as $p)
    {
      //echo "<br>".$p->{'id'}."::".$p->{'title'};
      //echo "<br>".var_dump($p);
      $src="http://farm".$p->{'farm'}.".staticflickr.com/".$p->{'server'}."/".$p->{'id'}."_".$p->{'secret'}."_m.jpg";
      $title=$p->{'title'};
      $href="http://flickr.com/photo.gne?id=".$p->{'id'};
      echo "<a href='$href'>";
      if ($p->{'media'}=='photo')
        {echo "<img src='$src' title='$title'>";}
      else
        {echo "<img video='$src' autoplay=false>";}
      echo "</a>";
      /*
      if ($p->{'iconserver'}=='0') 
      {
        $bi_url="http://www.flickr.com/images/buddyicon.gif";
      }
      else 
      {
      $bi_url= "http://farm".$p->{'iconfarm'}.".staticflickr.com/".$p->{'iconserver'}."/buddyicons/".$p->{'owner'}.".jpg";
      }
      $title=$p->{'ownername'};
      echo "<img src='$bi_url' title='$title'>";
      */
    }
  }
  
?>
