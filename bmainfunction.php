<?php
include_once ("../../weixinserve/smsservepp.php");
include_once ("../../sms1/lib/SendMsm.class.php");

//========================================    
// 登录    第三个参数是下发命令的响应
//        下发命令是由本命令来完成的。 本命令是周期命令。 若有下发命令，那么将下发命令随login的响应下发到终端，终端随及执行，然后再下一个login命令中返回响应
//========================================  
	function  login($account,$password,$cmdresponse)
	{	
        
        $db=conn2();
	    //若本设备的session还起作用，且是OK，那么不再核对数据库。
	    if($_SESSION['LOGIN']!="OK")
		{
		    
        	
	    	$result = mysql_query("SELECT id FROM  smtbx_info  where  devicesn='$account' and  devicepw='$password'",$db);  
			$num= mysql_numrows ($result); 
			if($num>0)
			{
		  		$response["responseCode"]="0";
		  		$_SESSION['LOGIN']="OK";
		  		$response["response"]=array("lonin"=>"ok");	
								
			}
			else
			{
		  		$response["responseCode"]="E0002";
		  		$_SESSION['LOGIN']="NOK";
		  		$response["response"]=array("lonin"=>"nok");
						  		
			}
				    				
		}
		else
		{
		  	$response["responseCode"]="0";
		  	$_SESSION['LOGIN']="OK";
		  	$response["response"]=array("lonin"=>"ok");	
		
		}
		if($_SESSION['LOGIN']=="OK")
		{
		    //将link参数达到数据库中
			
		    $result = mysql_query("SELECT link FROM  smtbx_info  where  devicesn='$account' LIMIT 1",$db);  
			$num= mysql_numrows ($result);
			// whl 判断num是否大于零，防止warning警告
			if($num>0){
				$link=mysql_result($result,0,"link");	
			}
			if($link<=0)
			{
			   $time=time();
			   $time0=$time+(int)$link*10-120;  //90
			   
			   mysql_query("INSERT INTO `smtbx_link` (`devicesn` ,`flg`,`time`) VALUES ('$account','2','$time0')",$db);  //记录短线时间，1上线，2断开
               mysql_query("INSERT INTO `smtbx_link` (`devicesn` ,`flg`,`time`) VALUES ('$account','1','$time')",$db);  //记录上线时间
			}  			
		    mysql_query("UPDATE `smtbx_info` SET `link`='18' WHERE  devicesn='$account' LIMIT 1",$db);  //120s 
		
	     	//将本设备的已经发送短信的记录，查询结果，将结果更新在smtbx_order表中
		
		 	//for test 
		 	$terminalId=$account;
			if(($terminalId!="201800000001")
				&&($terminalId!="201800000002")
	    		&&($terminalId!="201800000003")
				&&($terminalId!="201800000004")		
	    		&&($terminalId!="201800000005")
				&&($terminalId!="201800000006")		
	    		&&($terminalId!="201800000007")
				&&($terminalId!="201800000008")			
				&&($terminalId!="201800000009"))    

	    	{
	       		//checkmsmsendresult($account);  //该行为非测试代码    该行不需要了
	    	}
			//for test
			
           
			//返回的命令， 这个函数将处理返回的命令，同时可下发新的命令
			$command=downcommand($account,$cmdresponse);			
		
		}
	
		$response["command"]=$command;//array("cmdcode"=>"111","cmdcontent"=>"");	
		 return  $response;			
	}
//========================================    
// 新订单
//========================================  	
	function ConfirmDeliveryExRequest($itemId,$terminalId ,$boxId,$deliveryInfo,$operatorId,$localTime,$ordersn,$starttime,$infostatus,$kuaidi,$main_username)
	{
	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }
		$db=conn();
	    $db2=conn2();
		$db4=conn4();
		
		// ---预防由于网络等原因，产生重复发送的问题----
       	$sqlstr="SELECT password FROM `smtbx_order` WHERE `ordersn0` ='$ordersn' and `username` ='$operatorId'  and `devicesn` ='$terminalId' LIMIT 1";
		//$sqlstr="SELECT password FROM `smtbx_order` WHERE `ordersn0` ='$ordersn' and  `devicesn` ='$terminalId' LIMIT 1";
        $result0 = mysql_query($sqlstr,$db2);
		$num= mysql_numrows($result0); 		
		if($num!=0)
		{
		   $password=jiemi(mysql_result($result0,0,"password")); 
		   $resstatus=0; //成功返回
		   $response["responseCode"]="$resstatus";
    	   $response["response"]=array("password"=>"$password");
	       return 	$response;					
		}		
		//--------	
						
		//$rcvname=$deliveryInfo->userName;
		//$rcvnumber=$deliveryInfo->mobilePhone;	
		$rcvname=$deliveryInfo[userName];
		$rcvnumber=$deliveryInfo[mobilePhone];			
						   	     	
	
	   //准备发送短信	
	 //获得智能柜的短信内容   
	$sqlstr="SELECT msmcontent FROM `smtbx_info` WHERE `devicesn` LIKE '$terminalId' LIMIT 1";
    $result = mysql_query($sqlstr,$db2);    
	$msmcontent=mysql_result($result,0,"msmcontent"); 
	 //获得投递员账户的短信内容   
	$sqlstr="SELECT smscontent FROM `user` WHERE `username` LIKE '$main_username' LIMIT 1";
    $result = mysql_query($sqlstr,$db);    
	$smscontent=mysql_result($result,0,"smscontent"); 	

     //密码 
	 //for test 
			if(($terminalId!="201800000001")
				&&($terminalId!="201800000002")
	    		&&($terminalId!="201800000003")
				&&($terminalId!="201800000004")		
	    		&&($terminalId!="201800000005")
				&&($terminalId!="201800000006")		
	    		&&($terminalId!="201800000007")
				&&($terminalId!="201800000008")			
				&&($terminalId!="201800000009"))    

	{
	   $password=randpassword();  //没加密   //该行为非测试代码
	}
	else
	{
		$sp="10000";
		$sp1=$sp+$boxId;
		$spw="X".$sp1;
	    $password=$spw;
	}  
	//test		
	
	$md5password=md5($password); //已加密
	$itemIdh="*".substr($itemId,-4);
	$m_content="单号:".$itemIdh.",".$msmcontent.$smscontent;
	
	//-------	
	
    $passwordsql=jiami($password);  //
	
	//订单序列号，
	 $m_date= date('YmdHis');
     $m_time = mb_substr(microtime(), 2,6); 
     $msm_sn=$m_date.$m_time;
	 
	 
  	//发送短信
    //for test 
			if(($terminalId!="201800000001")
				&&($terminalId!="201800000002")
	    		&&($terminalId!="201800000003")
				&&($terminalId!="201800000004")		
	    		&&($terminalId!="201800000005")
				&&($terminalId!="201800000006")		
	    		&&($terminalId!="201800000007")
				&&($terminalId!="201800000008")			
				&&($terminalId!="201800000009"))    
                
	{
		// 判断用户费用是否充足否则不让发。
		$checkres = checkCharge($terminalId,$operatorId,$boxId,$db);
		if($checkres){
	    	//whl 发送短信
	    	$stationid = getStationId($terminalId,$db);
	    	if($stationid){
	    		$res = SendMsm::sendOneSMSforBox($rcvnumber, $m_content, $operatorId, $terminalId, $boxId,'',$stationid,1,$m_content,$itemId,$password,1);
	    		$resjson = json_decode($res,true);
	    		$res = $resjson['status']==0 ? 1 : 0;
	    		$msm_sn = $resjson['msm_sn'];
	    	}else{
	    		$res = 0;
	    	}
			//whl
		}else{
			$res = 0;
		}
    	
	}
	else
	{
	  $res=1;
	}
	// for test

    $resstatus=1; 	 
  
	if($res==1) //发送成功	
	{	
	//写入订单记录
		$sqlstr="INSERT INTO `smtbx_order` ( `devicesn` , `packageID` , `ordersn` , `username` , `rcvnumber` , `starttime` , `sentmsmflg` , `password` , `endtime` , `boxid` , `pickup` , `status` , `uptime`, `sentmsmflgs`, `ordersn0` ) VALUES ( '$terminalId' , '$itemId','$msm_sn', '$operatorId', '$rcvnumber', '$starttime', '0', '$passwordsql', '0', '$boxId', '0', '0', '$localTime', '1','$ordersn')";		
        $result = mysql_query($sqlstr,$db2); 	
	 $resstatus=0;	
//	}  
	
	       //修改boxinfo的状态
	       updateinfostatus($terminalId,$infostatus,$boxId); 
	       //-----------------------------------------------------------------------------------------
	
	      //对运单物流表进行补充更新
	      //首先判断BOX设备是否注册，然后转换出对应的站点账号，若没有注册，就直接跳过这一步
		  
		   $onlinetime=time();
           $result = mysql_query("SELECT * FROM  stations_manage   where  allbox  like '%$terminalId%' ",$db4); 
           
		   $num= mysql_numrows ($result);
		      	   
	       if($num!=0)
		   {
		      $stationaccount=mysql_result($result,0,"account"); //站点账号
		   
		   	   //判断该运单是否存在
		     $result = mysql_query("SELECT id,expressname FROM  logistics  where  stationaccount='$stationaccount'  and  expressno='$itemId'    order by   id   desc      limit 1",$db4);  
		     $num= mysql_numrows ($result);
			 			 
			 
		     if($num==0)
		     {  //在插入状态，将duanxintime同时插入diandantime
		        $sqlstr="INSERT INTO `logistics` (`pdasn`, `stationaccount`,`expressno`,`diandantime`,`diandanuser`,`phonenumber`,`distributeway`,`distributeuser`,`distributetime`,`huohao`,`msm_sn`,`expressname`,`onlinetime`) 
VALUES ('$terminalId','$stationaccount', '$itemId', '$starttime', '$operatorId', '$rcvnumber','2','$operatorId', '$localTime','$boxId','$msm_sn','$kuaidi','$onlinetime')";								  								                mysql_query($sqlstr,$db4); 
 	
		   	  }
		   	  else
		   	  {
			    $expressname=mysql_result($result,0,"expressname");
				//if($expressname=="")  每次覆盖
				{
				   $expressname=$kuaidi;  
				}    			  
		        $id=mysql_result($result,0,"id");	
		        $sqlstr="UPDATE `logistics` SET   `pdasn` = '$terminalId',`phonenumber` = '$rcvnumber',`distributeway`='2',`distributetime` = '$localTime',`distributeuser` = '$operatorId',`huohao` = '$boxId' ,`msm_sn` = '$msm_sn',`expressname` = '$expressname'  WHERE `id` ='$id' LIMIT 1";								  							              
				 mysql_query($sqlstr,$db4);  			   
		      } 	
			
		   }
	} //modify by 2016.1.20		   				  
     //----------------------------------------------------------------  
	//返回信息     
	$response["responseCode"]="$resstatus";
    $response["response"]=array("password"=>"$password");
	
	return 	$response;	
	
	}
	
	
//========================================    
// 订单补充命令    发送       don't use by 2014.11.30
//========================================  	
	function ConfirmDeliveryExRequestplus($operatorId,$terminalId,$packageID,$ordersn,$starttime,$endtime,$pickup,$status,$rcvnumber)
	{
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
		
	    $db2=conn2();
		

		$sqlstr="UPDATE `smtbx_order` SET  `starttime` = '$starttime',`endtime` = '$endtime',`pickup` = '$pickup',`status` = '$status' WHERE `ordersn0` ='$ordersn' and `username` ='$operatorId'  and `devicesn` ='$terminalId' LIMIT 1";	
        $result = mysql_query($sqlstr,$db2); 
		// pickup  取件方式， 1用户正常取件，签收，2超期回收，3退件,4管理员取件  
		 // status  订单状态， 0订单生成，1已上传平台，2短信已发，3短信重发, 4,5 备用, 6完成订单
	if(($status=="6")&&($pickup!="1"))   
                                              
	{
	//发送短信	   
	$sqlstr="SELECT msmcontentr FROM `smtbx_info` WHERE `devicesn` LIKE '$terminalId' LIMIT 1";
    $result = mysql_query($sqlstr,$db2);    
	$msmcontentr=mysql_result($result,0,"msmcontentr"); 
	$content="裹单:".$packageID.",".$msmcontentr; 
    sendonemsm($rcvnumber,$content,$operatorId,$terminalId,"");			
	}	
				    
	//返回信息     
	$response["responseCode"]="0";
    $response["response"]=array();
	
	return 	$response;	
	
	}	
	

//========================================    
// 完成订单（取回包裹）
//========================================  	
	function GetbackItemRequest($itemId,$terminalId ,$boxId,$operatorId,$localTime,$ordersn,$endtime,$pickup,$status,$rcvnumber,$infostatus,$main_username)
	{
	    $sss=$localTime.",".$ordersn.",".$endtime.",".$pickup;
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
	    $db=conn();
	    $db2=conn2();
		$db4=conn4();
		
		// ---预防由于网络等原因，产生重复发送的问题----
       	$sqlstr="SELECT password FROM `smtbx_order` WHERE `ordersn0` ='$ordersn' and `username` ='$operatorId'  and `devicesn` ='$terminalId'  and `status` ='$status'  LIMIT 1";
        $result0 = mysql_query($sqlstr,$db2);
		$num= mysql_numrows($result0); 		
		if($num!=0)
		{
		   $resstatus=0; //成功返回
		   $response["responseCode"]="$resstatus";
    	   $response["response"]=array();
	       return 	$response;					
		}		
		//--------			
		
							
	//修改订单记录
		$sqlstr="UPDATE `smtbx_order` SET `uptime` = '$localTime',`endtime`='$endtime',`pickup`='$pickup',`status`='$status'  WHERE `ordersn0` ='$ordersn' and `username` ='$operatorId'  and `devicesn` ='$terminalId' LIMIT 1";		
        $result = mysql_query($sqlstr,$db2);
		
		// pickup  取件方式， 1用户正常取件，签收，2超期回收，3退件,4管理员取件  
		 // status  订单状态， 0订单生成，  1已上传平台   6完成订单（0.1.6 三个状态）
    //暂不发短信，没有必要  2014.11.30, 2015.2.13再次使用
	if(($status=="6")&&(($pickup=="3")||($pickup=="2"))) //if(($status=="6")&&($pickup!="                                      
	{
	//发送短信	   
	$sqlstr="SELECT msmcontentr FROM `smtbx_info` WHERE `devicesn` LIKE '$terminalId' LIMIT 1";
    $result = mysql_query($sqlstr,$db2);    
	$msmcontentr=mysql_result($result,0,"msmcontentr");
	if($msmcontentr=="")
	{
	  $msmcontentr="您的快件在智能柜中长时间未取已退出，请及时取件。";
	} 		 
		 //获得投递员账户的短信内容   
	$sqlstr="SELECT smscontent FROM `user` WHERE `username` LIKE '$operatorId' LIMIT 1";
    $result = mysql_query($sqlstr,$db);    
	$smscontent=mysql_result($result,0,"smscontent");

	$itemIdh="*".substr($itemId,-4);
	$content="单号:".$itemIdh.",".$msmcontentr.$smscontent; 
	
	  	//发送短信
    	//for test 
			if(($terminalId!="201800000001")
				&&($terminalId!="201800000002")
	    		&&($terminalId!="201800000003")
				&&($terminalId!="201800000004")		
	    		&&($terminalId!="201800000005")
				&&($terminalId!="201800000006")		
	    		&&($terminalId!="201800000007")
				&&($terminalId!="201800000008")			
				&&($terminalId!="201800000009"))                   
		{
	  		//sendonemsm($rcvnumber,$content,$operatorId,$terminalId,$msm_sn);	 //该行为非测试代码
			// resendOneSMSforBox($rcvnumber,$content,$operatorId,$terminalId,$msm_sn,$boxId,$db);
				// 判断用户费用是否充足否则不让发。
			$checkres = checkCharge($terminalId,$operatorId,$boxId,$db);
			if($checkres){
		    	//whl 发送短信
		    	$stationid = getStationId($terminalId,$db);
		    	if($stationid){
		    		$res = SendMsm::sendOneSMSforBox($rcvnumber, $content, $main_username, $terminalId, $boxId,'',$stationid,1,$content,$itemId,'',1);
		    		$resjson = json_decode($res,true);
		    		$res = $resjson['status']==0 ? 1 : 0;
		    		$msm_sn = $resjson['msm_sn'];
		    	}
				//whl
			}
		}
		// for test
	}
	else if(($status=="6")&&($pickup=="1")) //正常取件
	{
		//-----------------------------------------------------------------------------------------
	
	      //对运单物流表进行补充更新   签单处理
	      //首先判断BOX设备是否注册，然后转换出对应的站点账号，若没有注册，就直接跳过这一步
		   $onlinetime=time();
           $result = mysql_query("SELECT * FROM  stations_manage   where  allbox  like '%$terminalId%' ",$db4);  
           $num= mysql_numrows ($result);
	       if($num!=0)
		   {
		      $stationaccount=mysql_result($result,0,"account"); //站点账号
			  
			   //判断该运单是否存在
		   $result = mysql_query("SELECT id FROM  logistics  where  stationaccount='$stationaccount'  and  expressno='$itemId'    order by   id   desc  limit 1",$db4);  
		   $num= mysql_numrows ($result);
		   if($num==0)
		   {  //在插入状态，将,'$qiandantime'也同时插入diandantime
		     //   $result = mysql_query("SELECT id FROM  logistics_a   where  stationaccount='$stationaccount'  and  expressno='$itemId' limit 1",$db4);  
		    //   $num= mysql_numrows ($result); //防止二次签单  
		   //    if($num==0)
			   {
		        $sqlstr="INSERT INTO `logistics` ( `pdasn`,`stationaccount`,`expressno`,`diandantime`,`diandanuser`,`signinguser`,`signingtime`,`distributeway`,`onlinetime`) VALUES ('$terminalId','$stationaccount', '$itemId','$localTime', '$operatorId', '$operatorId','$localTime','2','$onlinetime')";								  				
				mysql_query($sqlstr,$db4);  
			   }		
		   }
		   else
		   {
		        $id=mysql_result($result,0,"id");	
		        $sqlstr="UPDATE `logistics` SET  `signingtime` = '$localTime',`signinguser` = '$operatorId' ,`distributeway` = '2' ,`signingkind` = '1' WHERE `id` ='$id' LIMIT 1";								  							              
				 mysql_query($sqlstr,$db4);  			   
		   } 				  
	
		 }				  
     //----------
	
	}
	
	if(($status=="6")&&(($pickup=="3")||($pickup=="2")||($pickup=="4")||($pickup=="5")||($pickup=="6")))
	{
	       $result = mysql_query("SELECT * FROM  stations_manage   where  allbox  like '%$terminalId%' ",$db4);  
           $num= mysql_numrows ($result);
	       if($num!=0)
		   {
		      $stationaccount=mysql_result($result,0,"account"); //站点账号
			  $result = mysql_query("SELECT id FROM  logistics  where  stationaccount='$stationaccount'  and  expressno='$itemId'    order by   id   desc  limit 1",$db4);  
		      $num= mysql_numrows ($result);
		      if($num!=0)
			  {
			  	$id=mysql_result($result,0,"id");	
		      	// whl添加修改退件人和退件时间	
		      	$sqlstr="UPDATE `logistics` SET  `signingtime` = '$localTime',`signinguser` = '$operatorId' ,`distributeway` = '0'  WHERE `id` ='$id' LIMIT 1";							
			  	mysql_query($sqlstr,$db4);  			  
			  }		    
		   }	 
	}
	
//------------------------------------------------------------------------------------	
	
		//修改boxinfo的状态
	updateinfostatus($terminalId,$infostatus,$boxId);
	 						    
	//返回信息     
	$response["responseCode"]="0";
	
    $response["response"]=array();
	
	return 	$response;	
	
	}
	
	
//========================================    
// 再次发送短信
//========================================  	
	function OrderSendMsmAgain($itemId,$terminalId,$rcvnumber,$operatorId,$localTime,$ordersn,$sentmsmflg,$main_username)
	{	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }
		
		$db=conn(); 
	    $db2=conn2();
		$db4=conn4();
		// ---预防由于网络等原因，产生重复发送的问题----
       	$sqlstr="SELECT password FROM `smtbx_order` WHERE `ordersn0` ='$ordersn' and `username` ='$operatorId'  and `devicesn` ='$terminalId'  and `sentmsmflg` ='$sentmsmflg'  LIMIT 1";
        $result0 = mysql_query($sqlstr,$db2);
		$num= mysql_numrows($result0); 		
		if($num!=0)
		{
		   $password=jiemi(mysql_result($result0,0,"password")); 
		   $resstatus=0; //成功返回
		   $response["responseCode"]="$resstatus";
    	   $response["response"]=array("password"=>"$password");
	       return 	$response;								
		}		
		//--------									
									   	   
	   //准备发送短信	   
	
	 //获得智能柜的短信内容   
	$sqlstr="SELECT msmcontent FROM `smtbx_info` WHERE `devicesn` LIKE '$terminalId' LIMIT 1";
    $result = mysql_query($sqlstr,$db2);    
	$msmcontent=mysql_result($result,0,"msmcontent"); 
	 //获得投递员账户的短信内容   
	$sqlstr="SELECT smscontent FROM `user` WHERE `username` LIKE '$main_username' LIMIT 1";
    $result = mysql_query($sqlstr,$db);    
	$smscontent=mysql_result($result,0,"smscontent"); 		

     //获取首发密码
	$sqlstr="SELECT password,boxid FROM `smtbx_order` WHERE `devicesn` LIKE '$terminalId'  and  ordersn0 like $ordersn  LIMIT 1";
    $result = mysql_query($sqlstr,$db2);
	$num=mysql_numrows($result);
	$send_flg=0; //0不发送  1发送 
	if($num!=0)
	{
	   $password=mysql_result($result,0,"password");
	   $passwordsms=jiemi($password);
	   $send_flg=1;
	   $boxId=mysql_result($result,0,"boxid");
	} 
	
	$itemIdh="*".substr($itemId,-4);
	$m_content="单号:".$itemIdh.",".$msmcontent.$smscontent;

	
	if($send_flg==1)
	{
	  	//发送短信
		// 判断用户费用是否充足否则不让发。
		$checkres = checkCharge($terminalId,$main_username,$boxId,$db);
		if($checkres){
	    	//whl 发送短信
	    	$stationid = getStationId($terminalId,$db);
	    	if($stationid){
	    		$res = SendMsm::sendOneSMSforBox($rcvnumber, $m_content, $main_username, $terminalId, $boxId,'',$stationid,1,$m_content,$itemId,$passwordsms,1);
	    		$resjson = json_decode($res,true);
	    		$res = $resjson['status']==0 ? 1 : 0;
	    		$msm_sn = $resjson['msm_sn'];
	    	}else{
	    		$res = 0;
	    	}
			//whl
		}else{
			$res = 0;
		}	
	
	
		
    	$resstatus=1; 	 
		if($res==1) //发送成功	
		{		
		//写入订单记录
	 	$sqlstr="UPDATE `smtbx_order` SET  sentmsmflg=$sentmsmflg,`uptime` = '$localTime',`ordersn` = '$msm_sn',`sentmsmflgs` = '1' ,`password` = '$password'  WHERE `ordersn0` ='$ordersn' and `username` ='$operatorId'  and `devicesn` ='$terminalId' LIMIT 1";		
     	$result = mysql_query($sqlstr,$db2); 	
		$resstatus=0;
		}	
	}
	else
	{
	   $resstatus=0;
	   $passwordsms="noorder"; 
	} 
	
		//-------------------------------------------------
		      //对运单物流表进行补充更新   再次修改msm_sn
	      //首先判断BOX设备是否注册，然后转换出对应的站点账号，若没有注册，就直接跳过这一步
           $result = mysql_query("SELECT * FROM  stations_manage   where  allbox  like '%$terminalId%' ",$db4);  
           $num= mysql_numrows ($result);
	       if($num!=0)
		   {
		      $stationaccount=mysql_result($result,0,"account"); //站点账号
			  
			   //判断该运单是否存在
		       $result = mysql_query("SELECT id FROM  logistics  where  stationaccount='$stationaccount'  and  expressno='$itemId' limit 1",$db4);  
		       $num= mysql_numrows ($result);
		       if($num!=0)
		       {		      
		   		$id=mysql_result($result,0,"id");	
		        $sqlstr="UPDATE `logistics` SET  `msm_sn` = '$msm_sn' WHERE `id` ='$id' LIMIT 1";								  				            
				 mysql_query($sqlstr,$db4);  			   	   
		       }
	        }
	
	//-------------------------------------------------		
				    
	//返回信息     
	$response["responseCode"]="$resstatus";
    $response["response"]=array("password"=>"$passwordsms");
	
	return 	$response;	
	
	}
	
//========================================    
// 上传系统设置信息
//========================================  	
	function UploadSystemSetting($name,$value,$devicesn)
	{
	  
	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
	
	    $db2=conn2();
		
		if($name=="boxinfo")
		{
		   $value=boxinfostrconvert($value);
		}
										
	//修改记录
		$sqlstr="UPDATE `smtbx_info` SET `$name` = '$value' WHERE `devicesn` ='$devicesn' LIMIT 1";		
        $result = mysql_query($sqlstr,$db2); 
		$flg=0;
		if($name=="sys_set")
		{
		  $str=split(";;",$value);
		  $len=count($str);
		 
		  for($i=0;$i<$len;$i++)
		  {  
		     $str1=split(",,",$str[$i]);
		     $len1=count($str1);
			
			 for($j=0;$j<$len1;$j++)
			 {
			    if($str1[$j]=="MSMContent")
				{
				   $ccc=$str1[$j+1];
				   $str2=split(",,",$str[$i+1]);
				   $cccr=$str2[$j+1];				   
		           $sqlstr="UPDATE `smtbx_info` SET `msmcontent` = '$ccc',`msmcontentr` ='$cccr' WHERE `devicesn` ='$devicesn' LIMIT 1";		
                   mysql_query($sqlstr,$db2);
				   $flg=1;   				 
				   break; 				   				   
				}
			 }
			 if($flg==1)
			 {
			    break;
			 }			   
		  }	  	
		}
 			    
	//返回信息     
	$response["responseCode"]="0";
    $response["response"]=array();
	
	return 	$response;	
		
	}


//========================================    
// 上传系统Log信息
//========================================  	
	function UploadSystemLog($logdata,$time,$devicesn)
	{
	  
	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
	
	    $db2=conn2();
								
	//修改记录
		$sqlstr="INSERT INTO `smtbx_log` (`smtbx_id`,`sys_log`,`time` ) VALUES ('$devicesn', '$logdata', '$time')";		
        $result = mysql_query($sqlstr,$db2); 	
			    
	//返回信息     
	$response["responseCode"]="0";
    $response["response"]=array();
	
	return 	$response;	
		
	}
//========================================    
// 上传告警Log信息
//========================================  	
	function UploadAlarmLog($logdata,$time,$devicesn)
	{
	    
	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
	
	    $db2=conn2();
								
	//修改记录
		$sqlstr="INSERT INTO `smtbx_alarmlog` (`smtbx_id`,`alarm_log`,`time` ) VALUES ('$devicesn', '$logdata', '$time')";		
        $result = mysql_query($sqlstr,$db2); 	
			    
	//返回信息     
	$response["responseCode"]="0";
    $response["response"]=array();
	
	return 	$response;	
		
	}

//========================================    
// 投递人员身份验证
//========================================  	
	function VerifyOperatorRequest($operatorId,$password,$terminalId,$localTime)
	{
	  	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
	
        $db=conn();	
		 $res=10;     
         $result = mysql_query("SELECT * FROM user  where  username='$operatorId' limit 1",$db); 		 			  
         $num= mysql_numrows($result);
		 if($num==0)
		 {
		    $res=13;  //用户不存在			 
		 }
		 else
		 {
		    $pw=mysql_result($result,0,"password");
			$activation=mysql_result($result,0,"activation");
			if($pw!=$password)
			{
			  $res=14;  //密码不正确 			
			}
			else if($activation==0)
			{
			  $res=13;  //用户不存在	在没激活的状态下认为用户不存在
			}
			else
			{
				$name=mysql_result($result,0,"name");
				$company=mysql_result($result,0,"company");	
				$fund=mysql_result($result,0,"fund");

				
				$kuaidi=mysql_result($result,0,"kuaidi");   //格式例子：  1011,圆通快递,1012,顺丰快递
				$rate=mysql_result($result,0,"rate");
				
				//若该智能柜为站点用户，将获取该站点的快递设置
				//说明： 若该智能柜是站点柜子，那么快递公司的名称将以站点为准
				$result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$terminalId%' ",$db);  
                $num= mysql_numrows ($result);
				
				if($num!=0)
				{
				    $stationaccount=mysql_result($result,0,"account");				
				    $result = mysql_query("SELECT * FROM  dyhawk.expresselct  where  stationaccount='$stationaccount'",$db);  
                    $num= mysql_numrows ($result);
					$kuaidi="";
					for($i=0;$i<$num;$i++)
					{
					    $code=mysql_result($result,$i,"code");
					    $kname=mysql_result($result,$i,"name");
					    $kuaidi=$kuaidi.$code.",".$kname.",";
						
					}
					
				}
												
				//获取该站点的格口、短信计费设置
	            $result = mysql_query("SELECT * FROM  smartbox.smtbx_info where  devicesn='$terminalId'",$db); 	
				$gekouratestr="";	 			  
                $num= mysql_numrows($result);
				 if($num!=0)
				{
				   $small=mysql_result($result,0,"small");
				   $middle=mysql_result($result,0,"middle");
				   $large=mysql_result($result,0,"large"); 
				   $smssend=mysql_result($result,0,"smssend");
				   $gekouratestr=$small.",".$middle.",".$large.",".$smssend.",";	   
				}			
							
			    $res=11;  //一切正确 				
			} 
		 }
		   
		 $response["responseCode"]="0";
		 $response["response"]="$res";				

				
	    $time=time();
		  
 $response["operatorInfo"]=array("operatorId"=>"$operatorId","operatorName"=>urlencode("$name"),"orgnizationId"=>"","orgnization"=>urlencode("$company"),"fund"=>"$fund","kuaidi"=>urlencode("$kuaidi"),"rate"=>"$rate","gekouratestr"=>urlencode("$gekouratestr"),"time"=>"$time");			  
		  
		  			 		 	 			    
	//返回信息     	
	return 	$response;							
	}

//============================================================    
// 投递人员身份验证  新设备增加命令  2016.1.18  输入参数增加 $type
//============================================================  	
	function VerifyOperatorRequest1($operatorId,$password,$terminalId,$localTime,$type)
	{
	  	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }	
	
        $db=conn();	
		
		 $str="";
		 switch($type)
		 {
		  case 0:
		    $str="username='$operatorId'";
		   break;
		  case 1:
		    $str="cardid='$operatorId'";
		   break;		 
		  case 2:
		    $str="identitycard='$operatorId'";
		   break;		 
		 
		 }
			
		 $res=10;     
         $result = mysql_query("SELECT * FROM user  where  $str limit 1",$db); 		 			  
		 $num= mysql_numrows($result);

		 $operatorId_get = $operatorId;

         if($num>0){
         	// whl如果是主账户登录就查询该主账户的所有子账户
			$child_usernames = getAllChildUsername($operatorId,$db); 
			if($type==0)
			 {
			    $pw=mysql_result($result,0,"password");
				$activation=mysql_result($result,0,"activation");
				if($pw!=$password)
				{
				  $res=14;  //密码不正确 			
				}
				else if($activation==0)
				{
				  $res=13;  //用户不存在	在没激活的状态下认为用户不存在
				}
				else
				{
					$company=mysql_result($result,0,"company");	
					$fund=mysql_result($result,0,"fund");
					$name=mysql_result($result,0,"name");  
					$kuaidi=mysql_result($result,0,"kuaidi");   //格式例子：  1011,圆通快递,1012,顺丰快递
					$rate=mysql_result($result,0,"rate");
					$identitycard=mysql_result($result,0,"identitycard");	
					$cardid=mysql_result($result,0,"cardid");
					$password=mysql_result($result,0,"password");												
					$operatorId=mysql_result($result,0,"username");

					
					//若该智能柜为站点用户，将获取该站点的快递设置
					//说明： 若该智能柜是站点柜子，那么快递公司的名称将以站点为准
					$result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$terminalId%' ",$db);  
	                $num= mysql_numrows ($result);
					
					if($num!=0)
					{
					    $stationaccount=mysql_result($result,0,"account");				
					    $result = mysql_query("SELECT * FROM  dyhawk.expresselct  where  stationaccount='$stationaccount'",$db);  
	                    $num= mysql_numrows ($result);
						$kuaidi="";
						for($i=0;$i<$num;$i++)
						{
						    $code=mysql_result($result,$i,"code");
						    $kname=mysql_result($result,$i,"name");
						    $kuaidi=$kuaidi.$code.",".$kname.",";
						} 			
					}
													
					//获取该站点的格口、短信计费设置
		            $result = mysql_query("SELECT * FROM  smartbox.smtbx_info where  devicesn='$terminalId'",$db); 	
					$gekouratestr="";	 			  
	                $num= mysql_numrows($result);
					 if($num!=0)
					{
					   $small=mysql_result($result,0,"small");
					   $middle=mysql_result($result,0,"middle");
					   $large=mysql_result($result,0,"large"); 
					   $smssend=mysql_result($result,0,"smssend");
					   $gekouratestr=$small.",".$middle.",".$large.",".$smssend.",";	   
					}			
								
				    $res=11;  //一切正确 	
				} 
			 }
			 else
			 {
					$name=mysql_result($result,0,"name");
					$company=mysql_result($result,0,"company");	
					$fund=mysql_result($result,0,"fund");
					
					$kuaidi=mysql_result($result,0,"kuaidi");   //格式例子：  1011,圆通快递,1012,顺丰快递
					$rate=mysql_result($result,0,"rate");
					$identitycard=mysql_result($result,0,"identitycard");	
					$cardid=mysql_result($result,0,"cardid");
					$password=mysql_result($result,0,"password");
					$operatorId=mysql_result($result,0,"username");				
					
					//若该智能柜为站点用户，将获取该站点的快递设置
					//说明： 若该智能柜是站点柜子，那么快递公司的名称将以站点为准
					$result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$terminalId%' ",$db);  
	                $num= mysql_numrows ($result);
					
					if($num!=0)
					{
					    $stationaccount=mysql_result($result,0,"account");				
					    $result = mysql_query("SELECT * FROM  dyhawk.expresselct  where  stationaccount='$stationaccount'",$db);  
	                    $num= mysql_numrows ($result);
						$kuaidi="";
						for($i=0;$i<$num;$i++)
						{
						    $code=mysql_result($result,$i,"code");
						    $kname=mysql_result($result,$i,"name");
						    $kuaidi=$kuaidi.$code.",".$kname.",";
						} 			
					}
													
					//获取该站点的格口、短信计费设置
		            $result = mysql_query("SELECT * FROM  smartbox.smtbx_info where  devicesn='$terminalId'",$db); 	
					$gekouratestr="";	 			  
	                $num= mysql_numrows($result);
					 if($num!=0)
					{
					   $small=mysql_result($result,0,"small");
					   $middle=mysql_result($result,0,"middle");
					   $large=mysql_result($result,0,"large"); 
					   $smssend=mysql_result($result,0,"smssend");
					   $gekouratestr=$small.",".$middle.",".$large.",".$smssend.",";	   
					}			
								
				    $res=11;  //一切正确 		 

			 }    	
         }else{
         	// whl如果没有查到主账户就去子账户表查询
         	$child_username = $operatorId_get;
         	$data = searchChildUsername($child_username,$password,$db);
         	if($data){
         		if($data['status']==1){
         			$parent_username = $data['parent_username'];
         			$name = $data['name'];
         			$result = mysql_query("SELECT * FROM user  where  username='$parent_username' limit 1",$db);

					$company=mysql_result($result,0,"company");	
					$fund=mysql_result($result,0,"fund");
					$kuaidi=mysql_result($result,0,"kuaidi");   //格式例子：  1011,圆通快递,1012,顺丰快递
					$rate=mysql_result($result,0,"rate");
					$identitycard=mysql_result($result,0,"identitycard");	
					$cardid=mysql_result($result,0,"cardid");
					$password=mysql_result($result,0,"password");												
					$operatorId=mysql_result($result,0,"username");

					
					//若该智能柜为站点用户，将获取该站点的快递设置
					//说明： 若该智能柜是站点柜子，那么快递公司的名称将以站点为准
					$result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$terminalId%' ",$db);  
	                $num= mysql_numrows ($result);
					
					if($num!=0)
					{
					    $stationaccount=mysql_result($result,0,"account");				
					    $result = mysql_query("SELECT * FROM  dyhawk.expresselct  where  stationaccount='$stationaccount'",$db);  
	                    $num= mysql_numrows ($result);
						$kuaidi="";
						for($i=0;$i<$num;$i++)
						{
						    $code=mysql_result($result,$i,"code");
						    $kname=mysql_result($result,$i,"name");
						    $kuaidi=$kuaidi.$code.",".$kname.",";
						} 			
					}
													
					//获取该站点的格口、短信计费设置
		            $result = mysql_query("SELECT * FROM  smartbox.smtbx_info where  devicesn='$terminalId'",$db); 	
					$gekouratestr="";	 			  
	                $num= mysql_numrows($result);
					 if($num!=0)
					{
					   $small=mysql_result($result,0,"small");
					   $middle=mysql_result($result,0,"middle");
					   $large=mysql_result($result,0,"large"); 
					   $smssend=mysql_result($result,0,"smssend");
					   $gekouratestr=$small.",".$middle.",".$large.",".$smssend.",";	   
					}			
								
				    $res=11;  //一切正确 	
         		}else{
         			$res=14;  //密码不正确
         		}
         	}else{
         		$res=13;  //用户不存在
         	}	
         }

		 	   
		 $response["responseCode"]="0";
		 $response["response"]="$res";	
		 				
	    $time=time(); 
 $response["operatorInfo"]=array("operatorId"=>"$operatorId_get","operatorName"=>urlencode("$name"),"orgnizationId"=>"","orgnization"=>urlencode("$company"),"fund"=>"$fund","kuaidi"=>urlencode("$kuaidi"),"rate"=>"$rate","gekouratestr"=>urlencode("$gekouratestr"),"time"=>"$time","cardid"=>"$cardid","identitycard"=>"$identitycard","password"=>"$password","child_usernames"=>"$child_usernames","main_username"=>"$parent_username");			 
		 		 
		 				 		 	 			    
	//返回信息     	
	return 	$response;						
	}

//========================================    
// 终端用户数据同步
//========================================  	
	function TerminalAccountSyncData($terminalId,$content)
	{
  	
	     if($_SESSION['LOGIN']!="OK")
		 {
		  	$response["responseCode"]="E0002";
		  	$_SESSION['LOGIN']="NOK";
		  	$response["response"]=array("lonin"=>"nok");
			return  $response;					 
		 }
	
	
			 	
	   $db=conn();	
	   $data=split(",",$content); 
  
	   $fields=array("sex","regtime","name","province","city","area","county","company","category","activation","cardid"); 
	   $m=count($fields); 
	   $len=floor(count($data)/3);
	   $checksum=0;
	   $outstr="";
	   $str1="";
	   $n0=0;
	   for($i=0;$i<$len;$i++)
	   {
	     $username=$data[$i*3];
		 $password=$data[$i*3+1];
		 $cheksum0=$data[$i*3+2];
	     $result = mysql_query("SELECT * FROM user  where  username='$username'  and  password='$password'  limit 1",$db); 		  
         $num= mysql_numrows($result);		 
		 if($num!=0)
		 {   //有该记录
		    for($j=0;$j<$m;$j++)
			{
			  $str=mysql_result($result,0,$fields[$j]);
              $str1=$str1.$str.",";	
			  $vvv=str2hex2sum($str);  
			  $checksum+=str2hex2sum($str);//
			}
		    if($checksum!=$checksum0)  
			{//内容有变化
			  $outstr=$outstr."12".",".$str1;
			  $n0+=13;			
			} 
			else
			{ //内容无变化。 有无变化，每次都要将费用传回去
			  $outstr=$outstr."1".",";
			  $n0+=2;
			}  
		    $outstr=$outstr.mysql_result($result,0,"fund").",";  
		 }
		 else
		 {  //没有该记录，用户名不对或者密码不对， 不存在费用的问题
		     $outstr=$outstr."0".",";
			 $n0+=1;
		 }
	   
	   }
	   $n0++;
	   $outstr=$n0.",".$outstr;
	
	   $res=1;
  
		 $response["responseCode"]="0";
		 $response["response"]="$res";				
		 $response["content"]=urlencode($outstr);	
		 			 		 		 	 			    
	//返回信息     	
	return 	$response;							
	}


//========================================    
// 运单电话号码数据同步
//========================================  	
function  shujutongbu($terminalId)
{
   $db=conn();   
   $response="";
  $result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$terminalId%' ",$db);  
  $num= mysql_numrows ($result);
	
  $timecx=time()-3600*24*3; 			
  if($num!=0)
   {
	  $stationaccount=mysql_result($result,0,"account");
	   //logistics
      $result = mysql_query("SELECT expressno,phonenumber,expressname FROM  dyhawk.logistics  where  stationaccount='$stationaccount' and  signingkind<>'1' and diandantime>$timecx ",$db);  
      $num1= mysql_numrows($result);

      $str=array();
      for($i=0;$i<$num1;$i++)
      {       
  	 //  $str[$i*3+0]= urlencode(mysql_result($result,$i,"expressno"))."pxp";
	 //   $str[$i*3+1]=urlencode(mysql_result($result,$i,"phonenumber"))."pxp";	
	 //   $str[$i*3+2]=urlencode(mysql_result($result,$i,"expressname"))."pxp";
  	    $str[$i*3+0]= (mysql_result($result,$i,"expressno"))."pxp";
	    $str[$i*3+1]=(mysql_result($result,$i,"phonenumber"))."pxp";	
	    $str[$i*3+2]=(mysql_result($result,$i,"expressname"))."pxp";		
		
		
				   	
      }  
      $resp=implode('',$str);
	  $resp=$resp."0"."pxp";								
   }
   
   
 	//返回信息     

      

		 $response["responseCode"]="0";
		 $response["response"]="1";				
		 $response["content"]=$resp;	
   
   return  $response;
}



//------子函数-------------------------------------

 function smslimit($deviceID,$db){

   $result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$deviceID%'",$db);         
   $num= mysql_numrows ($result);   
   if($num!=0)
   {
	  $stationaccount=mysql_result($result,0,"account"); //站点账号
   } 
 
   $result = mysql_query("SELECT id FROM  dyhawk.smslimit  where  stationaccount='$stationaccount'   and  active=1",$db);
   $num= mysql_numrows($result);
   $ret=0;  
   if($num!=0)
   {
       $ret=1;
   }
   else
   {
      $ret=0;  
   }
   return $ret; 
 }

// 获取stationaccount
 function getStationId($deviceID,$db){

   $result = mysql_query("SELECT * FROM  dyhawk.stations_manage   where  allbox  like '%$deviceID%'",$db);         
   $num= mysql_numrows ($result);   
   if($num!=0)
   {
	 return $stationaccount=mysql_result($result,0,"account"); //站点账号
   } 
   return false;
 }

// 判断用户费用是否充足否则不让发。
function checkCharge($devicesn,$username,$boxId,$db){
	   $result = mysql_query("SELECT small,middle,large,smssend FROM   smartbox.smtbx_info  where  devicesn='$devicesn'",$db);  
	   $num= mysql_numrows ($result); 
	   
	   if($num==0)
	   {
	     return 0;
	   }
	   $small=mysql_result($result,0,"small");     //1000为1元
	   $middle=mysql_result($result,0,"middle");
	   $large=mysql_result($result,0,"large");
	   $smssend=mysql_result($result,0,"smssend");


	   //扣费   
	   $result = mysql_query("SELECT * FROM   deeyee.user  where  username='$username'",$db);  
	   $num= mysql_numrows ($result);
	 	 //判断有无这个用户 
	   	if($num==0)
		{
		  return 0;  //没有这个用户
		}  
	   
	   $befor_fund=mysql_result($result,0,"fund");
	   
	   $rate=mysql_result($result,0,"rate");

		if(($rate==1000)||($smssend==0))
		{
			$rate=0; //不收费
		} 
		else if($rate==0)
		{
			$rate=70; //默认7分/条
		}	
		     
	   $after_fund=$befor_fund-$rate;  //短信费用 
	   

	  	//判断大小格口,收费
		$rate=0;
		$boxsize=0;
		$boxId=$boxId%10;
		if($boxId==0)$boxId=10;
		
		if(($boxId==1)||($boxId==10))
		{
		   $rate=$large;
		   $boxsize=3;
		}
		else if(($boxId==2)||($boxId==3)||($boxId==8)||($boxId==9))	
		{
		   $rate=$middle;
		   $boxsize=2; 
		}
		else if(($boxId==4)||($boxId==5)||($boxId==6)||($boxId==7))	
		{
		   $rate=$small;
		   $boxsize=1; 
		}	 
	    
		$after_fund1=$after_fund-$rate;  //格口费用	
		
		if($after_fund1<0)
		{
		  return  0;  //费用不够，限制发短信
		}
		return  1;

}

// whl查询子账户是否存在，如果存在传回总账户号
function searchChildUsername($child_username,$pwd,$db){
	$res = mysql_query("SELECT parent_username,name,password from deeyee.user_child where child_username='$child_username' and status=0 limit 1",$db);
	if(mysql_num_rows($res)>0){
		$data['parent_username'] = mysql_result($res,0, 'parent_username');
		$data['name'] = mysql_result($res,0, 'name');
		$data['password'] = mysql_result($res,0, 'password');
		if($pwd==$data['password']){
			$data['status']=1;
		}else{
			$data['status']=0;//密码不正确
		}
		return $data;
	}
	return false;
}

// whl查询总账户的所有子账户，用逗号拼接成字符串返回。
function getAllChildUsername($username,$db){
	$res = mysql_query("SELECT child_username from deeyee.user_child where parent_username='$username' and status=0",$db);
	$num = mysql_num_rows($res);
	file_put_contents('./b.log', '123');
	$child_usernames = '';
	if($num>0){
		for($i=0;$i<$num;$i++){
			$child_username = mysql_result($res,$i, 'child_username');
			$child_usernames.=$child_username.',';
		}
		$child_usernames = trim($child_usernames,',');
		return $child_usernames;
	}else{
		return false;
	}
}

?>