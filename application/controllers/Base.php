<?php
class BaseController extends Yaf_Controller_Abstract
{
	var $api_db;
	var $api_mail;
	var $api_log;
	var $api_sms;
	var $api_base;
	var $_baseModel;
	var $_orderModel;
	var $_cooperateModel;
	var $_cooperatehistoryModel;
	var $_storeModel;
	var $_memberModel;
	var $balance;
	var $_buildingModel;
	var $_cooplocalModel;
	var $_regionModel;
	var $_goodsModel;
	var $_httprequest;
	
	
	/**
	 * 初始化类，相当于构造函数
	 * @return none
	 */
	private function init()
	{
		$this->api_db = Yaf_Registry::get('api_db');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
		$this->_baseModel = new BaseModel();
		$this->_orderModel = new OrderModel();
		$this->_cooperateModel = new CooperateModel();
		$this->_cooperatehistoryModel = new CooperateHistoryModel();
		$this->_storeModel = new StoreModel();
		$this->_memberModel = new MemberModel();
		$this->balance = array(
				"月付"=>1,
				"预付"=>2,
				"现付"=>3
		);
		$this->_buildingModel = new BuildingModel();
		$this->_cooplocalModel = new CoopLocalModel();
		$this->_regionModel = new RegionModel();
		$this->_goodsModel = new GoodsModel();
		$this->_httprequest = new PostMethod();
	}
	/**
	 * 格式化输出数据
	 * @param any
	 * @return void
	 */
	function dump($arr)
	{
	    echo '<pre>';
	    array_walk(func_get_args(), create_function('&$item, $key', 'print_r($item);'));
	    echo '</pre>';
	    exit();
	}
	
	/**
	 * 传入数据，生成创建订单的数据
	 * @param json $info
	 * @return array;
	 */
	public function dealOrderAction($jsoninfo){
		$info = json_decode($jsoninfo,true);
		$this->api_log->info($info);
		$partner_id = $info['partner_id'];
		$partner_order_id = $info['partner_order_id'];
		$push_time = $info['push_time'];
		$notify_url = $info['notify_url'];
		$sign = $info['sign'];
		$total_price = $info['total_price'];
		$return['order_id'] = $partner_order_id;
		if (empty($partner_id) || empty($partner_order_id) || empty($push_time) || empty($notify_url) || empty($sign) ) {
			$return['ret'] = "-1";
			$return['msg'] = "签名参数缺失";
		}else {
			$partner_info = $this->_cooperateModel->get($partner_id);
			if(empty($partner_info)){
				$return['ret'] = "-2";
				$return['msg'] = "身份验证失败";
			}else {
				$cooperate_name =$partner_info['appsecret'];
				$md5_sign=md5("partner_id=$partner_id#partner_order_id=$partner_order_id#push_time=$push_time#notify_url=$notify_url#key=$cooperate_name");
				$new_md5_sign = md5("partner_id=$partner_id#partner_order_id=$partner_order_id#push_time=$push_time#total_price=$total_price#key=$cooperate_name");
				if ( !in_array($sign, array($md5_sign,$new_md5_sign)) ) {
					$return['ret'] = "-3";
					$return['msg'] = "签名验证失败";
				}else {
					$info_condition = " `partner_order_id`='".$partner_order_id."' AND `cooperate_id`=".$partner_id;
					$infos = $this->_cooperatehistoryModel->query_info($info_condition);
					if($infos){
						$return['ret'] = "-4";
						$return['msg'] = "不能重复发送订单";
					}else {
						$his_array = array();
						$his_array['cooperate_id'] = $partner_id;
						$his_array['partner_order_id'] = $partner_order_id;
						$his_array['json_info'] = str_replace("'", "\\'", $jsoninfo);
						$his_array['add_time'] = time();
						if ($inId = $this->_cooperatehistoryModel->insert('ecm_cooperate_history',$his_array)) {
							//$coop_orderInfo = $this->_cooperatehistoryModel->get($inId);
							$info = json_decode($this->dealOrderInfoAction($his_array),true);
							if ($info['return']['ret'] == "1") {/*无误，准备拼凑订单信息*/
								if (!$info['order']) {
									$return['ret'] = "-12";
									$return['msg'] = "其它错误";
								}else {
									//$order = json_decode($this->createOrderInfoAction($info),true);
									$result = json_decode($this->createOrderAction($info),true);
									if ($result['ret'] != 1) {
										$return['ret'] = $result['ret'];
										$return['msg'] = $result['msg'];
									}else {
										$return['mss_order_id'] = $result['order_id'];
										$return['ret'] = $result['ret'];
										$return['msg'] = $result['msg'];
										$return['goods'] = ($result['goods']) ? $result['goods'] : array();
									}
								}
							}else {//有误，直接进行错误反馈
								$return['ret'] = $info['return']['ret'];
								$return['msg'] = $info['return']['msg'];
							}
						}else {
							$return['ret'] = "-20";
							$return['msg'] = "数据接收失败";
						}
					}
				}
			}
		}
		if(!in_array($return['ret'],array("-1","-2","-3","-4","-20"))){
			$states = ($return['ret'] == 1) ? "true" : "false";
			$update_arr = array(
					"update_time" => time(),
					"ret" => $return['ret'],
					"msg" => $return['msg'],
					"states" => $states
			);
			$this->_baseModel->update("ecm_cooperate_history", $update_arr,"`cooperate_id`=".$partner_id." AND `partner_order_id`='".$partner_order_id."'");
		}
		$this->api_log->info($return);
		return  json_encode($return);
	}
	
	/**
	 * 根据存储的本地数据进行处理
	 * @param array $info
	 * @return boolean
	 */
	public function dealOrderInfoAction($info){
		if (!$info) return false;
		$partner_order_id = $info['partner_order_id'];//合作伙伴的订单ID
		$cooperate_id = $info['cooperate_id'];//合作伙伴appKey
		$json_info = json_decode($info['json_info'],true);//反解析传过来的json数据
		$city = ($json_info['city']) ? $json_info['city'] : '北京市';//订单信息中传输的城市信息
		$notify_url = $json_info['notify_url'];
		$total_price = $json_info['total_price'];
		$remark= $json_info['remark'];
		$add_time = $json_info['add_time'];
		$request_time = $json_info['request_time'];
		$expectmeal_time = $json_info['expectmeal_time'];
		$custom_info = $json_info['custom_info'];
		$order_item = $json_info['order_items'];
		$order_goods = $order_item['order_goods'];
		$store_info = $order_item['store_info'];
		$invoice_title = $json_info['invoice'];
		$payment_name = $json_info['payment_name'];
		$phone_mob = $phone_tel = '';
		$if_pay = ($json_info['if_pay']) ? '1' : '0';
		$return = array();
		$store_info['city']=$city;
		$return['return'] = array();
		$return['return']['ret'] = "1";
		$return['return']['msg'] = "订单参数无误";
		$return['return']['partner_order_id']= $partner_order_id;
		$return['return']['cooperate_id']= $cooperate_id;
		$return['return']['notify_url']= $notify_url;
		$return['order'] = array();
		if ($this->check_exist(array($total_price,$custom_info,$order_item)))
		{
			$return['return']['ret'] = "-5";
			$return['return']['msg'] = "订单参数缺失";
			return json_encode($return);
		}else
		{
			/*-------------------------------非空信息以及有效性信息验证开始--------------------------------------*/
			$phone_check_result = json_decode($this->check_customInfo($custom_info),true);
			if ($phone_check_result['ret'] == "1") {
				$phone_mob = $phone_check_result['info']['phone_mob'];
				$phone_tel = $phone_check_result['info']['phone_tel'];
			}else {
				$return['return']['ret'] = $phone_check_result['ret'];
				$return['return']['msg'] = $phone_check_result['msg'];
				return json_encode($return);
			}
			$goods_check_result = json_decode($this->check_goodsInfo($order_goods),true);
			if ($goods_check_result['ret'] != "1") {
				$return['return']['ret'] = $goods_check_result['ret'];
				$return['return']['msg'] = $goods_check_result['msg'];
				return json_encode($return);
			}
			$store_check_result = json_decode($this->check_storeInfo($store_info),true);
			if ($store_check_result['ret'] != "1") {
				$return['return']['ret'] = $store_check_result['ret'];
				$return['return']['msg'] = $store_check_result['msg'];
				return json_encode($return);
			}
			/*----------------------------------非空信息以及有效性信息验证结束--------------------------------------------*/
			 	
			/*----------------------------------店铺信息处理开始-----------------------------------------------------*/
			$store_deal_info = $this->deal_storeInfo($cooperate_id,$store_info);
			if ($store_deal_info['ret'] != "1") {
				$return['return']['ret'] = $store_deal_info['ret'];
				$return['return']['msg'] = $store_deal_info['msg'];
				return json_encode($return);
			}
			$store_info_mss = $store_deal_info['info'];
			$store_info_mss['partner_store_id'] = $store_info['seller_id'];
			/*---------------------------------店铺信息处理结束------------------------------------------------------*/
			/*---------------------------------用户信息处理开始-----------------------------------------------------*/
			$custom_info['phone_mob'] = $phone_mob;
			$custom_info['phone_tel'] = $phone_tel;
			$custom_info['city'] = $city;
			$member_deal_info = $this->deal_customInfo($custom_info);
			if ($member_deal_info['ret'] != "1") {
				$return['return']['ret'] = $member_deal_info['ret'];
				$return['return']['msg'] = $member_deal_info['msg'];
				return json_encode($return);
			}
			$member_info_mss = $member_deal_info['info'];
			/*----------------------------------用户信息处理结束--------------------------------------------------------*/
			/*----------------------------------商品信息处理开始--------------------------------------------------------*/
			/*先处理配菜，将配菜也生成菜品，goods_mark带上“配 xxx”字样，处理完毕，将配菜信息清空*/
			foreach ($order_goods as $it_k=>$it_v){
				if ($it_v['garnish'] && $it_v['garnish']= array($it_v['garnish'])){
					foreach ($it_v['garnish'] as $gar_v){
						if( $gar_v['goods_name'] && (isset($gar_v['price'])) && ( abs($gar_v['quantity']) != 0 ) && $gar_v['specification'] ){
							foreach ($gar_v as $val_k=>$val_v){
								$val_v['goods_remark'] = '配 '.$it_v['goods_name'];
								$val_v['garnish'] = array();
								$val_v['invoice_title'] = $invoice_title;
								$order_goods[] = $val_v;
							}
						}
					}
					$order_goods[$it_k]['garnish'] = array();
				}else {
					$order_goods[$it_k]['invoice_title'] = $invoice_title;
				}
			}
			$goods_deal_info = $this->deal_goodsInfo($cooperate_id,$order_goods,$store_info_mss);
			if ($goods_deal_info['ret'] != "1") {
				$return['return']['ret'] = $goods_deal_info['ret'];
				$return['return']['msg'] = $goods_deal_info['msg'];
				return json_encode($return);
			}
			$order_goods = $goods_deal_info['info'];
			if ($goods_deal_info['goods']) {
				$return['return']['goods'] = $goods_deal_info['goods'];
			}
			if (!$order_goods) {
				$return['return']['ret'] = "-12";
				$return['return']['msg'] = "其它错误";
				return json_encode($return);
			}
			/*----------------------------------商品信息处理结束--------------------------------------------------------*/
			/*----------------------------------其它冗余订单信息处理开始--------------------------------------------------------*/
			foreach ($order_goods as $g_k=>$g_v){
				$orderInfo['goods_amount'] += $order_goods[$g_k]['subtotal'];
				$orderInfo['pack_fee'] += $order_goods[$g_k]['pack_fee'];
			}
			/*快递费设置:饿了么和美团目前的快递费都为0,其余的传多少是多少*/
			if (in_array($cooperate_id,array('100001','100005'))){
				$orderInfo['shipping_fee'] = 0;
			}else{
				if($json_info['shipping_fee']){
					$orderInfo['shipping_fee'] =$json_info['shipping_fee'];
				}else {
					$orderInfo['shipping_fee'] = 0;
				}
			}
			/*餐厅管理费设置*/
			if (!in_array($cooperate_id,array('100001'))){
				$orderInfo['management_fee'] = 0;
			}else{
				$management_info_store_sql = "SELECT * FROM `ecm_managementfee` WHERE `cooperate_key`=".$cooperate_id.' AND `store_id`='.$store_info_mss['store_id'];
				$management_info_cooperate_sql = "SELECT * FROM `ecm_managementfee` WHERE `cooperate_key`=".$cooperate_id.' AND `store_id`=0';
				$management_info_store = $this->_storeModel->getInfo($management_info_store_sql);
				$management_info_cooperate = $this->_storeModel->getInfo($management_info_cooperate_sql);
				if ($store_info_mss['visibility'] == 'Y'){//可见餐厅
					if ( ($management_info_store['payment_term'] == 0) || (!($management_info_store)) ){
						$orderInfo['management_fee'] = 0;
					}elseif ( ($management_info_store) && ($management_info_store['payment_term'] == 1) ) {
						$orderInfo['management_fee']  = $management_info_store['management_fee'];
					}else {
						$orderInfo['management_fee'] = 0;
					}
				}else {
					if ($management_info_store){
						$orderInfo['management_fee']  = $management_info_store['management_fee'];
					}else {
						$orderInfo['management_fee']  = $management_info_cooperate['management_fee'];
					}
				}
			}
			/*----------------------------------其它冗余订单信息处理结束--------------------------------------------------------*/
			/*----------------------------------转为本地数据开始-----------------------------------------------*/
			$orderInfo['store_info'] = $store_info_mss;
			$orderInfo['member_info'] = $member_info_mss;
			$orderInfo['goods_info'] = $order_goods;
			$orderInfo['add_time'] = ($add_time) ? $add_time : '';
			$orderInfo['request_time'] = ($request_time) ? $request_time : '';
			$orderInfo['expectmeal_time'] = ($expectmeal_time) ? $expectmeal_time : '';
			$orderInfo['remark'] = ($remark) ? $remark : '';
			$orderInfo['invoice_type'] = ($invoice_title) ? 2 : 0;
			$orderInfo['invoice_title'] = ($invoice_title) ? $invoice_title : 0;
			$orderInfo['payment_name'] = ($payment_name) ? $payment_name : '货到付款';
			$orderInfo['if_pay'] = $if_pay;
			/*----------------------------------转为本地数据结束-----------------------------------------------*/
			if (!$orderInfo) {
				$return['return']['ret'] = "-12";
				$return['return']['msg'] = "其它错误";
				return json_encode($return);
			}
			if ($orderInfo && is_array($orderInfo)){
				$data['return'] = $return['return'];
				$goods_item = $orderInfo['goods_info'];
				$store_item = $orderInfo['store_info'];
				$buyer_item = $orderInfo['member_info'];
				$cooperate = $return['return']['cooperate_id'];
				$cooperate_order_id = $return['return']['partner_order_id'];
				$goods_amount = $orderInfo['goods_amount'];
				$shipping_fee = $orderInfo['shipping_fee'];
				$pack_fee = $orderInfo['pack_fee'];
				$management_fee = $orderInfo['management_fee'];
				$invoice_type = $orderInfo['invoice_type'];
				$order_invoice_title = $orderInfo['invoice_title'];
				$order_request_time = $orderInfo['request_time'];
				$order_add_time = $orderInfo['add_time'];
				$order_expectmeal_time = $orderInfo['expectmeal_time'];
				$order_remark = $orderInfo['remark'];
				$order_payment_name = $orderInfo['payment_name'];
				$order_if_pay = $orderInfo['if_pay'];
				foreach ( $goods_item as $gi_k=>$gi_v){
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['user_id'] = $buyer_item['user_id'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['session_id'] = '';
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['store_id'] = $store_item['store_id'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['goods_id'] = $gi_v['goods_id'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['partner_goods_id'] = $gi_v['partner_goods_id'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['goods_name'] = $gi_v['goods_name'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['spec_id'] = $gi_v['spec_id'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['specification'] = $gi_v['specification'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['price'] = $gi_v['price'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['quantity'] = $gi_v['quantity'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['goods_image'] = $gi_v['goods_image'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['summary'] = $gi_v['summary'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['packing_fee'] = $gi_v['packing_fee'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['store_name'] = $store_item['store_name'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['subtotal'] = $gi_v['subtotal'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['pack_fee'] = $gi_v['pack_fee'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['goods_remark'] = $gi_v['goods_remark'];
					$insert_data[$store_item['store_id']]['goods'][$gi_k]['discount'] = $gi_v['discount'];
					$insert_data[$store_item['store_id']]['goods_amount'] = $goods_amount;
					$insert_data[$store_item['store_id']]['pack_fee'] = $pack_fee ;
					$insert_data[$store_item['store_id']]['shipping_id'] = '';
					$insert_data[$store_item['store_id']]['shipping_name'] = '';
					$insert_data[$store_item['store_id']]['shipping_fee'] = $shipping_fee;
					$insert_data[$store_item['store_id']]['postscript'] = $order_remark;
					//$insert_data[$store_item['store_id']]['timing'] = 1;
					$time = time();
					$insert_data[$store_item['store_id']]['add_time'] = ($order_add_time) ? $order_add_time : $time;
					$new_request_time = ($order_add_time) ? ($order_add_time + 50 * 60 ) : ($order_add_time + 50 * 60);
					$insert_data[$store_item['store_id']]['request_time'] = ($order_request_time) ? $order_request_time : $new_request_time;
					$insert_data[$store_item['store_id']]['expectmeal_time'] = $order_expectmeal_time;
					$insert_data[$store_item['store_id']]['invoice_type'] = $invoice_type;
					$insert_data[$store_item['store_id']]['invoice_title'] = $order_invoice_title;
					$insert_data[$store_item['store_id']]['remark1'] = '';
					$insert_data[$store_item['store_id']]['order_type'] = 4;
					$insert_data[$store_item['store_id']]['from_partner'] = $cooperate;
					$insert_data[$store_item['store_id']]['partner_order_id'] = $cooperate_order_id;
					$insert_data[$store_item['store_id']]['management_fee'] = $management_fee;
					$insert_data[$store_item['store_id']]['payment_name'] = $order_payment_name;
					$insert_data[$store_item['store_id']]['if_pay'] = $order_if_pay;
				}
				$data['order'] = $insert_data;
				$data['member_info'] = $buyer_item;
				$data['store_info'] = $store_item;
			}
		}
		return json_encode($data);
	}	
	function createOrderAction($data){
		if ($data['order'] && ($data['return']['ret']) ){
			$return['order_id'] = "0";
			if ($data['return']['goods']) {
				$return['goods'] = $data['return']['goods'];
			}
			$order_data = $data['order'];
			$user_data = $data['member_info'];
			$store_data = $data['store_info'];
			foreach ($order_data as $o_k=>$o_v){
				$order_id = $this->mss_insert_order($o_v, $user_data['region_id'], $user_data,$store_data);
				if ($order_id){
					$return['order_id'] = $order_id;
					$return['ret'] = "1";
					$return['msg'] = "订单插入成功";
				}else{
					$return['ret'] = "-12";
					$return['msg'] = "其它错误";
				}
			}
		}else{
			$return['ret'] = $data['return']['ret'];
			$return['msg'] = $data['return']['msg'];
		}
		return json_encode($return);
	}
	/**
	 * 传入手机号，返回验证结果
	 * 验证返回值与规则说明：
	 * -------------------------
	 * | 返回值   | 规则
	 * -------------------------
	 * |  1   |是正常的手机号
	 * -------------------------
	 * | -1   |强制转换类型后值为空
	 * -------------------------
	 * | -2   |手机号长度不为11位
	 * -------------------------
	 * | -3   |不是手机号
	 * -------------------------
	 * @param int/string $phone
	 * @return string
	 */
	function check_phone($phone){
		$phone = (string)$phone;
		//匹配一般手机号(以“13”、“15”或“18”开头的11位纯数字)
		$phone_str = "/^1[3,4,5,8]\d{9}$/";
		if (!$phone){
			return "-1";
		}else{
			if(strlen($phone) == "11"){
				if((preg_match($phone_str,$phone))){
					return "1";
				}else{
					return "-3";
				}
			}else{
				return "-2";
			}
		}
	}
	/**
	 * 如果返回值为true说明是空数据，如果返回false说明不是空数据
	 * @param array $info
	 * @return boolean
	 */
	function check_exist($info){
		if (!is_array($info)){
			return true;
		}
		$infos = array_filter($info,array($this,"check_empty"));
		if (count($info) != count($infos)) {
			return true;
		}
		return false;
	}
	/**
	 * 判断一个字段值是否为空，去掉false，FALSE，null以及“”的值
	 * @param any_type $val
	 * @return boolean
	 */
	function check_empty($val){
		if (in_array($val, array("FALSE","NULL","","TRUE","false","true","true")) || empty($val)){
			return false;
		}
		return true;
	}
	
	function check_customInfo($custom_info){
		$return['ret'] = "1";
		$return['msg'] = "用户信息无误";
		$return['info'] = array(
				"phone_mob" => "",
				"phone_tel" => ""
		);
		/* 买家信息验证
		 * ①、验证信息是否完整；
		* ②、验证信息是否有效，并处理无效信息
		*/
		//验证用户信息完整的规则：phone_mob和phone_tel必须有其一，而且地址必须存在
		if ( ( $this->check_exist(array($custom_info['phone_mob'])) && $this->check_exist(array($custom_info['phone_tel'])) ) || $this->check_exist(array($custom_info['address']))){
			$return['ret'] = "-6";
			$return['msg'] = "用户信息缺失";
		}else{
			/*用户信息不缺失,验证手机号*/
			/* 验证规则
			 * 一、如果存在phone_mob,验证是否为11位数值;
			* ①:若是11位,验证是否符合手机号验证规则,若符合规则,继续程序;若不符合,开始执行phone_tel
			* ②:若不是11位,开始执行phone_tel
			* 二、如果不存在phone_mob,判断是否存在phone_tel,若存在,执行程序,若不存在,用户信息缺失
			* phone_tel直接正常运行
			* */
			$phone_mob_validate = $this->check_phone($custom_info['phone_mob']);
			if ($phone_mob_validate == "-1"){//phone_mob为空
				if ($custom_info['phone_tel']){//phone_tel不为空
					$return['info'] = array(
						"phone_mob" => $custom_info['phone_tel'],
						"phone_tel" => $custom_info['phone_tel'],
					);
				}else {//phone_tel为空
					$return['ret'] = "-6";
					$return['msg'] = "用户信息缺失";
				}
			}elseif($phone_mob_validate == "-2"){//phone_mob不为空，但是长度不是11位
				if ($custom_info['phone_tel']){//tel不为空
					$return['info'] = array(
						"phone_mob" => $custom_info['phone_tel'],
						"phone_tel" => $custom_info['phone_tel']
					);
				}else {//tel为空
					$return['ret'] = "-10";
					$return['msg'] = "非法联系方式";
				}
			}elseif ($phone_mob_validate == "-3"){//phone_mob不为空，长度11位,不满足手机号的规则
				if ($custom_info['phone_tel']){//tel不为空
					$return['info'] = array(
						"phone_mob" => $custom_info['phone_tel'],
						"phone_tel" => $custom_info['phone_tel'],
					);
				}else {//tel为空
					$return['ret'] = "-10";
					$return['msg'] = "非法联系方式";
				}
			}else{//phone_mob是有效的手机号
				$return['info'] = array(
						"phone_mob" => $custom_info['phone_mob'],
						"phone_tel" => $custom_info['phone_tel']
				);
			}
		}
		return json_encode($return);
	}
	
	function check_goodsInfo($order_goods){
		/* 菜品信息验证
		 * 验证菜品信息是否完整；
		*/
		$return['ret'] = "1";
		$return['msg'] = "菜品信息无误";
		if ($this->check_exist(array($order_goods)) || (!is_array(current($order_goods))) ) {
			$return['ret'] = "-7";
			$return['msg'] = "菜品信息缺失";
		}else {
			foreach($order_goods as $og_k=>$og_v){
				//goods_name为空，price绝对值为0，quantity绝对值为0，specification为空，菜品信息缺失
				if ( $this->check_exist(array($og_v['goods_name'],$og_v['specification'])) || (!isset($og_v['price'])) || ( abs($og_v['quantity']) == 0 )) {
					$return['return']['ret'] = "-7";
					$return['return']['msg'] = "菜品信息缺失";
				}
			}
		}
		return json_encode($return);
	}
	function check_storeInfo($store_info){
		/* 店铺信息验证
		 * 验证店铺信息是否完整；
		*/
		$return['ret'] = "1";
		$return['msg'] = "店铺信息无误";
		if ($this->check_exist(array($store_info,$store_info['seller_name'],$store_info['address']))) {
			$return['ret'] = "-8";
			$return['msg'] = "店铺信息缺失";
		}
		return json_encode($return);
	}
	function deal_storeInfo($cooperate_id,$store_info){
		$return['ret'] = "1";
		$return['msg'] = "店铺信息无误";
		$return['info'] = array();
		$store_info_mss = StoreController::check_store_name($cooperate_id,$store_info['seller_name']);
		if ($store_info_mss){//存在店铺
			$store_id = $store_info_mss['store_id'];
			if(!$this->_storeModel->getStatus($store_id)){//店铺是否有效
				$return['ret'] = "-9";
				$return['msg'] = "店铺未开业";
			}else {
				$return['info'] = $store_info_mss;
			}
		}else {//不存在店铺,创建店铺
			$store_info_mss = StoreController::create_store($cooperate_id,$store_info);
			if(!$store_info_mss){//创建店铺失败
				$return['ret'] = "-12";
				$return['msg'] = "其它错误";
			}else {
				$return['info'] = $store_info_mss;
			}
		}
		return $return;
	}
	
	/**
	 * 对输入的字符串进行匹配，取出其中包含的建筑名称或别称的建筑ID
	 * @author Lessbom
	 * @param  string    $address      要进行匹配的地址
	 * @return array                   返回建筑ID数组的匹配结果
	 */
	function match_building_from_address($address)
	{
		if (!$address) return false;
		$build_sql_conditions = ($address) ? ' `bd_name` LIKE "'.$address.'" OR `bd_alias1` LIKE "'.$address.'" OR `bd_alias2` LIKE "'.$address.'" OR `bd_alias3` LIKE "'.$address.'"' : "";
		$bd_ids = $this->_buildingModel->query_all_info($build_sql_conditions." LIMIT 1");
		return $bd_ids;
	}
	
	function saveCoop($info,$belong,$coop){
		$partner = $coop;
		if ($belong != "goods"){
			$coop_itemid = $info['coop_itemid'];
			$local_itemid = $info['local_itemid'];
			if (empty($coop_itemid) || empty($local_itemid) || empty($partner) || empty($belong)){
				return false;
			}else {
				$coopInfo = $this->_cooplocalModel->query_info("`coop_itemid`='".$coop_itemid."' AND `local_itemid`='".$local_itemid."' AND `belong`='$belong' AND `partner`=$partner ");
				if (!$coopInfo){
					$inCoop = array(
							"coop_itemid"=>	$coop_itemid,
							"local_itemid"=>$local_itemid,
							"belong"=>$belong,
							"partner"=>$partner
					);
					$this->_cooplocalModel->insert("ecm_coop_local", $inCoop);
				}
			}
		}else{
			foreach ($info as $g_k => $g_v) {
				if (empty($g_v['goods_id']) || empty($g_v['partner_goods_id']) || empty($partner) || empty($belong)){
					continue;
				}else{
					$goodsInfo = $this->_cooplocalModel->query_info("`coop_itemid`='".$g_v['partner_goods_id']."' AND `local_itemid`='".$g_v['goods_id']."' AND `belong`='$belong' AND `partner`=$partner ");
					if ($goodsInfo){
						continue;
					}else{
						$inGoodsCoop = array(
								"coop_itemid"=>	$g_v['partner_goods_id'],
								"local_itemid"=>$g_v['goods_id'],
								"belong"=>$belong,
								"partner"=>$partner
						);
						$this->_cooplocalModel->insert("ecm_coop_local", $inGoodsCoop);
					}
				}
			}
		}
	}
	
	/**
	 * @desc 新收货地址
	 * @param array $d 包含用户ID和用户所选地区ID的数组
	 *              $d['user_id']   用户ID
	 *              $d['region_id'] 用户所选地区ID
	 * */
	function set_address($d = array())
	{
		if (empty($d['region_id']) || empty($d['user_info']) || empty($d['address']) ){
			return false;
		}
		/* 生成用户选中所在区域的完整字符串 */
		//因为$d['region_id']即用户当前的area_id值一般为大区ID，所以取上级地区时不需要考虑region_relation表中的“大区-子区”关系，可以直接使用$region_mod的get_parents()函数来获取上级数据
		$parent_region_sql = "`region_id`=".$d['region_id'];
		$sql = "SELECT `parent_id` FROM `ecm_region` WHERE `region_id`=".$d['region_id'];
		$parent_ids_sql = "`region_id` IN( ".$sql." )";
		$parent_region_ids = $this->_regionModel->query_all_info($parent_region_sql);
		$parent_id_arr = $this->_regionModel->query_all_info($parent_ids_sql);
		if ($parent_region_ids && $parent_id_arr ){
			$current_parent_id_arr = current($parent_id_arr);
			$current_parent_region_ids = current($parent_region_ids);
			$parent_ids = array($current_parent_id_arr['parent_id'],$current_parent_region_ids['parent_id'],$d['region_id']);
			$region_sql = " `region_id` ".$this->_regionModel->db_create_in(array_values($parent_ids));
			$regions_arr =  $this->_regionModel->query_all_info($region_sql);
			if (!$regions_arr){
				return false;
			}
			foreach ($regions_arr as $a_k=>$a_v){
				$regions[$a_v['region_id']] = $regions_arr[$a_k];
			}
			//生成字符串，按照$parent_ids的顺序来拼接所在地区字符串,如：北京市海淀区五道口
			$region_name = '';
			foreach ($parent_ids as $region_id) {
				$regions[$region_id] && $region_name .= $regions[$region_id]['region_name'];
			}
			//初始化数组，将一些不需要在此判断的数组先存入数组
			$addr_arr = array(
			'user_id' => intval($d['user_info']['user_id'] ),
			'zipcode' => '',
			'consignee' => addslashes($d['user_info']['real_name']),
			'phone_tel' => addslashes($d['user_info']['phone_tel'])  ? addslashes($d['user_info']['phone_tel']) : addslashes($d['user_info']['user_name']),
			'phone_mob' => addslashes($d['user_info']['phone_mob']) ? addslashes($d['user_info']['phone_mob']) : addslashes($d['user_info']['user_name']),
			'region_id' => $d['region_id'],
			'region_name' => $region_name,
			'address'   => $d['address'],
			);
	
			//因为$addr_arr之后要用于对address的插入，所以查询出的bd_id必须单独处理
			$addr_bd_id = 0;
	
			//对地址进行匹配，如地址能够匹配(即包含)数据库中此区域的某建筑，则订单的bd_id使用此建筑的建筑ID，如未能找到匹配，则bd_id设为0，bd_id为0时具体建筑信息将交由客服来向用户询问。
			$building_ids = $this->match_building_from_address($addr_arr['address']);
			if(!empty($building_ids) )
			{
				$bd_id = current($building_ids);
				if (!empty($bd_id)){
					$addr_bd_id = $bd_id['bd_id'];
				}
			}
	
			$insert_addr_id_sql = "`user_id`=".$addr_arr['user_id']." AND `region_id`=".$addr_arr['region_id']." AND `region_name`='".$addr_arr['region_name']."' AND `address`='".$addr_arr['address']."' GROUP BY `addr_id`";
			$insert_addr_info = $this->_regionModel->query_all_info($insert_addr_id_sql);
			if($insert_addr_info && is_array($insert_addr_info)){
				$current_insert_addr_info = current($insert_addr_info);
				$insert_addr_id = $current_insert_addr_info['last_addr_id'];
			}
	
			if(!$insert_addr_id)
			{
				$insert_addr_id = $this->_regionModel->insert('ecm_address', $addr_arr);
			}
			//确认$insert_addr_id存在，即此订单的地址信息已包含在address表中，将建筑ID和address_id放入数组，并将数组放入类变量，以便index()方法调用
			if($insert_addr_id)
			{
				$addr_arr['bd_id']          = $addr_bd_id;
				$addr_arr['insert_addr_id'] = $insert_addr_id;
				return $addr_arr;
			}else {
				return false;
			}
		}else {
			return false;
		}
	
	}
	
	function deal_customInfo($custom_info){
		$return['ret'] = "1";
		$return['msg'] = "用户信息处理无误";
		$return['info'] = array();
		//用户处理
		$buyer_name = $custom_info['buyer_name'];
		$consignee = ($custom_info['consignee']) ? $custom_info['consignee'] : $buyer_name ;
		$address = str_replace("'","\\'",$custom_info['address']);
		$member_info_mss = $this->_memberModel->get_user_info($custom_info['phone_mob'],$consignee,$custom_info['phone_mob'],$custom_info['phone_tel']);
		if (!$member_info_mss){
			$return['ret'] = "-12";
			$return['msg'] = "其它错误";
		}else{
			/*将原来由从本地数据库判断所属区域改为由地图API返回*/
			/*$building_ids = $class_order->match_building_from_address($address[$i_k]);
			 if(!empty($building_ids)){
			foreach ($building_ids as $building_id){
			$regions[]['region_id'] = $building_id['region_id3'];
			}
			}
			if ($regions) $region_id = $regions[0]['region_id'];
			$region_id = ($region_id) ? $region_id : 162; */
			$address_json_info = file_get_contents("http://www.meishisong.cn/map_api/address?address=".$address."&city=".$custom_info['city']);
			$opts = array(
					"http"=>array(
							"method"=>"GET",
							"timeout"=>4
					)
			);
			$context = stream_context_create($opts);
			$fp = file_get_contents("http://www.meishisong.cn/map_api/address.php?address=星科大厦A座0410",false,$context);
			fpassthru($fp);
			$region_info = $this->_regionModel->get("178");//空白区域
			if ($fp == false) {
				$region_id = $region_info['region_id'];
			}else {
				$address_info = json_decode($address_json_info,true);
				$regions = explode(',', $address_info['region']);
				if (count($regions) > 1){
					/*原来是小的区域ID，后来改为 空白区域 */
					//$region_id = $regions[0];
					$region_id = $region_info['region_id'];
				}else {
					$region_id = $address_info['region'];
				}
			}
// 			$address_info = json_decode($address_json_info,true);
// 			$region_info = $this->_regionModel->get("178");//空白区域
// 			if ($address_info['status'] == 'true'){//匹配成功
// 				$regions = explode(',', $address_info['region']);
// 				if (count($regions) > 1){
// 					/*原来是小的区域ID，后来改为 空白区域 */
// 					//$region_id = $regions[0];
// 					$region_id = $region_info['region_id'];
// 				}else {
// 					$region_id = $address_info['region'];
// 				}
// 			}else {//匹配未成功
// 				$region_id = $region_info['region_id'];
// 			}
			//保存地址信息
			$member_address_info_mss = $this->set_address(array('region_id' => $region_id , 'user_info' => $member_info_mss,'address' => $address));
			if (!$member_address_info_mss){
				$return['ret'] = "-12";
				$return['msg'] = "其它错误";
			}
		}
		if ($return['ret'] != "1") {
			return $return;
		}
		$member_info_mss['address'] = $address;
		$member_info_mss['partner_user_id'] = $custom_info['buyer_id'];
		$member_info_mss['region_id'] = $region_id;
		$member_info_mss['bd_info'] = $member_address_info_mss;
		$return['info'] = $member_info_mss;
		return $return;
	}
	
	function deal_goodsInfo($cooperate_id,$order_goods,$store_info_mss)
	{
		$return['ret'] = "1";
		$return['msg'] = "菜品信息处理无误";
		$return['goods'] = array();
		$return['info'] = array();
		/*处理重新生成的商品*/
		foreach ($order_goods as $g_k=>$g_v){
			$invoice_title = $g_v['invoice_title'];
			$goods_set_sql = '`goods_name`="'.$g_v['goods_name'].'" AND `store_id`='.$store_info_mss['store_id'];
			$goods_info_mss = $this->_goodsModel->query_info($goods_set_sql);
			/*
			 * 不存在商品，创建商品，在创建商品之前创建商品分类"新增菜单"
			*/
			if (empty($goods_info_mss)){
				$check_gcategory = $this->_storeModel->check_gcategory($store_info_mss['store_id'],'新增菜单');
				if (!$check_gcategory){
					$return['ret'] = "-12";
					$return['msg'] = "其它错误";//菜品分类添加失败
					return $return;
				}else {
					$cate_id = $check_gcategory['cate_id'];
				}
				$time = time();
				/*有店铺，有 新增菜单 分类，该添加菜品了*/
				$goods_data = array(
						"goods"=>array(
								"store_id"=>$store_info_mss['store_id'],
								"goods_name"=>str_replace("'","\\'",$g_v['goods_name']),
								"price"=>$g_v['price'],
								"description"=>"",
								"cate_id"=>"1",
								"cate_name"=>"常规热菜20分钟",
								"brand"=>"",
								"if_show"=>"0",
								"last_update"=>$time,
								"recommended"=>0,
								"tags"=>"",
								"discount_price"=>"",
								"is_packing"=>"0",
								"summary"=>"",
								"nreceipt_discount"=>"1",
								"receipt_discount"=>"1",
								"spec_name_1"=>$g_v['specification'],
								"type"=>"material",
								"closed"=>0,
								"add_time"=>$time,
						),
						"specs"=>array(
								"price"=>$g_v['price'],
								"stock"=>0,
								"sku"=>"",
								"spec_id"=>0,
						),
						"cates"=>array(
								"cate_id"=>$cate_id,
						),
						"goods_file_id"=>array(),
						"desc_file_id"=>array()
				);
				$goods_id = $this->_goodsModel->add_goods($goods_data);
				if (!$goods_id){
					$return['ret'] = "-12";
					$return['msg'] = "其它错误";//菜品添加失败
					return $return;
				}
				$goods_sql = 'SELECT * FROM `ecm_goods` WHERE `goods_id`='.$goods_id;
				$goods_info_mss = $this->_goodsModel->getInfo($goods_sql);
			}else {
				/* 如果存在该商品,判断是否为可见店铺
				 * ①如果为可见店铺,是公共店铺,判断菜品价格减去包装费的价格;
				* 一、如果价格高于美食送价格，价格不更新，进入系统
				* 二、如果价格低于美食送价格，返回异常，信息："xxx菜品价格不符"
				* ②如果不是可见店铺,判断价格:
				* 如果价格不同，更新价格
				* */
				$partner_packing_fee = ($g_v['packing_fee']) ? $g_v['packing_fee'] : 0;
				$partner_price = ($g_v['price']) ? $g_v['price'] : 0;
				if ($store_info_mss['visibility'] == 'Y'){
					if(strpos($g_v['goods_name'],"立减优惠")!=false && in_array($cooperate_id,array('100001'))){
						$this->api_db->query("UPDATE `ecm_goods` SET `price`=".$g_v['price'].",`if_show`='0',`closed`=0 WHERE `goods_id`=".$goods_info_mss['goods_id']);
					}else{
						if (in_array($cooperate_id, array("100005","100006","100007","100008"))) {
							if( abs( $partner_price - $partner_packing_fee ) < abs($goods_info_mss['price']) ){
								$return['ret'] = "-11";
								$return['msg'] = "菜品《".$g_v['goods_name']."》价格低于美食送系统价格,第三方价格:".$g_v['price'].",美食送价格:".$goods_info_mss['price'];//菜品价格不符
								return $return;
							}
						}else {
							if ($cooperate_id != "100010"){
								if( abs($partner_price) < abs($goods_info_mss['price']) ){
									$return['ret'] = "-11";
									$return['msg'] = "菜品《".$g_v['goods_name']."》价格低于美食送系统价格,第三方价格:".$g_v['price'].",美食送价格:".$goods_info_mss['price'];//菜品价格不符
									return $return;
								}
							}else {
								if( abs($partner_price) != abs($goods_info_mss['price']) ){
									$return['goods'][] = array(
										"goods_name"=>'"'.$g_v['goods_name'].'"',
										"partner_price"=>'"'.$g_v['price'].'"',
										"mss_price"=>'"'.$goods_info_mss['price'].'"',
									);
								}
							}
						}
					}
				}else {
					if($g_v['price'] != $goods_info_mss['price']){
						$this->_goodsModel->update('ecm_goods',array("price"=>$g_v['price']),'`goods_id`='.$goods_info_mss['goods_id']);
					}
				}
			}
			$order_goods[$g_k]['partner_goods_id'] = $g_v['goods_id'];
			$order_goods[$g_k]['goods_id'] = $goods_info_mss['goods_id'];
			$order_goods[$g_k]['spec_id'] = $goods_info_mss['default_spec'];
			$order_goods[$g_k]['goods_image'] = ($goods_info_mss['default_image']) ? $goods_info_mss['default_image'] : 'public/system/default_goods_image.jpg';;
			$order_goods[$g_k]['summary'] = $goods_info_mss['summary'];
			/*菜品包装费计算*/
			/*饿了么,淘宝目前的包装费统一为0,美团走趣活的包装费,其余的有传多少是多少*/
			if (in_array($cooperate_id,array('100001','100006'))){
				$order_goods[$g_k]['packing_fee'] = 0;
				$order_goods[$g_k]['pack_fee'] = 0;
			}elseif (in_array($cooperate_id,array('100005'))){
				$order_goods[$g_k]['packing_fee'] = $goods_info_mss['packing_fee'];
				$order_goods[$g_k]['pack_fee'] = ($goods_info_mss['packing_fee'] * $g_v['quantity']);
			}else{
				$goods_packing_fee = ($g_v['packing_fee']) ? $g_v['packing_fee'] : 0;
				$order_goods[$g_k]['packing_fee'] = $goods_packing_fee;
				$order_goods[$g_k]['pack_fee'] = ($goods_packing_fee * $g_v['quantity']);
			}
			$order_goods[$g_k]['subtotal'] = ($g_v['price'] * $g_v['quantity']);
			/*菜品当前折扣计算*/
			if ($g_v['discount']){
				$order_goods[$g_k]['discount'] = $g_v['discount'];
			}else{
				$goodsentty = $this->_goodsModel->getInfo("SELECT `receipt_discount`,`nreceipt_discount` FROM `ecm_goods` WHERE `goods_id`=".$goods_info_mss['goods_id']);
				if (!$goodsentty) {
					$order_goods[$g_k]['discount'] = "1";
				}else{
					$order_goods[$g_k]['discount'] = ($invoice_title) ? $goodsentty['receipt_discount'] : $goodsentty['nreceipt_discount'];
				}
			}
		}
		$return['info'] = $order_goods;
		return $return;
	}
	
	/**
	 *
	 * 插入订单
	 * */
	function mss_insert_order($data , $region_id, $user_info,$store_info )
	{
		if (empty($data) || empty($region_id) || empty($user_info) || empty($store_info))return false;
		/*----------本地/服务商ID信息同步开始----------*/
		if ($store_info['partner_store_id']) {
			$coop_local_storeInfo = array(
					"coop_itemid"=>	$store_info['partner_store_id'],
					"local_itemid"=>$store_info['store_id'],
			);
			$this->saveCoop($coop_local_storeInfo,"store",$data['from_partner']);
		}
		if ($user_info['partner_user_id']) {
			$coop_local_memberInfo = array(
					"coop_itemid"=>	$user_info['partner_user_id'],
					"local_itemid"=>$user_info['user_id'],
			);
			$this->saveCoop($coop_local_memberInfo,"member",$data['from_partner']);
		}
		$coop_local_goodsInfo = $data['goods'];
		$this->saveCoop($coop_local_goodsInfo,"goods",$data['from_partner']);
		/*----------本地/服务商ID信息同步结束----------*/
		$order_arr = array();
		$time = array(
				"request_time" => $data['request_time'],
				"add_time" => $data['add_time'],
		);
		/* 默认都是待付款 */
		$order_status = ORDER_COMMITED;
		$data['goods_amount'] >= 200 && $data['shipping_fee']=0;
		//插入业务主表
		$om_id=$this->_baseModel->insert("ecm_ordermain",array('create_date'=>$data['add_time'],'user_id'=>$user_info['user_id']));
		if (!$om_id)return false;
		/* 返回基本信息 */
		$insert_arr =  array(
				'order_sn'      =>  $this->_gen_order_sn(),
				'type'          =>  'material',
				'extension'     =>  'normal',
				'seller_id'     =>  $store_info['store_id'],
				'seller_name'   =>  addslashes($store_info['store_name']),
				'buyer_id'      =>  $user_info['user_id'],
				'buyer_name'    =>  addslashes($user_info['user_name']),
				'buyer_email'   =>  $user_info['email'],
				'status'        =>  $order_status,
				'add_time'      =>  $data['add_time'],
				'discount'      =>  $store_info['discount'],
				'tax'      		=>  $store_info['tax'],
				'anonymous'     =>  0,
				'postscript'    =>  addslashes(trim($data['postscript'])),
				'payment_name'  =>  $data['payment_name'],
				'invoice_type'  => $data['invoice_type'],
				'invoice_title' => addslashes($data['invoice_title']),
				'om_id'			=> $om_id,
				'remark1' 		=> addslashes($data['remark1']),
				'order_type' 	=> $data['order_type'],
				'from_partner' 	=> $data['from_partner'],
				'partner_order_id' => $data['partner_order_id'],
				'res_confirm'	=>$data['from_partner']=='100006'? 0:1,
				'management_fee'=> ($data['management_fee']) ? $data['management_fee'] : 0,
				'expectmeal_time'=> ($data['expectmeal_time']) ? $data['expectmeal_time'] : '',
				'is_new'  		=> 0,
				'if_pay'=>$data['if_pay']?$data['if_pay']:0,
		);
		if($data['if_pay']){
			$insert_arr['goods_amount']=0;
			$insert_arr['packing_fee']=0;
			$insert_arr['order_amount']=0;
		}else{
			$insert_arr['goods_amount']= ($data['goods_amount']) ? $data['goods_amount'] : 0;
			$insert_arr['packing_fee'] = ($data['pack_fee']) ? $data['pack_fee'] : 0;
			$insert_arr['order_amount'] =  $insert_arr['goods_amount']  + $data['shipping_fee'] + $insert_arr['packing_fee'];
		}
		/* echo "<pre>";
		 print_r($insert_arr);
		echo "<pre>"; die; */
		$order_id = $this->_baseModel->insert("ecm_order",$insert_arr);
		if (!$order_id)return false;
		//插入订单主表后，将当前下单会员的下单次数+1，将此单的下单时间更新到此会员的最后一次下单时间
		$order_num = $user_info['order_num']+1;
		$new_data = array(
				'order_num'       => $order_num,
				'last_order_time' => $insert_arr['add_time'],
				'last_order_region'=> $user_info['region_id'],
		);
		$this->_baseModel->update('ecm_member',$new_data,'`user_id`='.$user_info['user_id']);
		//向合作伙伴会员表中插入数据
		$sql_eleme = 'SELECT * FROM `ecm_partner_member` WHERE `user_id`= '.$user_info['user_id'].' AND `from_partner`='.$data['from_partner'];
		$pm_info = $this->_baseModel->getInfo($sql_eleme);
		if(empty($pm_info)){  //检索不到信息 说明是第一次通过此渠道下单   下单次数为1 最后一次下单时间为 add_time from_partner为空
			$data_1 = array(
			'user_id'      => $user_info['user_id'],
			'from_partner' => $data['from_partner'],
			'order_num'    => 1,
			'last_order_time' =>$insert_arr['add_time'],
			'last_order_region'=> $user_info['region_id'],
			);
			$this->_baseModel->insert("ecm_partner_member",$data_1);
		}else{//说明已经通过此渠道下过单，将下单次数加1 更新 last_order_time
			$pm_order_num = $pm_info['order_num'] + 1;
			$data_2 = array(
					'order_num'=>$pm_order_num,
					'last_order_time' =>$insert_arr['add_time'],
					'last_order_region'=> $user_info['region_id'],
			);
			$this->_baseModel->update('ecm_partner_member',$data_2,'`user_id`='.$user_info['user_id']);
		}
		$tmp_area_info = $this->get_area_info($user_info['region_id']);
		$orderextm_arr  = array(
				'order_id'      => $order_id,
				'consignee'     => $user_info['real_name'],
				'address'       => $user_info['address'],
				'phone_mob'     => $user_info['phone_mob'],
				'phone_tel'     => $user_info['phone_tel'],
				'purchasing_id' => 0,
				'bd_id'         => ($user_info['bd_info']) ? $user_info['bd_info']['bd_id'] : 0,
				'region_id'     => $user_info['region_id'],
				'region_name'   => ($tmp_area_info['region_name']) ? $tmp_area_info['region_name']: $user_info['bd_info']['region_name'],
				'shipping_fee'  => ($data['shipping_fee']) ? $data['shipping_fee'] : 0,
				'shipping_id'   => $data['shipping_id'],
				'shipping_name' => (  (($data['request_time'] - $data['add_time']) >= (50 * 60 )) && (($data['request_time'] - $data['add_time']) <= (60 * 60 )) ) ? '即时送达' : '定时送达',
				'request_time'  => $data['request_time'],
				'addr_id'       => ($user_info['bd_info']) ? $user_info['bd_info']['insert_addr_id'] : 0,
		);
		if($data['if_pay']){
			$orderextm_arr['shipping_fee ']=0;
		}
		$this->_baseModel->insert("ecm_order_extm",$orderextm_arr);
		$mp_id = $this->insert_messagepool($order_id,$data['request_time']);
		if (!$mp_id) return false;
		$this->insert_goods($order_id,$data);
		return $order_id;
	
	}
	function insert_messagepool($order_id , $request_date, $emp_id = 0  )
	{
		if(!$order_id ){
			return false;
		}
		/*插入messagepool消息队列*/
		$parameter['orderid']=$order_id;
		$custid=$this->getcustomerservice($parameter);
		//将一些基础信息放入数组进行准备
		$mp_data = array(
		'mp_type' => TYPE_NEW,
		'mp_createdate' => time(),
		'next_owner'=>$custid,
		);
		if($request_date)
		{
			$mp_data['mp_requestdate'] = $request_date;
		}
		//将订单ID填入准备信息，并插入“员工提醒表”给当区客服添加新提示
		$mp_data['order_id'] = $order_id;
		$insert_id = $this->_baseModel->insert("ecm_messagepool",$mp_data);
		return $insert_id;
	}
	function getcustomerservice($parameter)
	{
		$orderid=$parameter['orderid'];
		if (!$orderid) return false;
		$sql='SELECT c.`emp_id` FROM `ecm_order_extm` a
LEFT JOIN `ecm_employeeregion` b ON a.`region_id`=b.`er_regionid`
LEFT JOIN `ecm_employee` c ON b.`er_empno`=c.`emp_no`
LEFT JOIN `ecm_emprole` d ON d.`emp_no`=b.`er_empno`
WHERE `role_id`=2 and a.`order_id`='.$orderid;
		$empids=$this->_baseModel->getAllInfo($sql);
		$result.=',';
		foreach($empids as $k=>$v)
		{
			$result.=$v['emp_id'].',';
		}
		return $result;
	}
	/**
	 * 区域信息
	 * */
	function get_area_info($area_id)
	{
		$sql = "SELECT * FROM `ecm_region` WHERE `region_id`=".$area_id;
		 
		return     $this->_baseModel->getInfo($sql);
	
	}
	
	
	/**
	 *    生成订单号
	 *
	 *    @author    Garbin
	 *    @return    string
	 */
	function _gen_order_sn()
	{
		/* 选择一个随机的方案 */
		mt_srand((double) microtime() * 1000000);
		$timestamp = time();
		$y = date('y', $timestamp);
		$z = date('z', $timestamp);
		$order_sn = $y . str_pad($z, 3, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	
		$orders = $this->_baseModel->getAllInfo("SELECT * FROM `ecm_order` WHERE `order_sn`=".$order_sn);
		/* $model_order =& m('order');
		 $orders = $model_order->find('order_sn=' . $order_sn); */
		if (empty($orders))
		{
			/* 否则就使用这个订单号 */
			return $order_sn;
		}
	
		/* 如果有重复的，则重新生成 */
		return $this->_gen_order_sn();
	}
	
	/**
	 * @author Lessbom
	 * @desc 插入订单商品
	 *
	 * */
	function insert_goods($order_id,$data)
	{
		if(empty($order_id) || empty($data))return false;
		foreach ($data['goods'] as $k => $goods) {
			$insert_goods = array(
					'order_id' => $order_id,
					'goods_id' => $goods['goods_id'],
					'goods_name' => addslashes($goods['goods_name']),
					'spec_id' => ($goods['spec_id']) ? $goods['spec_id'] : 0,
					'specification' => $goods['specification'],
					'summary' => ($goods['summary']) ? $goods['summary'] : '',
					'price' => $goods['price'],
					'quantity' => $goods['quantity'],
					'goods_image' => ($goods['goods_image']) ? $goods['goods_image'] : '',
					'packing_fee' => ($goods['packing_fee']) ? $goods['packing_fee'] : 0,
					'discount' => ($goods['discount']) ? $goods['discount'] : 1,
					'goods_remark' => addslashes($goods['goods_remark']),
			);
			$this->_baseModel->insert("ecm_order_goods",$insert_goods);
			//更新商品统计
			$this->update_goods_statics($goods);
		}
	}
	/**
	 * @author Lessbom
	 * @desc 更新商品下单次数
	 *
	 * */
	function update_goods_statics($goods)
	{
		/* 更新下单次数 */
		if($goods['quantity'] && $goods['goods_id'])
		{
			$array = array(
				"orders" => "orders+1"
			);
			$this->_baseModel->update("ecm_goods_statistics", $array,"`goods_id`=".$goods['goods_id']);
			return true;
		}else{
			return false;
		}
	}
}
?>