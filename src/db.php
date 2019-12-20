<?php

//db connection class using singleton pattern
namespace feedmyinsight;

class DB{
 
//variable to hold connection object.
protected static $db;
protected $debug=false;

protected static $_host="feedmyinsightcom.ipagemysql.com";
protected static $_user="feedmyinsight";
protected static $_pass="f33dmy1ns1@hT";
protected static $_dbname="feedmyinsight"; 

//protected static $_host="localhost";
//protected static $_user="root";
//protected static $_pass="MySQLRoot";
//protected static $_dbname="feedmyinsight"; 
 
//private construct - class cannot be instatiated externally.
private function __construct() {
 
  try {
      // assign PDO object to db variable
      self::$db = new \PDO("mysql:host=".self::$_host.";dbname=".self::$_dbname, 
                  self::$_user, 
                  self::$_pass, 
                  array(  \PDO::ATTR_PERSISTENT => true, 
                          \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", 
                          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
                                  
    }
    catch (\PDOException $e) {
      //Output error - would normally log this to error file rather than output to user.
      echo "Connection Error: " . $e->getMessage();
    }
     
  }
   
  // get connection function. Static method - accessible without instantiation
  public static function getConnection() {
   
    //Guarantees single instance, if no connection object exists then create one.
    if (!self::$db) {
      //new connection object.
      new DB();
    }
   
    //return connection.
    return self::$db;
  }

} 

/*
  public static function Execute($table,$record=array())
{

  $cols=null;
  $vals=null;
  $update=null;
  $prms=array();
  $prmtypes=null;
  $coltypes=array();
  
  $coltypes = db_getcoldatatypes($table);
  
  foreach($record as $key => $val )
  {
    $cols=$cols.", ".$key;
    $vals=$vals.", ?";
    $update=$update.", ".$key." = ?";
    $prms[]=$val;
    $prmtypes=$prmtypes.getparamtype($coltypes[$key]);
      
  }
  
  $cols=preg_replace("/^, /","",$cols);
  $vals=preg_replace("/^, /","",$vals);
  $update=preg_replace("/^, /","",$update);
  
  $prms=array_merge($prms,$prms);
  $prmtypes=$prmtypes.$prmtypes;
  
  $sql= "INSERT INTO ".$table." (".$cols.") VALUES (".$vals.") ON DUPLICATE key UPDATE ".$update;
  $mysqli=db_connect();
  $query = $mysqli->prepare($sql);
  writeToLog("in db_save with: ".$sql."::".implode($prms,", "));
  $refArr = array_merge(array($prmtypes),$prms);
  
  $tmp = array();
  foreach($refArr as $key => $value) $tmp[$key] = &$refArr[$key];
  call_user_func_array(array($query, 'bind_param'), $tmp);
  
  $query->execute();
  $id=$mysqli->insert_id;
  writeToLog("error: ".$mysqli->error);
  writeToLog("inserted record id: ".$id);
  $query->close();
  $mysqli->close();
  return $id; 

}

function db_delete($table, $record) {
  
  $cols="";
  $vals="";
  
  $mysqli=db_connect();
  
  $coltypes = db_getcoldatatypes($table);
 
  $prmtype="";
  $prms = array();
  $predicate="";
   
  foreach($record as $key => $value )
     {
       
        $predicate=$predicate." AND ".$key." = ?";
        $prmtype=$prmtype.getparamtype($coltypes[$key]);
        $prms[]=$value;
                
     }

  $predicate=preg_replace("/^ AND/"," WHERE",$predicate);
    
  $qrytxt="DELETE FROM ".$table.$predicate;  
 
  $query = $mysqli->prepare($qrytxt);
  $refArr = array_merge(array($prmtype),$prms);
  $tmp = array();
  
  foreach($refArr as $key => $value) $tmp[$key] = &$refArr[$key];
  call_user_func_array(array($query, 'bind_param'), $tmp);
  
  $query->execute();
  writetolog("db_delete.error::".$mysqli->error);
  $query->close();
  $mysqli->close();
  return "1";
  }

function db_select($table, $record = null) {
       
  $predicate="";
  $result;
  $num; 
  $prmtype="";
  $selcols="";
                
  $mysqli=db_connect();

  $coltypes = db_getcoldatatypes($table);
      
  if (isset($record))
   {
     
     foreach($record as $key => $value )
     {
       if (strlen($value)>0)
       {
     
         $predicate=$predicate." AND ".$key."=?";
         $prmtype=$prmtype.getparamtype($coltypes[$key]);
         if (is_null($value)) {$prms[]='null';}
         else {$prms[]=$value;}
       }
     
       $row[$key]=$value;
       $selcols=$selcols.", ".$key;
     }
           
     if (strlen($predicate)>0) $predicate=preg_replace("/^ AND/"," WHERE",$predicate);
     if (strlen($selcols)>0) $selcols=preg_replace("/^,/","",$selcols);
   }
               
  $qrytxt="SELECT".$selcols." FROM ".$table.$predicate;  
 
  $query = $mysqli->prepare($qrytxt);
  
  writeToLog("query: ".$mysqli->error);
   
  if (isset($prms))
    {$refArrIn = array_merge(array($prmtype),$prms);
     $tmpIn = array();
     foreach($refArrIn as $key => $value) $tmpIn[$key] = &$refArrIn[$key];
      call_user_func_array(array($query, 'bind_param'), $tmpIn);
    }
   
  writeToLog("about to run ".$qrytxt." with ".implode($row,", "));  
  $query->execute();
      
  $tmpOut = array();
  foreach($row as $key => $value) $tmpOut[$key] = &$row[$key];
  call_user_func_array(array($query, 'bind_result'), $tmpOut);
          
  $results=array();
  $resultRow=array();
  while ($query->fetch())
  {
    reset($resultRow); $resultRow=array();
    foreach($row as $key => $value)
    {
      $resultRow[$key]= $value;
    }  
    $results[] = $resultRow;
  }
 
  return $results;
   
}

function db_runquery($query)
{

  //writeToLog("db_runquery with ".$query);
  $mysqli = db_connect();
  
  $result = $mysqli->query($query);
  //writeToLog("db_runquery result count: ".$result->num_rows);
  
  return $result;

}

function getparamtype($mysqltype)
{

  $result;
  switch ($mysqltype) {
    case "int":
      $result="i";
      break;
    case "varchar":
      $result="s";
      break;
    case "timestamp":
      $result="i";
      break;
    default:
      $result="s";
 }
  
  return $result;
}

function writeToLog($msg)
{
  global $debug;
  if ($debug) echo "<br>".$msg; 
}

function db_getcoldatatypes($table)
{

  //writeToLog("db_getcoldatatypes with ".$table);

  $query="SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY FROM v_keycols WHERE table_name = '".$table."'"; 
     
  $result = db_runquery($query);
  
  $cdts = array();
  
  while ($row = $result->fetch_assoc()) 
  {
      $cdts[$row["COLUMN_NAME"]] = $row["DATA_TYPE"];
  }
  return $cdts;
  
}

function db_getkeycols($table)
{

  $query="SELECT COLUMN_NAME FROM v_keycols WHERE COLUMN_KEY = 'PRI' and table_name = '".$table."'"; 
  writeToLog("db_keycols: ".$query);
     
  $result = db_runquery($query);
  //writeToLog("keycols: ".$result);
  return $result;
  
  }               
}//end class
*/ 
?>