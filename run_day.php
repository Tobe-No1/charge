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

$start_time = strtotime($start_date);
$end_time = $start_time + 86400;
$base_time = strtotime('2016-12-31');
$end_time = strtotime('2017-12-31');

$data = array();

//总用户数
$sql = "select count(*) as user_total_count from user where channel != 'nn_robot' and create_time <= ".$end_time;
$info = $game_db->get_one($sql);
$data['user_total_count'] = $info['user_total_count'];

#当前注册
$sql = "select count(*) as user_reg_count from user where channel != 'nn_robot' and create_time >= ".$start_time." and create_time <= ".$end_time;
$info = $game_db->get_one($sql);
$data['user_reg_count'] = $info['user_reg_count'];

#当日活跃
$sql = "select count(distinct a.uid) as online_count from log_login as a left join  user as b on a.uid = b.uid where b.channel != 'nn_robot' and a.login_time >= ".$start_time." and a.login_time <= ".$end_time;
$info = $game_db->get_one($sql);
$data['online_count'] = $info['online_count'];

#充值
$sql = "select sum(amount) as charge_count from charge where status = 1 and pay_time >= ".$start_time." and pay_time <= ".$end_time;
$info = $game_db->get_one($sql);
$data['charge_count'] = intval($info['charge_count']/100);

#消耗
$sql = "select sum(num) as cost_count from card_log where change_type = 0 and (type = 1 or type = 6) and create_time >= ".$start_time." and create_time <= ".$end_time;
$info = $game_db->get_one($sql);
$data['cost_count'] = $info['cost_count'];

$sql = "select sum(count) as cost_count from clubs_card_log where opt = 2  and create_time >= ".$start_time." and create_time <= ".$end_time;
$info = $game_db->get_one($sql);
$data['cost_count'] += $info['cost_count'];

$tmp = $gm_db->get_one(sprintf("select * from mg_base_statistic where create_time = '%s'",$start_date));
if(empty($tmp)) {
    $data['create_time'] = $start_date;
    $gm_db->insert_new('mg_base_statistic', $data);
}else{
    $gm_db->update_new('mg_base_statistic', "create_time = '".$start_date."'", $data);
}
var_dump($data);
/*ob_clean();//清除缓存,没有这个操作可能会出现乱码，excel打开错误
//$path = dirname(__FILE__); //找到当前脚本所在路径
$PHPExcel = new PHPExcel();
$PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
$PHPSheet->setTitle('demo'); //给当前活动sheet设置名称
$PHPSheet->setCellValue('A1','姓名')->setCellValue('B1','分数');//给当前活动sheet填充数据，数据填充是按顺序一行一行填充的，假如想给A1留空，可以直接setCellValue(‘A1’,’’);
$PHPSheet->setCellValue('A2','张三')->setCellValue('B2','50');
$PHPSheet->setCellValue('A3','张三')->setCellValue('B3','50');
$PHPSheet->setCellValue('A4','张三')->setCellValue('B4','50');
$PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');//按照指定格式生成Excel文件，‘Excel2007’表示生成2007版本的xlsx，‘Excel5’表示生成2003版本Excel文件
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
//header('Content-Type:application/vnd.ms-excel');//告诉浏览器将要输出Excel03版本文件
header('Content-Disposition: attachment;filename="01simple.xlsx"');//告诉浏览器输出浏览器名称
header('Cache-Control: max-age=0');//禁止缓存
$PHPWriter->save("php://output");*/



