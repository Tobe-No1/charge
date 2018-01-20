<?php

require 'common.php';
require 'mysql.php';

write_log_file($_GET);

$app_id = 4142 ;
$base_url = 'http://pay.csl2016.cn:8000/createOrder.e?';
$callback = 'iOSTABTkabxi://';
$partner_id = '1000100020001077';
$payway = 10;

$sign_keys = array(
	  '4142' => '27D49C391DFA43D9BCBC3A32EE2EE56D',
);


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
$charge_id = 'sys'.$app_id."-".$id;
$update_data = array(
    'charge_id'    => $charge_id,
);
$db->update_new('charge',"id=".$id, $update_data);



 
//掌支付
$arr = array(
	'partner_id'    => $partner_id,
	'app_id'        => $app_id,
	'wap_type'      => 1,
	'money'         => $amount,
	'out_trade_no'  => $charge_id,
	'qn'            => 'soda',
	'subject'       => urlencode($name),
	'return_url'    => urlencode($callback)
);
$app_key = $sign_keys[$app_id];
$sign = gen_sign($arr, $app_key);
$arr['sign'] = strtoupper($sign);

$return = array(
	'msg'       => 'success',
	'pay_info'  => 'http://pay.csl2016.cn:8000/createOrder.e?'.createLinkstring($arr),
	'payway'    => $payway,
	'errno'     => 0,
);
    

write_log_file($return);
echo json_encode($return);

