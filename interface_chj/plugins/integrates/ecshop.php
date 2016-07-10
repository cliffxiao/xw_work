<?php

/*** ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE) {
    $i = (isset($modules)) ? count($modules) : 0;
    /* 会员数据整合插件的代码必须和文件名保持一致 */
    $modules[$i]['code'] = 'ecshop';
    /* 被整合的第三方程序的名称 */
    $modules[$i]['name'] = 'Ecshop';
    /* 被整合的第三方程序的版本 */
    $modules[$i]['version'] = '2.0';
    /* 插件的作者 */
    $modules[$i]['author'] = 'ECSHOP TEAM';
    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ecshop.com';
    return;
}

require_once (ROOT_PATH . 'plugins/integrates/integrate.php');

/**
 * ECSHOP 会员数据处理类
 */
class ecshop extends integrate
{

    public $is_ecshop = 1;

    /**
     * 构造函数
     *
     * @param unknown $cfg 
     */
    public function __construct($cfg)
    {
        parent::__construct(array());
        $this->user_table = 'users';
        $this->field_id = 'user_id';
        $this->field_ec_salt = 'ec_salt';
        $this->field_salt = 'salt';
        $this->field_name = 'user_name';
        $this->field_pass = 'password';
        $this->field_email = 'email';
        $this->field_mobile = 'mobile_phone';
        $this->field_gender = 'sex';
        $this->field_bday = 'birthday';
        $this->field_reg_date = 'reg_time';
        $this->field_passwd_question = 'passwd_question';
        $this->need_sync = false;
        $this->is_ecshop = 1;
    }

    /**
     * 检查指定用户是否存在及密码是否正确(重载基类check_user函数，支持zc加密方法)
     *
     * @see integrate::check_user()
     */
    public function check_user($username, $password = null)
    {
        if ($this->charset != 'UTF8') {
            $username = ecs_iconv('UTF8', $this->charset, $username);
        }
        
        if ($password === null) {
            $sql = "SELECT " . $this->field_id . 
                   " FROM " . $this->table() . 
                   " WHERE " . $this->field_name . " = '$username'";
            return $row = ECTouch::db()->getOne($sql);
        } else {
            $sql = "SELECT " . $this->field_id . 
                   ", " . $this->field_pass . 
                   ", " . $this->field_salt . 
                   ", " . $this->field_ec_salt .
                   " FROM " . $this->table() . 
                   " WHERE " . $this->field_name . " = '$username'";
            $row = ECTouch::db()->getRow($sql);

            $ec_salt = $row[$this->field_ec_salt];
            if (empty($row)) {
                return 0;
            }
            
            if (empty($row[$this->field_salt])) {
                if ($row[$this->field_pass] != $this->compile_password(array(
                    'password' => $password,
                    'ec_salt' => $ec_salt
                ))) {
                    return 0;
                } else {
                    if (empty($ec_salt)) {
                        $rand = rand(1, 9999);
                        $password = md5(md5($password) . $rand);
                        $sql = "UPDATE " . $this->table() . 
                               " SET " . $this->field_ec_salt . "='$rand'" .
                               ", " . $this->field_pass . "='$password'" .
                               " WHERE " . $this->field_name . "='$username'";
                        ECTouch::db()->query($sql);
                    }
                    return $row['user_id'];
                }
            } else {
                /* 如果salt存在，使用salt方式加密验证，验证通过洗白用户密码 */
                $encrypt_type = substr($row['salt'], 0, 1);
                $encrypt_salt = substr($row['salt'], 1);
                
                /* 计算加密后密码 */
                $encrypt_password = '';
                switch ($encrypt_type) {
                    case ENCRYPT_ZC:
                        $encrypt_password = md5($encrypt_salt . $password);
                        break;
                    /* 如果还有其他加密方式添加到这里 */
                    // case other :
                    // ----------------------------------
                    // break;
                    case ENCRYPT_UC:
                        $encrypt_password = md5(md5($password) . $encrypt_salt);
                        break;
                    
                    default:
                        $encrypt_password = '';
                }
                
                if ($row[$this->field_pass] != $encrypt_password) {
                    return 0;
                }

                $password = $this->compile_password(array(
                    'password' => $password
                ));
                
                $sql = "UPDATE " . $this->table() . 
                       " SET password = '$password', salt=''" . 
                       " WHERE user_id = '$row[user_id]'";
                ECTouch::db()->query($sql);
                
                return $row['user_id'];
            }
        }
    }
}

?>