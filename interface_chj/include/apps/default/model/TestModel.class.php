<?php

/*** ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * �ļ����ƣ�IndexController.class.php
 * ----------------------------------------------------------------------------
 * ����������ECTouch��ҳ������
 * ----------------------------------------------------------------------------
 */


/* ���ʿ��� */
defined('IN_ZFT') or die('Deny Access');

class TestModel extends BaseModel {

    protected $table = 'users';

    /**
     * ������Ķ�ȡ��
     *
     * @param   string       $username          ע���û���
     * @access  public
     * @return  bool         $bool
     */
    function test_my_class() {
		$res =1;
		return $res;
		
    }

}

