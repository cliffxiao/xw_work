<?php

/*** ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WechatControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信公众平台API
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class WechatController extends CommonController
{

    private $weObj = '';

    private $orgid = '';

    private $wechat_id = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        
        // 获取公众号配置
        $this->orgid = I('get.orgid');
        if (! empty($this->orgid)) {
            $wxinfo = $this->get_config($this->orgid);
            
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            $this->weObj = new Wechat($config);
            $this->weObj->valid();
            $this->wechat_id = $wxinfo['id'];
        }
    }

    /**
     * 执行方法
     */
    public function index()
    {
        // 事件类型
        $type = $this->weObj->getRev()->getRevType();
        $wedata = $this->weObj->getRev()->getRevData();
        $keywords = '';
        if ($type == Wechat::MSGTYPE_TEXT) {
            $keywords = $wedata['Content'];
        } elseif ($type == Wechat::MSGTYPE_EVENT) {
            if ('subscribe' == $wedata['Event']) {
                // 关注
                $this->subscribe($wedata['FromUserName']);
                // 关注时回复信息
                $this->msg_reply('subscribe');
                exit();
            } elseif ('unsubscribe' == $wedata['Event']) {
                // 取消关注
                $this->unsubscribe($wedata['FromUserName']);
                exit();
            } elseif ('MASSSENDJOBFINISH' == $wedata['Event']) {
                // 群发结果
                $data['status'] = $wedata['Status'];
                $data['totalcount'] = $wedata['TotalCount'];
                $data['filtercount'] = $wedata['FilterCount'];
                $data['sentcount'] = $wedata['SentCount'];
                $data['errorcount'] = $wedata['ErrorCount'];
                // 更新群发结果
                $this->model->table('wechat_mass_history')
                    ->data($data)
                    ->where('msg_id = "' . $wedata['MsgID'] . '"')
                    ->update();
                exit();
            } elseif ('CLICK' == $wedata['Event']) {
                /*
                 * $wedata = array( 'ToUserName' => 'gh_1ca465561479', 'FromUserName' => 'oWbbLt4fDrg78mvacsfpvi9Juo4I', 'CreateTime' => '1408944652', 'MsgType' => 'event', 'Event' => 'CLICK', 'EventKey' => 'ffff' );
                 */
                // 点击菜单
                $keywords = $wedata['EventKey'];
            } elseif ('VIEW' == $wedata['Event']) {
                $this->redirect($wedata['EventKey']);
            }
        } else {
            $this->msg_reply('msg');
            exit();
        }
        // 回复
        if (! empty($keywords)) {
            $rs = $this->get_function($wedata['FromUserName'], $keywords);
            if (empty($rs)) {
                $rs1 = $this->keywords_reply($keywords);
                if (empty($rs1)) {
                    $this->msg_reply('msg');
                }
            }
        }
    }

    /**
     * 关注处理
     *
     * @param array $info            
     */
    private function subscribe($openid = '')
    {
        // 用户信息
        $info = $this->weObj->getUserInfo($openid);
        if (empty($info)) {
            $info = array();
        }
        
        // 查找用户是否存在
        $where['openid'] = $openid;
        $rs = $this->model->table('wechat_user')
            ->field('uid, subscribe')
            ->where($where)
            ->find();
        // 未关注
        if (empty($rs)) {
            // 用户注册
            $domain = get_top_domain();
            $username = time () . rand(100, 999);
            if (model('Users')->register($username, 'ecmoban',  $username. '@' . $domain) !== false) {     
                $data['user_rank'] = 99;
                
                $this->model->table('users')
                    ->data($data)
                    ->where('user_name = "' . $username . '"')
                    ->update();
            } else {
                die('');
            }
            $info['ect_uid'] = $_SESSION['user_id'];
            // 获取用户所在分组ID
            $group_id = $this->weObj->getUserGroup($openid);
            $info['group_id'] = $group_id ? $group_id : '';
            // 获取被关注公众号信息
            $info['wechat_id'] = $this->wechat_id;
            $info['subscribe'] = 1;
            $info['openid'] = $openid;
            $this->model->table('wechat_user')
                ->data($info)
                ->insert();
        } else {
            $info['subscribe'] = 1;
            $this->model->table('wechat_user')
                ->data($info)
                ->where($where)
                ->update();
        }
    }

    /**
     * 取消关注
     *
     * @param string $openid            
     */
    public function unsubscribe($openid = '')
    {
        // 未关注
        $where['openid'] = $openid;
        $rs = $this->model->table('wechat_user')
            ->where($where)
            ->count();
        // 修改关注状态
        if ($rs > 0) {
            $data['subscribe'] = 0;
            $this->model->table('wechat_user')
                ->data($data)
                ->where($where)
                ->update();
        }
    }

    /**
     * 被动关注，消息回复
     *
     * @param string $type            
     */
    private function msg_reply($type)
    {
        $replyInfo = $this->model->table('wechat_reply')
            ->field('content, media_id')
            ->where('type = "' . $type . '" and wechat_id = ' . $this->wechat_id)
            ->find();
        if (! empty($replyInfo)) {
            if (! empty($replyInfo['media_id'])) {
                $replyInfo['media'] = $this->model->table('wechat_media')
                    ->field('title, content, file, type, file_name')
                    ->where('id = ' . $replyInfo['media_id'])
                    ->find();
                if ($replyInfo['media']['type'] == 'news') {
                    $replyInfo['media']['type'] = 'image';
                }
                // 上传多媒体文件
                $rs = $this->weObj->uploadMedia(array(
                    'media' => '@' . ROOT_PATH . $replyInfo['media']['file']
                ), $replyInfo['media']['type']);
                
                // 回复数据重组
                if ($rs['type'] == 'image' || $rs['type'] == 'voice') {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                } elseif ('video' == $rs['type']) {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                }
                $this->weObj->reply($replyData);
            } else {
                // 文本回复
                $replyInfo['content'] = strip_tags($replyInfo['content']);
                $this->weObj->text($replyInfo['content'])->reply();
            }
        }
    }

    /**
     * 关键词回复
     *
     * @param string $keywords            
     * @return boolean
     */
    private function keywords_reply($keywords)
    {
        $endrs = false;
        $sql = 'SELECT r.content, r.media_id, r.reply_type FROM ' . $this->model->pre . 'wechat_reply r LEFT JOIN ' . $this->model->pre . 'wechat_rule_keywords k ON r.id = k.rid WHERE k.rule_keywords = "' . $keywords . '" and r.wechat_id = ' . $this->wechat_id . ' order by r.add_time desc LIMIT 1';
        $result = $this->model->query($sql);
        if (! empty($result)) {
            // 素材回复
            if (! empty($result[0]['media_id'])) {
                $mediaInfo = $this->model->table('wechat_media')
                    ->field('title, content, file, type, file_name, article_id, link')
                    ->where('id = ' . $result[0]['media_id'])
                    ->find();
                
                // 回复数据重组
                if ($result[0]['reply_type'] == 'image' || $result[0]['reply_type'] == 'voice') {
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array(
                        'media' => '@' . ROOT_PATH . $mediaInfo['file']
                    ), $result[0]['reply_type']);
                    
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('video' == $result[0]['reply_type']) {
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array(
                        'media' => '@' . ROOT_PATH . $mediaInfo['file']
                    ), $result[0]['reply_type']);
                    
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('news' == $result[0]['reply_type']) {
                    // 图文素材
                    $articles = array();
                    if (! empty($mediaInfo['article_id'])) {
                        $artids = explode(',', $mediaInfo['article_id']);
                        foreach ($artids as $key => $val) {
                            $artinfo = $this->model->table('wechat_media')
                                ->field('title, file, content, link')
                                ->where('id = ' . $val)
                                ->find();
                            $artinfo['content'] = strip_tags(html_out($artinfo['content']));
                            $articles[$key]['Title'] = $artinfo['title'];
                            $articles[$key]['Description'] = $artinfo['content'];
                            $articles[$key]['PicUrl'] = __URL__ . '/' . $artinfo['file'];
                            $articles[$key]['Url'] = $artinfo['link'];
                        }
                    } else {
                        $articles[0]['Title'] = $mediaInfo['title'];
                        $articles[0]['Description'] = strip_tags(html_out($mediaInfo['content']));
                        $articles[0]['PicUrl'] = __URL__ . '/' . $mediaInfo['file'];
                        $articles[0]['Url'] = $mediaInfo['link'];
                    }
                    // 回复
                    $this->weObj->news($articles)->reply();
                    $endrs = true;
                }
            } else {
                // 文本回复
                $result[0]['content'] = strip_tags($result[0]['content']);
                $this->weObj->text($result[0]['content'])->reply();
                $endrs = true;
            }
        }
        return $endrs;
    }

    /**
     * 功能变量查询
     *
     * @param unknown $tousername            
     * @param unknown $fromusername            
     * @param unknown $keywords            
     * @return boolean
     */
    public function get_function($fromusername, $keywords)
    {
        $rs = $this->model->table('wechat_extend')
            ->field('name, command, config')
            ->where('keywords like "%' . $keywords . '%" and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();
        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once ($file);
            $wechat = new $rs['command']();
            $data = $wechat->show($fromusername, $rs);
            if (! empty($data)) {
                $this->weObj->news($data)->reply();
                // 积分赠送
                $wechat->give_point($fromusername, $rs);
                $return = true;
            }
        }
        return $return;
    }

    /**
     * 获取用户昵称，头像
     *
     * @param unknown $user_id            
     * @return multitype:
     */
    public static function get_avatar($user_id)
    {
        $u_row = model('base')->model->table('wechat_user')
            ->field('nickname, headimgurl')
            ->where('ect_uid = ' . $user_id)
            ->find();
        if (empty($u_row)) {
            $u_row = array();
        }
        return $u_row;
    }

    /**
     * 微信OAuth操作
     */
    static function do_oauth()
    {
        // 默认公众号信息
        $wxinfo = model('Base')->model->table('wechat')
            ->field('id, token, appid, appsecret, oauth_redirecturi, type')
            ->where('default_wx = 1 and status = 1')
            ->find();
        if (! empty($wxinfo) && $wxinfo['type'] == 2) {
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            
            // 微信通验证
            $weObj = new Wechat($config);
            // 微信浏览器浏览
            if (self::is_wechat_browser() && $_SESSION['user_id'] === 0) {
                if (isset($_SERVER['REQUEST_URI']) && ! empty($_SERVER['REQUEST_URI'])) {
                    $redirecturi = __HOST__ . $_SERVER['REQUEST_URI'];
                } else {
                    $redirecturi = $wxinfo['oauth_redirecturi'];
                }
                
                $url = $weObj->getOauthRedirect($redirecturi, 1);
                if (isset($_GET['code']) && $_GET['code'] != 'authdeny') {
                    $token = $weObj->getOauthAccessToken();
                    if ($token) {
                        $userinfo = $weObj->getOauthUserinfo($token['access_token'], $token['openid']);
                        self::update_weixin_user($userinfo, $wxinfo['id'], $weObj);
                    } else {
                        header('Location:' . $url, true, 302);
                    }
                } else {
                    header('Location:' . $url, true, 302);
                }
            }
        }
    }

    /**
     * 更新微信用户信息
     *
     * @param unknown $userinfo            
     * @param unknown $wechat_id            
     * @param unknown $weObj            
     */
    static function update_weixin_user($userinfo, $wechat_id, $weObj)
    {
        $time = time();
        $ret = model('Base')->model->table('wechat_user')
            ->field('openid, ect_uid')
            ->where('openid = "' . $userinfo['openid'] . '"')
            ->getOne();
        if (empty($ret)) {
            // 会员注册
            $domain = get_top_domain();
            if (model('Users')->register($userinfo['openid'], 'ecmoban', $time . rand(100, 999) . '@' . $domain) !== false) {
                $new_user_name = 'wx' . $_SESSION['user_id'];
                $data['user_name'] = $new_user_name;
                $data['email'] = $new_user_name . '@' . $domain;
                $data['user_rank'] = 99;
                
                model('Base')->model->table('users')
                    ->data($data)
                    ->where('user_name = "' . $userinfo['openid'] . '"')
                    ->update();
            } else {
                die('授权失败，如重试一次还未解决问题请联系管理员');
            }
            $data1['wechat_id'] = $wechat_id;
            $data1['subscribe'] = 1;
            $data1['openid'] = $userinfo['openid'];
            $data1['nickname'] = $userinfo['nickname'];
            $data1['sex'] = $userinfo['sex'];
            $data1['city'] = $userinfo['city'];
            $data1['country'] = $userinfo['country'];
            $data1['province'] = $userinfo['province'];
            $data1['language'] = $userinfo['country'];
            $data1['headimgurl'] = $userinfo['headimgurl'];
            $data1['subscribe_time'] = $time;
            $data1['ect_uid'] = $_SESSION['user_id'];
            // 获取用户所在分组ID
            $group_id = $weObj->getUserGroup($userinfo['openid']);
            if ($group_id === false) {
                die($weObj->errCode . ':' . $weObj->errMsg);
            }
            $data1['group_id'] = $group_id;
            
            model('Base')->model->table('wechat_user')
                ->data($data1)
                ->insert();
        } else {
            model('Base')->model->table('wechat_user')
                ->data('subscribe = 1')
                ->where('openid = "' . $userinfo['openid'] . '"')
                ->update();
            $new_user_name = model('Base')->model->table('users')
                ->field('user_name')
                ->where('user_id = "' . $ret['ect_uid'] . '"')
                ->getOne();
        }
        // 推送量
        model('Base')->model->table('wechat')
            ->data('oauth_count = oauth_count + 1')
            ->where('default_wx = 1 and status = 1')
            ->update();
        
        session('openid', $userinfo['openid']);
        ECTouch::user()->set_session($new_user_name);
        ECTouch::user()->set_cookie($new_user_name);
        model('Users')->update_user_info();
    }

    /**
     * 检查是否是微信浏览器访问
     */
    static function is_wechat_browser()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 插件页面显示方法
     *
     * @param string $plugin            
     */
    public function plugin_show()
    {
        $plugin = I('get.name');
        $file = ADDONS_PATH . 'wechat/' . $plugin . '/' . $plugin . '.class.php';
        if (file_exists($file)) {
            include_once ($file);
            $wechat = new $plugin();
            $wechat->html_show();
        }
    }

    /**
     * 插件处理方法
     *
     * @param string $plugin            
     */
    public function plugin_action()
    {
        $plugin = I('get.name');
        $file = ADDONS_PATH . 'wechat/' . $plugin . '/' . $plugin . '.class.php';
        if (file_exists($file)) {
            include_once ($file);
            $wechat = new $plugin();
            $wechat->action();
        }
    }

    /**
     * 获取公众号配置
     *
     * @param string $orgid            
     * @return array
     */
    private function get_config($orgid)
    {
        $config = $this->model->table('wechat')
            ->field('id, token, appid, appsecret')
            ->where('orgid = "' . $orgid . '" and status = 1')
            ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }
}
