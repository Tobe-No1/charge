<?php

define('AppId', '2887');
define('AppKey', '18C19DC8B217482C8B7939811221EA96');
define('AppCP', 'xx');
define('NOTIFY_URL', 'xx');
define('NOTIFY_URL2', 'xx');

define('BASE_URL', 'http://wpay.sudagame.com');

define('H5wxpay_NOTIFY_URL', BASE_URL.'/callback_wxpay.php');
define('H5wxpay_RETURN_URL', BASE_URL.'/return_wxpay.php');
//define('H5wxpay_RETURN_URL', BASE_URL.'/wechat.php');

define('GMIP1', '127.0.0.1');
define('GMPort1', 8888);
//define('GMIP2', '10.27.113.107');
//define('GMPort2', 8888);

define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIR', dirname(__FILE__));
define('LOG_PATH', BASE_DIR . DS . 'log' . DS);

date_default_timezone_set('Asia/Shanghai');

$db_config = array(
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_user' => 'root',
    'db_pwd' => '',
    'db_name' => 'niuniu',
);

$gm_config = array(
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_user' => 'root',
    'db_pwd' => '',
    'db_name' => 'niuniu_db',
);



/* -----------------------------------------------function ------------------------------------------------------------- */

function send_socket_cmd($arr, $ip, $port, $typ) {
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 3, 'usec' => 0));
    socket_connect($socket, $ip, $port);
    $str = $typ . json_encode($arr);
    $size = strlen($str);
    $binary_str = pack("na" . $size, $size, $str);
    socket_write($socket, $binary_str, strlen($binary_str));
    $buf = socket_read($socket, 2048);
    $a = json_decode(substr($buf, 2), true);
    socket_close($socket);
    return $a;
}

function game_addorder($order_id, $uid, $account, $product_id, $card, $card_add) {
    $key = 'f42afdb92e2e66c24dfb9';
    $arr = array(
        'cmd'       => 18,
        'uid'       => $uid,
        'account'   => $account,
        'plat' => 'client',
        'order_id' => $order_id,
        'product_id' => $product_id,
        'card' => intval($card),
        'card_add' => intval($card_add),
    );

    $arr['sign'] = gen_sign($arr, $key);

    //验证
    //$check1 = send_socket_cmd(array('cmd'=>35,'account'=>$account), GMIP1, GMPort1);
    //if($check1 && $check1['online']==1) {
    $result = send_socket_cmd($arr, GMIP1, GMPort1, 'PHP1');

    //}else{
    //    $result = send_socket_cmd($arr, GMIP2, GMPort2);
    //}
    return $result;
}

function game_addGoldorder($order_id, $uid, $account, $product_id, $card, $card_add) {
    $key = 'f42afdb92e2e66c24dfb9';
    $arr = array(
        'cmd'       => 18,
        'uid'       => $uid,
        'account'   => $account,
        'plat' => 'client',
        'order_id' => $order_id,
        'product_id' => $product_id,
        'card' => intval($card),
        'card_add' => intval($card_add),
    );

    $arr['sign'] = gen_sign($arr, $key);

    //验证
    //$check1 = send_socket_cmd(array('cmd'=>35,'account'=>$account), GMIP1, GMPort1);
    //if($check1 && $check1['online']==1) {
    $result = send_socket_cmd($arr, GMIP1, GMPort1, 'PHP5');

    //}else{
    //    $result = send_socket_cmd($arr, GMIP2, GMPort2);
    //}
    return $result;
}

/* -----------------------------------------------public function ------------------------------------------------------------- */

/**
 * 生成签名
 * @param array params 签名数据
 * @param string app_key 签名秘钥
 * @return string 签名串
 */
function gen_sign($params, $app_key) {
    ksort($params);
    $str = '';
    foreach ($params as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    $str .= 'key=' . $app_key;
    $signature = md5($str);
    return $signature;
}

/**
 * 验证签名
 * @return boolean * 生成签名
 * @param array params 签名数据
 * @param string app_key 签名秘钥
 * @return boolean true|flase
 */
function check_sign($params, $app_key) {
    if (empty($params['signature'])) {
        return false;
    }
    ksort($params);
    $str = '';
    $signature = $params['signature'];
    foreach ($params as $k => $v) {
        if ($k != 'signature' and $v !== '') {
            $str .= $k . '=' . $v . '&';
        }
    }
    $str .= 'key=' . $app_key;
    if ($signature == md5($str)) {
        return true;
    }
    return false;
}

function write_log_file($data) {
    if (is_array($data)) {
        file_put_contents(LOG_PATH . date('Y-m-d') . ".log", date('Y-m-d H:i:s') . @json_encode($data) . "\n", FILE_APPEND);
    } else {
        file_put_contents(LOG_PATH . date('Y-m-d') . ".log", date('Y-m-d H:i:s') . $data . "\n", FILE_APPEND);
    }
}

function write_pay_file($data) {
    if (is_array($data)) {
        file_put_contents(LOG_PATH . "pay-" . date('Y-m-d') . ".log", date('Y-m-d H:i:s') . @json_encode($data) . "\n", FILE_APPEND);
    } else {
        file_put_contents(LOG_PATH . "pay-" . date('Y-m-d') . ".log", date('Y-m-d H:i:s') . $data . "\n", FILE_APPEND);
    }
}

function write_callback_file($plat, $data) {
    if (is_array($data)) {
        file_put_contents(LOG_PATH . $plat . "pay-" . date('Y-m-d') . ".log", date('Y-m-d H:i:s') . @json_encode($data) . "\n", FILE_APPEND);
    } else {
        file_put_contents(LOG_PATH . $plat . "pay-" . date('Y-m-d') . ".log", date('Y-m-d H:i:s') . $data . "\n", FILE_APPEND);
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
 * @return mixed
 */
function get_client_ip($type = 0, $adv = true) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset($arr[$pos]);
            $ip = trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}


function get_client_ip_new() {
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
}


function createLinkstring($para) {
	$arg = "";
	foreach ($para as $key => $val) {
		$arg.= $key . "=" . $val . "&";
	}
	//去掉最后一个&字符
	$arg = trim($arg, "&");
	return $arg;
}

/*
 * 把数据生成每个月的数组
 */
function data_format($arr,$field1,$field2="m"){
    $arr1=array();
    foreach($arr as $v){
        $arr1[(int)$v[$field2]]=$v[$field1];
    }
    return $arr1;
}
