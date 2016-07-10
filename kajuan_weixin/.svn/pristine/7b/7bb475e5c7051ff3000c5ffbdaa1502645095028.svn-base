<?php
/**
 * 订单中心
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class OrderController extends CommonController {

    private $size = 10;
    private $page = 1;

    public function __construct() {
        parent::__construct();
    }
    
    /* ------------------------------------------------------ */
    //列表
    /* ------------------------------------------------------ */
    public function index() {
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        /* 显示模板 */
        $this->display('order_list.dwt');
    }

	/* ------------------------------------------------------ */
    //创建订单
    /* ------------------------------------------------------ */
    public function create() {
    	//格式化返回数组
        $result = array(
            'msg' => '',
            'code' => '0',
        	'order_sn' => ''
        );
        $goodsid = $_REQUEST['goodsid'];
        $goods_number = $_REQUEST['goods_number'];
        //判断库存
        $stock_num = model('Order')->query_stock_num($goodsid);
        if($stock_num<$goods_number){
        	$result ['msg'] = "库存不足 请重新选择";
        	die(json_encode($result));
        }
        $goods = model('Goods')->get_goods_info($goodsid);
        $order['user_id'] = $_SESSION["user_id"];
        $order['order_sn'] = get_order_sn(); // 获取新订单号
        $order['goods_amount'] = $goods_number*$goods['shop_price'];
        $order['order_amount'] = $goods_number*$goods['shop_price'];
        $order['add_time'] = time();
        $order['order_type'] = 1;
        $order_id = model('Order')->create_order_kj($order);
        if($order_id){
        	$ordergoods['order_id'] = $order_id;
        	$ordergoods['goods_id'] = $goodsid;
        	$ordergoods['goods_name'] = $goods['goods_name'];
        	$ordergoods['goods_sn'] = $goods['goods_sn'];
        	$ordergoods['goods_number'] = $goods_number;
        	$ordergoods['market_price'] = $goods['market_price'];
        	$ordergoods['goods_price'] = $goods['shop_price'];
        	$ordergoods['is_real'] = 0;
        	$ordergoods['extension_code'] = 'virtual_card';
        	$res = model('Order')->create_ordergoods_kj($ordergoods);
        	if($res){
        		$res_card = model('Order')->update_goods_number($goodsid,$goods_number);
        		if($res_card){
        			$result ['code'] = "1";
        			$result ['msg'] = "订单创建成功";
        			$result ['order_sn'] = $order['order_sn'];
        		}else{
        			$result ['msg'] = "虚拟卡库存不足";
        		}
        	}
        }else{
        	$result ['msg'] = "订单创建失败";
        }
        die(json_encode($result));
    }
    
	/* ------------------------------------------------------ */
    //购券成功
    /* ------------------------------------------------------ */
    public function success() {
        $order_sn = $_REQUEST['id'];
        $order_res = model('Order')->get_orderinfo_success($order_sn);
        $is_active = model('Order')->get_active_success($order_sn);
        $this->assign('page_title', "购卡成功");
        $this->assign('order', $order_res);
        $this->assign('is_active', $is_active);
        /* 显示模板 */
        $this->display('order_success.dwt');
    }
    
	/* ------------------------------------------------------ */
    //激活卡券
    /* ------------------------------------------------------ */
    public function active() {
   		$key = $_REQUEST['key'];
  		$user_id = $_REQUEST['userid'];
  		if(!$key==md5("zftmd5".$user_id)){
  			return false;
  		}
    	$ids = $_REQUEST['id'];
    	$id_arr = explode(",", $ids);
    	$result = array();
    	foreach ($id_arr as $ikey=>$ival){
    		//获取卡信息
			$card_info = model('Couponorder')->get_card_info_bywx($ival);
			//激活卡号
			$data = model('Couponorder')->get_card_active($card_info);
    	}
    	if($data){
    		$result['success'] = 1;
    	}else{
    		$result['success'] = 0;
    	}
    	die(json_encode($result));
    }
}