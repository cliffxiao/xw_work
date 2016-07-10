<?php

include 'zy.php';
set_time_limit(0);

getResult2();

function insert($data,$calc,$title){
	echo iconv('UTF-8', 'GB2312', $title)."--";
	$result = array();
	$words = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
	foreach ($words as $key=>$val){
		foreach ($data as $key2=>$val2){
			$str = substr(Pinyin(str_replace(array(" ","\n","<br />","啶","嘧","溴","0","1","2","3","4","5","6","7","8","9","枞"), array("","","","定","密","秀","","","","","","","","","","","从"), strtolower($val2['title'])),1), 0,1);
			if($str==$val){
				$data[$key2]['orderid'] = ord(substr(Pinyin($val2['title'],1), 0,1))*pow(10, 4)+ord(substr(Pinyin($val2['title'],1), 1,1))*pow(10, 3)+ord(substr(Pinyin($val2['title'],1), 2,1))*pow(10, 2)+ord(substr(Pinyin($val2['title'],1), 3,1))*pow(10, 1)+ord(substr(Pinyin($val2['title'],1), 4,1))*pow(10, 0);
				$result['pinyin_'.$val][] = $data[$key2];
			}
		}
	}
	
	foreach ($words as $keys=>$vals){
		if(isset($result['pinyin_'.$vals])){
		//	deltable($vals);
			foreach ($result['pinyin_'.$vals] as $keys2=>$vals2){
				$res = insertResult2($vals, $vals2['catid'], $vals2['title'], $vals2['itemid'], $vals2['orderid']);
				if($res){
					echo $calc."success!<br />";
				}
			}
		}
	}
}

function getResult2(){
	$mysql_server_name='222.73.37.18'; 
	$mysql_username='icbatterystore'; 
	$mysql_password='kzh50593570'; 
	$mysql_database='icbatterystore';
	$conn=mysql_connect($mysql_server_name,$mysql_username,$mysql_password,$mysql_database); 
	$sql="select catid,itemid,title from store_article_21 where letter=''";
	mysql_select_db($mysql_database,$conn); 
	mysql_query("SET NAMES 'utf8'");
	$result=mysql_query($sql);
	$i = 0;
	while($list=mysql_fetch_array($result,MYSQL_ASSOC)){
		$list_arr = array();
		$list_arr[$i]=$list;  
		
		insert($list_arr,$i,$list['title']);      
		$i++;    
		sleep(0.03);
	}
	mysql_close($conn);
}

function insertResult2($table,$catid,$title,$contentid,$orderid){
	$mysql_server_name='222.73.37.18'; 
	$mysql_username='icbatterystore'; 
	$mysql_password='kzh50593570'; 
	$mysql_database='icbatterystore';
	$conn=mysql_connect($mysql_server_name,$mysql_username,$mysql_password,$mysql_database); 
	$sql="update store_article_21 set letter='$table' where itemid='$contentid'";

	mysql_select_db($mysql_database,$conn); 
	mysql_query("SET NAMES 'utf8'");
	$result=mysql_query($sql);
	mysql_close($conn);
	return $result;
}