<?php 

class zhuaqunews
{
	
	private $m_obj;
	private $m_obj2;
	
	public function __construct(){
		date_default_timezone_set("Asia/Shanghai");	
		$this->m_obj = mysql_connect("120.26.231.218", "iccsino", "kzh50593570");
		//$this->m_obj = mysql_connect("222.73.37.18", "engicbattery", "kzh50593570");
		//$this->m_obj = mysql_connect("192.168.3.245", "root", "");
		mysql_query("SET character_set_connection='utf8', character_set_results='utf8',character_set_client=binary",$this->m_obj);
		mysql_select_db("icbatterystore",$this->m_obj);
				
	}
	public function do_auto()
	{
		
		//读取en_news新闻内容文件
		
		
		$newsarr = file("ab.csv");
		for($i=0;$i<count($newsarr);$i++){
			$tmpcat = explode(",", $newsarr[$i]);
			$cname = $tmpcat[0];
			$zg = $tmpcat[1];
			$zw = $tmpcat[2];
			
			echo $cname.$zg.$zw."<br />";
			//$this->insert_cat($cat_name,$cat_en,$cat_sort);

		}
		exit;
		
	}
	


	
	/*
	 * 判断是否已插入了当天的market_news
	 * return true 已插入
	 */
	private function insert_cat($cat_name,$cat_en,$cat_sort)
	{

		$moduleid = 7;
		$catname = trim($cat_name);
		$catdir = $cat_en;
		$letter = $cat_sort;
		$level = 1;
		$group_list = "3,5,6,9,7,8,10,11,12,13";
		$group_show = "9,7,8,10";
		$parentid = '761';
		$arrparentid = '0,761';
		$child = 0;
		
		$strSQL = "insert into store_category(moduleid,catname,catdir,letter,level,parentid,arrparentid,child,group_list,group_show)
				  values('$moduleid','$catname','$catdir','$letter','$level','$parentid','$arrparentid','$child', '$group_list','$group_show') ";

			mysql_query($strSQL,$this->m_obj2);
			
			$new_cat_id = mysql_insert_id($this->m_obj2);				
			//反过来更新linkurl
			$linkurl = "list-htm-catid-".$new_cat_id.".html";
			$upstrSQL = "update store_category set linkurl='{$linkurl}',listorder='$new_cat_id' where catid='{$new_cat_id}'  ";
			mysql_query($upstrSQL,$this->m_obj2);	


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
	 * 随机生成分秒
	 * return 34:31
	 */
	private function get_output_time($oldtime){
		$minute = intval(date("i",strtotime($oldtime)));

		
		if($minute >10){
			$minute = rand(1,30);
		}
		$second = intval(date("s",strtotime($oldtime)));
		
		$restime = date("Y-m-d 10:").$minute.":".$second;
		
		return date("Y-m-d H:i:s",strtotime($restime));
	}


	/*
	 * 判断是否已插入了当天的market_news
	 * return true 已插入
	 */
	private function check_current_insert_article_news($catid,$title)
	{

		$res = array();
		$strSQL = "select * from store_article_21 where catid='".$catid."' and title='".$title."' ";
		$res = $this->getResults($strSQL,$this->m_obj2);
		if(count($res)>0){
			return true;
		}else{
			return false;
		}

	}
	
}	

header("Content-Type: text/html; charset=UTF-8");

$obj = new zhuaqunews();

$obj->do_auto();


?>