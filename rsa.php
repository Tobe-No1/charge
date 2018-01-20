<?php

class mycrypt
{

    public $public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC0dYM3DXkVg9q+WcNjBPWaUwKo
eRMrwdE4p4F6fiztv/Ys6F5AxGCbFW5UfbtbQavMp9Rrg3+8mJ5/Lp8sjf471NFe
6EvbCcVwJ63Q6fA4xVyCAE7mQdfAlpCk9WKN7Qa/HqwO/OM6JDyOyycnjnNi3f3K
2tK/JbWd/SHYOSMEDQIDAQAB
-----END PUBLIC KEY-----';
    
   
    function decrypt($data)
    {
        // 这个函数可用来判断公钥是否是可用的
        $pu_key = openssl_pkey_get_public($this->public_key);
        $b64 = base64_decode($data);
//         echo $b64 . '<br>';
//         echo '<br>len:' . strlen($b64) . '<br>';
        $rs = '';
        for($i = 0;$i*128<strlen($b64);$i++){
            
            openssl_public_decrypt(substr($b64, $i*128,128), $decrypted, $pu_key); // 私钥加密的内容通过公钥可用解密出来
            $rs .= $decrypted;
        }
//         echo $rs, "<br>";
        return $rs;
    }

    function encrypt($data)
    {
        // 这个函数可用来判断公钥是否是可用的
        $pu_key = openssl_pkey_get_public($this->public_key);
        $rs = '';
        for($i = 0;$i*117<=strlen($data);$i++){
           openssl_public_encrypt(substr($data, $i*117,117), $encrypted, $pu_key); // 公钥加密
            $rs .= $encrypted;
        }
        $rs = base64_encode($rs);
        return $rs;
    }
}
// $rsa = new mycrypt();
// echo $rsa->decrypt("enBIq6cyA0imWthjApNkcdiSyVnsKdLN4DtENzL7mIk+U4aliJKpk9SvCKYBocPe5hVQoEMMJWQHqGn3S8SyMd4XkBvi7qPIpQOYYku2dMmg1AxgEbrWaTk+bkAlITh3eT99U/wBAT5NKx7Sv6e3V4IX+sRkPsuSLRFY1fnPqzk=");
// echo $rsa->encrypt("amount=1&appid=wawafasfas&body=测试测试&clientIp=192.168.1.11&mchntOrderNo=1465272262443&ifyUrl=https://www.baidu.com&subject=测试商品&signature=833f223dd9fe0f791b604e8ea6ccbd33a");

