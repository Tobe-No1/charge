<?php

$result = isset($_GET['result'])?trim($_GET['result']):0;
if($result==1) {
    echo '支付成功,请回游戏查收！';
}else{
    echo '支付失败！';
}