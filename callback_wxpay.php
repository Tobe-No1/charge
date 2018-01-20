<?php

require 'common.php';
require 'mysql.php';
require 'include/wxpay.php';

$need_filters = array();
if($respose = file_get_contents("php://input")){
	write_callback_file('wxpay',$respose);
	$data = (array) simplexml_load_string($respose, 'SimpleXMLElement', LIBXML_NOCDATA);
}
$success_result = '<xml><return_code>SUCCESS</return_code><return_msg>成功</return_msg></xml>';
$fail_result = '<xml><return_code>FAIL</return_code><return_msg>%s</return_msg></xml>';
$now = time();

//验证签名
if (empty($data)) {
    write_callback_file('wxpay','error empty data');
    die(sprintf($fail_result, '数据为空'));
}

if ($data['return_code'] != 'SUCCESS') {
    write_callback_file('wxpay'.'network error');
    die();
}

$pay_object = new wxpay($data);
$check_sign = $pay_object->signverify($data);
if (!$check_sign) {
    write_callback_file('wxpay','sign error');
    die(sprintf($fail_result, '签名失败'));
}
    write_callback_file('wxpay','sign success');
//db //$dbhost, $dbport, $dbuser, $dbpw, $dbname
$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);
$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);

//查询订单
$cpOrderId = $data['out_trade_no'];
$sql="select * from charge where charge_id='".$cpOrderId."'";
$charge_info = $db->get_one($sql);
if (empty($charge_info)) {
    write_callback_file('wxpay','not find order');
    die(sprintf($fail_result, '错误玩家id'));
}

//是否已经发货
if ($charge_info['status'] == 1) {
    write_callback_file('wxpay','repeat order');
    die($success_result);
}

//金额
write_callback_file('wxpay',$charge_info['amount'].'--'.$data['total_fee']);
if ($charge_info['amount'] > $data['total_fee']) {
    write_callback_file('wxpay','errot amount');
    die(sprintf($fail_result, '金额错误'));
}

$user = $db->get_one(sprintf("select * from user where uid = %d", $charge_info['uid']));
if (empty($user)) {
    write_callback_file('wxpay','not find account');
    die(sprintf($fail_result, '错误玩家id'));
}

$sql = "select * from products where id = ".$charge_info['product_id'];
$product = $db->get_one($sql);

//添加房卡
game_addorder($charge_info['charge_id'], $charge_info['uid'], $user['account'], $charge_info['product_id'], $product['card'], $product['card_add']);

$status = 1;
$update_data = array(
    'status' => $status,
    'pay_time' => $now,
    'orderNo' => $data['transaction_id'],
);

$res = $db->update_new('charge', "charge_id='" . $charge_info['charge_id']."'", $update_data);
if($res){
	write_callback_file('wxpay','success');
	die($success_result);
}else{
	write_callback_file('wxpay','update order fail');
	die(sprintf($fail_result,'更新订单失败'));
}
