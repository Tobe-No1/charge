<?php
//require 'vendor/autoload.php';
require 'vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
require 'vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
//require 'vendor/phpoffice/phpexcel/Classes/PHPExcel/Writer/Excel2007.php';
require 'common.php';
require 'mysql.php';

//use PHPExcel_IOFactory;
//use PHPExcel;

//db //$dbhost, $dbport, $dbuser, $dbpw, $dbname
$game_db = new Db_class($db_config['db_host'], $db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], $db_config['db_name']);

$gm_db = new Db_class($gm_config['db_host'], $gm_config['db_port'], $gm_config['db_user'], $gm_config['db_pwd'], $gm_config['db_name']);

$start_date = date('Y-m-d', time() - 86400);
if(!empty($argv[1])){
    $start_date = $argv[1];
}
//var_dump($start_date);

/*$start_time = strtotime($start_date);
$end_time = $start_time + 86400;*/
$start_time = strtotime('2016-12-31');
$end_time = strtotime('2017-12-31');

$data = array();
//累计2017-1-1之前的用户数
$sql = "select count(*) as user_base_count from user where channel != 'nn_robot' and create_time <= ".$start_time;
$info = $game_db->get_one($sql);
$base_count = $info['user_base_count'];
//echo $base_count;
//1 2、累计2017年每月注册用户数（user_total_count）和月新增注册数（month_reg_count）
$sql = "select count(*) as user_total_count,FROM_UNIXTIME(create_time,'%m') as m from user where channel != 'nn_robot' and create_time <=".$end_time." and create_time >" .$start_time ." group by FROM_UNIXTIME(create_time,'%Y-%m')";
$info = $game_db->get_all($sql);
//$info1=array();//每月注册用户
$info1 = data_format($info,'user_total_count');
//var_dump($info1);
for($i=1;$i<=12;$i++){
    if(array_key_exists($i,$info1)){
        $data['user_total_count'][$i] = $info1[$i]+$base_count;
        $base_count = $data['user_total_count'][$i];
        //每月新增用户加入data
        $data['month_reg_count'][$i] = $info1[$i];
    }else{
        $data['user_total_count'][$i] = $base_count;
        $data['month_reg_count'][$i] = 0;
    }
}
//3、月在线峰值
for($i=1;$i<=12;$i++){
    $data['online_max'][$i] = 0;
}
//4、累计2017年每月活跃用户数
$sql = "select count(distinct a.uid) as online_count,from_unixtime(a.login_time,'%m') as m from log_login as a left join  user as b on a.uid = b.uid where b.channel != 'nn_robot' and a.login_time >= ".$start_time." and a.login_time <= ".$end_time." group by FROM_UNIXTIME(a.login_time,'%Y-%m')";
$info = $game_db->get_all($sql);
$info1=data_format($info,'online_count');

//data_month($data,$info1,"online_count");
for($i=1;$i<=12;$i++){
    if(array_key_exists($i,$info1)){
        $data['online_count'][$i] = (int)$info1[$i];
    }else{
        $data['online_count'][$i] = 0;
    }
}
//5、累计2017年每月付费用户数
$sql = "select count(distinct a.uid) as charge_count,from_unixtime(a.pay_time,'%m') as m from charge as a left join  user as b on a.uid = b.uid where b.channel != 'nn_robot' and a.pay_time >= ".$start_time." and a.pay_time <= ".$end_time." group by FROM_UNIXTIME(a.pay_time,'%Y-%m')";
$info = $game_db->get_all($sql);
$info1=data_format($info,'charge_count');
//var_dump($info1);
for($i=1;$i<=12;$i++){
    if(array_key_exists($i,$info1)){
        $data['charge_count'][$i] = (int)$info1[$i];
    }else{
        $data['charge_count'][$i] = 0;
    }
}
//6、活跃付费率
for($i=1;$i<=12;$i++){
    if($data['online_count'][$i]!=0){
        $data['online_percent'][$i] = $data['charge_count'][$i]/$data['online_count'][$i];
    }else{
        $data['online_percent'][$i]=null;
    }

}

//7、每月充值量
$sql = "select sum(amount) as charge_amount,from_unixtime(pay_time,'%m') as m from charge where status = 1 and pay_time >= ".$start_time." and pay_time <= ".$end_time." group by FROM_UNIXTIME(pay_time,'%Y-%m')";
$info = $game_db->get_all($sql);
$info1=data_format($info,'charge_amount');
for($i=1;$i<=12;$i++){
    if(array_key_exists($i,$info1)){
        $data['charge_amount'][$i] = (int)$info1[$i]/100;
    }else{
        $data['charge_amount'][$i] = 0;
    }
}
//8、月平均付费
for($i=1;$i<=12;$i++){
    if($data['user_total_count'][$i]!=0){
        $data['month_average_fee'][$i] = $data['charge_amount'][$i]/$data['user_total_count'][$i];
    }else{
        $data['month_average_fee'][$i]=null;
    }

}

//9、月平均活跃付费
for($i=1;$i<=12;$i++){
    if($data['online_count'][$i]!=0){
        $data['online_month_average_fee'][$i] = $data['charge_amount'][$i]/$data['online_count'][$i];
    }else{
        $data['online_month_average_fee'][$i]=null;
    }

}
//横向标题
$cols=['月份','累计注册用户数(NU)','月新增注册用户数（MNU)','月在线用户峰值','月活跃人数（MAU)','月付费人数
','活跃付费率','付费额（月）','ARPU(月平均用户付费）','ARPPU(月平均活跃用户付费）','次日留存','7日留存','14日留存','30日留存'];
//excel表格是A1.B2来定位的，用该数组来配合数组下标
$rows=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

$new_data=array();
foreach($data as $v){
    $new_data[]=$v;
}

//var_dump($data);
ob_clean();//清除缓存
//$path = dirname(__FILE__); //找到当前脚本所在路径
$PHPExcel = new PHPExcel();
$PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
$PHPSheet->setTitle('2017年1-12月主要运营数据'); //给当前活动sheet设置名称
for($i=1;$i<=count($cols);$i++){
    switch ($i){
        case $i:$PHPSheet->setCellValue($rows[$i-1].'1',$cols[$i-1]);
    }
}
for($i=2;$i<=13;$i++){
    $PHPSheet->setCellValue('A'.$i,($i-1).'月');
    if($i<count($new_data)+2){
        for($j=0;$j<count($new_data[$i-2]);$j++){
            $PHPSheet->setCellValue($rows[($i-1)].($j+2),$new_data[$i-2][($j+1)]);
        }
    }
}
$PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');//按照指定格式生成Excel文件，‘Excel2007’表示生成2007版本的xlsx，‘Excel5’表示生成2003版本Excel文件
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
//header('Content-Type:application/vnd.ms-excel');//告诉浏览器将要输出Excel03版本文件
header('Content-Disposition: attachment;filename="01simple.xlsx"');//告诉浏览器输出浏览器名称
header('Cache-Control: max-age=0');//禁止缓存
$PHPWriter->save("php://output");



