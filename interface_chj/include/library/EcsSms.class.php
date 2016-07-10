<?php

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

/* 短信模块主类 */
class EcsSms {

    var $sms_name = NULL; //用户名
    var $sms_password = NULL; //密码
    var $sms_auth_str = null; //短信签名

    function __construct() {
        /* 直接赋值 */
        $this->sms_name = C('CFG.SMS_USER_NAME');
        $this->sms_password = C('CFG.SMS_PASSWORD');
        $this->sms_auth_str = C('CFG.SMS_AUTH_STR');
    }

    // 发送短消息
    function send($phones, $msg, &$sms_error = '', $send_date = '', $send_num = 1, $sms_type = '', $version = '1.0') {

        /* 检查发送信息的合法性 */
        $contents = $this->get_contents($phones, $msg);
        if (!$contents) {
            return false;
        }

        /* 获取API URL */
        $sms_url = "http://cf.51welink.com/submitdata/Service.asmx/g_Submit";
        $count = count($contents);

        foreach ($contents as $key => $val) {
            $post_data = "sname=" . $this->sms_name . 
                         "&spwd=" . $this->sms_password . 
                         "&scorpid=&sprdid=1012888" . 
                         "&sdst=" . $val['phones'] .
                         "&smsg=" .rawurlencode($val['content']);
            $get = $this->Post($post_data, $sms_url);
            $gets = $this->xml_to_array($get);
            if($count > 1)
            {
                sleep(1);
            }
        }
        if(empty($gets)){
            $sms_error = $get;
            $this->logResult($sms_error);
            return false;
        }
        if ($gets['CSubmitState']['State'] == 0) {
            return true;
        } else {
            $sms_error = $gets['CSubmitState']['MsgState'];
            $this->logResult($sms_error);
            return false;
        }
    }

    private function Post($data, $target) {
        $url_info = parse_url($target);
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        //$httpheader .= "Connection:Keep-Alive\r\n\r\n";
        $httpheader .= $data;

        $fd = fsockopen($url_info['host'], 80);
        fwrite($fd, $httpheader);
        $gets = "";
        while(!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);
        if($gets != ''){
            $start = strpos($gets, '<?xml');
            if($start > 0) {
                $gets = substr($gets, $start);
            }        
        }
        return $gets;
    }

    private function xml_to_array($xml) {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = $this->xml_to_array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    //检查手机号和发送的内容并生成生成短信队列
    private function get_contents($phones, $msg) {
        if (empty($phones) || empty($msg)) {
            return false;
        }

        $msg .= $this->sms_auth_str;

        if (EC_CHARSET != 'utf-8') {
            $msg = $this->auto_charset($msg);
        }

        $phone_key = 0;
        $i = 0;
        $phones = explode(',', $phones);
        foreach ($phones as $key => $value) {
            if ($i < 200) {
                $i++;
            } else {
                $i = 0;
                $phone_key++;
            }
            if ($this->is_moblie($value)) {
                $phone[$phone_key][] = $value;
            } else {
                $i--;
            }
        }
        if (!empty($phone)) {
            foreach ($phone as $phone_key => $val) {
                $phone_array[$phone_key]['phones'] = implode(',', $val);
                $phone_array[$phone_key]['content'] = $msg;
            }
            return $phone_array;
        } else {
            return false;
        }
    }

    // 自动转换字符集 支持数组转换
    private function auto_charset($fContents, $from = 'gbk', $to = 'utf-8') {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if (is_string($fContents)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($fContents, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $fContents);
            } else {
                return $fContents;
            }
        } elseif (is_array($fContents)) {
            foreach ($fContents as $key => $val) {
                $_key = $this->auto_charset($key, $from, $to);
                $fContents[$_key] = $this->auto_charset($val, $from, $to);
                if ($key != $_key)
                    unset($fContents[$key]);
            }
            return $fContents;
        }
        else {
            return $fContents;
        }
    }

    // 检测手机号码是否正确
    private function is_moblie($moblie) {
        return preg_match("/^0?1((3|7|8)[0-9]|5[0-35-9]|4[57])\d{8}$/", $moblie);
    }

    //打印日志
    private function logResult($word = '') {
        $fp = fopen(ROOT_PATH . "data/smserrlog.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

}

?>