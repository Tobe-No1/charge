<?php
header("Content-Type:text/html; charset=utf-8");
require_once("lib/itppay.class.php");


require 'common.php';
require 'mysql.php'; 

$notify_url = ADMIN_URL . '/callback_h5.php';
$call_url = ADMIN_URL . '/callback_view.php';

/* *
 * 配置信息hhhhhhh
 */

$itppay_config["appid"]="0000001245";//交易发起所属app
$itppay_config["key"]="190630049acf1af3d1401b90e8bfcfe2";//合作密钥


$product_id = isset($_REQUEST['product_id']) ? intval(trim($_REQUEST['product_id'])) : 0;
$uid = isset($_REQUEST['uid']) ? intval(trim($_REQUEST['uid'])) : 0;
if ($uid == 0) {
	$uid = 1;
   // die(json_encode(array('errno' => 1, 'msg' => 'create order error')));
}

$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);

$sql = "select * from products where id = ".$product_id;
$product = $db->get_one($sql);
if (empty($product)) {
	$product['amount'] = '1000';
	$product['name'] = '购买房卡10张，额外赠送4张'; 
}
$amount = $product['amount'];
$name = $product['name'];

$charge_data = array(
    'charge_id' => "cp",
    'amount' => $amount,
    'clientIp' => get_client_ip(),
    'payChannelId' => 'h5',
    'uid' => $uid,
    'product_id' => $product_id,
    'create_time' => time(),
    'status' => 0,
);

$ret = $db->insert_new('charge', $charge_data);
if (!$ret) {
	$result = json_encode(json_encode(array('errno' => 3, 'msg' => 'create order error')));
	write_log_file($result);
    die($result);
}

$id = $db->insert_id();
$charge_id = $itppay_config["appid"]."-".$id;
$update_data = array(
    'charge_id'    => $charge_id,
);
$db->update_new('charge',"id=".$id, $update_data);



/* *
 * 请求参数，参数须按字符升序排列
 */
$parameter=array(
	"amount"         =>$amount,//[必填]订单总金额，单位(分)
	"appid"          =>$itppay_config["appid"],//[必填]//交易发起所属app
	"body"           =>$name,//[必填]商品描述
	"clientIp"       =>get_client_ip(),//[必填]客户端IP
	"cpChannel"      =>"",//CP分发渠道
	"currency"       =>"",//币种，默认RMB
	"description"    =>"",//订单附加描述
	"expireMs"       =>"",//过期时间毫秒数，默认24小时过期
	"extra"          =>"",//附加数据，以键值对形式存放，例如{"key":"value"}
	"mchntOrderNo"   =>$charge_id,//[必填]商户订单号，必须唯一性
	"notifyUrl"      =>$notify_url,//[必填]订单支付结果异步通知地址，用于接收订单支付结果通知，必须以http开头
	"payChannelId"   =>"0000000007", //支付渠道id，无收银台传此参数
	"returnUrl"      =>$call_url,//[必填]订单支付结果同步跳转地址，用于同步跳转到商户页面，必须以http开头
	"subject"        => $name,//[必填]商品名称
	"version"        =>"h5_NoEncrypt",//接口版本号，值为h5_NoEncrypt时,则明天平台返回商户参数时，不进行RSA加密
);

/* *
 * 建立请求
 */
var_dump($_SERVER);
$itpPay = new itpPay($itppay_config);
//$orderInfo=$itpPay->getOrderInfo($parameter);
$orderInfo=$itpPay->setPostValue($parameter);
$html=$itpPay->RequestForm($orderInfo, "跳转中...");
echo $html;

?>
