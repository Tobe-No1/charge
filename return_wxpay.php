<?php
require 'common.php';
require 'mysql.php';
require 'include/wxpay.php';
$charge_id = $_GET['id'];

$db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);
//$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);
$sql = "select * from charge where id= ".$charge_id;
$res = $db->get_one($sql);
if($res['status']=="1"){
    //处理业务开始
    echo "</br>支付成功!</br></br>";
    echo "用户编号：".$res['uid']."</br>";
    echo "产品编号：".$res['product_id']."</br>";
    echo "商户系统订单号：".$res['charge_id']."</br>";
    echo "微信系统订单号：".$res['orderNo']."</br>";
    echo "订单金额：".round($res['amount']/100,2)."</br>";
    echo "支付时间：".date('Y-m-d H:m:s',$res['create_time'])."</br>";
    echo "用户IP：".$res['clientIp']."</br>";
}else{
    "支付失败";
}