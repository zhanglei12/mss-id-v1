<?php

class EleMa {
	var $partner_url;

	public function __construct($arr = array('partner_url'=>NULL)) {
		$this->partner_url = $arr['partner_url'];
	}

	/**
	 * 通过传过来的店铺id可以获取店铺信息;
	 */
	function postStore($store_id) {
		$requestTime = time();
		$url = "/v1/eleme/restaurants/" . $store_id . "/distribution/confirm";
		$sarr = array(
				"authKey" => AUTH_KEY,
				"requestTime" => $requestTime 
		);
		$sign = $this->genarateSignature("POST",$url,$sarr);
		$push_url = $url . "?authKey=" . AUTH_KEY . "&requestTime=" . $requestTime . "&requestSignature=" . $sign;
		$re = $this->request_post_url($push_url);
		if ($re["status"] == 200) {
			return array(
					"status" => 1,
					"message" => "成功",
					"data" => $re 
			);
		} else {
			return array(
					"status" => 2,
					"message" => $re["message"] 
			);
		}
	}

	/**
	 * 修改配送餐厅的配送费
	 * 
	 * @author zhanglei
	 * @param array $store_info        	
	 * @return array
	 */
	function sendShippingFee($store_info) {
		$requestTime = time();
		$url = "/v1/eleme/restaurants/" . $store_info['store_id'] . "/distribution/update";
		$sarr = array(
				"authKey" => AUTH_KEY,
				"requestTime" => $requestTime,
				"chargingDescription" => "每单" . $store_info['shipping_fee'] . "元" 
		);
		var_dump($sarr);
		$sign = $this->genarateSignature("POST",$url,$sarr);
		$push_url = $url . "?authKey=" . AUTH_KEY . "&requestTime=" . $requestTime . "&requestSignature=" . $sign;
		var_dump($push_url);
		$re = $this->request_post_url($push_url,array(
				"chargingDescription" => "每单" . $store_info['shipping_fee'] . "元" 
		));
		if ($re["status"] == 200) {
			return array(
					"status" => 1,
					"message" => "成功",
					"data" => $re 
			);
		} else {
			return array(
					"status" => 2,
					"message" => $re["message"] 
			);
		}
	}

	function request_post_url($url,$posting = array()) {
		$data = preg_replace('/%5B[0-9]+%5D/simU','%5B%5D',http_build_query($posting));
		$curl = curl_init();
		$this_header = array(
				"content-type: application/x-www-form-urlencoded;charset=UTF-8" 
		);
		curl_setopt($curl,CURLOPT_HTTPHEADER,$this_header);
		curl_setopt($curl,CURLOPT_URL,$this->partner_url . $url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		$data1 = curl_exec($curl);
		$re = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		return array(
				"status" => $re,
				"message" => $data1 
		);
	}

	/**
	 * 确认订单
	 */
	function accept($arr) {
		// partner_order_id
		$requestTime = time();
		$url = "/v1/eleme/orders/" . $arr['partner_order_id'] . "/delivery/accept";
		$sarr = array(
				"authKey" => AUTH_KEY,
				"requestTime" => $requestTime 
		);
		$sign = $this->genarateSignature("POST",$url,$sarr);
		$push_url = $url . "?authKey=" . AUTH_KEY . "&requestTime=" . $requestTime . "&requestSignature=" . $sign;
		$re = $this->request_post_url($push_url);
		var_dump($re);
		if ($re["status"] == 200) {
			return json_encode(array(
					"state" => "true" 
			));
		} else {
			return json_encode(array(
					"state" => "false",
					"date" => $re,
					"date1" => $push_url 
			));
		}
	}

	/**
	 * 分配，取餐，完成
	 */
	function update($arr) {
		// partner_order_id
		$status = array(
				2 => "confirmed",
				6 => "started",
				3 => "complete" 
		);
		$requestTime = time();
		$url = "/v1/eleme/orders/" . $arr['partner_order_id'] . "/delivery/update";
		$sarr = array(
				"authKey" => AUTH_KEY,
				"requestTime" => $requestTime,
				"deliveryOrderId" => $arr['mss_order_id'],
				"status" => $status[$arr['order_state']],
				"deliverymanName" => $arr['emp_name'] ? $arr['emp_name'] : "美食送",
				"deliverymanMobile" => "52285085",
				"description" => "" 
		);
		$form = array(
				"deliveryOrderId" => $arr['mss_order_id'],
				"status" => $status[$arr['order_state']],
				"deliverymanName" => $arr['emp_name'] ? $arr['emp_name'] : "美食送",
				"deliverymanMobile" => "52285085",
				"description" => "" 
		);
		$sign = $this->genarateSignature("POST",$url,$sarr);
		$push_url = $url . "?authKey=" . AUTH_KEY . "&requestTime=" . $requestTime . "&requestSignature=" . $sign;
		$re = $this->request_post_url($push_url,$form);
		if ($re["status"] == 200) {
			return json_encode(array(
					"state" => "true" 
			));
		} else {
			return json_encode(array(
					"state" => "false" 
			));
		}
	}

	function cancel($arr) {
		// partner_order_id
		$requestTime = time();
		$url = "/v1/eleme/orders/" . $arr['partner_order_id'] . "/delivery/cancel";
		$sarr = array(
				"authKey" => AUTH_KEY,
				"requestTime" => $requestTime,
				"reason" => $arr['reason'] ? $arr['reason'] : '取消订单' 
		);
		$form = array(
				"reason" => $arr['reason'] ? $arr['reason'] : '取消订单' 
		);
		
		$sign = $this->genarateSignature("POST",$url,$sarr);
		$push_url = $url . "?authKey=" . AUTH_KEY . "&requestTime=" . $requestTime . "&requestSignature=" . $sign;
		$re = $this->request_post_url($push_url,$form);
		if ($re["status"] == 200) {
			return json_encode(array(
					"state" => "true" 
			));
		} else {
			return json_encode(array(
					"state" => "false" 
			));
		}
	}

	/**
	 * 订单状态回调
	 * 
	 * @author zhanglei
	 * @param array $arr        	
	 * @return str
	 */
	function sendStatus($arr) {
		$re = array(
				"state" => "true" 
		);
		switch ($arr['order_state']) {
			case ORDER_COMFIRED_TO_PARTNER : // 已确认
				$re = $this->accept($arr);
				break;
			case in_array($arr['order_state'],array(
					ORDER_ALLOTED_TO_PARTNER,
					ORDER_FINISHED_TO_PARTNER,
					ORDER_GOTFOOD_TO_PARTNER 
			)) : // 分配，取餐，送达
				$re = $this->update($arr);
				break;
			case ORDER_CANCEL_TO_PARTNER : // 取消
				$re = $this->cancel($arr);
				break;
		}
		return $re;
	}

	/**
	 * 饿了么生成签名
	 * 
	 * @author zhanglei
	 * @param array $parameters,str
	 *        	$method $method
	 * @return str
	 */
	function genarateSignature($method,$path,$parameters) {
		$sorted = array();
		foreach ($parameters as $key => $value) {
			array_push($sorted,"$key=$value");
		}
		sort($sorted);
		$str = $method . $path . implode("",$sorted) . SECRET_KEY;
		return md5($str);
	}
}