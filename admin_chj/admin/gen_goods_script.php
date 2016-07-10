<?php
/** * 云平台 生成显示商品的js代码
 * ============================================================================
 * * 版权所有 2016-2017 中国支付通集团，并保留所有权利。
 * $Id: gen_goods_script.php 17217 
 */

define('IN_ZFT', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 生成代码
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'setup')
{
    /* 检查权限 */
    admin_priv('gen_goods_script');

    /* 编码 */
    $lang_list = array(
        'UTF8'   => $_LANG['charset']['utf8'],
        'GB2312' => $_LANG['charset']['zh_cn'],
        'BIG5'   => $_LANG['charset']['zh_tw'],
    );

    /* 参数赋值 */
    $ur_here = $_LANG['16_goods_script'];
    $smarty->assign('ur_here',    $ur_here);
    $smarty->assign('cat_list',   cat_list());
    $smarty->assign('brand_list', get_brand_list());
    $smarty->assign('intro_list', $_LANG['intro']);
    $smarty->assign('url',        $ecs->url());
    $smarty->assign('lang_list',  $lang_list);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('gen_goods_script.htm');
}

?>