<?php

require 'common.php';
require 'mysql.php';
$app_id = '3555';

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
    'charge_id' => "cp",
    'amount' => $amount,
    'clientIp' => get_client_ip(),
    'payChannelId' => 'zzf_wechat',
    'uid' => $uid,
    'product_id' => $product_id,
    'create_time' => time(),
    'status' => 0,
);

$ret = $db->insert_new('charge', $charge_data);
if (!$ret) {
    die(json_encode(array('errno' => 3, 'msg' => 'create order error')));
}

$id = $db->insert_id();
$charge_id = $app_id."-".$id;
$update_data = array(
    'charge_id'    => $charge_id,
);
$db->update_new('charge',"id=".$id, $update_data);



$params = array(
    'partnerId' => '1000100020001077',
    'money'   	=> $amount,
    'appId'     => AppId,
    'qn'        => 'zyap2887_55419_100',
    'currency'  => '1000200010000000',
    'cpparam'   => $charge_id,
    'notifyUrl' => NOTIFY_URL2,
    'appFeeName' => $name,
);
$params['errno'] = 0;
$params['sign'] = AppKey;
write_log_file($params);
echo json_encode($params);
