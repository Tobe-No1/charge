<?php

require 'common.php';
require 'mysql.php';
require 'include/ipay.php';

$data = $_REQUEST;
write_callback_file("ipay-pay", $data);

/** @var \pay\ipay $pay_object */
$pay_object = new ipay();

$outer_order_data = $pay_object->callback($data);
if (!$outer_order_data) {
    write_callback_file("ipay-pay", 'callback error');
    die();
}

$cporderid = $outer_order_data['order_id'];
$rmb = intval($outer_order_data['rmb'] * 100);
$orderid = $outer_order_data['transid'];
$appid = $outer_order_data['appid'];

//db //$dbhost, $dbport, $dbuser, $dbpw, $dbname
$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);
$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);
$need_filters = array();

//查询订单
$charge_info = $db->get_one("select * from charge where charge_id = '{$cporderid}'");
if (empty($charge_info)) {
    write_callback_file("ipay-pay", 'not find order');
    die('failure');
}

//是否已经发货
if ($charge_info['status'] == 1) {
    write_callback_file("ipay-pay", 'repeat order');
    die('success');
}

//金额
if ($charge_info['amount'] > $rmb) {
    write_callback_file("ipay-pay", 'errot amount');
    die('failure');
}

$user = $db->get_one(sprintf("select * from user where uid = %d", $charge_info['uid']));
if (empty($user)) {
    write_callback_file("ipay-pay", 'not find account');
    die('failure');
}

$sql = "select * from products where status = 1 and id = " . $charge_info['product_id'];
$product = $db->get_one($sql);

//添加房卡
$result = game_addorder($charge_info['charge_id'], $charge_info['uid'], $user['account'], $charge_info['product_id'], $product['card'], $product['card_add']);

$update_data = array(
    'status'    => 1,
    'pay_time' => time(),
    'orderNo' => $orderid,
    'appid' => $appid,
);

$db->update_new('charge', "charge_id='{$charge_info['charge_id']}'", $update_data);
write_callback_file("ipay-pay", 'success');
die('success');
