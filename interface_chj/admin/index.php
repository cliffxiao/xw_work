<?php

/*** ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：index.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch项目后台入口文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 启用session机制 */
session_start();
$_SESSION['safe_route'] = true;
header('location:../index.php?m=admin');
