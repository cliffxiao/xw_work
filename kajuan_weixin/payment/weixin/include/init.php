<?php
header('Content-type: text/html; charset=utf-8');
session_start();
require 'config.inc.php';
require 'db_mysql.class.php';

$db = new db_mysql;

$db->connect(DB_HOST, DB_USER, DB_PW, DB_NAME, DB_PCONNECT, DB_CHARSET);

function do_active($trade_no,$user_id){
	global $db;
	//分配卡券
	$order_goods = $db->get_one("select g.goods_id as goodsid,g.goods_number as goods_number from zft_order_info as o left join zft_order_goods as g on o.order_id=g.order_id where o.order_sn='$trade_no'");
	$goodsid = $order_goods['goodsid'];
	$goods_number = $order_goods['goods_number'];
	$card_id_res = $db->select("select card_id from zft_virtual_card where goods_id=$goodsid and is_saled=0 limit $goods_number");
    $card_ids = "";
    if(count($card_id_res)>1){
    	$card_id_res_new = array();
    	foreach ($card_id_res as $ckey=>$cval){
    		$card_id_res_new[$ckey] = $cval['card_id'];
    	}
    	$card_ids = implode(",", $card_id_res_new);
    }else{
    	$card_ids = $card_id_res[0]['card_id'];
    }
    if($card_ids){
    	$card_res = $db->query("update zft_virtual_card set is_saled=1,order_sn='$trade_no' where card_id in ($card_ids)");
    }
	//激活卡券
	$card_id = $db->select("select card_id from zft_virtual_card where order_sn='$trade_no'");
	$card_id_new = array();
	foreach ($card_id as $ckey=>$cval){
		$card_id_new[$ckey] = $cval['card_id'];
	}
	$card_id_str = implode(",", $card_id_new);
	$md5_str = md5("zftmd5".$user_id);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://weixin.hemaquan.com/index.php?m=default&c=order&a=active");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "id=$card_id_str&key=$md5_str&userid=$user_id");
	$res = curl_exec( $ch );
	curl_close( $ch );
	$resdata = json_decode($res);
	$time = date("Y:m:d H:i:s",time());
	$result = $resdata->success;
	if($result==1){
		$db->query("update zft_virtual_card set is_act=1 where order_sn='$trade_no'");
		return true;
	}else{
		return false;
	}
}