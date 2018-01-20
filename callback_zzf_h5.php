<?php

require 'common.php';
require 'mysql.php';

$cp = '1000100020001077';
$data = $_REQUEST;
write_callback_file("zzf-pay",$data);

//验证签名
if(empty($data)) {
    write_callback_file("zzf-pay",'error empty data');
    die('1');
}

$keys = array(
    '4142' => '27D49C391DFA43D9BCBC3A32EE2EE56D',
);

$app_id = $data['app_id'];
$code = $data['code'];
$invoice_no = $data['invoice_no'];
$money = $data['money'];
$out_trade_no = $data['out_trade_no'];
$pay_way = $data['pay_way'];
$qn = $data['qn'];
$up_invoice_no = $data['up_invoice_no'];
$sign = $data['sign'];
$app_secret_key = $keys[$app_id];
//{"app_id":"3412","code":"0","invoice_no":"91a95e94852e4b67848295948261d5a5","money":"20","out_trade_no":"50000973","pay_way":"1","qn":"sdk_v6.0.00","up_invoice_no":"8022017070715244543839653","sign":"AE25CFD44E6F15D22FA2EB6BDE1327F5"}
$str = "app_id={$app_id}&code={$code}&invoice_no={$invoice_no}&money={$money}&out_trade_no={$out_trade_no}&pay_way={$pay_way}&qn={$qn}&up_invoice_no={$up_invoice_no}&key={$app_secret_key}";
$gen_str = strtoupper(md5($str));
if($sign != $gen_str){
    write_callback_file("zzf-pay",'error sign');
    die('1');
}

//db //$dbhost, $dbport, $dbuser, $dbpw, $dbname
$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);
$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);
$need_filters = array();

//查询订单
$charge_info = $db->get_one("select * from charge where charge_id = '{$out_trade_no}'");
if(empty($charge_info)) {
    write_callback_file("zzf-pay",'not find order');
    die('1');
}

//是否已经发货
if($charge_info['status'] == 1) {
    write_callback_file("zzf-pay",'repeat order');
    die('0');
}

//金额
if($charge_info['amount'] > $data['money']) {
    write_callback_file("zzf-pay",'errot amount');
    die('1');
}

$user = $db->get_one(sprintf("select * from user where uid = %d",$charge_info['uid']));
if(empty($user)) {
    write_callback_file("zzf-pay",'not find account');
     die('1');
}

$sql = "select * from products where status = 1 and id = ".$charge_info['product_id'];
$product = $db->get_one($sql);

//添加房卡
$result = game_addorder($charge_info['charge_id'], $charge_info['uid'], $user['account'], $charge_info['product_id'], $product['card'], $product['card_add']);
var_dump($result);

$update_data = array(
    'status'    => $status,
    'pay_time'  => time(),
    'orderNo'   => $invoice_no,
    'appid'     => $app_id,
);

$db->update_new('charge', "charge_id='{$charge_info['charge_id']}'", $update_data);

write_callback_file("zzf-pay",'success');

die('0');