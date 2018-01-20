<?php

require 'common.php';
require 'mysql.php';

$app_id = 'apple';
$receiptdata = $_POST['transaction_id'];
$uid = $_POST['uid'];
write_log_file($_POST);

$products = array(
    'com.soda.jtx.1' => array('id'=>1,'amount'=>600,'card'=>6,'card_add'=>0,'mtype'=>1),
    'com.soda.jtx.2' => array('id'=>2,'amount'=>1200,'card'=>12,'card_add'=>0,'mtype'=>1),
    'com.soda.jtx.3' => array('id'=>3,'amount'=>1800,'card'=>18,'card_add'=>0,'mtype'=>1),
    'com.soda.jtx.4' => array('id'=>4,'amount'=>2500,'card'=>25,'card_add'=>0,'mtype'=>1),

);

//db //$dbhost, $dbport, $dbuser, $dbpw, $dbname
$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);

$user = $db->get_one(sprintf("select * from user where uid = %d",$uid));
if(empty($user)) {
    $result = array('errno'=>1,'msg'=>'not find account');
    write_log_file($result);
    die(json_encode($result));
}

$appleURL = "https://buy.itunes.apple.com/verifyReceipt";   //正式
$receiptJson = json_encode(array("receipt-data" => $receiptdata));
$options = array(
    'http' => array(
        'method' => 'POST',
        'header' => 'Content-type:application/x-www-form-urlencoded',
        'content' => $receiptJson,
        'timeout' => 50, // 超时时间（单位:s）  
    )
);
$context = stream_context_create($options);
$response_json = file_get_contents($appleURL, false, $context);
$response = json_decode($response_json, true);
$status = $response['status'];
if ($status == 21007) {
    $appleURL = "https://sandbox.itunes.apple.com/verifyReceipt";   //正式
    $receiptJson = json_encode(array("receipt-data" => $receiptdata));
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $receiptJson,
            'timeout' => 50, // 超时时间（单位:s）  
        )
    );
    $context = stream_context_create($options);
    $response_json = file_get_contents($appleURL, false, $context);
    $response = json_decode($response_json, true);
    $status = $response['status'];
}

if ($status != 0) {
    $result = array('errno'=>2,'msg'=>$status);
    write_log_file($result);
    die(json_encode($result));
}
$pay_info = $response['receipt']['in_app'][0];


$product = $products[$pay_info['product_id']];

$charge_data = array(
    'charge_id' => "cp",
    'amount'        => $product['amount'],
    'clientIp'      => get_client_ip(),
    'payChannelId'  => 'iap',
    'uid'           => $uid,
    'product_id'    => $product['id'],
    'os'            => 'ios',
    'create_time'   => time(),
    'status'        => 1,
    'orderNo'       => $pay_info['transaction_id'],
);
    
$ret = $db->insert_new('charge', $charge_data);
if(!$ret) {
    die(json_encode(array('errno'=>3,'msg'=>'create order error')));
}

$id = $db->insert_id();
$charge_id = $app_id."-".$id;
$update_data = array(
    'charge_id'    => $charge_id,
);
$db->update_new('charge',"id=".$id, $update_data);

if ($product['mtype'] == 1) {
//添加房卡
game_addorder($charge_id, $uid, $user['account'], $product['id'],$product['card'], $product['card_add']);
}else{
//添加房卡
game_addGoldorder($charge_id, $uid, $user['account'], $product['id'],$product['card'], $product['card_add']); 
}

$result = array('errno'=>0,'msg'=>'success');
write_log_file($result);
die(json_encode($result));
