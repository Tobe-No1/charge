<?php
 class ipay {
     // sdk rmb / ipay rmb = rate
    private $rmb_rate = 100;
    //爱贝商户后台接入url
    // $coolyunCpUrl="http://pay.coolyun.com:6988";
    private $iapppayCpUrl="http://ipay.iapppay.com:9999";
    //登录令牌认证接口 url
    private $tokenCheckUrl= "/openid/openidcheck";
    //下单接口 url
    // $orderUrl=$coolyunCpUrl . "/payapi/order";
    private $orderUrl= "/payapi/order";
    //支付结果查询接口 url
    private $queryResultUrl="/payapi/queryresult";
    //契约查询接口url
    private $querysubsUrl="/payapi/subsquery";
    //契约鉴权接口Url
    private $ContractAuthenticationUrl="/payapi/subsauth";
    //取消契约接口Url
    private $subcancel="/payapi/subcancel";    
    //应用编号
    private $appid="301085480";
    //应用私钥
    private $appkey="MIICXAIBAAKBgQC2IDfcQCLoT8ma0aZZRsVDyn4wP+wMen3zBiozzj27JcB3pM6Dy7XUV3l36WdyjLdwPtEXRNZeUU9vxsaV3ZKtP54HLLcSbteNB4h0z4T3nFCscMKYsIc5PHWLigeu8tlxuPL211Q9sbsaNIrEQkHJPAGCO4nI1FzASckHoD78RQIDAQABAoGAbCK1CBEMqOK20TWpj1h1x8pzIjLR9JbGMYV3iUrrXDMOE/vGonpRe8J0XL44rcrfH8YsX6R/o4CjqbpcLp9gdnm5bkZUXU6ciiT36A+7dSTBcInPgNa/eiXrBeXmekMO7VE386aCb6Y8NzXsi3y4aOM/oM+ImKBWhlXe6bu2IAECQQD8rDXhyBnFD4ZjaFknadCRG2lwf5WKKHOuW/VjHy5yLlYIvow6TgKcaHv8yaBbP/mnMoQzhrLGLoiRo7kiR97bAkEAuIYvnC3lEI8FXjeZ7BU5x3sEMK67UbyOJrKPuZBKVakbDkZ9XD9ZIapsn/C1vHSCxIrBTMrSauxHbV/V5VWrXwJAUVZ6jfElYotp3oxTzt1AV8X0PlPD5tK40pMlvj/marlB1tTIPWipbIIyD6E0bst8aXdYmWGuISAPtJp42XE7awJAb+EsTM2m7XtKDe44bWQRPFniGIJZIR3qMpxheGl8KKP3u5gRujTOnhLu+arBBhq+jtxyh1USb7IDPNW2ou5mkQJBAMJikDWfpVTQARTePE4fRF9x477wWZQHAl6u0uKSVj+3Kh6PVd6T6ABYh4xc66wwoFvGZN8u4lGgx1eY4yElY/A=";
    //平台公钥
    private $platpkey="MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCSyzxPt4iU7FNrlczvWaSomps/K+8zitTrGCaoxg8S12/bSS8b8ieEWtof477tLVRiJN6pmNnw6/K7IcNYtmagF2KptbliJVYTZQrrb0QzrTynYsGzH2ew+SRsJX1pWMk63kIeX4t3g39IRI8vehvGG8rH1bErziM69u4b24jNwQIDAQAB";
   

     /**
      * ipay constructor.
      * @param string $appid        爱贝支付应用ID,爱贝后台申请
      * @param string $appkey       爱贝支付应用秘钥,爱贝后台申请
      * @param string $platpkey     爱贝支付应用平台解密秘钥
      * @param int $pay_way_id      第一波SDK支付渠道Id
      */
    public function __construct($appid='',$appkey='',$platpkey='',$pay_way_id=2){
        //登录令牌认证接口 url
        $this->tokenCheckUrl=$this->iapppayCpUrl . $this->tokenCheckUrl;
        //下单接口 url
        $this->orderUrl=$this->iapppayCpUrl . $this->orderUrl;
        //支付结果查询接口 url
        $this->queryResultUrl=$this->iapppayCpUrl . $this->queryResultUrl;
        //契约查询接口url
        $this->querysubsUrl=$this->iapppayCpUrl.$this->querysubsUrl;
        //契约鉴权接口Url
        $this->ContractAuthenticationUrl=$this->iapppayCpUrl.$this->ContractAuthenticationUrl;
        //取消契约接口Url
        $this->subcancel=$this->iapppayCpUrl.$this->subcancel;
        
        if($appid){
            $this->appid = $appid;
        }        
        if($appkey){
            $this->appkey = $appkey;
        }
        if($platpkey){
            $this->platpkey = $platpkey;
        }
  
    }
    /**下单操作
     * $order_id string SDK订单
     * $price int/Float 价格 分
     * $user_id string 用户ID
     * $notifyUrl string 订单回调地址
     * $waresid string 商品Id
     * $cpprivateInfo string 游戏或者SDK保留信息
     * return  array 消息格式 ['state'=>0,'msg'=>'msg','data'=>array(),'transid'=>0]  // state:1 未成功 ,transid 订单号
     * 
     */
    public function trade($order_id,$price,$user_id,$notifyUrl,$waresid,$cpprivateInfo){            
        //包装数据
        $orderReq['appid'] = (string)($this->appid);
        $orderReq['waresid'] = (int)($waresid);
        $orderReq['cporderid'] = (string)($order_id);   //确保该参数每次 都不一样。否则下单会出问题。
        $orderReq['price'] = $this->paywayRMB($price);   //单位：分转换为元
        $orderReq['currency'] = 'RMB';
        $orderReq['appuserid'] = (string)($user_id);
        $orderReq['cpprivateinfo'] = (string)($cpprivateInfo);
        $orderReq['signtype'] = 'RSA';
        //兼容
        $orderReq['notifyurl'] = $notifyUrl;
        //加密数据
        $reqData = $this->composeReq($orderReq, $this->appkey);
        //下单操作
        $respData = $this->request_by_curl($this->orderUrl, $reqData, 'order test');
        if(!$respData){
            return array('state'=>0,'transid'=>0,'data'=>'request is empty.');
        }
        //下单结果
        if($this->parseResp($respData, $this->platpkey, $respJson)) {
            return array('state'=>1,'data'=>$respJson,'transid'=>$respJson['transid']);
        }
        //返回数据失败
        return array('state'=>0,'transid'=>0,'data'=>$respData);
    }

     /**
      * 支付回调
      * @param $postData
      * @return array|null
      */
    public function before_callback($postData){
        if(!$postData){            
            return null;
        }
        $transdata=$postData['transdata'];
        if(stripos("%22",$transdata)){ //判断接收到的数据是否做过 Urldecode处理，如果没有处理则对数据进行Urldecode处理
            $postData= array_map ('urldecode',$postData);
        }
        $respData = 'transdata='.$postData['transdata'].'&sign='.$postData['sign'].'&signtype='.$postData['signtype'];//把数据组装成验签函数要求的参数格式
        if(!$this->parseResp($respData, $this->platpkey, $respJson)) {
            return null;
        }else{
            $transdata=$postData['transdata'];
            $arr=json_decode($transdata);
            return array(
                'order_id'=>$arr->cporderid,     //商户订单号 , SDK订单号
                'user_id'=>$arr->appuserid,        //商户用户号 , SDK用户号
                'paytype'=>$arr->paytype,           //商户用户号 , 支付方式.
                'rmb'=>$arr->money,                //商户金额信息 ,         由 下单是保留进去
                'currency'=>$arr->currency,        //爱贝交易货币类型 ,     爱贝交易货币类型
                'waresid'=>$arr->waresid,        //爱贝的商品Id ,         由 生成订单时加入
                'appid'=>$arr->appid,            //商户使用的appid ,     由 SDK游戏 游戏配置定义的爱贝账号
                'transid'=>$arr->transid,        //爱贝的交易号 ,         下单后返回的结果
                'transtime'=>$arr->transtime,    //爱贝的交易时间 ,     支付完成时间
                'return'=>$arr->result,            //爱贝的交易结果 ,     完成结果
                'cpprivate'=>$arr->cpprivate);    //商户私密信息
        }
    }
     /**
      * @param null $postData 如果为 null  ,则为 $_POST
      * @return array|null
      */
    public function callback($postData = null){
        if(is_null($postData)){
            $postData = $_POST;
        }
        $order_data = $this->before_callback($postData);
        if(!$order_data){            
//            echo 'failure';
            return null;
        }else{            
//            echo 'success';
            return $order_data;
        }
    }
    /**
    * 人民币 单位 转换
    */
    public function paywayRMB($rmb_cent){
        return intval($rmb_cent) / $this->rmb_rate;
    }
    public function isSuccess($result){
        return $result == '0';
    }
    /**格式化公钥
     * $pubKey PKCS#1格式的公钥串
     * return pem格式公钥， 可以保存为.pem文件
     */
    private function formatPubKey($pubKey) {
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($pubKey);
        for($i = 0; $i < $len; ) {
            $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }


    /**格式化公钥
     * $priKey PKCS#1格式的私钥串
     * return pem格式私钥， 可以保存为.pem文件
     */
    private function formatPriKey($priKey) {
        $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
        $len = strlen($priKey);
        for($i = 0; $i < $len; ) {
            $fKey = $fKey . substr($priKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END RSA PRIVATE KEY-----";
        return $fKey;
    }

    /**RSA签名
     * $data待签名数据
     * $priKey商户私钥
     * 签名用商户私钥
     * 使用MD5摘要算法
     * 最后的签名，需要用base64编码
     * return Sign签名
     */
    private function sign($data, $priKey) {
        //转换为openssl密钥
        $res = openssl_get_privatekey($priKey);

        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_MD5);

        //释放资源
        openssl_free_key($res);
        
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**RSA验签
     * $data待签名数据
     * $sign需要验签的签名
     * $pubKey爱贝公钥
     * 验签用爱贝公钥，摘要算法为MD5
     * return 验签是否通过 bool值
     */
    private function verify($data, $sign, $pubKey)  {
        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_MD5);
        
        //释放资源
        openssl_free_key($res);

        //返回资源是否成功
        return $result;
    }


    /**
     * 解析response报文
     * $content  收到的response报文
     * $pkey     爱贝平台公钥，用于验签
     * $respJson 返回解析后的json报文
     * return    解析成功TRUE，失败FALSE
     */
    private function parseResp($content, $pkey, &$respJson) {
        $arr=array_map(create_function('$v', 'return explode("=", $v);'), explode('&', $content));
        foreach($arr as $value) {
            $resp[($value[0])] = $value[1];
        }

        //解析transdata
        if(array_key_exists("transdata", $resp)) {
            $respJson = json_decode($resp["transdata"],true);
        } else {
            return FALSE;
        }
        //验证签名，失败应答报文没有sign，跳过验签
        if(array_key_exists("sign", $resp)) {
            //校验签名
            $pkey = $this->formatPubKey($pkey); 
            return $this->verify($resp["transdata"], $resp["sign"], $pkey);
        } else if(array_key_exists("errmsg", $respJson)) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * curl方式发送post报文
     * $remoteServer 请求地址
     * $postData post报文内容
     * $userAgent用户属性
     * return 返回报文
     */
    private function request_by_curl($remoteServer, $postData, $userAgent) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remoteServer);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $data = urldecode(curl_exec($ch));
        curl_close($ch);

        return $data;
    }


    /**
     * 组装request报文
     * $reqJson 需要组装的json报文
     * $vkey  cp私钥，格式化之前的私钥
     * return 返回组装后的报文
     */
    public function composeReq($reqJson, $vkey) {
        //获取待签名字符串
        $content = json_encode($reqJson);
        //格式化key，建议将格式化后的key保存，直接调用
        $vkey = $this->formatPriKey($vkey);
        
        //生成签名
        $sign = $this->sign($content, $vkey);
        
        //组装请求报文，目前签名方式只支持RSA这一种
        $reqData = "transdata=".urlencode($content)."&sign=".urlencode($sign)."&signtype=RSA";
     
        return $reqData;
    }

 }