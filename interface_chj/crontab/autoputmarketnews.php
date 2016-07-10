<?php 

class autopricenews
{
	private	$lastdayMarketNewsDataArr;
	
	private $m_obj;

	
	public function __construct(){
		date_default_timezone_set("Asia/Shanghai");	
		$this->m_obj = mysql_connect("127.0.0.1", "root", "343221");
		//$this->m_obj = mysql_connect("192.168.3.245", "root", "");
		mysql_query("SET character_set_connection='utf8', character_set_results='utf8',character_set_client=binary",$this->m_obj);
		mysql_select_db("chinacarbon_new");
		$this->lastdayMarketNewsDataArr = array();
	}
	public function do_auto(){
		//1.从market_news中取出昨天(上一次)的market_news（及所有数据）
		if($this->get_last_market_news_data()){
			/*根据1中的market_news.id取出market_items中的数据;
			 *入新的market_news,修改对应的日期，标题，
			 *插入对应新的market_items的数据，
			 *同时写入英文对应表
			 */
			
			$dataArr = $this->lastdayMarketNewsDataArr;

			for($i=0;$i<count($dataArr);$i++){
				$data['old_item_id'] = $dataArr[$i]['itemid'];
				$data['catid'] = $dataArr[$i]['catid'];
				//$data['level'] = $dataArr[$i]['level'];
				
				$data['username'] = $dataArr[$i]['username'];
				$data['editor'] = $dataArr[$i]['editor'];		
				
				//处理标题
				$m_day = date("d");
				$data['title'] = $m_day.substr($dataArr[$i]['title'],2);
				$data['content'] = $dataArr[$i]['content'];
				$data['remark'] = $dataArr[$i]['remark'];
				$inputtime = $this->get_output_time($dataArr[$i]['addtime']);
				$data['addtime'] = strtotime($inputtime);
				$data['adddate'] = date("Y-m-d",strtotime($inputtime));
				$data['edittime'] = strtotime($inputtime);
				
				$this->insert_into_market_news($data);
			}
		}else{
		
			exit;
		}
	}
	
	//
	private function get_last_market_news_data(){
		$lastday = $this->get_last_day();
		//判断当天是否已经插入了数据

		if($this->check_current_insert_market_news()){
		
			return false;
		}else{
			$strSQL = "select sq.itemid,sq.catid,sq.catid,sq.username,sq.title,sq.editor,sq.remark,sq.addtime,sqd.content from store_quote sq,store_quote_data sqd where sq.adddate='".$lastday."' and sq.itemid=sqd.itemid ";
	
			$this->lastdayMarketNewsDataArr = $this->getResults($strSQL);
			return true;
		}
	}

	private function get_last_day()
	{
		$strSQL = "select adddate from store_quote order by adddate desc limit 1 ";
		$res = $this->getResults($strSQL);
		return $res[0]['adddate'];
	}
	
	/*
	 * 判断是否已插入了当天的market_news
	 * return true 已插入
	 */
	private function check_current_insert_market_news()
	{
		$res = array();
		$nowdate = date("Y-m-d");
		$strSQL = "select * from store_quote where adddate='".$nowdate."' ";
		$res = $this->getResults($strSQL);
		if(count($res)>0){
			return true;
		}else{
			return false;
		}
	}
	
	private function getResults($strSQL)
	{
		$resultArr = array();
		$objResult = mysql_query($strSQL,$this->m_obj);
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
			$minute = rand(5,10);
		}
		$second = intval(date("s",strtotime($oldtime)));
		
		$restime = date("Y-m-d 08:").$minute.":".$second;
		
		return date("Y-m-d H:i:s",strtotime($restime));
	}
	
	private function insert_into_market_news($data)
	{
	
	

		$old_market_id = $data['old_item_id'];
		$catid = $data['catid'];
		$username = $data['username'];
		$editor = $data['editor'];
		$title = $data['title'];
		$content = $data['content'];
		$remark = $data['remark'];
		$addtime = $data['addtime'];		
		$adddate = $data['adddate'];
		$edittime = $data['edittime'];
		$ip = "220.248.16.194";
		$template = "";
		

		$strSQL = "insert into store_quote 
			   (catid,username,title,editor,remark,addtime,adddate,edittime,ip,template,status)
				values('{$catid}','{$username}','{$title}','{$editor}','{$remark}','{$addtime}',
				'{$adddate}','{$edittime}','{$ip}','$template',3) ";
		mysql_query($strSQL,$this->m_obj);
		$new_market_id = mysql_insert_id($this->m_obj);
		
		//反过来更新linkurl
		$linkurl = "show.php?itemid=".$new_market_id;
		$upstrSQL = "update store_quote set linkurl='{$linkurl}' where itemid='{$new_market_id}' ";
		mysql_query($upstrSQL,$this->m_obj);	
		//更新内容
		$strSQL = "insert into store_quote_data
			   (itemid,content)
				values('{$new_market_id}','{$content}') ";
		mysql_query($strSQL,$this->m_obj);		
		
		
		//插入market_items
		$data2['old_market_id'] =  $old_market_id;
		$data2['new_market_id'] = $new_market_id;
		$this->insert_into_market_items($data2);
		

		return true;
	}
	
	private function save_en_market_news($data)
	{
		$old_cn_newsId = $data['old_market_id'];
		$new_cn_newsId = $data['new_market_id'];
		$createTime = $data['createTime'];
		$strSQL = "INSERT INTO en_market_news (cn_newsId, adminId, title, subtitle, contents, varietyFatherId, varietyFatherName, varietyId, varietyName, factoryId, factoryName, cityId, cityName, key1, key2, key3, key4, remark, createTime) 
					select $new_cn_newsId, adminId, title, subtitle, contents, varietyFatherId, varietyFatherName, varietyId, varietyName, factoryId, factoryName, cityId, cityName, key1, key2, key3, key4, remark, '$createTime' from en_market_news where cn_newsId = '{$old_cn_newsId}' ";
		mysql_query($strSQL,$this->m_obj);
	}
	
	private function insert_into_market_items($data){
		$old_market_id = $data['old_market_id'];
		$new_market_id = $data['new_market_id'];
		$strSQL = "insert into market_items (catid,marketnewsId, kname, specs, origin, price, priceMax, oldPrice, oldPriceMax, isGather, isLine, remark,orderId) 
					select catid,$new_market_id, kname, specs, origin, price, priceMax, oldPrice, oldPriceMax, isGather, isLine, remark,orderId from market_items where marketnewsId = '{$old_market_id}' ";
		mysql_query($strSQL,$this->m_obj);

	}
	
}	


$obj = new autopricenews();

$obj->do_auto();


?>