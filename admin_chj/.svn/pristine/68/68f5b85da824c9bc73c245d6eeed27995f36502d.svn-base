<?php

/** * 云平台 管理中心商店设置
 * ============================================================================
 * * 版权所有 2016-2017 中国支付通集团，并保留所有权利。
 * $Id: shop_config.php 17217 
 */

define('IN_ZFT', true);

/* 代码 */
require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 机构设置 ?act=set
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
     /* 检查权限 */
     admin_priv('organize_manage');

    /* 查询 */
    $result = organize_list(); 

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['organize_list']); // 当前导航
    $smarty->assign('action_link', array('href' => 'organize.php?act=add', 'text' => $_LANG['add_organize']));
    $smarty->assign('full_page',        1); // 翻页参数
    $smarty->assign('organize_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_organize_id', '<img src="images/sort_desc.gif">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('organize_list.htm');    
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('organize_manage');

    $result = organize_list();

    $smarty->assign('organize_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('organize_list.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));    
}
/*------------------------------------------------------ */
//-- 列表页编辑名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_organize_name')
{
    check_authz_json('organize_manage');

    $id     = intval($_POST['id']);
    $name   = json_str_iconv(trim($_POST['val']));

    /* 判断名称是否重复 */
    $sql = "SELECT organize_id
            FROM " . $ecs->table('organize') . "
            WHERE organize_name = '$name'
            AND organize_id <> '$id' ";
    if ($db->getOne($sql))
    {
        make_json_error(sprintf($_LANG['organize_name_exist'], $name));
    }
    else
    {
        /* 保存机构信息 */
        $sql = "UPDATE " . $ecs->table('organize') . "
                SET organize_name = '$name'
                WHERE organize_id = '$id'";
        if ($result = $db->query($sql))
        {
            /* 记日志 */
            admin_log($name, 'edit', 'organize');

            clear_cache_files();

            make_json_result(stripslashes($name));
        }
        else
        {
            make_json_result(sprintf($_LANG['agency_edit_fail'], $name));
        }
    }
}
/*------------------------------------------------------ */
//-- 删除机构
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('organize_manage');

    $id = intval($_REQUEST['id']);
    $sql = "SELECT organize_id, organize_name
            FROM " . $ecs->table('organize') . "
            WHERE organize_id = '$id'";
    $organize = $db->getRow($sql, TRUE);

    if ($organize['organize_id'])
    {
        $organize_exists = oranize_exists($organize['organize_id']);
        if ($organize_exists > 0)
        {
            $url = 'organize.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
            ecs_header("Location: $url\n");
            exit;
        }
        $sql = "DELETE FROM " . $ecs->table('organize') . "
            WHERE organize_id = '$id'";
        $db->query($sql);

        /* 记日志 */
        admin_log($organize['organize_name'], 'remove', 'organize');

        /* 清除缓存 */
        clear_cache_files();
    }

    $url = 'organize.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");

    exit;
}
/*------------------------------------------------------ */
//-- 修改机构状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'is_check')
{
    check_authz_json('organize_manage');

    $id = intval($_REQUEST['id']);
    $sql = "SELECT organize_id, is_check
            FROM " . $ecs->table('organize') . "
            WHERE organize_id = '$id'";
    $organize = $db->getRow($sql, TRUE);

    if ($organize['organize_id'])
    {
        $_organize['is_check'] = empty($organize['is_check']) ? 1 : 0;
        $db->autoExecute($ecs->table('organize'), $_organize, '', "organize_id = '$id'");
        clear_cache_files();
        make_json_result($_organize['is_check']);
    }

    exit;
}
/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 取得要操作的记录编号 */
    if (empty($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_record_selected']);
    }
    else
    {
        /* 检查权限 */
        admin_priv('organize_manage');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['remove']))
        {
            $sql = "SELECT organize_id, organize_name
                    FROM " . $ecs->table('organize') . "
                    WHERE organize_id " . db_create_in($ids);
            $organize = $db->getAll($sql);

            foreach ($organize as $key => $value)
            {
                $organize_exists = oranize_exists($value['organize_id']);
                if ($organize_exists > 0)
                {
                    unset($organize[$key]);
                }
            }
            if (empty($organize))
            {
                sys_msg($_LANG['batch_drop_no']);
            }


            $sql = "DELETE FROM " . $ecs->table('organize') . "
                WHERE organize_id " . db_create_in($ids);
            $db->query($sql);

            /* 记日志 */
            $organize_names = '';
            foreach ($organize as $value)
            {
                $organize_names .= $value['organize_name'] . '|';
            }
            admin_log($organize_names, 'remove', 'organize');

            /* 清除缓存 */
            clear_cache_files();

            sys_msg($_LANG['batch_drop_ok']);
        }
    }
}
/*------------------------------------------------------ */
//-- 添加、编辑机构
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('add', 'edit')))
{
    /* 检查权限 */
    admin_priv('organize_manage');

    if ($_REQUEST['act'] == 'add')
    {
        $organize = array();

        /* 取得所有管理员，*/
        /* 标注哪些是该机构的('this')，哪些是空闲的('free')，哪些是别的机构的('other') */
        /* 排除是办事处的管理员 */
        /*$sql = "SELECT user_id, user_name, CASE
                WHEN organize_id = 0 THEN 'free'
                ELSE 'other' END AS type
                FROM " . $ecs->table('admin_user') . "
                WHERE agency_id = 0
                AND action_list <> 'all'";
        $organize['admin_list'] = $db->getAll($sql);*/
        $organize['admin_list'] = null;

        $smarty->assign('ur_here', $_LANG['add_organize']);
        $smarty->assign('action_link', array('href' => 'organize.php?act=list', 'text' => $_LANG['organize_list']));

        $smarty->assign('form_action', 'insert');
        $smarty->assign('organize', $organize);
        /* 取得国家 */
        $smarty->assign('country_list', get_regions());
        assign_query_info();

        $smarty->display('organize_info.htm');

    }
    elseif ($_REQUEST['act'] == 'edit')
    {
        $organize = array();

        /* 取得机构信息 */
        $id = $_REQUEST['id'];
        $sql = "SELECT * FROM " . $ecs->table('organize') . " WHERE organize_id = '$id'";
        $organize = $db->getRow($sql);
        if (count($organize) <= 0)
        {
            sys_msg('organize does not exist');
        }

        /* 取得所有管理员，*/
        /* 标注哪些是该机构的('this')，哪些是空闲的('free')，哪些是别的机构的('other') */
        /* 排除是办事处的管理员 */
        /*$sql = "SELECT user_id, user_name, CASE
                WHEN organize_id = '$id' THEN 'this'
                WHEN organize_id = 0 THEN 'free'
                ELSE 'other' END AS type
                FROM " . $ecs->table('admin_user') . "
                WHERE agency_id = 0
                AND action_list <> 'all'";
        $organize['admin_list'] = $db->getAll($sql);*/
        $organize['admin_list'] = null;

        $smarty->assign('ur_here', $_LANG['edit_organize']);
        $smarty->assign('action_link', array('href' => 'organize.php?act=list', 'text' => $_LANG['organize_list']));

        $smarty->assign('form_action', 'update');
        $smarty->assign('organize', $organize);
        /* 取得国家 */
        $smarty->assign('country_list', get_regions());
        if ($organize['country'] > 0)
        {
            /* 取得省份 */
            $smarty->assign('province_list', get_regions(1, $organize['country']));
            if ($organize['province'] > 0)
            {
                /* 取得城市 */
                $smarty->assign('city_list', get_regions(2, $organize['province']));
                if ($organize['city'] > 0)
                {
                    /* 取得区域 */
                    $smarty->assign('district_list', get_regions(3, $organize['city']));
                }
            }
        }

        assign_query_info();

        $smarty->display('organize_info.htm');
    }

}
/*------------------------------------------------------ */
//-- 提交添加、编辑机构
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('insert', 'update')))
{
    /* 检查权限 */
    admin_priv('organize_manage');

    if ($_REQUEST['act'] == 'insert')
    {
        /* 提交值 */
        $organize = array(
            'organize_name'     => trim($_POST['organize_name']),
            'organize_desc'     => trim($_POST['organize_desc']),
            'country'           => intval($_POST['country']),
            'province'          => intval($_POST['province']),
            'city'              => intval($_POST['city']),
            'district'          => intval($_POST['district']),            
            'organize_address'  => trim($_POST['organize_address']),
            'contacts'          => trim($_POST['contacts']),
            'phone'             => trim($_POST['phone']),
            'mobile'            => trim($_POST['mobile']),
            'email'             => trim($_POST['email']),
            'fax'               => trim($_POST['fax']),
            'remark'            => trim($_POST['remark']),
            'is_check'          => intval($_POST['is_check']),
            'add_time'          => time(),
            'add_user_name'     => $_SESSION['admin_name'],
        );

        /* 判断名称是否重复 */
        $sql = "SELECT organize_id
                FROM " . $ecs->table('organize') . "
                WHERE organize_name = '" . $organize['organize_name'] . "' ";
        if ($db->getOne($sql))
        {
            sys_msg($_LANG['organize_name_exist']);
        }

        $db->autoExecute($ecs->table('organize'), $organize, 'INSERT');
        $organize['organize_id'] = $db->insert_id();

        if (isset($_POST['admins']))
        {
            $sql = "UPDATE " . $ecs->table('admin_user') . " SET organize_id = '" . $organize['organize_id'] . "', action_list = '" . organize_ACTION_LIST . "' WHERE user_id " . db_create_in($_POST['admins']);
            $db->query($sql);
        }

        /* 记日志 */
        admin_log($organize['organize_name'], 'add', 'organize');

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(array('href' => 'organize.php?act=add',  'text' => $_LANG['continue_add_organize']),
                       array('href' => 'organize.php?act=list', 'text' => $_LANG['back_organize_list'])
                       );
        sys_msg($_LANG['add_organize_ok'], 0, $links);

    }

    if ($_REQUEST['act'] == 'update')
    {
        /* 提交值 */
        $organize = array('id'   => trim($_POST['id']));

        $organize['new'] = array(
            'organize_name'     => trim($_POST['organize_name']),
            'organize_desc'     => trim($_POST['organize_desc']),
            'country'           => intval($_POST['country']),
            'province'          => intval($_POST['province']),
            'city'              => intval($_POST['city']),
            'district'          => intval($_POST['district']),           
            'organize_address'  => trim($_POST['organize_address']),
            'contacts'          => trim($_POST['contacts']),
            'phone'             => trim($_POST['phone']),
            'mobile'            => trim($_POST['mobile']),
            'email'             => trim($_POST['email']),
            'fax'               => trim($_POST['fax']),
            'remark'            => trim($_POST['remark']),
            'is_check'          => intval($_POST['is_check']),
            'last_user_name'    => $_SESSION['admin_name'],
            'last_update_time'  => time(),
        );

        /* 取得机构信息 */
        $sql = "SELECT * FROM " . $ecs->table('organize') . " WHERE organize_id = '" . $organize['id'] . "'";
        $organize['old'] = $db->getRow($sql);
        if (empty($organize['old']['organize_id']))
        {
            sys_msg('organize does not exist');
        }

        /* 判断名称是否重复 */
        $sql = "SELECT organize_id
                FROM " . $ecs->table('organize') . "
                WHERE organize_name = '" . $organize['new']['organize_name'] . "'
                AND organize_id <> '" . $organize['id'] . "'";
        if ($db->getOne($sql))
        {
            sys_msg($_LANG['organize_name_exist']);
        }

        /* 保存机构信息 */
        $db->autoExecute($ecs->table('organize'), $organize['new'], 'UPDATE', "organize_id = '" . $organize['id'] . "'");

        /* 清空机构的管理员 */
        //$sql = "UPDATE " . $ecs->table('admin_user') . " SET organize_id = 0, action_list = '" . organize_ACTION_LIST . "' WHERE organize_id = '" . $organize['id'] . "'";
        //$db->query($sql);

        /* 添加机构的管理员 */
        if (isset($_POST['admins']))
        {
            $sql = "UPDATE " . $ecs->table('admin_user') . " SET organize_id = '" . $organize['old']['organize_id'] . "' WHERE user_id " . db_create_in($_POST['admins']);
            $db->query($sql);
        }

        /* 记日志 */
        admin_log($organize['old']['organize_name'], 'edit', 'organize');

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links[] = array('href' => 'organize.php?act=list', 'text' => $_LANG['back_organize_list']);
        sys_msg($_LANG['edit_organize_ok'], 0, $links);
    }

}

/**
 *  获取机构列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function organize_list()
{
    $result = get_filter();
    if($result == false)
    {
        $ajax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'organize_id' : trim($_REQUEST['sort_by']);  
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        //$where = 'WHERE 1 ';
        $where = $_SESSION['organize_id'] > 0 ? "WHERE organize_id = " . $_SESSION['organize_id']." " : "WHERE 1 ";

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('organize') . $where;   
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT organize_id, organize_name, contacts, mobile, is_check, add_time, last_update_time
                FROM " . $GLOBALS['ecs']->table("organize") . "
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);                                          
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);    


    foreach ($row AS $key=>$val)
    {
        $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
        $row[$key]['last_update_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['last_update_time']);
    }

    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

/**
 *  判断机构是否存的管理员
 *
 * @access  public
 * @param
 *
 * @return void
 */
function oranize_exists($id)
{
    $sql = "SELECT COUNT(*)
            FROM " . $GLOBALS['ecs']->table('admin_user') . "
                WHERE organize_id = '$id'";
    return $GLOBALS['db']->getOne($sql, TRUE);
}


?>