<?php

class flickrOauth
{
  
  public $consumer_key;
  public $consumer_secret;
  public $oauth_token;
  public $oauth_token_secret;
  public $oauth_verifier;
  public $sig_method;
  public $oversion;
  public $callbackURL;
  //public $nonce;
  public $requestURL;
  public $oauth_signature;    
  public $cc_secret;
  public $perm;
  public $username;
  public $user_nsid;
  public $fullname;
  //public $timestamp;
  
  public function __construct($c_key=null,$c_secret=null)
  { 
    $this->consumer_key = 'd74950cba80067f29fb92350ee20773d';
    $this->consumer_secret = '35502fd513d2f041';
    $this->sig_method = "HMAC-SHA1";
    $this->oversion = "1.0";
    
    if (isset($c_key)) $this->consumer_key = $c_key;
    if (isset($c_secret)) $this->consumer_secret = $c_secret;
    
  }
  
  public function nonce()
  {
   
   $mt = microtime();
   $rand = mt_rand();
   $oauth_nonce = md5($mt . $rand);
   return $oauth_nonce; 
  }
  
  public function timestamp()
  {
  $ts = gmdate('U'); //It must be UTC time
  return $ts;
  }
  
  public function oauth_signature($basestring)
  {
     /*
      use $hashkey (consumer_secret + & + token_secret) to generate oauth_signature
        using (for flickr) sha1 encoding, as follows:
        $oauth_signature = hash_hmac('sha1', $basestring, $hashkey, true);
        $oauth_signature = base64_encode($oauth_signature);
        NB to get request token, we don't have oauth_token_secret,
        so $hashkey is consumer_secret + & 
    */
     // Create $hashkey (see 1. above)
    $hashkey = "$this->consumer_secret&$this->oauth_token_secret";
    // Create $oauth_signature using $basesecret & $haskey
    $oauth_signature = hash_hmac('sha1', $basestring, $hashkey, true);
    // Encode $oauth_signature
    $oauth_signature = base64_encode($oauth_signature);
    // Add to $fields array
    return $oauth_signature;
  }
  
  public function HTTP_fields($fields)
  {
    // Create HTTP param string from $fields array
    $fields_string = "";
    foreach($fields as $key=>$value) $fields_string .= "$key=".urlencode($value)."&";
    $fields_string = rtrim($fields_string,'&');
    return $fields_string;
  }  
  
  public function getRequestToken()
  {
    $url = "http://www.flickr.com/services/oauth/request_token";
    //Despite what Flickr API eg shows, need to pass consumer key & secret
    //as well as token key & secret
    $fields = array(
      'oauth_callback'=>$this->callbackURL,
      'oauth_consumer_key'=>$this->consumer_key,
      'oauth_nonce'=>$this->nonce(),
      'oauth_signature_method'=>$this->sig_method,
      'oauth_timestamp'=>$this->timestamp(),
      'oauth_version'=>$this->oversion
      );
  
    /* We need basestring to get oauth_signature.
    $basestring = (HTTP Method+URL+request parameters) and 
    */
    $response=$this->_query($url,$fields);
    $rsp_arr = explode('&',$response); 
    $ta=array();
    array_walk($rsp_arr,function($val, $key) use(&$ta)
      {$kv=explode("=",$val); $ta[$kv[0]]=$kv[1];});
    
    $this->oauth_token=$ta['oauth_token'];
    $this->oauth_token_secret=$ta['oauth_token_secret'];
    //$this->_query();
    
 }
  
 public function getAccessToken()
  {
    //Despite what Flickr API eg shows, need to pass consumer key & secret
    //as well as token key & secret
    $url = "http://www.flickr.com/services/oauth/access_token";
    $fields = array(
      'oauth_consumer_key'=>$this->consumer_key,
      'oauth_consumer_secret'=>$this->consumer_secret,
      'oauth_nonce'=>$this->nonce(),
      'oauth_signature_method'=>$this->sig_method,
      'oauth_timestamp'=>$this->timestamp(),
      'oauth_token' =>$this->oauth_token,
      'oauth_token_secret' =>$this->oauth_token_secret,
      'oauth_verifier'=>$this->oauth_verifier,
      'oauth_version'=>$this->oversion
      );
  
    $response=$this->_query($url,$fields);
    $rsp_arr = explode('&',$response); 
    $ta=array();
    array_walk($rsp_arr,function($val, $key) use(&$ta)
      {$kv=explode("=",$val); $ta[$kv[0]]=$kv[1];});
    $this->fullname=$ta['fullname']; 
    $this->oauth_token=$ta['oauth_token'];
    $this->oauth_token_secret=$ta['oauth_token_secret'];
    $this->user_nsid=$ta['user_nsid'];
    $this->username=$ta['username'];
    
 } 
 
  public function authQuery($url='http://api.flickr.com/services/rest', $method='flickr.test.login',$format='json',$nojsoncallback='1',$prms=array())
  {
    $fields=array('format'=>$format,
      'method'=>$method,
      'nojsoncallback'=>$nojsoncallback,
      'oauth_consumer_key'=>$this->consumer_key,
      'oauth_consumer_secret'=>$this->consumer_secret,
      'oauth_nonce'=>$this->nonce(),
      'oauth_signature_method'=>$this->sig_method,
      'oauth_timestamp'=>$this->timestamp(),
      'oauth_token'=>$this->oauth_token,
      'oauth_token_secret'=>$this->oauth_token_secret,
      'oauth_version'=>$this->oversion
      );
      
      foreach($prms as $key=>$val)
      {
        $fields[$key]=$val;
      }
  
      //var_dump($fields);
      $rsp_arr = $this->_query($url,$fields);
      //echo "<br>";
      //var_dump($rsp_arr);
      return $rsp_arr;
      
    }

  public function publicQuery($url='http://api.flickr.com/services/rest', $method='flickr.test.login',$format='json',$nojsoncallback='1',$prms=array())
  {

    $fields=array('format'=>$format,
      'method'=>$method,
      'oauth_consumer_key'=>$this->consumer_key,
      'nojsoncallback'=>$nojsoncallback
      );
      
      foreach($prms as $key=>$val)
      {
        $fields[$key]=$val;
      }
  
      //var_dump($fields);
      $rsp_arr = $this->_query($url,$fields);
      return $rsp_arr;
      
    }
  
  private function _query($url,$fields=array())
  {
      
      $this->request_url=$url;
      $basestring = "GET&".urlencode($this->request_url)."&".urlencode(http_build_query($fields));
      $fields['oauth_signature']=$this->oauth_signature($basestring);
      // Create HTTP param string from $fields array
      $fields_string = $this->HTTP_fields($fields);
      
      $url = $this->request_url."?".$fields_string;
      $rsp_arr = $this->execCURL($url);
      return $rsp_arr;
      
  }    

private function execCURL($url,$timeout=5)
{
  
  $ch = curl_init(); 
  curl_setopt ($ch, CURLOPT_URL, $url); 
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
  $file_contents = curl_exec($ch); 
  curl_close($ch); 
  //$rsp_arr = explode('&',$file_contents); 
  //return $rsp_arr;
  return $file_contents;
}

}

?>