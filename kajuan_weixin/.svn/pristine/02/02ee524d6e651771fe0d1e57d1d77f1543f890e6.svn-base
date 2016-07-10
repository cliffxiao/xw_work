<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CommonModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 公共函数 模型
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class CommonModel extends BaseModel {

    /**
     * 获得指定页面的动态内容
     *
     * @access  public
     * @param   string  $tmp    模板名称
     * @return  void
     */
    function assign_dynamic($tmp) {
        $sql = 'SELECT id, number, type FROM ' . $this->pre .
                "template WHERE filename = '$tmp' AND type > 0 AND remarks ='' AND theme='" . C('template') . "'";
        $res = $this->query($sql);
        foreach ($res AS $row) {
            switch ($row['type']) {
                case 1:
                    /* 分类下的商品 */
                    ECTouch::view()->assign('goods_cat_' . $row['id'], model('Goods')->assign_cat_goods($row['id'], $row['number']));
                    break;
                case 2:
                    /* 品牌的商品 */
                    $brand_goods = model('Goods')->assign_brand_goods($row['id'], $row['number']);

                    ECTouch::view()->assign('brand_goods_' . $row['id'], $brand_goods['goods']);
                    ECTouch::view()->assign('goods_brand_' . $row['id'], $brand_goods['brand']);
                    break;
                case 3:
                    /* 文章列表 */
                    $cat_articles = model('Article')->assign_articles($row['id'], $row['number']);

                    ECTouch::view()->assign('articles_cat_' . $row['id'], $cat_articles['cat']);
                    ECTouch::view()->assign('articles_' . $row['id'], $cat_articles['arr']);
                    break;
            }
        }
    }

    /**
     * 统计访问信息
     *
     * @access  public
     * @return  void
     */
    function visit_stats() {
        if (C('visit_stats') == 'off') {
            return;
        }
        $time = gmtime();
        /* 检查客户端是否存在访问统计的cookie */
        $visit_times = (!empty($_COOKIE['ECS']['visit_times'])) ? intval($_COOKIE['ECS']['visit_times']) + 1 : 1;
        setcookie('ECS[visit_times]', $visit_times, $time + 86400 * 365, '/');

        $browser = get_user_browser();
        $os = get_os();
        $ip = real_ip();
        $area = ecs_geoip($ip);

        /* 语言 */
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $pos = strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ';');
            $lang = addslashes(($pos !== false) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, $pos) : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $lang = '';
        }

        /* 来源 */
        if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9) {
            $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
            if ($pos !== false) {
                $domain = substr($_SERVER['HTTP_REFERER'], 0, $pos);
                $path = substr($_SERVER['HTTP_REFERER'], $pos);

                /* 来源关键字 */
                if (!empty($domain) && !empty($path)) {
                    save_searchengine_keyword($domain, $path);
                }
            } else {
                $domain = $path = '';
            }
        } else {
            $domain = $path = '';
        }

        $sql = 'INSERT INTO ' . $this->pre . 'stats ( ' .
                'ip_address, visit_times, browser, system, language, area, ' .
                'referer_domain, referer_path, access_url, access_time' .
                ') VALUES (' .
                "'$ip', '$visit_times', '$browser', '$os', '$lang', '$area', " .
                "'" . addslashes($domain) . "', '" . addslashes($path) . "', '" . addslashes(PHP_SELF) . "', '" . $time . "')";
        $this->query($sql);
    }

    /**
     * 获取指定主题某个模板的主题的动态模块
     *
     * @access  public
     * @param   string       $theme    模板主题
     * @param   string       $tmp      模板名称
     *
     * @return array()
     */
    function get_dyna_libs($theme, $tmp) {
        $tmp_array = explode('.', $tmp);
        $ext = end($tmp_array);
        $tmp = basename($tmp, ".$ext");
        $sql = 'SELECT region, library, sort_order, id, number, type' .
                ' FROM ' . $this->pre .
                "template WHERE theme = '$theme' AND filename = '" . $tmp . "' AND type > 0 AND remarks=''" .
                ' ORDER BY region, library, sort_order';
        $res = $this->query($sql);

        $dyna_libs = array();
        foreach ($res AS $row) {
            $dyna_libs[$row['region']][$row['library']][] = array(
                'id' => $row['id'],
                'number' => $row['number'],
                'type' => $row['type']
            );
        }

        return $dyna_libs;
    }

    /**
     * 取得某模板某库设置的数量
     * @param   string      $template   模板名，如index
     * @param   string      $library    库名，如recommend_best
     * @param   int         $def_num    默认数量：如果没有设置模板，显示的数量
     * @return  int         数量
     */
    function get_library_number($library, $template = null) {
        global $page_libs;

        if (empty($template)) {
            $template = basename(PHP_SELF);
            $template = substr($template, 0, strrpos($template, '.'));
        }
        $template = addslashes($template);

        static $lib_list = array();

        /* 如果没有该模板的信息，取得该模板的信息 */
        if (!isset($lib_list[$template])) {
            $lib_list[$template] = array();
            $sql = "SELECT library, number FROM " . $this->pre .
                    "template WHERE theme = '" . C('template') . "'" .
                    " AND filename = '$template' AND remarks='' ";
            $res = $this->query($sql);
            foreach ($res as $key => $row) {
                $lib = basename(strtolower(substr($row['library'], 0, strpos($row['library'], '.'))));
                $lib_list[$template][$lib] = $row['number'];
            }
        }

        $num = 0;
        if (isset($lib_list[$template][$library])) {
            $num = intval($lib_list[$template][$library]);
        } else {
            /* 模板设置文件查找默认值 */
            //include_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_template.php');
            static $static_page_libs = null;
            if ($static_page_libs == null) {
                $static_page_libs = $page_libs;
            }
            $lib = '/library/' . $library . '.lbi';

            $num = isset($static_page_libs[$template][$lib]) ? $static_page_libs[$template][$lib] : 3;
        }

        return $num;
    }

    /**
     * 取得自定义导航栏列表
     * @param   string      $type    位置，如top、bottom、middle
     * @return  array         列表
     */
    function get_navigator($ctype = '', $catlist = array()) {
        $sql = 'SELECT * FROM ' . $this->pre .
                'touch_nav WHERE ifshow = \'1\' ORDER BY type, vieworder';
        $res = $this->query($sql);
        $cur_url = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);

        if (intval(C('rewrite'))) {
            if (strpos($cur_url, '-')) {
                preg_match('/([a-z]*)-([0-9]*)/', $cur_url, $matches);
                $cur_url = $matches[1] . '.php?id=' . $matches[2];
            }
        } else {
            $cur_url = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);
        }

        $noindex = false;
        $active = 0;
        $navlist = array(
            'top' => array(),
            'middle' => array(),
            'bottom' => array()
        );
        foreach ($res as $key => $row) {
            $navlist[$row['type']][] = array(
                'name' => $row['name'],
                'pic' => $row['pic'],
                'opennew' => $row['opennew'],
                'url' => $row['url'],
                'ctype' => $row['ctype'],
                'cid' => $row['cid'],
            );
        }
        /* 遍历自定义是否存在currentPage */
        foreach ($navlist['middle'] as $k => $v) {
            $condition = empty($ctype) ? (strpos($cur_url, $v['url']) === 0) : (strpos($cur_url, $v['url']) === 0 && strlen($cur_url) == strlen($v['url']));
            if ($condition) {
                $navlist['middle'][$k]['active'] = 1;
                $noindex = true;
                $active += 1;
            }
        }

        if (!empty($ctype) && $active < 1) {
            foreach ($catlist as $key => $val) {
                foreach ($navlist['middle'] as $k => $v) {
                    if (!empty($v['ctype']) && $v['ctype'] == $ctype && $v['cid'] == $val && $active < 1) {
                        $navlist['middle'][$k]['active'] = 1;
                        $noindex = true;
                        $active += 1;
                    }
                }
            }
        }

        if ($noindex == false) {
            $navlist['config']['index'] = 1;
        }

        return $navlist;
    }

    /**
     * 过滤表字段
     * @param type $table
     * @param type $data
     * @return type
     */
    function filter_field($table, $data) {
        $this->table = $table;
        $field = $this->getFields();
        $res = array();
        foreach ($field as $field_name) {
            if (array_key_exists($field_name['Field'], $data) == true) {
                $res[$field_name['Field']] = $data[$field_name['Field']];
            }
        }
        return $res;
    }
    
	/**
     * 获取城市列表
     */
    function get_city_list() {
        $res = $this->query('select * from ' . $this->pre .'region');
        $res_province = array();
        $flag_i = 0;
        $flag_j = 0;
        foreach ($res as $key=>$val){
        	if($val['parent_id']==1){
        		$res_province[$flag_i]['region_id'] = $val['region_id'];
        		$res_province[$flag_i]['region_name'] = $val['region_name'];
        		$flag_i++;
        	}
        }
    	foreach ($res as $key=>$val){
    		foreach ($res_province as $pkey=>$pval){
	    		if($val['parent_id']==$pval['region_id']){
	        		$res_province[$pkey]['region_city'][$flag_j]['region_id'] = $val['region_id'];
	        		$res_province[$pkey]['region_city'][$flag_j]['region_name'] = $val['region_name'];
	        		$flag_j++;
	        	}
    		}
        }
        return $res_province;
    }
    
    //获取当前IP
    function GetIp(){  
	    $realip = '';  
	    $unknown = 'unknown';  
	    if (isset($_SERVER)){  
	        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){  
	            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);  
	            foreach($arr as $ip){  
	                $ip = trim($ip);  
	                if ($ip != 'unknown'){  
	                    $realip = $ip;  
	                    break;  
	                }  
	            }  
	        }else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)){  
	            $realip = $_SERVER['HTTP_CLIENT_IP'];  
	        }else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)){  
	            $realip = $_SERVER['REMOTE_ADDR'];  
	        }else{  
	            $realip = $unknown;  
	        }  
	    }else{  
	        if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)){  
	            $realip = getenv("HTTP_X_FORWARDED_FOR");  
	        }else if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)){  
	            $realip = getenv("HTTP_CLIENT_IP");  
	        }else if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)){  
	            $realip = getenv("REMOTE_ADDR");  
	        }else{  
	            $realip = $unknown;  
	        }  
	    }  
	    $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;  
	    return $realip;  
	}  
    
    /**
     * 获取当前定位城市
     */
    function GetIpLookup($ip = ''){  
	    if(empty($ip)){  
	        $ip = $this->GetIp();  
	    }  
	    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);  
	    if(empty($res)){ return false; }  
	    $jsonMatches = array();  
	    preg_match('#\{.+?\}#', $res, $jsonMatches);  
	    if(!isset($jsonMatches[0])){ return false; }  
	    $json = json_decode($jsonMatches[0], true);  
	    if(isset($json['ret']) && $json['ret'] == 1){  
	        $json['ip'] = $ip;  
	        unset($json['ret']);  
	    }else{  
	        return false;  
	    }  
	    return $json;  
	}  
	
	/**
     * 获取订单列表
     */
    function get_order_list() {
        $res = $this->query('select * from ' . $this->pre .'order_info where order_status=0 and pay_status=0');
        return $res;
    }
    
	/**
     * 设置订单自动过期、释放库存
     */
    function cancel_order_auto($order_id,$order_sn) {
        $order_goods = $this->query("select goods_id,goods_number from " . $this->pre ."order_goods where order_id='$order_id'");
        //更新订单状态为取消
        $res1 = $this->query("update " . $this->pre ."order_info set order_status=2 where order_id='$order_id'");
        //更新库存
        $goods_number = $order_goods[0]['goods_number'];
        $goods_id=$order_goods[0]['goods_id'];
        $res2 = $this->query("update " . $this->pre ."goods set goods_number=goods_number+$goods_number where goods_id=$goods_id");
        //释放卡片
        $res3 = $this->query("update " . $this->pre ."virtual_card set is_saled=0,order_sn='' where goods_id=$goods_id and order_sn='$order_sn'");
        return $res1;
    }
    
	/**
     * insert debug log
     */
    function insert_debug_log($name,$value) {
        $this->query("insert into " . $this->pre ."debug_log (name,value) values ('$name','$value')");
    }
}
