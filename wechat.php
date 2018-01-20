<?php

	$code = trim($_REQUEST['code']);
	$game_flag = trim($_REQUEST['game_flag']);
	//$pack = trim($_REQUEST['pack']);
	
	
    $AppID = 'wxb962990281401398';
    $AppSecret = 'd73a9ecbfc49cd1671cd81c544f9a861';


if($game_flag == 'dh'){
                $AppID = 'wx5f2ded1e777944ac';
                $AppSecret = '7b11460ca0c3caad45dbf15303a669ef';
        }
	
	$url = sprintf('https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code',$AppID,$AppSecret,$code);
	
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $tmp = curl_exec($ch);
    curl_close($ch);
//    $tmp = file_get_contents($url);
	if(!$tmp) {
		$re = array('ret'=>1,'msg'=>'get userinfo error');
		die(json_encode($re));
	}
	
	$access_token = json_decode($tmp,true);	
	if(isset($access_token['errcode'])){
		$re = array('ret'=>2,'msg'=>$access_token['errmsg']);
		die(json_encode($re));
	}
	
	$userinfo_url = sprintf("https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s",$access_token['access_token'],$access_token['openid']);
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $tmp_info = curl_exec($ch);
    curl_close($ch);
	file_put_contents('/tmp/bb.txt',$tmp_info."\n",FILE_APPEND);
	if(!$tmp_info) {
		$re = array('ret'=>2,'msg'=>'get userinfo error');
		die(json_encode($re));
	}
	$userinfo = json_decode($tmp_info,true);
	
	$userinfo['nickname'] = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['nickname']);
	$userinfo['nickname'] = str_replace("'", '', $userinfo['nickname']);	

	$re = array(
		'ret' => 0,
		'msg' => 'success',
		'info'=> $userinfo,
		'expires_time' => time() + 86400 * 365,
		'password' => md5($access_token['openid'].time()),
	);
	
	echo json_encode($re);
