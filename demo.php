<?php
/**
 * Created by PhpStorm.
 * User: hada
 * Date: 2018-01-19
 * Time: 14:45
 */
require 'common.php';
require 'mysql.php';
require 'include/wxpay.php';
$os = array();
$alipay = new wxpay($os);
echo $alipay->signverify();