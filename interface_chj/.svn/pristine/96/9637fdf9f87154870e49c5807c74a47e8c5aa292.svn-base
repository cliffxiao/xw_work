<?php

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

/**
 * 表单验证类
 */
class Check {

    //执行验证规则
    /*
      用法：
      Check::rule(
      array(验证函数1，'错误返回值1'),
      array(验证函数2，'错误返回值2'),
      );
      若有一个验证函数返回false,则返回对应的错误返回值，若全部通过验证，则返回true。
      验证函数，可以是自定义的函数或类方法，返回true表示通过，返回false，表示没有通过
     */
    public static function rule($array = array()) {
        //可以采用数组传参，也可以采用无限个参数方式传参
        if (!isset($array[0][0]))
            $array = func_get_args();

        if (is_array($array)) {
            foreach ($array as $vo) {
                if (is_array($vo) && isset($vo[0]) && isset($vo[1])) {
                    if (!$vo[0])
                        return $vo[1];
                }
            }
        }
        return true;
    }

    /**
     * 检查字符串长度
     * @param type $str
     * @param type $min
     * @param type $max
     * @return boolean
     */
    public static function len($str, $min = 0, $max = 255) {
        if (empty($str)) {
            return true;
        }
        $length = strlen($str);
        return $length >= $min && $length <= $max;
    }

    /**
     * 检查字符串是否为空
     * @param type $str
     * @return type
     */
    public static function must($str) {
        return self::regex($str, 'require');
    }

    /**
     * 检查两次输入的值是否相同
     * @param type $str1
     * @param type $str2
     * @return type
     */
    public static function same($str1, $str2) {
        return $str1 == $str2;
    }

    public static function difference($str1, $str2){
        return self::same($str1, str2) ? false : true;
    }

    /**
     * 检查用户名
     * @param type $str
     * @param type $len_min
     * @param type $len_max
     * @param type $type
     * @return boolean
     */
    public static function userName($str, $len_min = 0, $len_max = 255, $type = 'ALL') {
        if (empty($str))
            return true;
        if (self::len($str, $len_min, $len_max) == false) {
            return false;
        }

        switch ($type) {    //纯英文
            case "EN":$pattern = "/^[a-zA-Z]+$/";
                break;
            //英文数字                           
            case "ENNUM":$pattern = "/^[a-zA-Z0-9]+$/";
                break;
            //允许的符号(|-_字母数字)   
            case "ALL":$pattern = "/^[\-\_a-zA-Z0-9]+$/";
                break;
            //用户自定义正则
            default:$pattern = $type;
                break;
        }

        if (preg_match($pattern, $str))
            return true;
        else
            return false;
    }

    /**
     * 验证邮箱
     * @param type $str
     * @return boolean
     */
    public static function email($str) {
        if (empty($str)) {
            return true;
        }
        return self::regex($str, 'email');
    }

    /**
     * 验证手机号码
     * @param type $str
     * @return boolean
     */
    public static function mobile($str) {
        if (empty($str)) {
            return true;
        }
        return self::regex($str, 'mobile');
    }

    /**
     * 验证固定电话
     * @param type $str
     * @return boolean
     */
    public static function tel($str) {
        if (empty($str)) {
            return true;
        }
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/', trim($str));
    }

    /**
     * 验证qq号码
     * @param type $str
     * @return boolean
     */
    public static function qq($str) {
        if (empty($str)) {
            return true;
        }
        return self::regex($str, 'qq');
    }

    /**
     * 验证邮政编码
     * @param type $str
     * @return boolean
     */
    public static function zipCode($str) {
        if (empty($str)) {
            return true;
        }
        return self::regex($str, 'zip');
    }

    /**
     * 验证ip
     * @param type $str
     * @return boolean
     */
    public static function ip($str) {
        if (empty($str)) {
            return true;
        }
        return self::regex($str, 'ip');
    }

    /**
     * 验证身份证(中国)
     * @param type $str
     * @return boolean
     */
    public static function idCard($str) {
        if (empty($str)) {
            return true;
        }
        return self::regex($str, 'idcard');
    }

    /**
     * 验证网址
     * @param type $str
     * @return boolean
     */
    public static function url($str) {
        if (empty($str)) {
            return true;
        }

        return preg_match('#^(http|https|ftp|ftps)://([\w-]+\.)+[\w-]+(/[\w-./?%&=]*)?#i', $str) ? true : false;
    }

    public static function regex($value, $rule) {
        /*扩展*/
        $validate = array(
            'require'   =>  '/\S+/',
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
            'symbol'    =>  '/^[!@#$%^&*]+$/',
            'chinese'   =>  '/^[\x{4e00}-\x{9fa5}] $/u',
            'mobile'    =>  '/^1[3|4|5|7|8][0-9]\d{8}$/', //11位手机号
            'date'      =>  '/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', //2011-8-22
            'idcard'  => '/^[\d]{6}((19[\d]{2})|(200[0-8]))((0[1-9])|(1[0-2]))((0[1-9])|([12][\d])|(3[01]))[\d]{3}[0-9xX]$/',//身份证号
            'qq'        =>  '/^[1-9][0-9]{5,11}$/',
            'time'      =>  '/^([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/',
            'ip'        =>  '/^(\d{1,3}\.){3}\d{1,3}$/',

        );
        // 检查是否有内置的正则表达式
        if(isset($validate[strtolower($rule)]))
            $rule       =   $validate[strtolower($rule)];
        return preg_match($rule,$value)===1;
    }    

}

?>