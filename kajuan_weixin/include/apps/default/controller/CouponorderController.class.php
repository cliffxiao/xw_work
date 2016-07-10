<?php
/**
 * 券包
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class CouponorderController extends CommonController {

    protected $user_id = '';
    protected $code = '';
    protected $page = 1;
    protected $data_path = '';
    protected $sign_key = 'xS3BjCLj8xALRcMw';

    public function __construct() {
        parent::__construct();

		$this->user_id = I('session.user_id', 0, 'intval');

        if (ACTION_NAME == 'list') {
            $this->index();
        }

        $this->data_path = ROOT_PATH . 'data/notify/';
        if(!is_dir($this->data_path)){
            mkdir($this->data_path, 0777);
        }
    }

    /* ------------------------------------------------------ */
    //列表
    /* ------------------------------------------------------ */

    public function index() {
        $status = I('get.status', 1, 'intval');
        $pay_info = I('get.pay_info', null, 'intval');
        $size = 10;
        $filter['status'] = $status;
        $filter['page'] = '{page}';
        $page_title = '卡包-';
        $where = " AND v.is_saled = 1 ";
        if($status == 1){ // 有效
            $where .= " AND v.is_act = 1 ";
            $where .= " AND v.is_used = 0 ";
            $where .= " AND v.is_don = 0 ";
            $where .= " AND v.end_date >= '" . NOW_TIME . "' ";
            $page_title .= '有效';
        }elseif($status == 2){ // 待激活
            $where .= " AND v.is_act = 0 ";
            $where .= " AND v.end_date >= '" . NOW_TIME . "' ";
            $page_title .= '待激活';
        }elseif ($status == 3) { // 转赠
            $where .= " AND v.is_don = 1 ";
            //$where .= " AND v.end_date >= '" . NOW_TIME . "' ";
            $page_title .= '已转赠';
        }elseif ($status == 4) { // 已用完
            $where .= " AND v.is_used = 1 ";
            $where .= " AND v.is_don = 0 ";
            $page_title .= '已用完';
        }elseif($status == 5) { // 已过期
            $where .= " AND v.end_date < '" . NOW_TIME . "' ";
            $where .= " AND v.is_don = 0 ";
            $where .= " AND v.is_used = 0 ";
            $page_title .= '已过期';
        }else{
            return false;
        }
        $sql = "SELECT COUNT(v.card_id) as count FROM " . 
               $this->model->pre . "virtual_card as v, " .
               $this->model->pre . "order_goods as g, " .
               $this->model->pre . "order_info as i " .
               "WHERE v.order_sn = i.order_sn AND g.order_id = i.order_id AND v.is_saled = 1 AND g.goods_id = v.goods_id AND i.user_id = '" . 
               $this->user_id . "' " . $where;
        $count = $this->model->query($sql);
        $count = $count[0]['count'];
        $limit = $this->pageLimit(url('Couponorder/index', $filter), $size);

        $sql = "SELECT v.card_id, v.card_sn, v.card_password, v.add_date, v.end_date, v.is_saled, v.is_act, v.is_used, g.goods_name as card_name, g.market_price  as card_amount, (SELECT c.cat_name FROM ". $this->model->pre . "goods as gg LEFT JOIN " . $this->model->pre . "category as c ON gg.cat_id = c.cat_id WHERE gg.goods_id = v.goods_id) as cat_name  FROM " . 
               $this->model->pre . "virtual_card as v, " .
               $this->model->pre . "order_goods as g, " .
               $this->model->pre . "order_info as i " .
               "WHERE v.order_sn = i.order_sn AND g.order_id = i.order_id AND v.is_saled = 1 AND g.goods_id = v.goods_id AND i.user_id = '" . 
               $this->user_id . "' " . $where . 
               " ORDER BY i.order_id DESC LIMIT " . $limit;
        $card_list = $this->model->query($sql);
        foreach ($card_list as $key => $value) {
            //只查询激活并未用完的卡
            if($value['is_act'] == 1 && $value['is_used'] == 0){
                $card_sn[] = $value['card_sn'];
            }
        }
        $balane_total['count'] = 0;
        $balane_total['amount'] = 0;
        if(!empty($card_sn)){
            $balance = array();
            $card_balance = model('Couponorder')->get_card_balance($card_sn);

			if ($card_balance) {
				foreach ($card_balance as $key => $value) {
					$balance[$value['card_no']] = $value['avail_amt'];
					$balane_total['amount'] += $value['avail_amt'];
				}    
                $balane_total['count'] = count($card_balance);
			}
        }
        $balane_total['amount'] = number_format($balane_total['amount'],2);
        $this->assign('page_title', $page_title);
        $this->assign('status', $status);
        $this->assign('card_list', $card_list);
        $this->assign('card_balance', $balance);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('balane_total', $balane_total);
        /* 显示模板 */
        if(IS_AJAX){
            $this->display('coupon_order_list_ajax.dwt');
        }else{
            $template = $pay_info === 1 ? 'coupon_order_info_list.dwt' : 'coupon_order_list.dwt';
            $this->display($template);
        }
    }

    /* ------------------------------------------------------ */
    //详情
    /* ------------------------------------------------------ */
    public function info() {
        $card_id = I('get.id', 0, 'intval');
        $card_info = model('Couponorder')->get_card_info($card_id);
        if(empty($card_info))
        {
            $this->alert("卡片不存在。");
        }
        $card_balance = model('Couponorder')->get_card_balance($card_info['card_sn']);
        if($card_balance == false)
        {
            $message = ECTouch::err()->last_message();
            $this->alert($message[0]);
        }        
        $card_info['card_balance'] = $card_balance[$card_info['card_sn']]['avail_amt'];
        $this->assign('page_title',$card_info['cat_name']);
        $this->assign('card_info', $card_info);
        $this->display('coupon_order_detail.dwt');
    }
    
	/* ------------------------------------------------------ */
    //付款
    /* ------------------------------------------------------ */
    public function pay() {
        $card_id = I('get.id', 0, 'intval');
        $card_info = model('Couponorder')->get_card_info($card_id);
        if(empty($card_info))
        {
            $this->alert("卡片不存在。");
        }
        $card_balance = model('Couponorder')->get_card_balance($card_info['card_sn']);
        if($card_balance == false)
        {
            $message = ECTouch::err()->last_message();
            $this->alert($message[0]);
        }   


        $time = 60*10; //时分钟有效
        $filename = $this->data_path . md5($card_info['card_sn']);
        if(is_file($filename)){   
            if((filemtime($filename)+$time) < NOW_TIME){
                @unlink($file);
            }            
        }

        $card_info['card_balance'] = $card_balance[$card_info['card_sn']]['avail_amt'];

        $code = model('Couponorder')->get_card_paycode($card_info['card_sn'], $card_info['prdtNo'], $card_info['openBrh']);
        if($code == false)
        {
            $message = ECTouch::err()->last_message();
            $this->alert($message[0]);
        } 
        $_SESSION['code'] = $code;
        //$strlen = strlen($code);
        //$part = $strlen / 3;
        $code = str_split($code, 4);
        $this->assign('page_title', '付款码');
        $this->assign('card_info', $card_info);
        $this->assign('code', implode(' ', $code));
        $this->display('coupon_order_pay.dwt');
    }

    /* ------------------------------------------------------ */
    //通知结果
    /* ------------------------------------------------------ */
    public function notify()
    {
        $data['pay_account'] = I('post.pay_account');
        $data['pay_card'] = I('post.pay_card');
        $data['pay_status'] = I('post.pay_status', 0, 'intval');
        $data['pay_time'] = I('post.pay_time');
        $data['pay_type'] = I('post.pay_type');
        if(empty($data['pay_card'])){
            $this->log('卡号不存在', json_encode($data));
            $this->jserror('卡号不存在');
        }    
        if($data['pay_status'] == 0){
            $this->log('支付失败', json_encode($data));
            $this->jserror('支付失败');
        }    
        ksort($data);
        foreach ($data as $key => $value) {
            $temp[] = $key . "=". $value;
        }
        $sign = md5($this->sign_key . implode('', $temp) . $this->sign_key);
        if($sign != I('post.sign'))
        {
            $this->log('校验码不正确', json_encode($data));
            $this->jserror('校验码不正确');
        }
        if(file_put_contents($this->data_path . md5('hemaquan.com'.$data['pay_card'].'hemaquan.com'), serialize($data))){
            $this->jssuccess('返回数据成功', '');
        }
    }
    public function ajax_notify()
    {
        $id = I('get.id');
        if(is_file($this->data_path . $id)){
            $this->jssuccess('', url('couponorder/payres', array('id'=>$id)));
        }
    }
    
	/* ------------------------------------------------------ */
    //卡券支付结果
    /* ------------------------------------------------------ */
    public function payres() {
        $filename = $this->data_path . I('get.id');
        if(is_file($filename)){
            $card_detail = file_get_contents($filename);
            $card_detail = unserialize($card_detail);
            $card_detail['pay_time'] = date_from_format('YmdHis',$card_detail['pay_time']);
            $card_detail['pay_account'] = $card_detail['pay_account'] / 100; 
            unlink($filename);
            $card_balance = model('Couponorder')->get_card_balance($data['pay_card']);
            $this->assign('page_title', '购卡成功');
            $this->assign('card_detail', $card_detail);
            $this->display('coupon_order_payres.dwt');
        }else{
            redirect(url('couponorder/index'));
        }
    }
    
	/* ------------------------------------------------------ */
    //交易明细
    /* ------------------------------------------------------ */
    public function tradetail() {
        $card_id = I('get.id', 0, 'intval');
        $card_info = model('Couponorder')->get_card_info($card_id);

        if(empty($card_info))
        {
            $this->alert("卡片不存在。");
        }
        $card_details = model('Couponorder')->get_card_details($card_info['card_sn']);

        $card_total['txn_at'] = 0;
        $card_total['count'] = 0;
        $arr = array();
        if($card_details)
        {
            foreach ($card_details as $key => $value) {
                $txn_dt = $value['txn_dt'];
                $value['txn_dt'] = date('Y-m-d H:i', $txn_dt);
                if($value['txn_type'] == 'C0016'){
                    $value['mer_nm'] = '购券';
                    $value['txn_at_type'] = sprintf('+%s', $value['txn_at']);                      
                }else{
                    $card_total['count']++;
                    $card_total['txn_at'] += $value['txn_at'];
                    $value['txn_at_type'] = sprintf('-%s', $value['txn_at']);               
                }
                $arr[date('Y', $txn_dt)][date('m', $txn_dt)][] = $value;
            }
        }


        $this->assign('page_title', '交易明细');
        $this->assign('card_total', $card_total);
        $this->assign('card_details', $arr);
        $this->display('coupon_order_tradetail.dwt');
    }
    
	/* ------------------------------------------------------ */
    //使用说明
    /* ------------------------------------------------------ */
    public function instructions() {
        $this->assign('page_title', "使用说明");
        $this->display('coupon_order_instructions.dwt');
    }
    
	/* ------------------------------------------------------ */
    //购券说明
    /* ------------------------------------------------------ */
    public function buydesc() {
        $this->assign('page_title', "购卡说明");
        $this->display('coupon_order_buydesc.dwt');
    }
    
	/* ------------------------------------------------------ */
    //使用说明
    /* ------------------------------------------------------ */
    public function tecrule() {
        $this->assign('page_title', "电子卡章程");
        $this->display('coupon_order_tecrule.dwt');
    }


	/* ------------------------------------------------------ */
    // 激活卡号
    /* ------------------------------------------------------ */
  	public function active()
  	{
  		$card_id = I('get.id', 0, 'intval');
  		$card_info = model('Couponorder')->get_card_info($card_id);
  		if(empty($card_info))
  		{
  			$this->alert("待激活卡片不存在。");
  		}
  		$card_info['custId'] = $card_info['card_sn'];
  		$card_info['amountAt'] = $card_info['card_amount'];
  		
        $end_dt = model('Couponorder')->get_card_active($card_info);
        if(empty($end_dt)){
            $this->alert("卡片激活失败。"); 
        }
  		$where['card_id'] = $card_info['card_id'];
  		$data['is_act'] = 1;
  		//$data['end_date'] = date_from_format('Ymd', $end_dt);
        if($this->model->table('virtual_card')->where($where)->data($data)->update()){
            $this->alert("卡片激活成功。", url('couponorder/index', array('status'=>2)));          
        }else{
            $this->alert("卡片激活失败。"); 
        }
  	}


    public function qrcode()
    {
        $logo = ROOT_PATH . 'logo/kailiantong.png';
        QRcode::png($_SESSION['code'], NULL, 'H',12, 2.2, file_get_contents($logo)); 
    }

    public function barcode()
    {
        $barcode = new Barcode($_SESSION['code'], '');
        $barcode->createBarCode();           
    }

    private function log($api_msg = '', $api_data =''){
        $data['api_data'] = $api_data;
        $data['api_date'] = NOW_TIME;
        $data['api_url'] = "http://weixin.hemaquan.com/index.php?m=default&c=couponorder&a=notify";
        $data['api_msg'] = $api_msg;
        $data['api_type'] = 'POST';
        $data['api_status'] = 2;
        $data['api_method'] = 'couponorder/notify';
        $this->model->table('api_log')->data($data)->insert();
    }

    private function kaquan_notify($data = array())
    {
        $keys = array_keys($data);
        $values = array_values($data);
        $sql = "REPLACE INTO {pre}kaquan_notify (`" . implode("`, `", $keys) . "`) VALUES('" . implode("', '", $values) . "')"; 
        $this->model->query($sql);       
    }

}

