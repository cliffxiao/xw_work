<?php

/** * 云平台 角色管理信息以及权限管理程序
 * ============================================================================
 * * 版权所有 2016-2017 中国支付通集团，并保留所有权利。* ============================================================================
 * $Author: wangleisvn $
 * $Id: privilege.php 16529 2009-08-12 05:38:57Z wangleisvn $
*/

define('IN_ZFT', true);

require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'login';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/* 初始化 $exc 对象 */
$exc = new exchange($ecs->table("role"), $db, 'role_id', 'role_name');

/*------------------------------------------------------ */
//-- 退出登录
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'logout')
{
    /* 清除cookie */
    setcookie('ECSCP[admin_id]',   '', 1);
    setcookie('ECSCP[admin_pass]', '', 1);

    $sess->destroy_session();

    $_REQUEST['act'] = 'login';
}

/*------------------------------------------------------ */
//-- 登陆界面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'login')
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    if ((intval($_CFG['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0)
    {
        $smarty->assign('gd_version', gd_version());
        $smarty->assign('random',     mt_rand());
    }

    $smarty->display('login.htm');
}


/*------------------------------------------------------ */
//-- 角色列表页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'list')
{
    /* 模板赋值 */
    $smarty->assign('ur_here',     $_LANG['admin_role']);
    $smarty->assign('action_link', array('href'=>'role.php?act=add', 'text' => $_LANG['admin_add_role']));
    $smarty->assign('full_page',   1);
    $smarty->assign('admin_list',  get_role_list());

    /* 显示页面 */
    assign_query_info();
    $smarty->display('role_list.htm');
}

/*------------------------------------------------------ */
//-- 查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $smarty->assign('admin_list',  get_role_list());

    make_json_result($smarty->fetch('role_list.htm'));
}

/*------------------------------------------------------ */
//-- 添加角色页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('admin_manage');
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/priv_action.php');

    $priv_str = '';

    /* 查询部份权限 */
    $action_list = get_action_list();
    if(!empty($action_list))
    {
        $sql = "SELECT parent_id FROM " .$ecs->table('admin_action').
                " WHERE action_code " . db_create_in($action_list) . " GROUP BY parent_id";
        $parent_list = $db->getAll($sql);
        foreach ($parent_list as $key => $value) {
            if($value['parent_id'] > 0)
            {
                $parent_ids[] = $value['parent_id'];
            }     
        }
    }

    /* 获取权限的分组数据 */
    $sql_query = "SELECT action_id, parent_id, action_code, relevance FROM " .$ecs->table('admin_action').
                 " WHERE parent_id = 0";
    if(!empty($parent_ids)) 
    {
        $sql_query .= ' AND action_id ' . db_create_in($parent_ids);
    }            

    $res = $db->query($sql_query);
    while ($rows = $db->FetchRow($res))
    {
        $priv_arr[$rows['action_id']] = $rows;
    }


    /* 按权限组查询底级的权限名称 */
    $sql = "SELECT action_id, parent_id, action_code, relevance FROM " .$ecs->table('admin_action').
           " WHERE parent_id " .db_create_in(array_keys($priv_arr));

    if(!empty($action_list))
    {
        $sql .= " AND action_code " . db_create_in($action_list);
    }

    $result = $db->query($sql);
    while ($priv = $db->FetchRow($result))
    {
        $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
    }

    // 将同一组的权限使用 "," 连接起来，供JS全选
    foreach ($priv_arr AS $action_id => $action_group)
    {
        $action_group['priv'] = is_array($action_group['priv'])?$action_group['priv']:array();
        $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

        foreach ($action_group['priv'] AS $key => $val)
        {
            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
        }
    }

     /* 模板赋值 */
    $smarty->assign('ur_here',     $_LANG['admin_add_role']);
    $smarty->assign('action_link', array('href'=>'role.php?act=list', 'text' => $_LANG['admin_list_role']));
    $smarty->assign('form_act',    'insert');
    $smarty->assign('action',      'add');
    $smarty->assign('lang',        $_LANG);
    $smarty->assign('priv_arr',    $priv_arr);

    /* 显示页面 */
    assign_query_info();
    $smarty->display('role_info.htm');




}

/*------------------------------------------------------ */
//-- 添加角色的处理
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
    admin_priv('admin_manage');
    $act_list = @join(",", $_POST['action_code']);

    $sql = "INSERT INTO ".$ecs->table('role')." (role_name, action_list, role_describe, user_id) ".
           "VALUES ('".trim($_POST['user_name'])."','$act_list','".trim($_POST['role_describe'])."', '".$_SESSION['admin_id']."')";

    $db->query($sql);
    /* 转入权限分配列表 */
    $new_id = $db->Insert_ID();

    /*添加链接*/

    $link[0]['text'] = $_LANG['admin_list_role'];
    $link[0]['href'] = 'role.php?act=list';

    sys_msg($_LANG['add'] . "&nbsp;" .$_POST['user_name'] . "&nbsp;" . $_LANG['action_succeed'],0, $link);

    /* 记录管理员操作 */
    admin_log($_POST['user_name'], 'add', 'role');
 }

/*------------------------------------------------------ */
//-- 编辑角色信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
     include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/priv_action.php');
    $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    /* 查看是否有权限编辑其他管理员的信息 */
    if ($_SESSION['admin_id'] != $_REQUEST['id'])
    {
        admin_priv('admin_manage');
    }

    /* 获取角色信息 */
    $sql = "SELECT role_id, user_id, role_name, action_list, role_describe FROM " .$ecs->table('role').
           " WHERE role_id = '".$_REQUEST['id']."'";
    $user_info = $db->getRow($sql);

    /* 只能编辑自己创建的管理员 */
    if($_SESSION['admin_id'] != $user_info['user_id'])
    {
        $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
        sys_msg($_LANG['edit_admininfo_cannot'], 0, $link);     
    }

    /* 获得该管理员的权限 */
    $priv_str = $user_info['action_list'];

    /* 查询部份权限 */
    $action_list = get_action_list();
    if(!empty($action_list))
    {
        $sql = "SELECT parent_id FROM " .$ecs->table('admin_action').
                " WHERE action_code " . db_create_in($action_list) . " GROUP BY parent_id";
        $parent_list = $db->getAll($sql);
        foreach ($parent_list as $key => $value) {
            if($value['parent_id'] > 0)
            {
                $parent_ids[] = $value['parent_id'];
            }     
        }
    }

    /* 获取权限的分组数据 */
    $sql_query = "SELECT action_id, parent_id, action_code,relevance FROM " .$ecs->table('admin_action').
                 " WHERE parent_id = 0";

    if(!empty($parent_ids)) 
    {
        $sql_query .= ' AND action_id ' . db_create_in($parent_ids);
    } 

    $res = $db->query($sql_query);
    while ($rows = $db->FetchRow($res))
    {
        $priv_arr[$rows['action_id']] = $rows;
    }

    /* 按权限组查询底级的权限名称 */
    $sql = "SELECT action_id, parent_id, action_code,relevance FROM " .$ecs->table('admin_action').
           " WHERE parent_id " .db_create_in(array_keys($priv_arr));

    if(!empty($action_list))
    {
        $sql .= " AND action_code " . db_create_in($action_list);
    }

    $result = $db->query($sql);
    while ($priv = $db->FetchRow($result))
    {
        $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
    }

    // 将同一组的权限使用 "," 连接起来，供JS全选
    foreach ($priv_arr AS $action_id => $action_group)
    {
        $action_group['priv'] = is_array($action_group['priv'])?$action_group['priv']:array();$priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

        foreach ($action_group['priv'] AS $key => $val)
        {
            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
        }
    }


    /* 模板赋值 */

    $smarty->assign('user',        $user_info);
    $smarty->assign('form_act',    'update');
    $smarty->assign('action',      'edit');
    $smarty->assign('ur_here',     $_LANG['admin_edit_role']);
    $smarty->assign('action_link', array('href'=>'role.php?act=list', 'text' => $_LANG['admin_list_role']));
    $smarty->assign('lang',        $_LANG);
    $smarty->assign('priv_arr',    $priv_arr);
    $smarty->assign('user_id',     $_GET['id']);

    assign_query_info();
    $smarty->display('role_info.htm');
}

/*------------------------------------------------------ */
//-- 更新角色信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update')
{
    /* 更新管理员的权限 */
    $act_list = @join(",", $_POST['action_code']);
    $sql = "UPDATE " .$ecs->table('role'). " SET action_list = '$act_list', role_name = '".$_POST['user_name']."', role_describe = '".$_POST['role_describe']." ' ".
           "WHERE role_id = '$_POST[id]'";
    $db->query($sql);
    $user_sql = "UPDATE " .$ecs->table('admin_user'). " SET action_list = '$act_list' ".
           "WHERE role_id = '$_POST[id]'";
    $db->query($user_sql);
    /* 提示信息 */
    $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'role.php?act=list');
    sys_msg($_LANG['edit'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $_LANG['action_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 删除一个角色
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('admin_drop');

    $id = intval($_GET['id']);
    $num_sql = "SELECT count(*) FROM " .$ecs->table('admin_user'). " WHERE role_id = '$id'";
    $remove_num = $db->getOne($num_sql);
    if($remove_num > 0)
    {
        make_json_error($_LANG['remove_cannot_user']);
    }
    else
    {
        $sql = "SELECT user_id  FROM " .$ecs->table('role'). " WHERE role_id = '$id'";
        $user_id = $db->getOne($sql);

        /* 只能删除自己创建的管理员 */
        if($_SESSION['admin_id'] != $user_id)
        {
            $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
            sys_msg($_LANG['remove_cannot_user'], 0, $link);     
        }    

        $exc->drop($id);
        $url = 'role.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    }

    ecs_header("Location: $url\n");
    exit;
}

/* 获取角色列表 */
function get_role_list()
{
    $list = array();
    $sql  = 'SELECT r.role_id, r.role_name, r.action_list, r.role_describe, u.user_name, u.true_name FROM ' .
            $GLOBALS['ecs']->table('role') . ' AS r LEFt JOIN ' .
            $GLOBALS['ecs']->table('admin_user') . ' AS u ' .
            "ON u.user_id = r.user_id " . 
            "WHERE r.user_id = '".$_SESSION['admin_id']. "' " .
            'ORDER BY role_id DESC';
    $list = $GLOBALS['db']->getAll($sql);

    return $list;
}

/* 获取当前角色所拥有的权限集 */
function get_action_list()
{
    $list = array();
    $action_list = array();
    $sql  = 'SELECT u.action_list FROM ' .
            $GLOBALS['ecs']->table('role').' as r, '.
            $GLOBALS['ecs']->table('admin_user').' as u '.
            'WHERE u.role_id = r.role_id '.
            'AND u.user_id = ' . $_SESSION['admin_id'] . ' ' .
            'GROUP BY u.action_list ORDER BY r.role_id DESC';
    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list as $key => $value) {
        $action_list = array_merge($action_list, explode(',', $value['action_list']));
    }
    return $action_list;
    //return "'". implode("','", $action_list)."'";
}
?>
