<?php

function randpassword()
{ 
    $db2=conn2();
	$time0=time()-10*24*3600;  //10天密码不重复， 但一个订单的二次发送的密码可能重复
	$num=1;
	while($num!=0)
	{
	$len=1;
    $chars='12356789123567891235678912356789'; 
    // characters to build the password from 
    mt_srand((double)microtime()*1000000*getmypid()); 
    // seed the random number generater (must be done) 
    $password1=''; 
    while(strlen($password1)<$len) 
    $password1.=substr($chars,(mt_rand()%strlen($chars)),1); 

	$len=5;
    $chars='1235678912356789ABCDEFGHJKLMNPQRSTUVWXYZ12356789'; 
    // characters to build the password from 
    mt_srand((double)microtime()*1000000*getmypid()); 
    // seed the random number generater (must be done) 
    $password2=''; 
    while(strlen($password2)<$len) 
    $password2.=substr($chars,(mt_rand()%strlen($chars)),1); 

    $password=$password1.$password2;
	
	$passwordjiami=jiami($password);

	//$result = mysql_query("SELECT * FROM  smtbx_order  where  password='$passwordjiami'  and  uptime>'$time0' ",$db2);
	$result = mysql_query("SELECT * FROM  smtbx_order  where  password='$passwordjiami'",$db2);  //全部在箱密码
    $num= mysql_numrows ($result);
	
	$result1 = mysql_query("SELECT * FROM  smtbx_endorder  where  password='$passwordjiami'",$db2);  //完成密码 and  starttime >'$time0'  
    $num1= mysql_numrows ($result1);
		
	$num=$num+$num1;	
	
	}	
    return $password; 
}
 

function  sendonemsm($rcvnumber,$content,$username,$devicesn,$msm_sn)
{
   $db=conn();
   $db1=conn1();
   //扣费
   $result = mysql_query("SELECT * FROM user  where  username='$username'",$db);  
   $num= mysql_numrows ($result);
 	 //判断有无这个用户 
   	if($num==0)
	{
	 return 0;  //没有这个用户  
	}  
   
   $befor_fund=mysql_result($result,0,"fund");
   
   $rate=mysql_result($result,0,"rate");
	if($rate==0)
	{
		$rate=70; //默认7分/条
	}
	else if($rate==1000)
	{
		$rate=0; //不收费
	}      
   $after_fund=$befor_fund-$rate;  //短信费用  
   //$after_fund=$befor_fund-70;  //短信费用 
   $sendtime=mktime();
     
	 //限制发短信 
   	if($after_fund<0)
	{
	 return 0;  
	}
	
	//改变用户的资金
	$sqlstr="UPDATE  `user` SET  `fund` =  '$after_fund'  WHERE  `username` =$username  LIMIT 1" ;
    mysql_query($sqlstr,$db);
	
	//扣除费用记录
    $sqlstr="INSERT INTO  `consumingrecords` (`username` ,`beforsend` ,`aftersend` ,`msm_num` ,`operation` ,`time` ) 
VALUES ('$username',  '$befor_fund',  '$after_fund',  '1',  '$devicesn',  '$sendtime')" ;
    mysql_query($sqlstr,$db);
	
	//记录发送短信内容
     $sqlstr="INSERT INTO  `sendmsmcontent` ( `username` ,`sendtime` ,`content`,`stationid`,`num` ) 
                                     VALUES ('$username','$sendtime','$content','$devicesn','1')";
     mysql_query($sqlstr,$db); 	
		       
  //发短信
   $sqlstr="INSERT INTO  `msmwait` ( `username` ,`rcvnumber` ,`sendtime` ,`stationid` ,`content`,`msm_sn` ) 
                                  VALUES ('$username', '$rcvnumber',  '$sendtime', '$devicesn', '$content', '$msm_sn')";								 								  
	 mysql_query($sqlstr,$db1);	
	  	
    return  1;
}
//===================================
// checkmsmsendresult($account)
// 作用：将该设备的所有已经发送短信的且没有完成的记录，到sendmsm表中进行查询，将结果更新smtbx_order。这个是为返回状态作准备       
//===================================
function  checkmsmsendresult($account)
{
   $db=conn();
   $db2=conn2();
   //得到本设备已经发送短信的记录
   $time0=time()-4*3600;//4小时后不再检查
  // $result = mysql_query("SELECT ordersn,username FROM smtbx_order  where  devicesn='$account' and  sentmsmflgs<'2'",$db2);  
    $result = mysql_query("SELECT ordersn,username FROM smtbx_order  where  devicesn='$account' and  sentmsmflgs<'2'  and  uptime>$time0",$db2); 
   $num= mysql_numrows ($result);
//   echo "ssss$num";
   if($num==0)
   {
     return;
   }
   for($i=0;$i<$num;$i++)
   {
     $msm_sn=mysql_result($result,$i,"ordersn");
	 $username=mysql_result($result,$i,"username");
     
//	 echo "< $msm_sn $username >";	
     $result1 = mysql_query("SELECT * FROM  sendmsm  where  username='$username' and  msm_sn='$msm_sn'",$db);  
     $num1= mysql_numrows ($result1);
	 if($num1>0)
	 {
	   $status=mysql_result($result1,0,"status");
	  //  echo "ddd$status  </br>";  
	    $sqlstr="UPDATE `smtbx_order` SET `sentmsmflgs`='$status',`downflg`=0  where  username='$username' and  ordersn='$msm_sn'";		
        mysql_query($sqlstr,$db2); 	
	 }    	  
   }
}


//=========================================================
// $cmdresponse =array("result"="","content"="")  result=0成功，1不成功，      
//=========================================================
function  downcommand($account,$cmdresponse)
{
  	$db2=conn2(); 
  	//$result=(int)($cmdresponse->result);
  	//$rescontent=$cmdresponse->rescontent;
  	$result=(int)($cmdresponse[result]);
  	$rescontent=$cmdresponse[rescontent];	
	
	
 	$cmdcode=$_SESSION['cmdcode'];
 	$id=$_SESSION['id'];
 
 	//应答处理
  	switch($cmdcode)
  	{
  		case  1000:
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_order` SET `downflg`='1'  where  devicesn='$account' and  downflg='2'";		
  				mysql_query($sqlstr,$db2); 		 	
			}  
  			break;
  		case  1001:
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 
  		case  1002: // 获取电压温度等数据
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 
			   //将参数达到数据库中
		        mysql_query("UPDATE `smtbx_info` SET `volttemp`='$rescontent' WHERE  devicesn='$account' LIMIT 1",$db2); 		 	
			}       
  			break; 			
  		case  1003: //获得operatorlog指定时间段内的数据
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 			
  		case  1004: //获得order指定时间段内的记录数据    命令  内容 starttime,endtime,  开始时间  结束时间  
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 
			
  		case  1005: //修改order某一记录    内容： ordersn,字段名,内容,...
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 			
			
  		case  1006: //远程开箱  内容;  格口号
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 						
  		case  1007: //远程重启计算机   内容： 无
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 								
  		case  1008: //下载当前所有在箱的订单   内容： 无
    		if($result==0)
			{
  				$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3',`replay`='$rescontent'  where  id='$id'  limit 1";		
  				mysql_query($sqlstr,$db2); 		 	
			}       
  			break; 										 
  	}
  
 //------------------------------------------------
 //发送命令
 //------------------------------------------------
 
         $cmdcontent=""; //命令的内容
		 
        // 0)$cmdcode=1000
		$cmdcode=1000;
   		$sqlstr="UPDATE `smtbx_order` SET `downflg`='2'  where  devicesn='$account' and  downflg='0' and  sentmsmflgs>1";		
  		mysql_query($sqlstr,$db2); 	
		$result=mysql_query("SELECT ordersn0,sentmsmflgs FROM  smtbx_order where  devicesn='$account' and  downflg='2' and  sentmsmflgs>1",$db2);
  		$num= mysql_numrows ($result);
  		$str="";
  		if($num>0)
  		{  
    		for($i=0;$i<$num;$i++)
    		{
      			$ordersn0=mysql_result($result,$i,"ordersn0");
	 			$sentmsmflgs=mysql_result($result,$i,"sentmsmflgs");	 
	 			$cmdcontent.=$ordersn0.",".$sentmsmflgs.",";
  			}
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  						
  		} 
		
        // 1)$cmdcode=1001
		$cmdcode=1001;	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='1001' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		 	$cmdcontent=mysql_result($result,0,"cmdbody"); //cmdbody==1001001下载命令  1001002前向升级   1001003后向升级   1001004版本信息获得命令
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
         	if(($cmdcontent=="1001002")||($cmdcontent=="1001003"))
		 	{
		  		$sqlstr="UPDATE `smtbx_downcmd` SET `status`='3'  where  id='$id'";
		 	}
		 	else
		 	{
		 		$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";
		 	}	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}		
	  //2)$cmdcode=1002
		$cmdcode=1002;	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='1002' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			$cmdcontent="abc";
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}			  
	  
	  //3)$cmdcode=1003
		$cmdcode=1003;	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='1003' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		    $cmdcontent=mysql_result($result,0,"cmdbody"); 
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}
		
		
	  //4)$cmdcode=1004
		$cmdcode=1004;  //获取订单的记录	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='$cmdcode' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		    $cmdcontent=mysql_result($result,0,"cmdbody"); 
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}

	  //5)$cmdcode=1005
		$cmdcode=1005;  //修改指定订单的记录的内容	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='$cmdcode' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		    $cmdcontent=mysql_result($result,0,"cmdbody"); 
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}
	  //6)$cmdcode=1006
		$cmdcode=1006;  //远程开箱	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='$cmdcode' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		    $cmdcontent=mysql_result($result,0,"cmdbody"); 
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}


	  //7)$cmdcode=1007
		$cmdcode=1007;  //远程重启计算机	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='$cmdcode' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		    $cmdcontent=mysql_result($result,0,"cmdbody"); 
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$cmdcontent="abc";
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}

	  //8)$cmdcode=1008
		$cmdcode=1008;  //下载当前所有在箱的订单（7天内）	
		$result=mysql_query("SELECT id,cmdbody FROM  smtbx_downcmd where devicesn='$account'  and  command='$cmdcode' and status='1' limit 1",$db2);
  		$num= mysql_numrows ($result);
		if($num!=0)
		{
		    $cmdcontent=mysql_result($result,0,"cmdbody"); 
		 	$id=mysql_result($result,0,"id");
		 	$_SESSION['id']=$id;
		 	$sqlstr="UPDATE `smtbx_downcmd` SET `status`='2'  where  id='$id'";	
  		 	mysql_query($sqlstr,$db2); 
			//---
			$_SESSION['cmdcode']=$cmdcode;  
     		if($cmdcontent=="")  //没有要发送的命令
	 		{
	   			$cmdcode=0;
	 		}
  	  		$res=array("cmdcode"=>"$cmdcode","cmdcontent"=>"$cmdcontent");
  	  		return  $res;  											 		 		
		}

	  
     //----没有要发送的命令-------
	 $cmdcode=0;	
     $_SESSION['cmdcode']=$cmdcode;  
     if($cmdcontent=="")  //没有要发送的命令
	 {
	   $cmdcode=0;
	 }
  	  $res=array("cmdcode"=>"$cmdcode","cmdcontent"=>urlencode("$cmdcontent"));
  	  return  $res;  		
	     
}


function  updateinfostatus($devicesn,$infostatus,$boxid)
{
   if($infostatus=="")
   return;
   $db2=conn2();
   $sqlstr="SELECT boxinfo FROM `smtbx_info` WHERE  `devicesn` ='$devicesn' LIMIT 1";
   $result = mysql_query($sqlstr,$db2);
   $num= mysql_numrows($result);
   
   $strinfo=mysql_result($result,0,"boxinfo"); 
     
   $infostatus1=split(",",$infostatus);
   $infostatus2="";
    for($i=0;$i<5;$i++)
	{
	  $infostatus2=$infostatus2.$infostatus1[$i]; 
	}
	   
    $strinfo1=substr_replace($strinfo,$infostatus2,20*($boxid-1)+15,5);
	
	$sqlstr="UPDATE `smtbx_info` SET `boxinfo` = '$strinfo1' WHERE `devicesn` ='$devicesn' LIMIT 1";		
    $result = mysql_query($sqlstr,$db2); 	

}


function  boxinfostrconvert($strinfo)
{

$strinfo0=str_replace(";;", ",,",$strinfo);
$strinfo1=split(",,",$strinfo0);
$row=floor(count($strinfo1)/12);
$str="";   		
   for($i=0;$i<$row;$i++)
   {
     $num1= sprintf("%03d",$strinfo1[$i*12+1]);  //boxno
     $num2= sprintf("%02d",$strinfo1[$i*12+2]);  //deskno
     $num3= sprintf("%01d",$strinfo1[$i*12+3]);  //deskAB
     $num4= sprintf("%02d",$strinfo1[$i*12+4]);  //deskboxno
     $num5= sprintf("%01d",$strinfo1[$i*12+5]);  //boxtype
     $num6= sprintf("%06d",0);  //cmd
     $num7= sprintf("%01d",$strinfo1[$i*12+7]);  //open
     $num8= sprintf("%01d",$strinfo1[$i*12+8]);  //locked
     $num9= sprintf("%01d",$strinfo1[$i*12+9]);  //goods
     $num10= sprintf("%01d",$strinfo1[$i*12+10]);  //occupy	 
     $num11= sprintf("%01d",$strinfo1[$i*12+11]);  //failure
 	$str=$str.$num1.$num2.$num3.$num4.$num5.$num6.$num7.$num8.$num9.$num10.$num11;	      
   }			
  return $str;
}


//------------------------------------
function  str2hex2sum($str)
{
  if($str!="")
 {
  $s1=chunk_split(bin2hex($str), 2, ',');
 $s=split(",",$s1);
 $n=count($s);
 $sum=0; 
 for($i=0;$i<$n-1;$i++)
 {
   $sum=$sum+hexdec($s[$i]);
 }
 }
 else
 {
   $n=0;
   $sum=0;
 }
 return $sum;
}
//----------------------------------
function  jiami( $pw)
{
   $pw=base64_encode($pw);
   $len=strlen($pw);
   $pw1=substr($pw,0,$len-2);
   $pw2=substr($pw,-2);
   $pw=$pw2.$pw1;   
   return $pw;

}
function  jiemi( $pw)
{
   $pw1=substr($pw,2);
   $pw2=substr($pw,0,2);
   $pw=$pw1.$pw2;      
   $pw=base64_decode($pw);
   return $pw;
}

?>