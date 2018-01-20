<?php

/**
 * User: iokdo2012@163.com
 * Date: 2016/9/2
 * Time: 11:50
 */
class alipay{
    
    private $rmb_rate = 100;
    private $alipay_config = array(
        'partner' => '2088911700468143',
        'seller_id' => '2088911700468143',
        'private_key' => 'je2rooi0ziupmj3i3nfrfpsp7ld27yl2',
        'alipay_private_key' => 'MIICXQIBAAKBgQDkEODhHzo3ByFdgA8aGVLDw+zXSsBjldqUq5uXNLQ5ZoH9ww6aLUMB5UBh36f8MnD63CYyLfRbFj9RjO9FRd+GS0BGZ/4owoOEXYcFsi242LU1LYvTj49NQNHHvqeie5TVpvhcPChuR+7Bz5gV/Ku/lED3apWQWwLI+iQAh7xbKwIDAQABAoGBAMfY0nXyuzyFDwOzt4gTMKdDBEMTycp6AfPM/KZfFJ5H6RjI1/7aMqFylhwttDIWsVHhn8bQ5hL7R7jaWsDFHbwUJR87gB4FOnZ8GeT9E6JIXQLYtj6mWWndWdg6Run0wINWwASpIVuSnzUs7Uk/HicvYBvsckp8d/Y7uDlXShqBAkEA/l4HUt4YpDTpcieJ/VKjBXwS1PZeAWPkabD2y2qiZEGxmxIZs9Q2ufFuS2jB0COtvdb8DMC20AQXoRqCAoftuwJBAOWHocZC85UvMCvm2fvSDDtlKZh3ryosQ7QuQPbO7axT3xjiIC9TSWLBOACiaxpUJz7svWtIs+Os7Ow0EenquVECQQDGndswHJW3Gk2yQidgM3dxn+kAewL6KOrAkFqUiUYV7KnmwOJq5Wh6FoedwEWd5U4TrtuOhaf5k0h0FkOwAD1jAkBcLxIA/4i2xOHy799ibOTuwd1n18GpDWgPqHf85/S6vmCerMrCVw/lMrbcQl8DjwLIGeqleOiOqzfoUa1UJipRAkBRof1DzjhYUd3DV9XvMwsWd76KoWrL1jh26hvyCaKBOs6Y6186hoA7HrghV27B1IhTjnn3AFHSfqpD0+DTSXzl',
        'alipay_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB',
        'service' => 'mobile.securitypay.pay',
        'sign_type' => 'RSA',
        '_input_charset' => 'utf-8',
        'cacert' => '/cacert.pem',
        'transport' => 'http',
    );

    /**
     * HTTPS形式消息验证地址
     */
    private $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

    /**
     * HTTP形式消息验证地址
     */
    private $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    public function __construct() {
        //$this->alipay_config = $alipay_config;
    }
    
    public function trade($sdk_order_id,$order_rmb,$sdk_notify_url) {
        $rmb = sprintf("%.2f",($order_rmb/100));
        $params = array(
            'partner'       => $this->alipay_config['partner'],
            'seller_id'     => $this->alipay_config['partner'],
            'out_trade_no'  => $sdk_order_id,
            'subject'       => '商品'.$rmb,
            'body'          => '商品'.$rmb,
            'total_fee'     => $rmb,
            'notify_url'    => $sdk_notify_url,
            'service'       => $this->alipay_config['service'],
            'payment_type'  => 1,
            '_input_charset'=> $this->alipay_config['_input_charset'],
            'it_b_pay'      => '30m',
        );
        $params['sign'] = $this->rsaSign($this->createLinkstring3($params),  $this->alipay_config['alipay_private_key']);
        $params['sign_type'] = 'RSA';
        $transid =  $this->createLinkstring3($params);
        return array(
            'state'     => 1,
            'transid'   => $transid,
        );
    }
    
     public function paywayRMB($rmb_cent){
        return intval($rmb_cent) / $this->rmb_rate;
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $isSgin = false;
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "RSA" :
                $isSgin = $this->rsaVerify($prestr, trim($this->alipay_config['alipay_public_key']), $sign);
                break;
            default :
                $isSgin = false;
        }

        return $isSgin;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id) {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

        return $responseTxt;
    }

    /*     *
     * 支付宝接口RSA函数
     * 详细：RSA签名、验签、解密
     * 版本：3.3
     * 日期：2012-07-23
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
     */

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key 商户私钥字符串
     * return 签名结果
     */
    function rsaSign($data, $private_key) {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $private_key = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $private_key);
        $private_key = str_replace("-----END RSA PRIVATE KEY-----", "", $private_key);
        $private_key = str_replace("\n", "", $private_key);
        $pem = chunk_split($private_key,64,"\n");
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n".$pem."-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($private_key);
        if ($res) {
            openssl_sign($data, $sign, $res);
        } else {
            echo "您的私钥格式不正确!" . "<br/>" . "The format of your private_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
        //base64编码
        $sign = urlencode(base64_encode($sign));
        return $sign;
    }

    /**
     * RSA验签
     * @param $data 待签名数据
     * @param $alipay_public_key 支付宝的公钥字符串
     * @param $sign 要校对的的签名结果
     * return 验证结果
     */
    function rsaVerify($data, $alipay_public_key, $sign) {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $alipay_public_key = str_replace("-----BEGIN PUBLIC KEY-----", "", $alipay_public_key);
        $alipay_public_key = str_replace("-----END PUBLIC KEY-----", "", $alipay_public_key);
        $alipay_public_key = str_replace("\n", "", $alipay_public_key);
        
        $alipay_public_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . wordwrap($alipay_public_key, 64, "\n", true) . PHP_EOL . '-----END PUBLIC KEY-----';
        $res = openssl_get_publickey($alipay_public_key);
        if ($res) {
            $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        } else {
            echo "您的支付宝公钥格式不正确!" . "<br/>" . "The format of your alipay_public_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
        return $result;
    }

    /*     *
     * 支付宝接口公用函数
     * 详细：该类是请求、通知返回两个文件所调用的公用函数核心处理文件
     * 版本：1.0
     * 日期：2016-06-06
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
     */

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg.=$key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }
    
    function createLinkstring2($para) {
        $arg = "";
        foreach($para as $key => $val){
            $arg.= $key . "='" . $val . "'&";
        }
        //去掉最后一个&字符
        $arg = trim($arg,"&");
        
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        
        return $arg;
    }
    
    function createLinkstring3($para) {
        $arg = "";
        foreach($para as $key => $val){
            $arg.= $key . '="' . $val . '"&';
        }
        //去掉最后一个&字符
        $arg = trim($arg,"&");
        
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        
        return $arg;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") {
                continue;
            } else {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    function query_timestamp() {
        $url = $this->alipay_gateway_new . "service=query_timestamp&partner=" . trim(strtolower($this->alipay_config['partner'])) . "&_input_charset=" . trim(strtolower($this->alipay_config['input_charset']));
        $encrypt_key = "";

        $doc = new DOMDocument();
        $doc->load($url);
        $itemEncrypt_key = $doc->getElementsByTagName("encrypt_key");
        $encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

        return $encrypt_key;
    }

    /**
     * 远程获取数据，POST模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * @param $para 请求的数据
     * @param $input_charset 编码格式。默认值：空值
     * return 远程输出的数据
     */
    function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

        if (trim($input_charset) != '') {
            $url = $url . "_input_charset=" . $input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_POST, true); // post传输数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $para); // post传输数据
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * return 远程输出的数据
     */
    function getHttpResponseGET($url, $cacert_url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 实现多种字符编码方式
     * @param $input 需要编码的字符串
     * @param $_output_charset 输出的编码格式
     * @param $_input_charset 输入的编码格式
     * return 编码后的字符串
     */
    function charsetEncode($input, $_output_charset, $_input_charset) {
        $output = "";
        if (!isset($_output_charset))
            $_output_charset = $_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else
            die("sorry, you have no libs support for charset change.");
        return $output;
    }

    /**
     * 实现多种字符解码方式
     * @param $input 需要解码的字符串
     * @param $_output_charset 输出的解码格式
     * @param $_input_charset 输入的解码格式
     * return 解码后的字符串
     */
    function charsetDecode($input, $_input_charset, $_output_charset) {
        $output = "";
        if (!isset($_input_charset))
            $_input_charset = $_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else
            die("sorry, you have no libs support for charset changes.");
        return $output;
    }

}
