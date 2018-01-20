<?php

require 'common.php';
require 'mysql.php';  
 
// $respose = $_REQUEST;

$respose = file_get_contents("php://input");
write_callback_file('h5',$respose); 

$data = json_decode($respose,true);
// $data = $respose;

//0:待支付,1:支付中,2:支付成功,3:支付失败，4：已关闭
if($data['paySt'] != 2){
    echo '{"success":"true"}';
    exit;
}

$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);
$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);

//查询订单
$cpOrderId = $data['mchntOrderNo'];

$charge_info = $db->get_one("select * from charge where charge_id = '{$cpOrderId}'");
if (empty($charge_info)) {
    write_callback_file('h5','not find order'); 
    echo '{"success":"true"}';exit; 
}

//是否已经发货
if ($charge_info['status'] == 1) { 
    write_callback_file('h5','repeat order');
    echo '{"success":"true"}';exit; 
}

//金额
if ($charge_info['amount'] > $data['amount']) {
    write_callback_file('h5','errot amount');
    die(sprintf($fail_result, '金额错误'));
}

$user = $db->get_one(sprintf("select * from user where uid = %d", $charge_info['uid']));
if (empty($user)) {
    write_callback_file('h5','not find account');
    die(sprintf($fail_result, '错误玩家id'));
}

$sql = "select * from products where id = ".$charge_info['product_id'];
$product = $db->get_one($sql);

//添加房卡
game_addorder($charge_info['charge_id'], $charge_info['uid'], $user['account'], $charge_info['product_id'], $product['card'], $product['card_add']);


$update_data = array(
    'status' => 1,
    'pay_time' => time(),
    'orderNo' => $data['orderNo'],
//    'appid' => $data['appid'],
);

$db->update_new('charge', "charge_id='{$charge_info['charge_id']}'", $update_data);
 write_callback_file('h5','success');
echo '{"success":"true"}';