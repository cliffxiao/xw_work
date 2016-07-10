<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WechatControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信公众平台API
 * ----------------------------------------------------------------------------
 * 
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
                $data['source'] = 1;
                $data['is_validated'] = 1;
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
            $info2['subscribe'] = 1;
            $this->model->table('wechat_user')
                ->data($info2)
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
            //$_SESSION['user_id'] = 0;
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
//        $ret = model('Base')->model->table('wechat_user')
//            ->field('openid, ect_uid')
//            ->where('openid = "' . $userinfo['openid'] . '"')
//            ->getOne();
        $ret = model('Base')->get_weixin_user($userinfo['openid']);
        if (empty($ret)) {
            // 会员注册
            $domain = get_top_domain();
            if (model('Users')->register($userinfo['openid'], 'ecmoban', $time . rand(100, 999) . '@' . $domain) !== false) {
                $new_user_name = 'wx' . $_SESSION['user_id'];
                $data['user_name'] = $new_user_name;
                $data['email'] = $new_user_name . '@' . $domain;
                $data['user_rank'] = 99;
                $data['source'] = 1;
                $data['is_validated'] = 1;
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
            if(isset($userinfo['unionid'])){
           		 $data1['unionid'] = $userinfo['unionid'];
            }
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

    /**
     * 创建微信会员卡
     * @return array
     */
    public function create_card()
    {
        // 默认公众号信息
        $wxinfo = model('Base')->get_weixin_config();
        if (! empty($wxinfo) && $wxinfo['type'] == 2) {
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];

            // 微信通验证
            $weObj = new Wechat($config);
            // 微信浏览器浏览
            //$_SESSION['user_id'] = 0;
            //if (self::is_wechat_browser() && $_SESSION['user_id'] === 0) {
                if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                    $redirecturi = __HOST__ . $_SERVER['REQUEST_URI'];
                } else {
                    $redirecturi = $wxinfo['oauth_redirecturi'];
                }
                $data = array(
                    "card"=> array(
                        "card_type" => "MEMBER_CARD",
                        "member_card"=> array(
                            "base_info"=> array(
                                "logo_url"=> "http://mmbiz.qpic.cn/mmbiz/iaL1LJM1mF9aRKPZJkmG8xXhiaHqkKSVMMWeN3hLut7X7hicFNjakmxibMLGWpXrEXB33367o7zHN0CwngnQY7zb7g/0",
                                "brand_name"=> "海底捞",   //商户名字*
                                "code_type"=> "CODE_TYPE_TEXT", //商户展示类型*
                                "title"=> "海底捞会员卡", //卡券名*
                                "color"=> "Color010",   //券颜色*
                                "notice"=> "使用时向服务员出示此券",   //卡券使用提醒*
                                "service_phone"=> "020-88888888",   //客服电话
                                "description"=> "不可与其他优惠同享",    //卡券使用说明*
                                "date_info"=> array(    //使用日期*
                                    "type"=> "DATE_TYPE_PERMANENT"  //使用时间的类型*
                                ),
                                "sku"=> array(  //商品信息*
                                    "quantity"=> 50000000   //卡券库存的数量*
                                ),
                                "get_limit"=> 3,    //每人可领券的数量限制
                                "use_custom_code"=> false,  //是否自定义Code码。填写true或false，默认为false。通常自有优惠码系统的开发者选择自定义Code码，在卡券投放时带入。
                                "can_give_friend"=> true,   //卡券是否可转赠
                                "custom_url_name"=> "立即使用", //自定义跳转外链的入口名字
                                "custom_url"=> "http://www.kajuan.new/index.php",    //自定义跳转的URL
                                "custom_url_sub_title"=> "6个汉字tips",    //显示在入口右侧的提示语
                                "promotion_url_name"=> "营销入口1", //营销场景的自定义入口名称
                                "promotion_url"=> "http://www.xxx.com", //入口跳转外链的地址链接
                                "need_push_on_view"=> true  //填写true为用户点击进入会员卡时推送事件，默认为false
                            ),
                            "supply_bonus"=> true,  //显示积分，填写true或false，如填写true，积分相关字段均为必填*
                            "supply_balance"=> false,   //是否支持储值，填写true或false。如填写true，储值相关字段均为必填。
                            "prerogative"=> "test_prerogative", //会员卡特权说明
                            "auto_activate"=> true, //设置为true时用户领取会员卡后系统自动将其激活，无需调用激活接口
//                            "custom_field1"=> array(    //自定义会员信息类目，会员卡激活后显示。
                                "name_type"=> "FIELD_NAME_TYPE_COUPON",  //会员信息类目名称
                                "url"=> "http://www.xxx.com"    //点击类目跳转外链url
                            ),
                            "activate_url"=> "http://www.xxx.com",  //激活会员卡的url
                            "custom_cell1" => array(    //自定义会员信息类目，会员卡激活后显示
                                "name"=> "使用入口2",
                                "tips"=> "激活后显示",   //入口右侧提示语，6个汉字内
                                "url"=> "http://www.xxx.com"    //入口跳转链接
                            ),
                            "bonus_rule" => array(  //积分规则
                                "cost_money_unit"=> 100,    //消费金额。以分为单位
                                "increase_bonus"=> 1,   //对应增加的积分
                                "max_increase_bonus"=> 200, //积分上限
                                "init_increase_bonus"=> 10  //初始设置积分
                            ),
                            "discount"=> 10 //折扣，该会员卡享受的折扣优惠,填10就是九折
                        )
                    );
                $url = $weObj->createCard($data);
                if (isset($_GET['code']) && $_GET['code'] != 'authdeny') {

                } else {
                    header('Location:' . $url, true, 302);
                }
            //}
        }
    }

    /**
     * 微信上传logo图片
     * @param string $buffer 图片路劲
     * @return array
     */
    public function uploadLogo($buffer)
    {
        // 默认公众号信息
        $wxinfo = model('Base')->get_weixin_config();
        if (! empty($wxinfo) && $wxinfo['type'] == 2) {
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];

            // 微信通验证
            $weObj = new Wechat($config);
            $data = array('media' =>  '@'.$buffer);
            //print_r($data);exit();
            $result = $weObj->uploadLogo($data);
            if (isset($result['url'])) {
                print_r($result);
            } else {
                header('Location:' . $url, true, 302);
            }
        }
    }

    
}
