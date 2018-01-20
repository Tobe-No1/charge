<?php

require 'common.php';
require 'mysql.php';
require 'include/wxpay.php';

write_log_file($_REQUEST);

$product_id = isset($_REQUEST['product_id']) ? intval(trim($_REQUEST['product_id'])) : 0;
$uid = isset($_REQUEST['uid']) ? intval(trim($_REQUEST['uid'])) : 0;
//$client_ip = isset($_REQUEST['ip']) ? trim($_REQUEST['ip']) : '127.0.0.1';
//获取真实$client_ip
$client_ip = get_client_ip();
//echo $client_ip;
if ($uid == 0) {
    die(json_encode(array('errno' => 1, 'msg' => 'create order error')));
}

$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);

$sql = "select * from products where id = {$product_id}";
$product =$db->get_one($sql);
if (empty($product)) {
    die(json_encode(array('errno' => 2, 'msg' => 'product_id error')));
}
$amount = $product['amount'];
$name = $product['name'];


$charge_data = array(
    'charge_id' => "cp",
    'amount'    => $amount,
    'clientIp' => $client_ip,
    'payChannelId' => 'h5wxpay',
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
$charge_id = 'sys'.$id;
$update_data = array(
    'charge_id'    => $charge_id,
);
$db->update_new('charge',"id=".$id, $update_data);
//微信支付
$alipay = new wxpay($os);
$result = $alipay->trade($charge_id, $amount, H5wxpay_NOTIFY_URL, H5wxpay_RETURN_URL, $client_ip);
echo $alipay->config['api_key'];
//var_dump($result);
if($result['state'] != 1){
    die(json_encode(array('errno' => 4, 'msg' => 'wxpay trade error')));
}
$return = array(
    'pay_info' => $result['pay_url'],
    'errno' => 0,
);

write_log_file($return);
$html="<script>
	var pay_info = '".$return['pay_info']."';
	setTimeout(function(){
		window.location.href=pay_info;
	},1000);
	</script>
	<span>支付完成！</span>
	<a href='./return_wxpay.php？id=".$id."'>点击查看订单</a>
	";

