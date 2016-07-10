<?php

/*** ============================================================================
 * Copyright (c) 2016 All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TestController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：测试程序编写
 * ----------------------------------------------------------------------------
 * 接口访问：http://url/interface/index.php?m=default&c=Test
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class TestController extends CommonController {

    private $mypara = '';

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 测试程序
     */
    public function index() {
		$username = $_GET['username'];
		$password = md5('rick123'.$_POST['password']);
		$token = rand(100000,999999);
		//调用model类方式
		
		$test = model('Test')->test_my_class();
		print_r($test);
		
		if ($username == '' or $password == '') {
			$result = '{"message":"1"}'; // 数据错误
		} else {
			//读取类
			$row = "";
			if (! is_array($row)) {
				$result = '{"message":"2"}'; // 不存在
			} else {
				if ($password != $row['password']) {
					$result = '{"message":"3"}'; // 密码错误
				} else {
					$result = '{"message":"0","username":' . $row['userid'] . ',"token":' . $token . '}';
				}
			}
		}
		echo $result;
    }


}

?>
