<?php   session_start();?>
<?php
include_once("../../dataserve/conn_mysql.php");
include_once("bmainfunction.php");
include_once("bclientinterface.php");
include_once("bfunction.php");

$json=$_POST["box"];

set_time_limit(120); //2·ÖÖÓ
$res=bclientinterface($json);

//   $a= base64_decode($json);
// $r=gzcompress($res, 9); 
//$r=gzdeflate($res,9);
// $r= gzcompress($res, 9);
// $b=gzuncompress($a); 

//$res= gzcompress($res, 9);
echo    base64_encode(urldecode(json_encode($res))); //  
//echo "</br>";
//print_r($res);


?>