<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UserController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用户中心
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class UsercenterController extends CommonController
{

    protected $user_id = '';

    protected $action;

    protected $back_act = '';

    protected $page = 1;

    protected $count = 0;
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 属性赋值
        $this->user_id = I('session.user_id', 0, 'intval');

    }

    /**
     * 会员中心欢迎页
     */
    public function index()
    {
        $where['ect_uid'] = $this->user_id;
        $wechat_info = $this->model->table('wechat_user')
                            ->field('nickname, headimgurl')
                            ->where($where)
                            ->find();
        $this->assign('page_title', '我的');
    	$this->assign('wechat_info', $wechat_info);
        $this->display('usercenter.dwt');
    }


    /**
     * 会员中心欢迎页
     */
    public function order()
    {
        $status = I('get.status', 1, 'intval');
        $size = C('page_size');

        $filter['status'] = $status;
        $filter['page'] = '{page}';

        $limit = $this->pageLimit(url('order', $filter), $size);
      
        $orders = $this->get_user_orders($status, $limit);

        foreach ($orders as $key => $value) {
            $where['order_id'] = $value['order_id'];
            $orders[$key]['goods_info'] = $this->model->table('order_goods')
                           ->field('goods_name, goods_price, market_price, goods_number')
                           ->where($where)->find();
        }
        $this->assign('page_title', '我的订单');
        $this->assign('status', $status);
        $this->assign('count', $this->count);
        $this->assign('pager', $this->pageShow($this->count));
        $this->assign('orders_list', $orders);
        if(IS_AJAX){
            $this->display('user_order_list_ajax.dwt');
        }else{
            $this->display('user_order_list.dwt');
        }
    }

    /**
     * 取消一个用户订单
     */
    public function cancel_order()
    {
        $order_id = I('get.order_id', 0, 'intval');
        if(model('Users')->cancel_order($order_id, $this->user_id)){
            $this->jssuccess("订单取消成功", url('usercenter/order'));
        }else{
            $message = ECTouch::err()->last_message();
            if(empty($message[0])){
                $message[0] = '订单取消失败';
            }
            $this->jserror($message[0]);
        }
    }

    /**
     * 获取订单
     */
    private function get_user_orders($status = '', $limit = '')
    {
        $field = 'order_id, order_sn, shipping_id, order_status, shipping_status, pay_status, add_time, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee';
        
        if($status == 1){
            $where = "`pay_status` = '".PS_UNPAYED."' AND `order_status` <> '".OS_CANCELED."' AND `user_id` = '". $this->user_id ."' AND `order_type` = 1";

            $total_fee = $this->model->table('order_info')->field('SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee')->where($where)->order('order_id DESC')->select();
            $this->assign('total_fee', $total_fee[0]['total_fee']);
            
        }elseif($status == 2){
            $where['order_status'] = OS_CANCELED;
            $where['user_id'] = $this->user_id;
            $where['order_type'] = 1;
        }elseif($status == 3){
            $where = "`order_type` = 1 AND `user_id` = '". $this->user_id ."'";
        }else{
            return false;
        }

        $this->count = $this->model->table('order_info')->where($where)->count();

        return $this->model->table('order_info')
                           ->field($field)
                           ->where($where)
                           ->order('add_time desc')
                           ->limit($limit)->select();      
    }
}
