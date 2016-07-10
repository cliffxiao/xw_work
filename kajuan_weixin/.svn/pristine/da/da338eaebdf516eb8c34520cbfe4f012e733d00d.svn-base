<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch首页控制器
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class IndexController extends CommonController {

    protected $user_id = '';

    public function __construct() {
        parent::__construct();

        $this->user_id = I('session.user_id', 0, 'intval');
    }
    /**
     * 首页信息
     */
    public function index() {
    	$ipInfos = model('Common')->GetIpLookup(); //baidu.com IP地址  
		if($ipInfos){
			$city = $ipInfos['city'];
		}
		if(!$_COOKIE['curcity']){
			if($city){
				setcookie('curcity',$city,time()+60*60*24);
			}else{
				setcookie('curcity',"上海",time()+60*60*24);
			}
		}
        // 自定义导航栏
        $navigator = model('Common')->get_navigator();
        $this->assign('navigator', $navigator['middle']);
        $this->assign('card_total', $this->get_card_total());
        $this->display('index.dwt');



    }
    
	/**
     * 城市列表
     */
    public function citylist() {
    	$ipInfos = model('Common')->GetIpLookup(); //baidu.com IP地址  
		if($ipInfos){
			$city = $ipInfos['city'];
		}
		//获取城市列表
    	$city_list = model('Common')->get_city_list();
    	$this->assign('city_list', $city_list);
    	$this->assign('cur_city', $city);
        $this->display('citylist.dwt');
    }
    
	/**
     * 城市选择
     */
    public function setcity() {
    	//获取城市列表
    	setcookie('curcity',$_REQUEST['cityname'],time()+60*60*24);
		header('Location: index.php?m=default&c=index');
    }

    /**
     * 获取有几张卡和金额
     */
    private function get_card_total()
    {
        $where = " AND v.is_saled = 1 "; // 已售出
        $where .= " AND v.end_date >= '" . NOW_TIME . "' "; // 有效期内
        $where .= " AND v.is_don = 0 "; // 未转赠
        $where .= " AND v.is_used = 0 "; // 未用完

        $sql = "SELECT v.card_id, v.card_sn, v.card_password, v.add_date, v.end_date, v.is_saled, v.is_act, v.is_used, g.goods_name as card_name, g.market_price  as card_amount  FROM " . 
               $this->model->pre . "virtual_card as v, " .
               $this->model->pre . "order_goods as g, " .
               $this->model->pre . "order_info as i " .
               "WHERE v.order_sn = i.order_sn AND g.order_id = i.order_id AND v.is_saled = 1 AND g.goods_id = v.goods_id AND i.user_id = '" . 
               $this->user_id . "' " . $where;
       
        $card_total['amount'] = 0;
        $card_list = $this->model->query($sql);
        $card_total['count'] = count($card_list);

        $card_key = 0;
        $i = 0;
        foreach ($card_list as $key => $value) {
            if($value['is_act'] == 1 && $value['is_used'] == 0){
                if ($i < 10) {
                    $i++;
                } else {
                    $i = 0;
                    $card_key++;
                }
                $card_sn[$card_key][] = $value['card_sn'];
            }else{
                $card_total['amount'] += $value['card_amount'];
            }
        }
        
        if(!empty($card_sn)){
            $balance = array();
            foreach ($card_sn as $key => $card) {
                $card_balance = model('Couponorder')->get_card_balance($card);
                if($card_balance){
                    foreach ($card_balance as $k => $v) {
                        $card_total['amount'] += $v['avail_amt'];
                    }  
                } 
            }
 
        }        
        return $card_total;
    }
}
