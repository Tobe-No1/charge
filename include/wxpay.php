<?php

class wxpay {

    /**
      配置参数
     */
    private $rmb_rate = 1;  //用于折扣
    private $config = array(
        'appid' => "wxb962990281401398", /* 微信开放平台上的应用id 1 */
        'mch_id' => "1462911402", /* 微信申请成功之后邮件中的商户id */
        'api_key' => "d9e7bd6d2585a05c73a04d2331ea9b6a", /* 在微信商户平台上自己设定的api密钥 32位 */
    );
    private $unified_order_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder'; //统一下单地址

    public function __construct($pay_way_config) {
        if ($pay_way_config['wx_appid']) {
            $this->config['appid'] = $pay_way_config['wx_appid'];
        }
        if ($pay_way_config['wx_secret_key']) {
            $this->config['api_key'] = $pay_way_config['wx_secret_key'];
        }

        if ($pay_way_config['wx_mch_id']) {
            $this->config['mch_id'] = $pay_way_config['wx_mch_id'];
        }
    }

    /**
     * 支付下单
     * @param $order_id
     * @param $order
     * @param $notifyUrl
     * @return array
     * return  array 消息格式 ['state'=>0,'msg'=>'msg','data'=>array(),'transid'=>0]  // state:1 未成功 ,transid 订单号
     */
    public function trade($order_id, $amount, $notifyUrl, $returnUrl = "", $client_ip, $trade_type = "MWEB", $open_id = "") {
        $data["appid"] = $this->config["appid"];
        $data["body"] = $amount;
        $data["mch_id"] = $this->config['mch_id'];
        $data["nonce_str"] = $this->getRandChar(32);
        $data["notify_url"] = $notifyUrl;
        $data["out_trade_no"] = $order_id;
        $data["spbill_create_ip"] = $client_ip;
        $data["total_fee"] = $amount; //单位分
        $data["trade_type"] = $trade_type; //JSAPI--公众号支付、NATIVE--原生扫码支付、APP--app支付 MWEB  H5支付
        $data['attach'] = $order_id;
        $data['device_info']    = '100';
        if ($trade_type == "NATIVE") {
            /* $data["product_id"] = $order_id; */
        } elseif ($trade_type == "JSAPI") {
            $data["openid"] = $open_id;
        }
        $sign = $this->getSign($data, false);
        $data["sign"] = $sign;
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $this->unified_order_url);
//        var_dump($response);
        file_put_contents('/tmp/data.txt', $xml,FILE_APPEND);
        //将微信返回的结果xml转成数组
        $response = (array) simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        file_put_contents('/tmp/data.txt', $xml,FILE_APPEND);
        
        if ($response['result_code'] != 'SUCCESS') {
            return array('state' => 0, 'transid' => 0, 'data' => $response);
        } else {
            if ($trade_type == "NATIVE") {
                return array('state' => 1, 'pay_url' => $response['code_url'], 'data' => $response);
            } elseif ($trade_type == "JSAPI") {
                return array('state' => 1, 'transid' => $response['prepay_id'], 'data' => $response);
            } elseif ($trade_type == "MWEB") {
                return array('state' => 1, 'pay_url' => $response['mweb_url'] . '&redirect_url=' . urlencode($returnUrl), 'data' => $response);
            }
        }
    }

    /**
     * 验证签名
     * @param $post
     * @return bool
     */
    public function signverify($QueryStr) {
        $signature_str = "";
        ksort($QueryStr);
        foreach ($QueryStr as $key => $value) {//ASCII 以AppSecretKey作为key,使用hmac-sha1带密钥(secret)的哈希算法对代签字符串进行签名计算,签名的结果由16进制表示
            if ($key == 'sign' || $key == 'signature' || $value == '')
                continue;
            $signature_str .= $key . '=' . $value . '&';
        }
        $signature_str .= 'key='.$this->config['api_key'];
        return $signature_str;
        /*
        if ($QueryStr['sign'] == strtoupper(md5($signature_str))) {
            return true;
        } else {
            return false;
        }*/
    }
    public function checkSign($data) {
        unset($data['sign']);
        $sign = $this->getSign($data);
        return $sign;
        /*
        if ($QueryStr['sign'] == strtoupper(md5($signature_str))) {
            return true;
        } else {
            return false;
        }*/
    }
    /**
     * 回调参数转换
     * @param null $postData 如果为 null  ,则为 $_POST
     * @return array|null
     */
    static function callback($postData = null) {
        return array(
            'order_id' => $postData['out_trade_no'], //商户订单号
            'rmb' => intval($postData['total_fee']), //商户金额信息 分 数据库为分
            'transid' => $postData['transaction_id'], //支付平台的交易号
            'transtime' => strtotime($postData['time_end']), //支付交易结束时间
            'return' => ($postData['result_code']) == "SUCCESS" ? true : false,
            'result' => json_encode($postData),
        );
    }

    /**
     * 人民币 单位 转换
     */
    public function paywayRMB($rmb_cent) {
        return intval($rmb_cent) * $this->rmb_rate;
    }

    /**
     * 返回
     * @param null $postData 如果为 null  ,则为 $_POST
     * @return array|null
     */
    static function ruturn($bool = false) {
        return $bool ? '<xml><return_code>SUCCESS</return_code><return_msg>成功</return_msg></xml>' : '<xml><return_code>FAIL</return_code><return_msg>失败</return_msg></xml>';
    }

    /**
     *
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws WxPayException
     *
     * @return json数据，可直接填入js函数作为参数
     */
    public function GetJsApiParameters($prepay_id) {
        if (empty($prepay_id)) {
            return array();
        }
        $values['appId'] = $this->config['appid'];
        $timeStamp = time();
        $values['timeStamp'] = "$timeStamp";
        $values['nonceStr'] = $this->getRandChar(32);
        $values['package'] = "prepay_id=" . $prepay_id;
        $values['signType'] = "MD5";
        $values['paySign'] = $this->JsApiMakeSign($values);
        $parameters = json_encode($values);
        return $parameters;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function JsApiMakeSign($values) {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $string = trim($buff, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->config['api_key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    //-----------------------  其他参数 -----------------------

    public function gen_ext($prepayid) {
        $params = array(
            'appid' => $this->config['appid'],
            'partnerid' => $this->config['mch_id'],
            'prepayid' => $prepayid,
            'package' => 'Sign=WXPay',
            'noncestr' => $this->getRandChar(32),
            'timestamp' => time(),
        );
        $params['sign'] = $this->getSign($params);
        return $this->createLinkstring($params);
    }

    function createLinkstring($para) {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = trim($arg, "&");

//        //如果存在转义字符，那么去掉转义
//        if (get_magic_quotes_gpc()) {
//            $arg = stripslashes($arg);
//        }

        return $arg;
    }

    /*
      生成签名
     */

    function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[strtolower($k)] = $v;
        }
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String . "&key=" . $this->config['api_key'];
        $result_ = strtoupper(md5($String));
        return $result_;
    }

    //获取指定长度的随机字符串
    function getRandChar($length) {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

    //数组转xml
    function arrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    //post https请求，CURLOPT_POSTFIELDS xml格式
    function postXmlCurl($xml, $url, $second = 30) {
        //初始化curl
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

    /*
      获取当前服务器的IP
     */

    function get_client_ip() {
//        if ($_SERVER['REMOTE_ADDR']) {
//            $cip = $_SERVER['REMOTE_ADDR'];
//        } elseif (getenv("REMOTE_ADDR")) {
//            $cip = getenv("REMOTE_ADDR");
//        } elseif (getenv("HTTP_CLIENT_IP")) {
//            $cip = getenv("HTTP_CLIENT_IP");
//        } else {
//            $cip = "unknown";
//        }
//        return $cip;
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';
    }

    //将数组转成uri字符串
    function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= strtolower($k) . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

}
