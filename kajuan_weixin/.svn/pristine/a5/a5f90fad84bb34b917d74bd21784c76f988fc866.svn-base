<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CouponorderModel.php
 * ----------------------------------------------------------------------------
 * 功能描述：卡包模型
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class CouponorderModel extends BaseModel {

    /**
     * 获取发卡信息
     */
    public function get_card_info($card_id = '', $is_act = 1){
    	$table[] = $this->pre . "virtual_card as v";
    	$table[] = $this->pre . "order_goods as g";
    	$table[] = $this->pre . "order_info as i";

    	$field = "v.card_id, v.card_sn, v.openBrh, v.prdtNo, v.is_act, v.is_used, v.is_don, v.is_saled, g.market_price as card_amount, g.goods_name as card_name, (SELECT c.cat_name FROM ". $this->pre . "goods as gg LEFT JOIN " . $this->pre . "category as c ON gg.cat_id = c.cat_id WHERE gg.goods_id = v.goods_id) as cat_name";

    	$where[] = "v.order_sn = i.order_sn";
    	$where[] = "g.order_id = i.order_id";
    	$where[] = "g.goods_id = v.goods_id";
        $where[] = "v.is_saled = 1";
        //$where[] = "v.is_used = 0";  
		//$where[] = "v.is_act = '" . $is_act . "'";
		$where[] = "v.card_id = '" .$card_id. "'";
        $where[] = "i.user_id = '" . I('session.user_id', 0, 'intval'). "'";

        return $this->model->table(implode(', ', $table), true)
                    ->field($field)->where(implode(' AND ', $where))
                    ->find();
    }
    
	/**
     * 获取发卡信息-weixin
     */
    public function get_card_info_bywx($card_id = '', $is_act = 1){
    	$table[] = $this->pre . "virtual_card as v";
    	$table[] = $this->pre . "order_goods as g";
    	$table[] = $this->pre . "order_info as i";

    	$field = "v.card_id, v.card_sn, v.openBrh, v.prdtNo, v.is_act, v.is_used, v.is_don, v.is_saled, g.market_price as card_amount, g.goods_name as card_name";

    	$where[] = "v.order_sn = i.order_sn";
    	$where[] = "g.order_id = i.order_id";
    	$where[] = "g.goods_id = v.goods_id";
        $where[] = "v.is_saled = 1";
        $where[] = "v.is_used = 0";  
		//$where[] = "v.is_act = '" . $is_act . "'";
		$where[] = "v.card_id = '" .$card_id. "'";

        return $this->model->table(implode(', ', $table), true)
                    ->field($field)->where(implode(' AND ', $where))
                    ->find();
    }

    /**
     * 更新卡片已用完
     */
    public function card_closed($card_sn = '')
    {
        $where['card_sn'] = $card_sn;
        $data['is_used'] = 1;
        return $this->model->table('virtual_card')
                           ->where($where)->data($data)->upate();
    }    

    
    /**
     * 获取发卡余额
     */
    public function get_card_balance($card_sn = array())
    {
    	if(empty($card_sn)){
    		ECTouch::err()->add('卡号不能为空。');
    		return false;
    	}

        $config['is_log'] = false;
        $kltong = new Kltong($config);
        $result = $kltong->card_balance($card_sn);
        if($kltong->error){
            ECTouch::err()->add($kltong->error);
            return false;
        }   

        $arr = array();
        $detail_list = $result['detail_list'];

        // 金额转换成0.00
        if(is_array($detail_list[0])){
            foreach ($detail_list as $key => $value) {
                if($value['avail_amt'] <= 0)
                {
                    $this->model->table('virtual_card')
                         ->data(array('is_used'=>1))
                         ->where(array('card_sn'=>$value['card_no']))
                         ->update();
                }
                $value['avail_amt'] = $value['avail_amt'] / 100;
                $arr[$value['card_no']] = $value;
            }     
        }else{
            if($detail_list['avail_amt'] <= 0)
            {
                $this->model->table('virtual_card')
                     ->data(array('is_used'=>1))
                     ->where(array('card_sn'=>$detail_list['card_no']))
                     ->update();
            }            
            $detail_list['avail_amt'] = $detail_list['avail_amt'] / 100;
            $arr[$detail_list['card_no']] = $detail_list;
        }
        return $arr;
    }

    /**
     * 获取发卡支付码
     */
    public function get_card_paycode($card_sn = '', $prdt_no= '', $card_brh = ''){
    	if(empty($card_sn)){
    		ECTouch::err()->add('卡号不能为空。');
    		return false;
    	}
        elseif(empty($prdt_no) || empty($card_brh))
        {
            ECTouch::err()->add('机构号(或产品号不存在。');
            return false;            
        }         
        $config['is_log'] = false;
        $kltong = new Kltong($config);
        $result = $kltong->card_paycode($card_sn, $prdt_no, $card_brh);
        if(empty($result['dime_code'])){
            ECTouch::err()->add("卡号支付生成失败。");
            return false;
        }
        return $result['dime_code'];
    }  

    /**
     * 激活已买的卡
     */
    public function get_card_active($card_info = array()){
    	if(empty($card_info)){
    		ECTouch::err()->add('没有卡号信息。');
    		return false;
    	}    	
  		$card_info['custId'] = $card_info['card_sn'];
  		$card_info['amountAt'] = $card_info['card_amount'];

  		$kltong = new Kltong();
  		$result = $kltong->card_active($card_info);
  		if($kltong->error){
  			ECTouch::err()->add($kltong->error);
  			return false;
  		}
        if(empty($result['end_dt'])){
            ECTouch::err()->add('返回数据出错。');
            return false;
        }
        return $result['end_dt'];	
    }  

    /**
     * 获取发卡消费记录
     */
    public function get_card_details($card_no = '', $start_dt ='', $end_dt = '', $page_num = 1, $page_size = 20){
        if(empty($card_no)){
            ECTouch::err()->add('卡号不能为空。');
            return false;
        } 
        if(empty($end_dt)){ // 结束日期为今天
            $end_dt = date('Ymd');
        }
        if(empty($start_dt)){ // 开始日期为3个月前
            $start_dt = date('Ymd', strtotime("-3 months"));
        }
        $config['is_log'] = false;
        $kltong = new Kltong($config);
        $result = $kltong->card_details($card_no, $start_dt, $end_dt, $page_num, $page_size);
        if($kltong->error){
            ECTouch::err()->add($kltong->error);
            return false;
        } 

        $arr = array();
        $detail_list = $result['detail_list'];
        if(empty($detail_list)){
            return $arr;
        }
        // 金额转换成0.00
        if(is_array($detail_list[0])){
            foreach ($detail_list as $key => $value) {
                $value['txn_dt'] = date_from_format('YmdHis', $value['txn_dt'].$value['txm_tm']);
                $value['avil_bal_at'] = $value['avil_bal_at'] / 100;
                $value['txn_at'] = $value['txn_at'] / 100;
                $arr[] = $value;
            }     
        }else{
            $detail_list['txn_dt'] = date_from_format('YmdHis', $detail_list['txn_dt'].$detail_list['txm_tm']);
            $detail_list['avil_bal_at'] = $detail_list['avil_bal_at'] / 100;
            $detail_list['txn_at'] = $detail_list['txn_at'] / 100;
            $arr[] = $detail_list;
        }
        return $arr;

    }
}