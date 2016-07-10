<?php
/** * 云平台 帮助信息接口
 * ============================================================================
 * * 版权所有 2016-2017 中国支付通集团，并保留所有权利。
 * $Id: respond.php 16220 2009-06-12 02:08:59Z liubo $
 */

define('IN_ZFT', true);
require(dirname(__FILE__) . '/includes/init.php');

$get_keyword = trim($_GET['al']); // 获取关键字
header("location:http://help.ecshop.com/do.php?k=".$get_keyword."&v=".$_CFG['ecs_version']."&l=".$_CFG['lang']."&c=".EC_CHARSET);
?>