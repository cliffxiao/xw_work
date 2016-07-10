<?php
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once "../lib/WxPay.Api.php";
require_once '../lib/WxPay.Notify.php';
require_once 'log.php';
require_once '../include/init.php';

//初始化日志
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			global $db;
			$trade_no = $result['out_trade_no'];
			$transaction_id = $result['transaction_id'];
			$is_pay = $db->get_one("select * from zft_order_info where pay_status=2 and order_sn='$trade_no'");
			if(!$is_pay){
				$pay_time = time();
				$updateorder = $db->query("update zft_order_info set order_status=1,pay_status=2,pay_time='$pay_time' where order_sn='$trade_no'");
				$order_info = $db->get_one("select user_id from zft_order_info where order_sn='$trade_no'");
				do_active($trade_no,$order_info['user_id']);
				Log::DEBUG("query:" . json_encode($updateorder));
				header("Location: http://weixin.hemaquan.com/index.php?m=default&c=order&a=success&id=$trade_no");
			}
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}
}

Log::DEBUG("begin notify");
$notify = new PayNotifyCallBack();
$notify->Handle(false);
