<?php
/* *
 * 功能：明天云平台支付类
 * 说明：构造支付请求函数及生成签名，支付接口表单HTML文本
 * 版本：1.0
 * 日期：2017-05-15
 */
class itpPay{
	
	var $itppay_config;
	
	//支付请求地址
	var $itppay_gateway = "http://trans.palmf.cn/sdk/api/v1.0/cli/order_h5/0";
//	var $itppay_gateway = "http://www.itppay.com/sdk/api/v1.0/cli/order_h5/0";

	function __construct($itppay_config){
		$this->itppay_config = $itppay_config;
	}
	
	/**
	 * 生成签名
	 * $parameter 已排序要签名的数组
	 * return 签名结果字符串
	 */
	function setSignature($parameter) {
		$signature="";
		if(is_array($parameter)){
			foreach($parameter as $k=>$v){
				if(!empty($v) && !is_null($v)){
					$signature .= $k."=".$v."&";
				}
			}
			if($signature){
				$signature .= "key=".$this->itppay_config["key"];
				$signature = md5($signature);
			}
		}
		
		return $signature;
	}
	
	/**
	 * 生成POST传递值
	 * $parameter 已排序要签名的数组
	 * return 生成的字符串
	 */
	function setPostValue($parameter) {
		$orderInfo=array();
		if(is_array($parameter)){
			$parameter["signature"] = $this->setSignature($parameter);
			foreach($parameter as $k=>$v){
				if(!empty($v) && !is_null($v)){
					$orderInfo[$k] = $v;
				}
			}
		}
		$orderInfo = json_encode($orderInfo,JSON_UNESCAPED_UNICODE);
		
		return $orderInfo;
	}
	
	/**
	 * 获取加密后的参数数据
	 * $parameter 已排序要签名的数组
	 * return 加密后的字符串
	 */
	function getOrderInfo($parameter) {
		$crypto="";
		$orderInfo = $this->setPostValue($parameter);
		$itppay_cert = file_get_contents("itppay_cert.pem");
		$publickey = openssl_pkey_get_public($itppay_cert);
		foreach(str_split($orderInfo, 117) as $chunk){
			openssl_public_encrypt($chunk, $encryptData, $publickey);
			$crypto .= $encryptData;
		}
		$crypto = base64_encode($crypto);
		
		return $crypto;
	}
	
	/**
	 * 建立以表单形式构造
	 * $orderInfo POST传递参数值
	 * $button 提交按钮显示内容
	 * return HTML表单
	 */
	function RequestForm($orderInfo, $button){
		$html = '';
		$html .= "<form id=\"itppay_form\" name=\"itppay_form\" action=\"".$this->itppay_gateway."\" method=\"post\">";
		$html .= "<input type=\"hidden\" name=\"orderInfo\" value=\"".$orderInfo."\">";
		$html .= "<input type=\"submit\" value=\"".$button."\">";
		$html .= "<span>".$orderInfo."</span>";
		$html .= "</form>";
		
		$html .= "<script>setTimeout(function(){document.forms['itppay_form'].submit();}, 2000);</script>";
		
		return $html;
	}

}

?>
