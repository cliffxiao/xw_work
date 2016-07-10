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
		
		//读取en_news新闻内容文件
		$strSQL = "select * from news where columns='行业资讯' and is_sitesm =1 ";
		$catid = 661;
		$newsarr = $this->getResults($strSQL);

		for($i=0;$i<count($newsarr);$i++){
			
			$this->insert_article($newsarr[$i],$catid);

		}
		exit;
		
	}
	

	
	/*
	 * 判断是否已插入了当天的market_news
	 * return true 已插入
	 */
	private function insert_article($newsarr,$catid)
	{

		$catid = $catid;
		$username = "lianping";
		$author = "ICCSINO";
		$copyfrom = "-ICCSINO-";
		$editor = "lianping";
		$title = $newsarr['title'];
		$content = htmlspecialchars_decode($newsarr['newsContents']);
		$addtime = strtotime($newsarr['createTime']);	
		$strkey = "";
		
		if($newsarr['key1']<>""){
		$strkey .= $newsarr['key1']."|";
		}
		if($newsarr['key2']<>""){
		$strkey .= $newsarr['key2']."|";
		}
		if($newsarr['key3']<>""){
		$strkey .= $newsarr['key3']."|";
		}
		if($newsarr['key4']<>""){
		$strkey .= $newsarr['key4']."|";
		}

		$edittime = strtotime($newsarr['editTime']);	

		$ip = "222.44.185.120";
		$template = "";
		$tag = substr(trim($strkey), 0,-1);  
		$introduce = trim($newsarr['brief']);
		
		//判断是否存在该标题
		if(!$this->check_current_insert_article_news($catid,$title)){
			$strSQL = "insert into store_article_21
				   (catid,username,title,tag,introduce,author,copyfrom,editor,addtime,edittime,ip,template,status)					     values('{$catid}','{$username}','{$title}','{$tag}','{$introduce}','{$author}','{$copyfrom}','{$editor}','{$addtime}',
					'{$edittime}','{$ip}','$template',3) ";
	
			mysql_query($strSQL,$this->m_obj2);
			$new_article_id = mysql_insert_id($this->m_obj2);				
			//反过来更新linkurl
			$linkurl = "show-htm-itemid-".$new_article_id.".html";
			$upstrSQL = "update store_article_21 set linkurl='{$linkurl}' where itemid='{$new_article_id}' ";
			mysql_query($upstrSQL,$this->m_obj2);	
			//更新内容
			$strSQL = "insert into store_article_data_21
				   (itemid,content)
					values('{$new_article_id}','{$content}') ";
			mysql_query($strSQL,$this->m_obj2);		
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
set_time_limit(0);
$obj = new zhuaqunews();

$obj->do_auto();


?>