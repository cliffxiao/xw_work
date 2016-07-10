<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticleBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 文章基础模型
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class ArticleBaseModel extends BaseModel {

    protected $table = 'article';

    /**
     * 获得文章分类下的文章列表
     * 
     * @access public
     * @param integer $cat_id            
     * @param integer $page            
     * @param integer $size            
     * @return array
     */
    public function get_cat_articles($cat_id, $page = 1, $size = 20, $requirement = '') {
        // 取出所有非0的文章
        if ($cat_id == '-1') {
            $cat_str = 'cat_id > 0';
        } else {
            $cat_str = get_article_children($cat_id);
        }
        $condition = 'is_open = 1 AND ' . $cat_str;
        // 增加搜索条件，如果有搜索内容就进行搜索
        if ($requirement != '') {
            $condition .= ' AND title like \'%' . $requirement . '%\'';
        }
        $limit = ($page - 1) * $size . ',' . $size;
        $list = $this->select($condition, 'article_id, title, author, add_time, file_url, open_type', 'article_type DESC, article_id DESC', $limit);

        $i = 1;
        $arr = array();
        if (is_array($list)) {
            foreach ($list as $vo) {
                $article_id = $vo['article_id'];
                $arr[$article_id]['id'] = $article_id;
                $arr[$article_id]['index'] = $i;
                $arr[$article_id]['title'] = $vo['title'];
                $arr[$article_id]['short_title'] = C('article_title_length') > 0 ? sub_str($vo['title'], C('article_title_length')) : $vo['title'];
                $arr[$article_id]['author'] = empty($vo['author']) || $vo['author'] == '_SHOPHELP' ? C('shop_name') : $vo['author'];
                $arr[$article_id]['url'] = $vo['open_type'] != 1 ? build_uri('article/info', array('aid' => $article_id )) : trim($vo['file_url']);
                $arr[$article_id]['add_time'] = date(C('date_format'), $vo['add_time']);
                $i++;
            }
        }
        return $arr;
    }

    /**
     * 获得指定分类下的文章总数
     * 
     * @param integer $cat_id            
     * @return integer
     */
    public function get_article_count($cat_id, $requirement = '') {
        $condition = get_article_children($cat_id) . ' AND is_open = 1';
        if ($requirement != '') {
            $condition .= ' AND title like \'%' . $requirement . '%\'';
        }
        return $this->count($condition);
    }

    /**
     * 获得指定文章分类下所有底层分类的ID
     *
     * @access public
     * @param integer $cat
     *            指定的分类ID
     *            
     * @return void
     */
    function get_article_children($cat = 0) {
        return db_create_in(array_unique(array_merge(array(
            $cat
                                ), array_keys($this->article_cat_list($cat, 0, false)))), 'cat_id');
    }

    /**
     * 获得指定分类下的子分类的数组
     *
     * @access public
     * @param int $cat_id
     *            分类的ID
     * @param int $selected
     *            当前选中分类的ID
     * @param boolean $re_type
     *            返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param int $level
     *            限定返回的级数。为0时返回所有级数
     * @return mix
     */
    function article_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0) {
        static $res = NULL;
        if ($res === NULL) {
            $data = read_static_cache('art_cat_pid_releate');
            if ($data === false) {
                $sql = "SELECT c.*, COUNT(s.cat_id) AS has_children, COUNT(a.article_id) AS aricle_num , tc.is_mobile " . ' FROM ' . $this->pre . "article_cat AS c" . " LEFT JOIN " . $this->pre . "article_cat AS s ON s.parent_id=c.cat_id" . " LEFT JOIN " . $this->pre . "article AS a ON a.cat_id=c.cat_id" . " LEFT JOIN " . $this->pre . "touch_article_cat AS tc ON c.cat_id = tc.cat_id" . " GROUP BY c.cat_id " . " ORDER BY parent_id, sort_order ASC";
                $res = $this->query($sql);
                write_static_cache('art_cat_pid_releate', $res);
            } else {
                $res = $data;
            }
        }

        if (empty($res) == true) {
            return $re_type ? '' : array();
        }

        $options = $this->article_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组
        /* 截取到指定的缩减级别 */
        if ($level > 0) {
            if ($cat_id == 0) {
                $end_level = $level;
            } else {
                $first_item = reset($options); // 获取第一个元素
                $end_level = $first_item['level'] + $level;
            }

            /* 保留level小于end_level的部分 */
            foreach ($options as $key => $val) {
                if ($val['level'] >= $end_level) {
                    unset($options[$key]);
                }
            }
        }

        $pre_key = 0;
        foreach ($options as $key => $value) {
            $options[$key]['has_children'] = 1;
            if ($pre_key > 0) {
                if ($options[$pre_key]['cat_id'] == $options[$key]['parent_id']) {
                    $options[$pre_key]['has_children'] = 1;
                }
            }
            $pre_key = $key;
        }

        if ($re_type == true) {
            $select = '';
            foreach ($options as $var) {
                $select .= '<option value="' . $var['cat_id'] . '" ';
                $select .= ' cat_type="' . $var['cat_type'] . '" ';
                $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
                $select .= '>';
                if ($var['level'] > 0) {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['cat_name'])) . '</option>';
            }

            return $select;
        } else {
            foreach ($options as $key => $value) {
                $options[$key]['url'] = build_uri('article_cat/index', array(
                    'acid' => $value['cat_id']
                ));
            }
            return $options;
        }
    }

    /**
     * 过滤和排序所有文章分类，返回一个带有缩进级别的数组
     *
     * @access private
     * @param int $cat_id
     *            上级分类ID
     * @param array $arr
     *            含有所有分类的数组
     * @param int $level
     *            级别
     * @return void
     */
    function article_cat_options($spec_cat_id, $arr) {
        static $cat_options = array();

        if (isset($cat_options[$spec_cat_id])) {
            return $cat_options[$spec_cat_id];
        }

        if (!isset($cat_options[0])) {
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = array();
            while (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = array(
                            $cat_id
                        );
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                }
            }
            $cat_options[0] = $options;
        } else {
            $options = $cat_options[0];
        }

        if (!$spec_cat_id) {
            return $options;
        } else {
            if (empty($options[$spec_cat_id])) {
                return array();
            }

            $spec_cat_id_level = $options[$spec_cat_id]['level'];

            foreach ($options as $key => $value) {
                if ($key != $spec_cat_id) {
                    unset($options[$key]);
                } else {
                    break;
                }
            }

            $spec_cat_id_array = array();
            foreach ($options as $key => $value) {
                if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) || ($spec_cat_id_level > $value['level'])) {
                    break;
                } else {
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;

            return $spec_cat_id_array;
        }
    }

}
