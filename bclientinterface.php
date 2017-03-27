<?php
//include_once("define.php");

//----test------------------------------

//$s1=mktime(0,0,0,1,1,2014);
//$e1=mktime();
//$tst1='{"length":"4","CMD":"17","username":"12362" ,"time1":1388505600,"time2":1392007674,"stationid":"2" ,"telenumber":"13766655454" }';
//$tst1='{"length":"5","CMD":"5","username":"12362" ,"P1":"上京史蒂芬森大家","P2":"SJFDGJSAGFJSDFA几十个附加费等国1233际" }';
//$tst1='{"length":"6","CMD":"13","username":"13111111111","name":"aa","area":"bb","company":"wwww"  }';"PID1":"23","P1":"上京史蒂芬森大家"

//$tst1='{"length":"9","CMD":"13","username":"13111111111","password":"123","message":"asd","rcvnumber1":"13564567859","location1":"1","stationid1":"2","frequency1":"1"}';
//echo  "aaa$tst1";
//$json1=base64_encode($tst1);
//---------------------------------------
//$json=$_POST["dy"];
//==================================================
// 
//
//==================================================

function  bclientinterface($json)
{

$json1=str_replace(" ","+",$json);

$json2=base64_decode($json1);

$json2=preg_replace("/\s/","",$json2);  //去掉回车符

//$json2='{"method":"login","account":"201411152147" ,"password":"123124","cmdresponse":"eeee" }';




$json3=json_decode($json2,true); 

$method=$json3["method"]; //得到函数名称
	
switch($method)
{
case  "login":
      $res=login($json3["account"],$json3["password"],$json3["cmdresponse"]);
break;

case  "ConfirmDeliveryExRequest":
    	  
	  $itemId=$json3["itemId"];
	  $terminalId=$json3["terminalId"];
	  $boxId=$json3["boxId"];	  
	  $operatorId=$json3["operatorId"];	  
	  $localTime=$json3["localTime"];	  
	  $ordersn=$json3["ordersn"];  
	  $starttime=$json3["starttime"];
	  $deliveryInfo=$json3["deliveryInfo"];
	  $infostatus=$json3["infostatus"];	
	  $kuaidi=$json3["kuaidi"];	  	    
	  $main_username=$json3["main_username"];	  	    
	  $res=ConfirmDeliveryExRequest($itemId,$terminalId ,$boxId,$deliveryInfo,$operatorId,$localTime,$ordersn,$starttime,$infostatus,$kuaidi,$main_username);
break;
case  "GetbackItemRequest":	  
	  $itemId=$json3["itemId"];
	  $terminalId=$json3["terminalId"];
	  $boxId=$json3["boxId"];
	  $operatorId=$json3["operatorId"];
	  $localTime=$json3["localTime"];
	  $ordersn=$json3["ordersn"];
	  $endtime=$json3["endttime"];  //注意  endttime  两个t，在webservice接口中没有问题，在json中存在问题
	  $pickup=$json3["pickup"];
	  $status=$json3["status"];
	  $rcvnumber=$json3["rcvnumber"];
	  $infostatus=$json3["infostatus"];	  
	  $main_username=$json3["main_username"];	  
	  $res=GetbackItemRequest($itemId,$terminalId ,$boxId,$operatorId,$localTime,$ordersn,$endtime,$pickup,$status,$rcvnumber,$infostatus,$main_username);
break;
case  "OrderSendMsmAgain":
       $itemId=$json3["itemId"];
	   $terminalId=$json3["terminalId"];
	   $rcvnumber=$json3["rcvnumber"];
	   $operatorId=$json3["operatorId"];
	   $localTime=$json3["localTime"];
	   $ordersn=$json3["ordersn"];
	   $sentmsmflg=$json3["sentmsmflg"];  
	   $main_username=$json3["main_username"];  
       $res=OrderSendMsmAgain($itemId,$terminalId,$rcvnumber,$operatorId,$localTime,$ordersn,$sentmsmflg,$main_username);
break;

case  "UploadSystemSetting":
       $name=$json3["name"];
	   $value=$json3["value"];
	   $devicesn=$json3["devicesn"];
       $res=UploadSystemSetting($name,$value,$devicesn);
break;
case  "UploadSystemLog":
      $logdata=$json3["logdata"];
	  $time=$json3["time"];
	  $devicesn=$json3["devicesn"];
      $res=UploadSystemLog($logdata,$time,$devicesn);
break;
case  "UploadAlarmLog":
      $logdata=$json3["logdata"];
	  $time=$json3["time"];
	  $devicesn=$json3["devicesn"];
      $res=UploadAlarmLog($logdata,$time,$devicesn);
break;
case  "VerifyOperatorRequest":
	   $operatorId=$json3["operatorId"];
	   $password=$json3["password"];
	   $terminalId=$json3["terminalId"];
	   $localTime=$json3["localTime"];	   	   	   
       $res=VerifyOperatorRequest($operatorId,$password,$terminalId,$localTime);
break;

case  "VerifyOperatorRequest1":
	   $operatorId=$json3["operatorId"];
	   $password=$json3["password"];
	   $terminalId=$json3["terminalId"];
	   $localTime=$json3["localTime"];
	   $type=$json3["type"];   	   	   
       $res=VerifyOperatorRequest1($operatorId,$password,$terminalId,$localTime,$type);
break;



case  "TerminalAccountSyncData":
	   $terminalId=$json3["terminalId"];
	   $content=$json3["content"];	   
       $res=TerminalAccountSyncData($terminalId,$content);
break;

case  "shujutongbu":
	   $terminalId=$json3["terminalId"];   
       $res=shujutongbu($terminalId);
break;


}

return  $res;

}




?>
