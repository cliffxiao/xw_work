<?php

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

/**
 * 开联通卡
 */
class Kltong {

    private $app_key = 'zz901'; //AOP分配给应用统一的AppKey
    private $method = ''; // API接口名称
    private $timestamp = ''; //时间戳，格式为yyyyMMddHHmmss
    private $v = '1.0';  // API协议版本，可选值:1.0
    private $sign_v = '1'; // 签名版本号，每次更新后+1递增，可选值：1
    private $sign = 'zz9011aopreq201211071748130tnFzL7a'; // API输入参数签名结果，使用md5加密
    private $format = 'json'; //指定响应格式。默认json,目前支持格式为xml,json
    private $req_seq = ''; // 请求交易流水号，10位数字
    private $req_dt = ''; // 请求交易日期，YYYYMMDD
    private $req_tm = ''; // 请求交易时间，HHMMSS
    private $partner_id = 'aop-sdk-java-20110125'; // 操作员，如网站和app为不同值
    private $card_sn = ''; //卡号

    // 正式地址：http://mscnew.koolyun.cn/aop/rest
    private $url = "http://mscnew.koolyun.cn/aop/rest";
    //private $url = "http://192.168.88.22:8080/aop/rest";

    public  $error = ''; // 错误代码

    private $is_log = true;
    //private $model = '';
    //
    private $card_status = array(
            '0' => '正常',
            '1' => '挂失',
            '3' => '销卡',
            '4' => '止付',
            '5' => '临时挂失',
        );
    private $txn_status = array(
            '0' => '挂起', //（暂无使用）
            '1' => '失败', //（暂无使用）
            '2' => '成功',
            '3' => '已冲正', //（消费、撤销、圈存写）
            '4' => '已取消', //（消费填写）
        );

    public function __construct($config = array())
    {
        if(isset($config['is_log'])){
            $this->is_log = $config['is_log'];
        }
        $this->model = ECTouch::db();
        $this->pre = C('DB.DB_PREFIX');
    }

    // 处理参数
    public function parseData($config = array())
    {
        $data['app_key'] = $this->app_key;
        $data['method'] = $this->method;
        $data['timestamp'] = date('YmdHis');
        $data['v'] = $this->v;
        $data['sign_v'] = $this->sign_v;
        $data['format'] = $this->format;
        $data['req_seq'] = NOW_TIME;
        $data['req_dt'] = date('Ymd');
        $data['req_tm'] = date('His');
        $data['partner_id'] = $this->partner_id;
        $data = array_merge($data, $config);
        ksort($data);
        foreach ($data as $key => $value) {
            $parseData[] = $key .'='. $value;
            $sign[] = $key.$value;
        }
        $parseData = implode('&', $parseData);
        $sign = md5($this->sign.implode('', $sign).$this->sign);
        return 'sign='.$sign . '&' . $parseData;
    }

    //卡券激活
    public function card_active($card_info = array())
    {
        if(empty($card_info['custId']) || empty($card_info['amountAt']))
        {
            $this->error = '没有卡号或金额。';
            return false;
        }elseif(empty($card_info['openBrh']) || empty($card_info['prdtNo']))
        {
            $this->error = '机构号或产品号不存在。';
            return false;            
        }
        $this->method = 'allinpay.ggpt.ecard.cardbal.add';
        $config['reqSeq'] = time();
        $config['custId'] = $card_info['custId'];
        $config['prdtNo'] = $card_info['prdtNo'];
        $config['openBrh'] = $card_info['openBrh'];
        $config['amountAt'] = $config['txAt'] = $card_info['amountAt'] * 100;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data); 
        return $this->callback($data, "ggpt_ecard_cardbal_add_response");      
    }

    //卡券余额
    public function card_balance($cards = array())
    {
        $this->method = 'smartpay.ggpt.commoncard.blance';
        if(is_array($cards)){
            $config['cards'] = "'".implode("','", $cards)."'";
        }else{
            $config['cards'] = "'".$cards."'";
        }
        //$config['brhId'] = '0279200002';

        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "qry_blance_response");
    }

    //卡券交易明细查询
    public function card_details($card_no = '', $start_dt ='', $end_dt = '', $page_num = 1, $page_size = 20)
    {
        if(empty($card_no))
        {
            $this->error = '卡号不能为空。';
            return false;            
        }
        elseif(empty($start_dt) || empty($end_dt))
        {
            $this->error = '查询开始日期或结束日期不存在。';
            return false;            
        }        
        $this->method = 'smartpay.ggpt.commoncard.txnlog';
        $config['cardNo'] = $card_no;
        $config['startDt'] = $start_dt;
        $config['endDt'] = $end_dt;
        $config['pageNum'] = $page_num;
        $config['pageSize'] = $page_size;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "qry_txnlog_response");
    }

    // 卡券二维码生成
    public function card_paycode($card_no = '', $prdt_no = '', $card_brh = '')
    {     
        if(empty($card_no))
        {
            $this->error = '卡号不能为空。';
            return false;            
        }        
        $this->method = 'smartpay.ggpt.commoncard.dimecode';
        $config['cardNo'] = $card_no;
        $config['cardBrh'] = $card_brh;
        $config['prdtNo'] = $prdt_no;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "build_dimecode_response");
    }

    //解析结果
    private function callback($data, $type='')
    {
        if(empty($data)){
            $this->error = '没有数据。';
            return false;
        }  
        $api_id = $this->log($data, 'JSON', 2);              
        //$this->write_file($data);
        $data = preg_replace( "/:(\d+)(,|})/", ':"$1"$2', $data);
        $data = json_decode($data);
        $data = $this->object_to_array($data);
        if($data['error_response']){
            $api_code = $data['error_response']['sub_code'];
            $api_msg = $data['error_response']['sub_msg'];
            if(empty($api_msg)){
                $api_msg = $data['error_response']['msg'];
            }
            $this->error = $api_msg;
            $this->update_log($api_id, $api_code, $api_msg);
            return false;
        }
        elseif($data[$type]['rsp_code'] == '0000')
        {
            $api_code = $data[$type]['rsp_code'];
            $api_msg = $data[$type]['msg']? : '成功';
            $this->update_log($api_id, $api_code, $api_msg);
            return $data[$type];
        }
        else
        {
            $this->error = $data[$type]['msg'];
            return false;
        }
    }

    //检验数据
    public function check_data()
    {
    }

    /**
     * Gets the properties of the given object recursion
     *
     * @access private
     *
     * @return array
     */
    private function object_to_array($obj) {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        if(!is_array($_arr)){
            return array();
        }
        foreach ($_arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
            if(is_numeric($val)){
                $val = (string)$val;
            }
            $arr[$key] = $val;
        }
        return $arr;
    } 

    //写日志
    private function write_file($content) {
        if($this->is_log == false){
            return false;
        }
        $log_path = ROOT_PATH . 'data/cardlog/';
        $this->mkdirs($log_path);
        $filename = $log_path . date('Y-m-d') . '.log';

        $fp = fopen($filename, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y-%m-%d %H:%M:%S", time()) . PHP_EOL . $content . PHP_EOL . PHP_EOL . PHP_EOL);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    private function mkdirs($dir, $mode = 0777) {
        if (is_dir($dir) || @mkdir($dir, $mode))
            return TRUE;
        if (!$this->mkdirs(dirname($dir), $mode))
            return FALSE;
        return @mkdir($dir, $mode);
    } 

    private function Post($post_data){
        $this->log($post_data, 'POST', 1);
        //$this->write_file($this->url .'?'. $post_data);
        return Http::doPost($this->url, $post_data);
    } 

    private function log($post_data, $api_type = 'JSON', $api_status = 0){
        $data['user_id'] = I('session.user_id', 0, 'intval');
        $data['api_url'] = $this->url;
        $data['api_method'] = $this->method;
        $data['api_data'] = addslashes($post_data);
        $data['api_date'] = NOW_TIME;
        $data['api_type'] = $api_type;
        $data['api_status'] = $api_status;
        $keys = array_keys($data);
        $values = array_values($data);
        $sql = "INSERT INTO " . $this->pre .
               "api_log (`" . implode('`,`', $keys). "`) VALUES('" . implode("','", $values). "')";
        $this->model->query($sql); 
        return $this->model->insert_id();                 
    }

    public function update_log($api_id = 0, $api_code = '', $api_msg = ''){
        $set = array();
        if($api_code) {
            $set[] = "`api_code` = '$api_code'";
        }
        if($api_msg) {
            $set[] = "`api_msg` = '$api_msg'";
        }    
        if(empty($set) || empty($api_id)){
            return false;
        }    
        $sql = "UPDATE " . $this->pre .
               "api_log SET " . implode(', ', $set) . " WHERE `api_id` = '$api_id'";
        $this->model->query($sql);
    }

}

?>