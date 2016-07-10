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

class CronController extends CommonController {

    public function __construct() {
        parent::__construct();
    }
    /**
     * default
     */
    public function index() {
    	//crontab
    }
    
	/**
     * 订单过期更新
     */
    public function order() {
    	$order_list = model('Common')->get_order_list();
    	$cur_time = time();
    	foreach ($order_list as $okey=>$oval){
    		if($cur_time-$oval['add_time']>15*60){
    			//设置订单过期
    			$cancel_order = model('Common')->cancel_order_auto($oval['order_id'],$oval['order_sn']);
    		}
    	}
    }
}
