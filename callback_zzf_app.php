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

$appid = $data['app_id'];
if(empty($appid)) {
    $appid = $data['appId'];
    if(empty($appid)) {
        write_callback_file("zzf-pay",'no app id');
        die('1');   
    }   
}

$key = '';
if($appid == 3555 ){
	$key = '05485AE88C1C4B8690B75E57483F8A20';
}
else if($appid == 3301){
	$key = '92EA19177E214CAEA22BB7B05A45A890';
}
else if($appid == 4142){
	$key = '27D49C391DFA43D9BCBC3A32EE2EE56D';
}

$cporderid = $data['out_trade_no'];
$paidfee = $data['money'];
$orderid = $data['invoice_no'];
$str = "app_id=".$appid."&code=0"."&invoice_no=".$data['invoice_no'] ."&money=".$data['money'] ."&out_trade_no=".$data['out_trade_no'] ."&pay_way=". $data['pay_way']."&qn=".$data['qn'] ."&up_invoice_no=" .$data['up_invoice_no']."&key=".$key;
write_callback_file("zzf-pay",$str);
$gen_str = strtoupper(md5($str));
if($data['sign'] != $gen_str){
	write_callback_file("zzf-pay",'error sign');
	die('1');
}
//db //$dbhost, $dbport, $dbuser, $dbpw, $dbname
$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);
$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);
$need_filters = array();

//查询订单
$charge_info = $db->get_one("select * from charge where charge_id = '{$cporderid}'");
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
if($charge_info['amount'] > $paidfee) {
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
    'status'    => 1,
    'pay_time'  => time(),
    'orderNo'   => $orderid,
    'appid'     => $cp,
);

$db->update_new('charge', "charge_id='{$charge_info['charge_id']}'", $update_data);

write_callback_file("zzf-pay",'success');

die('0');
