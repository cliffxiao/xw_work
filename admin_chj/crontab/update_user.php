<?php 

class zhuaqunews
{
	
	private $m_obj;
	private $m_obj2;
	
	public function __construct(){
		date_default_timezone_set("Asia/Shanghai");	
		$this->m_obj = mysql_connect("222.73.37.18", "chinacarbon", "kzh50593570");
		//$this->m_obj = mysql_connect("222.73.37.18", "engicbattery", "kzh50593570");
		//$this->m_obj = mysql_connect("192.168.3.245", "root", "");
		mysql_query("SET character_set_connection='utf8', character_set_results='utf8',character_set_client=binary",$this->m_obj);
		mysql_select_db("chinacarbon",$this->m_obj);
		
		$this->m_obj2 = mysql_connect("222.73.37.18", "icbattery", "kzh50593570");
		//$this->m_obj2 = mysql_connect("192.168.3.245", "root", "");
		
		mysql_query("SET character_set_connection='utf8', character_set_results='utf8',character_set_client=binary",$this->m_obj2);
		mysql_select_db("chinacarbon_new",$this->m_obj2);		
		
	}
	public function do_auto()
	{
		
		//读取用户信息
		$strSQL = "select u.username,u.paymentBeginTime,u.paymentEndTime,u.password,u.email,u.timeCreated,u.timeLastModified,u.registerIp,u.adminId,up.cnName,up.jobs,up.mobile,up.telephone,up.msn,up.qq,up.conEmail,up.otherContact,up.remark,up.companyName,up.companyShortname,up.companyMain,up.companyInfo,up.province,up.city,up.companyAddress,up.companyZip,up.companyPhone,up.companyFax,up.companyEmail,up.companyWebSite,up.companyRemark from user u,userprofile up where u.id=up.userId and u.id in(14890,13753,14616,10927,10244,9709,9548,9198,8060,2867,2477,1661,451,385,115,14683,14252,13282,10787,10542,8993,8786,2804,13551,9998,7592,3034,2723,1599,1569,787,726,375,14996,14271,14269,13885,13678,10453,9868,9590,9176,8973,8123,6577,6544,5547,1806,1027,14967,14815,14619,14287,14169,13560,13450,10267,10047,9966,13290,9874,9599,9522,9487,9091,9060,8183,7802,7478,6776,3479,3249,2110,790,7990,14716,15226,15198,15175,15187,15236)";

		$userInfoarr = $this->getResults($strSQL);

		for($i=0;$i<count($userInfoarr);$i++){
			
			$this->insert_userinfo($userInfoarr[$i]);

		}
		
		exit;
		
	}
	

	
	/*
	 * 判断是否已插入了当天的market_news
	 * return true 已插入
	 */
	private function insert_userinfo($userInfoarr)
	{
		$username = $userInfoarr['username'];
		$passport = $userInfoarr['username'];
		$company =  $userInfoarr['companyName'];
		$password = md5(md5($userInfoarr['password']));
		$payword = $password;
		$email = $userInfoarr['email'];
		
		if($email ==""){
		
			$email = rand(10,500)."@icc.com";
		}
		
		$truename = $userInfoarr['cnName'];
		$mobile = $userInfoarr['mobile'];
		$msn = $userInfoarr['msn'];
		$qq = $userInfoarr['qq'];
		$career = $userInfoarr['jobs'];
		$originalpassword = $userInfoarr['password'];
		$originalpaypassword = $userInfoarr['password'];
		
		$adminArrs = $this->get_adminusers($userInfoarr['adminId']);
		$adminid = $adminArrs['id'];
		$admintruename = $adminArrs['name'];
		
		$groupid = 7;      //收费会员
		$edittime = strtotime($userInfoarr['timeLastModified']);  //转换成unix时间戳
		$regtime = strtotime($userInfoarr['timeCreated']);        //转换成unix时间戳
		$loginip = $userInfoarr['registerIp'];
		$regip = $loginip;
		$logintime = $regtime;                           //转换成unix时间戳
		
		$vip = 1;
		$vipt = 1;
		$type = "企业单位";
		
		$areaid = $this->get_area_id($userInfoarr['city']);   //根据城市查找对应的ID

		
		$mode = "制造商,贸易商";
		$regunit = "人民币";
		$business = $userInfoarr['companyMain'];
		$telephone = $userInfoarr['companyPhone'];
		$fax = $userInfoarr['companyFax'];
		$mail = $userInfoarr['companyEmail'];
		$address = $userInfoarr['companyAddress'];
		$postcode = $userInfoarr['companyZip'];
		$homepage = $userInfoarr['companyWebSite'];
		
		$fromtime = strtotime($userInfoarr['paymentBeginTime']);    //转换成unix时间戳
		$totime = strtotime($userInfoarr['paymentEndTime']);        //转换成unix时间戳
		$introduce = $userInfoarr['companyInfo']; 
		$content = $introduce;
		
		//判断该用户是否插入过
		if(!$this->check_current_insert_username($username)){

			//插入store_member
			$strSQL = "insert into store_member
						(username,passport,company,password,payword,email,truename,
						mobile,msn,qq,career,groupid,regid,areaid,edittime,regip,
						regtime,loginip,logintime,originalpassword,originalpaypassword,
						adminid,admintruename)
						values
					    ('$username','$passport','$company','$password','$payword','$email','$truename',
					    '$mobile','$msn','$qq','$career','$groupid','$regip','$areaid','$edittime','$regip',
						'$regtime','$loginip','$logintime','$originalpassword','$originalpaypassword',
						'$adminid','$admintruename') ";
			mysql_query($strSQL,$this->m_obj2);
			$new_user_id = mysql_insert_id($this->m_obj2);		

	
			//插入store_company
			$strSQL2 = "insert into store_company
				   (userid,username,groupid,company,vip,vipt,type,
				   areaid,mode,regunit,business,telephone,fax,mail,
				   address,postcode,homepage,fromtime,totime,introduce)
				   values
				   ('$new_user_id','$username','$groupid','$company','$vip','$vipt','$type',
				   '$areaid','$mode','$regunit','$business','$telephone','$fax','$mail',
				   '$address','$postcode','$homepage','$fromtime','$totime','$introduce') ";
			mysql_query($strSQL2,$this->m_obj2);	

			//插入store_company_data
			$strSQL3 = "insert into store_company_data
				   (userid,content)
				   values
				   ('$new_user_id','$content') ";
			mysql_query($strSQL3,$this->m_obj2);
			

				
		}	
		
		
	}
	
	private function getResults($strSQL,$m_obj="")
	{
		$resultArr = array();
		if($m_obj==""){
			$m_obj = $this->m_obj;
		
		}
		$objResult = mysql_query($strSQL,$m_obj);
		if($objResult)
		{
			//取得返回集
			while ($row = mysql_fetch_array($objResult,MYSQL_ASSOC))
			{
				$resultArr[] = $row;
			}
			mysql_free_result($objResult);
		}
		return $resultArr;
		
	}
	
	/*
	 * 获取城市ID
	 * return 34:31
	 */
	private function get_area_id($areaname)
	{
		$res = array();
		$strSQL = "select areaid from store_area where areaname like'".$areaname."%' ";
		$res = $this->getResults($strSQL,$this->m_obj2);
		$areaid = isset($res[0]['areaid'])?$res[0]['areaid']:1;
		return $areaid;
	}


	/*
	 * 获取新管理员名称及ID
	 * return 34:31
	 */
	private function get_adminusers($oldadminid)
	{
		$res['name'] = '连萍';
		$res['id'] = '2';
		
		if($oldadminid ==6){
			$res['name'] = '程玲';
			$res['id'] = '75';
		}elseif($oldadminid ==27){
			$res['name'] = '沈柳';
			$res['id'] = '440';
		}elseif($oldadminid ==15){
			$res['name'] = '潘芸';
			$res['id'] = '32';
		}elseif($oldadminid ==12){
			$res['name'] = '王梁';
			$res['id'] = '5';
		}elseif($oldadminid ==21){
			$res['name'] = '戴含笑';
			$res['id'] = '8';
		}elseif($oldadminid ==24){
			$res['name'] = '张艳蓉';
			$res['id'] = '6';
		}
		return $res;
	}


	/*
	 * 判断用户名是否已插入了store_member
	 * return true 已插入
	 */
	private function check_current_insert_username($username)
	{

		$res = array();
		$strSQL = "select * from store_member where username='".$username."' ";
		$res = $this->getResults($strSQL,$this->m_obj2);
		if(count($res)>0){
			return true;
		}else{
			return false;
		}
	}
	
}	

header("Content-Type: text/html; charset=UTF-8");
set_time_limit(0);
$obj = new zhuaqunews();

$obj->do_auto();


?>