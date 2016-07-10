<?php

/*** ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SmsController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 短信发送控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class SmsController extends CommonController {

    protected $mobile;
    //短信验证码
    protected $mobile_code;
    //安全码
    protected $sms_code;
    //日志路径
    protected $log_path;
    //用户ID
    protected $user_id;
    //错误提示
    protected $sms_error;
    //错误代码
    protected $code;

    public function __construct() {
        parent::__construct();
        $this->log_path = ROOT_PATH . 'data/smslog/' . date('Ymd') . '/';
        $this->mobile = I('post.mobile');
        //$this->sms_code = I('post.sms_code');
        $this->check();
    }

    //发送
    public function send() {
        if(empty($this->sms_error))
        {
            $this->mobile_code = $this->random(6, 1);
            $message = "您的验证码是：" . $this->mobile_code . "，请不要把验证码泄露给其他人，如非本人操作，可不用理会！";

            $sms = new EcsSms();
            $sms_error = '';
            $send_result = $sms->send($this->mobile, $message, $sms_error);
            $this->write_file($this->mobile, date("Y-m-d H:i:s"));

            if ($send_result) {
                $_SESSION['sms_mobile'] = $this->mobile;
                $_SESSION['sms_mobile_code'] = $this->mobile_code;
                $this->code = '0000';
                $this->sms_error = '手机验证码已经成功发送到您的手机！';
            } else {
                $this->code = '0001';
                $this->sms_error = empty($sms_error)?'手机验证码发送失败！':$sms_error;
            }
        }

        $this->error();
    }

    //验证
    private function check() {
        /*if (empty($this->sms_code) || $_SESSION['sms_code'] != $this->sms_code)
        {
            $this->sms_error = "验证码不匹配！";
        }
        else*/if (empty($this->mobile))
        {
            $this->sms_error = "手机号码不能为空！";
        }
        elseif (!preg_match('/^1[3|4|5|7|8][0-9]\d{8}$/', $this->mobile))
        {
            $this->sms_error = "手机号码格式不正确！";
        }
        elseif ($this->mobile)
        {
            $content = $this->read_file($this->mobile);
            if (strtotime($content) > (time() - 60)) {
            	$this->code = '0002';
                $this->sms_error = "获取验证码太过频繁，一分钟之内只能获取一次！";
            }
        }

        if(empty($this->sms_error))
        {
            $flag = I('post.flag');
            if ($flag == 'register')
            {
                //手机注册
                if (!empty($this->get_user_id())) {
                	$this->code = '0003';
                    $this->sms_error = "手机号码已存在，请更换手机号码！";
                }
            }
            elseif ($flag == 'forget') 
            {
                //找回密码
                if (empty($this->get_user_id())) {
                	$this->code = '0004';
                    $this->sms_error = "手机号码不存在无法通过该号码找回密码！";
                }
            } 
            elseif ($flag == 'pay') 
            {
                //找回支付密码
                if (empty($this->get_user_id())) {
                    $this->code = '0005';
                    $this->sms_error = "手机号码不存在无法通过该号码找回支付密码！";
                }
            } 
            else 
            {
                $this->code = '9999';
                $this->sms_error = "手机验证码发送失败！！";            
            }
        }  
    }

    private function error()
    {
        //exit(json_encode(array('code' => $code, 'msg' => $this->sms_error)));
        $result[] = $this->sms_error;
        $result['code'] = $this->code;
        ECTouch::err()->add($result);   
        if($this->code == '0000')
        {
            ECTouch::err()->success();
        }else{
            ECTouch::err()->error();
        }
    }

    private function get_user_id()
    {
        $condition['mobile_phone'] = $this->mobile;
        return $this->user_id = M('users')->field('user_id')
                                          ->where($condition)->getOne();
    }

    private function random($length = 6, $numeric = 0) {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    private function write_file($file_name, $content) {
        $this->mkdirs($this->log_path);
        $filename = $this->log_path . $file_name . '.log';
        $Ts = fopen($filename, "a+");
        fputs($Ts, PHP_EOL . $content);
        fclose($Ts);
    }

    private function mkdirs($dir, $mode = 0777) {
        if (is_dir($dir) || @mkdir($dir, $mode))
            return TRUE;
        if (!$this->mkdirs(dirname($dir), $mode))
            return FALSE;
        return @mkdir($dir, $mode);
    }

    private function read_file($file_name) {
        $content = '';
        $filename = $this->log_path . $file_name . '.log';
        if (function_exists('file_get_contents')) {
            @$content = file_get_contents($filename);
        } else {
            if (@$fp = fopen($filename, 'r')) {
                @$content = fread($fp, filesize($filename));
                @fclose($fp);
            }
        }
        $content = explode(PHP_EOL, $content);
        return end($content);
    }

}