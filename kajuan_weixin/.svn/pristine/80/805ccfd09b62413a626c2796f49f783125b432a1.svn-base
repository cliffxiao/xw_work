<?php 
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);
require_once "../lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";
require_once 'log.php';
require_once '../include/init.php';

//初始化日志
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

//打印输出数组信息
function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    }
}
//记录当前系统订单号
$trade_no = $_POST['orderid'];
if($trade_no){
	$_SESSION['orderid'] = $trade_no;
}

//①、获取用户openid
$tools = new JsApiPay();
$openId = $tools->GetOpenid();

global $db;

$trade_no = $_SESSION['orderid'];

$totalprice = $db->get_one("select goods_amount,order_id from zft_order_info where order_sn='$trade_no'");
$order_goods = $db->get_one("select goods_name,goods_number from zft_order_goods where order_id=".$totalprice['order_id']);

$price = 100*$totalprice['goods_amount'];
$price = 1;

//②、统一下单
$input = new WxPayUnifiedOrder();
$input->SetBody("上海启峻信息科技有限公司");
$input->SetAttach("上海启峻信息科技有限公司");
//WxPayConfig::MCHID.date("YmdHis")
$input->SetOut_trade_no($trade_no);
$input->SetTotal_fee($price);
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("test");
$input->SetNotify_url("http://weixin.hemaquan.com/payment/weixin/example/notify.php");
$input->SetTrade_type("JSAPI");
$input->SetOpenid($openId);
$order = WxPayApi::unifiedOrder($input);
//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
//printf_info($order);
$jsApiParameters = $tools->GetJsApiParameters($order);

//获取共享收货地址js函数参数
$editAddress = $tools->GetEditAddressParameters();

//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */
?>

<html>
<meta name="format-detection" content="telephone=no" />
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/> 
    <title>订单信息</title>
    <script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',
			<?php echo $jsApiParameters; ?>,
			function(res){
				WeixinJSBridge.log(res.err_msg);
				//alert(res.err_code+res.err_desc+res.err_msg);
				if(res.err_msg == "get_brand_wcpay_request:ok" ){
				    //在这里面进行 支付成功操作！
					location.href = "http://weixin.hemaquan.com/index.php?m=default&c=order&a=success&id=<?php echo $trade_no; ?>";
				}
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	//获取共享地址
//	function editAddress()
//	{
//		WeixinJSBridge.invoke(
//			'editAddress',
//			<?php echo $editAddress; ?>,
//			function(res){
//				var value1 = res.proviceFirstStageName;
//				var value2 = res.addressCitySecondStageName;
//				var value3 = res.addressCountiesThirdStageName;
//				var value4 = res.addressDetailInfo;
//				var tel = res.telNumber;
//				
//				alert(value1 + value2 + value3 + value4 + ":" + tel);
//			}
//		);
//	}
//	
//	window.onload = function(){
//		if (typeof WeixinJSBridge == "undefined"){
//		    if( document.addEventListener ){
//		        document.addEventListener('WeixinJSBridgeReady', editAddress, false);
//		    }else if (document.attachEvent){
//		        document.attachEvent('WeixinJSBridgeReady', editAddress); 
//		        document.attachEvent('onWeixinJSBridgeReady', editAddress);
//		    }
//		}else{
//			editAddress();
//		}
//	};
	
	</script>
</head>
<body>
	<div style="font-size:1.2em;padding:0.8em 0.5em 0.8em;border-top:1px solid #DDE4E6;">订单信息</div>
    <div style="padding:0.8em 0.6em;">订单编号：<a style="text-decoration:none;"><?php echo $trade_no;?></a></div>
    <div style="padding:0.5em 0 0.8em 0;margin:0 0.6em;border-bottom:1px solid #DDE4E6;"><?php echo $order_goods['goods_name'];?><span style="float:right;">x<?php echo $order_goods['goods_number'];?></span></div>
    <div style="border-bottom:1px solid #DDE4E6;padding:1em 0.6em;">支付金额：<span style="color:red;font-size:1.1em;">￥<?php echo $totalprice['goods_amount'];?></span></div>
	<div align="center" style="margin:3em 0;">
		<button style="width:94%; height:2.5em; border-radius: 10px;background-color:#03a9f4;color:white; border:none; font-size:1.2em;" type="button" onclick="callpay();" >确认支付</button>
	</div>
</body>
</html>