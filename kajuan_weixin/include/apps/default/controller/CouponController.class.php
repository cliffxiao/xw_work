<?php
/**
 * 购券
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class CouponController extends CommonController {

private $cat_id = 0; // 分类id

    private $number_max = 10; // 最大数量
    private $user_rank = 99; // 微信用户

    public function __construct() {
        parent::__construct();

        $this->cat_id = I('request.id', 16, 'intval');

        if (ACTION_NAME == 'list') {
            $this->index();
        }
        if(!empty($_SESSION['user_rank']))
        {
            $this->user_rank = $_SESSION['user_rank'];
        }
        $this->assign('number_max', $this->number_max);
    }

    /* ------------------------------------------------------ */
    //列表
    /* ------------------------------------------------------ */

    public function index() {
        $cat_list = model('CategoryBase')->get_categories_tree($this->cat_id);
        $cat_list = $cat_list[$this->cat_id];
        foreach ($cat_list['cat_id'] as $key => $value) {
            $market_price['max'] = $this->get_market_price($value['id'], 'market_price desc');
            $market_price['min'] = $this->get_market_price($value['id'], 'market_price asc');
            $cat_list['cat_id'][$key]['market_price'] = $market_price;
        }
        $this->assign('cat_list', $cat_list);
        $this->assign('page_title', "券伯乐商城");
        /* 显示模板 */
        $this->display('coupon_list.dwt');
    }

    /* ------------------------------------------------------ */
    //详情
    /* ------------------------------------------------------ */
    public function info() {
        //do something
        $catid = $_REQUEST['id'];
        $goodslist = model('Category')->get_category_goods_byid($catid);
        $cat_info = model('Category')->get_cat_info($catid);
        $default_shop_price = 0;
        $default_market_price = 0;
        $zhekou = 0;
        foreach ($goodslist as $gkey=>$gval){
        	if($gval['is_first'] == 1){
        		$default_goods_id = $gval['id'];
        		$default_goods_sn = $gval['goods_sn'];
        		$default_shop_price = $gval['shop_price'];
	        	$default_market_price = $gval['market_price'];
	        	$zhekou = ($default_shop_price/$default_market_price)*100;
        	}
        }
        $this->assign('default_goods_id', $default_goods_id);
        $this->assign('default_goods_sn', $default_goods_sn);
        $this->assign('default_shop_price', $default_shop_price);
        $this->assign('default_market_price', $default_market_price);
        $this->assign('zhekou', $zhekou);
        $this->assign('goodslist', $goodslist);
        $this->assign('page_title',$cat_info['cat_name']);
        $this->display('coupon_detail.dwt');
    }
    
	/* ------------------------------------------------------ */
    //获取单个商品信息
    /* ------------------------------------------------------ */
    public function detail() {
    	//格式化返回数组
        $res = array(
        	'code' => '',
            'err_msg' => '',
            'result' => '',
        	'number' => '',
        );
        //do something
        $goodsid = $_REQUEST['id'];
        $number = $_REQUEST['number'];
        $goods = model('Goods')->get_goods_info($goodsid);
        $res ['number'] = $goods['goods_number'];
        $res ['sub_number'] = $number;
        if($goods){
        	if($number==$goods['goods_number']){
        		$res ['code'] = 1;
        	}elseif($number>$goods['goods_number']){
        		$res ['err_msg'] = "商品库存不足";
        	}else{
        		$res['shop_price'] = $number*$goods['shop_price'];
        		$res['market_price'] = $number*$goods['market_price'];
        	}
        }else{
        	$res ['err_msg'] = "商品信息获取失败";
        }
        die(json_encode($res));
    }
    
	/* ------------------------------------------------------ */
    //获取商店价格
    /* ------------------------------------------------------ */
    private function get_market_price($cat_id, $order){
        //$cats = get_children($cat_id);
        $where['cat_id'] = $cat_id;
        $where['is_on_sale'] = 1;
        $where['is_delete'] = 0;
        return $this->model->table('goods')
                           ->field('market_price')->where($where)
                           ->order($order)
                           ->getOne();
    }

}