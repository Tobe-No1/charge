<?php

require 'common.php';
require 'mysql.php';
require 'include/ipay.php';

$sdk_notify_url = 'http://charge.sudagame.com:8080/callback_ipay.php';


write_log_file($_POST);

$product_id = isset($_REQUEST['product_id']) ? intval(trim($_REQUEST['product_id'])) : 0;
$uid = isset($_REQUEST['uid']) ? intval(trim($_REQUEST['uid'])) : 0;
if ($uid == 0) {
    die(json_encode(array('errno' => 1, 'msg' => 'create order error')));
}

$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);

$sql = "select * from products where id = {$product_id}";
$product = $db->get_one($sql);
if (empty($product)) {
    die(json_encode(array('errno' => 2, 'msg' => 'product_id error')));
}
$amount = $product['amount'];
$name = $product['name'];


$charge_data = array(
    'amount' => $amount,
    'clientIp' => get_client_ip(),
    'payChannelId' => 'ipay',
    'uid' => $uid,
    'product_id' => $product_id,
    'create_time' => time(),
    'status' => 0,
);

$ret = $db->insert_new('charge', $charge_data);
if (!$ret) {
    die(json_encode(array('errno' => 3, 'msg' => 'create order error')));
}

$charge_id = $db->insert_id();

$pay_obejct = new ipay();
$result = $pay_obejct->trade($charge_id, $amount, $uid, $sdk_notify_url, $product_id, '');
if ($result['state']) {
    //提取 支付订单号.
    $trans_id = $result['transid'];
    $return = array(
        'trans_id' => $trans_id,
        'errno' => 0,
    );
    write_log_file($result);
} else {
    $return = array('errno' => 3, 'msg' => 'ipay error');
}
write_log_file($result);
echo json_encode($return);
