<?php
class SchedulingController extends Yaf_Controller_Abstract
{
	var $_db;
	var $api_sms;
	var $_statusArr;
	var $order_model;
	var $page;
	var $limit;
	var $addgoods_id;

	public function init()
	{
		session_start();
		header("Content-type: text/html; charset=utf-8");
		$this->order_model = new OrderModel();
		// if(!isset($_SESSION['username'])) {
		// 	header("Location: ".WEB_PATH."/member/member/login");
		// }
		$this->getView()->assign('username', $_SESSION['username']);
		$this->getView()->assign('empname', $_SESSION['empname']);
		
		$this->_db = Yaf_Registry::get("api_db");
		$this->_db_lgs = Yaf_Registry::get("api_db_lgs");
		$this->api_sms = Yaf_Registry::get('api_sms');
		if(!empty($_GET)) {
			$_REQUEST = $_GET;
		} else if(!empty($_POST)) {
			$_REQUEST = $_POST;
		}
		// 快递区域
		$parentArea = $this->order_model->getParentArea();	
		$parent_areaList[0] = '全部';
		foreach($parentArea as $areaV) {
			$parent_areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		$this->getView()->assign('parent_areaList', $parent_areaList);

		// $areaArr = get_express_area();
		$areaList[0] = '全部';
		// foreach($areaArr as $areaV) {
		// 	$areaList[$areaV['region_id']] = $areaV['region_name'];
		// }
		$this->getView()->assign('areaList', $areaList);
		// 分页
		include_once(APP_PATH."/application/modules/Store/controllers/Page.php");
		$this->limit = 50;
		$this->page	= isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		// 订单类型
		$this->_statusArr['order_type'] = array(
			'1' => '电话订单',
			'2' => '网络订单',
			'3' => '手机订单',
			'4' => '其他订单',
			'5' => '微信订单',
			'6' => '积分订单',
		);
		$orderStatus = array(
			'0'=>'取消',
			'10'=>'已提交',
			'11'=>'已确认',
			'12'=>'已分配',
			'13'=>'再分配',
			'20'=>'已接受',
			'21'=>'拒绝',
			'22'=>'已取餐',
			'23'=>'已退回',
			'24'=>'已到达目的地',
			'30'=>'异常关闭',
			'31'=>'异常',
			'32'=>'食物损坏',
			'33'=>'收货人未在指定地址',
			'34'=>'餐厅打烊',
			'35'=>'食物售馨',
			'36'=>'其他',
			'50'=>'已完成',
			'-100'=>'已修改'
		);
		$orderTp = $this->_statusArr['order_type'];
		$where = "where store_id in (13489,58169,58172)";
		$Store = $this->order_model->getStoreInfo($where);
		$storeinfo = array();
		foreach ($Store as $k => $v) {
			$storeinfo[$v['store_id']] = $v['store_name'];
		}
		$building = $this->order_model->getBuildingInfo();
		$json_building = json_encode($building);
		$cooperate = $this->order_model->get_cooperate();
		foreach($cooperate as $k=>$v){
			$partner[$v['appkey']] = $v['cooperate_name'];
		}
		$partnerList = $partner;
		$partner[''] = '';
		$json_statusArr = json_encode($this->_statusArr['order_type']);
		$json_orderStatus = json_encode($orderStatus);
		$json_partner = json_encode($partner);
		// 订单关闭原因
		// $close_reason = get_order_close_reason();
		// $g_close_array = $close_reason['data'];
		// $this->getView()->assign('g_close_array', json_encode($g_close_array));
		$this->getView()->assign('app_path', APP_PATH);
		$this->getView()->assign('web_path', WEB_PATH);
		$this->getView()->assign('WEB_SOCKET_IP', WEB_SOCKET_IP_ID);
		$this->getView()->assign('WEB_SOCKET_PORT', WEB_SOCKET_PORT_ID);
		$this->getView()->assign("partner", $partner);
		$this->getView()->assign("partnerList", $partnerList);
		$this->getView()->assign("storeinfo", $storeinfo);
		$this->getView()->assign("json_building", $json_building);
		$this->getView()->assign('json_statusArr', $json_statusArr);
		$this->getView()->assign('json_orderStatus', $json_orderStatus);
		$this->getView()->assign('json_partner', $json_partner);
		$this->getView()->assign("orderStatus", $orderStatus);
		$this->getView()->assign("orderTp", $orderTp);
	}
	
	public function indexAction()
	{
		// $this->getView()->display("crmorder/index");
	}
	
	// 接力订单
	public function relayOrderAction()
	{
		// $lgsInfo = emp_reassign(268568, 46, $_SESSION['uid'], 101);
		// if($_SESSION['username'] == 101207)
			// var_dump($lgsInfo);
		$this->getView()->display("scheduling/relayOrder");
	}
	
	// 未处理订单
	public function untreatedOrderAction()
	{
		// 11,21
		$this->getView()->display("scheduling/untreatedOrder");
	}
	
	// 未处理订单_bak
	public function untreatedOrder_bakAction()
	{
		$this->getView()->display("scheduling/untreatedOrder");
	}
	
	// 已分配订单
	public function assignOrderAction()
	{
		$todate = date('Y-m-d');
		// Search-拼接SQL条件
		$searchSql = " a.status IN (" . ORDER_ALLOTED.','.ORDER_REALLOT.','.ORDER_RECEIVED.','.ORDER_GOTFOOD.','.ORDER_ARRIVAL . ")" ;
		$orderBy = " ORDER BY a.add_time DESC ";
		// Search-time | 要求送达时间 OR 下单时间
		$request_times = substr(trim($_REQUEST['request_times']), 0, 10);
		$request_timee = substr(trim($_REQUEST['request_timee']), 0, 10);
		$add_times = substr(trim($_REQUEST['add_times']), 0, 10);
		$add_timee = substr(trim($_REQUEST['add_timee']), 0, 10);
		$request_times = $request_times == NULL ? $todate : $request_times;
		$request_timee = $request_timee == NULL ? $todate : $request_timee;
		$add_times = $add_times == NULL ? $todate : $add_times;
		$add_timee = $add_timee == NULL ? $todate : $add_timee;
		$this->getView()->assign('request_times', $request_times);
		$this->getView()->assign('request_timee', $request_timee);
		$this->getView()->assign('add_times', $add_times);
		$this->getView()->assign('add_timee', $add_timee);

		if($request_times > $request_timee || $add_times > $add_timee) {
			exit("开始时间不能大于结束时间！");
		} else {
			if($request_times) {
				$request_times .= ' 00:00:00';
				$searchSql .= " AND b.request_time >= '". strtotime($request_times) . "' ";
			}
			if($request_timee) {
				$request_timee .= ' 23:59:59';
				$searchSql .= " AND b.request_time <= '". strtotime($request_timee) ."' ";
			}
			if($add_times) {
				$add_times .= ' 00:00:00';
				$searchSql .= " AND a.add_time >= '". strtotime($add_times) . "' ";
			}
			if($add_timee) {
				$add_timee .= ' 23:59:59';
				$searchSql .= " AND a.add_time <= '". strtotime($add_timee) ."' ";
			}
		}
		// Search-判断是否搜索
		if($_REQUEST['search']) {
			// Search-订单编号
			if(trim($_REQUEST['order_sn']) != '') {
				$order_sn = trim($_REQUEST['order_sn']);
				$this->getView()->assign('order_sn', $order_sn);
				$searchSql .= " AND a.order_sn LIKE '%".$order_sn."%' ";
			}
			// Search-收货人用户名
			if(trim($_REQUEST['consignee']) != '') {
				$consignee = trim($_REQUEST['consignee']);
				$this->getView()->assign('consignee', $consignee);
				$searchSql .= " AND b.consignee LIKE '%".$consignee."%' ";
			}
			// Search-收货人电话
			if(trim($_REQUEST['phone_mob']) != '') {
				$phone_mob = trim($_REQUEST['phone_mob']);
				$this->getView()->assign('phone_mob', $phone_mob);
				$searchSql .= " AND b.phone_mob LIKE '%".$phone_mob."%' ";
			}
			// Search-店铺名称
			if(trim($_REQUEST['seller_name']) != '') {
				$seller_name = trim($_REQUEST['seller_name']);
				$this->getView()->assign('seller_name', $seller_name);
				$searchSql .= " AND a.seller_name LIKE '%".$seller_name."%' ";
			}
			// Search-快递员
			if(trim($_REQUEST['emp_name']) != '') {
				$emp_name = trim($_REQUEST['emp_name']);
				$this->getView()->assign('emp_name', $emp_name);
				$searchSql .= " AND a.emp_name LIKE '%".$emp_name."%' ";
			}
			// Search-区域
			if(trim($_REQUEST['area']) != '' && $_REQUEST['area'] != 0) {
				if($_REQUEST['area'] !=''){
					$area = implode(",", $_REQUEST['area']);
				}
				$allarea = $this->order_model->getAllChildArea();
				if(count($_REQUEST['area'] != count($allarea))){
					$searchSql .= " AND b.region_id in (".$area.") ";
				}
				$parent_area = trim($_REQUEST['parent_area']);
				$this->getView()->assign('area', $area);
				$this->getView()->assign('parent_area', $parent_area);
			}
			// ORDER BY
			if(trim($_REQUEST['orderBy']) != '') {
				$orderBy = trim($_REQUEST['orderBy']);
				$orderBy = " ORDER BY a.".orderBy ."DESC ";
			}
		}
		
		// 获取全部订单
		$searchSqlBy = $searchSql.$orderBy;
		$order_array = $this->order_model->getAllOrder($this->page, $this->limit, $searchSqlBy);
		$order_summary_result = $this->order_model->getAllOrderSUM($searchSql, $this->limit);
		if($order_array['state'] == 1) {
			$orderArr = $order_array['data'];
			foreach ($orderArr as $key => $value) {
				$orderArr[$key]['order_type_str'] = $this->_statusArr['order_type'][$value['order_type']];
			}
		}
		
		$this->getView()->assign("store_summary", $order_summary_result);
		$this->getView()->assign("orderArr", $orderArr);
		$this->getView()->display("scheduling/assignOrder");
	}
	
	// 全部订单
	public function allOrderAction()
	{
		$todate = date('Y-m-d');
		// Search-拼接SQL条件
		$searchSql = " 1 " ;
		$orderBy = " ORDER BY a.add_time DESC ";
		// Search-time | 要求送达时间 OR 下单时间
		$request_times = substr(trim($_REQUEST['request_times']), 0, 10);
		$request_timee = substr(trim($_REQUEST['request_timee']), 0, 10);
		$add_times = substr(trim($_REQUEST['add_times']), 0, 10);
		$add_timee = substr(trim($_REQUEST['add_timee']), 0, 10);
		$request_times = $request_times == NULL ? $todate : $request_times;
		$request_timee = $request_timee == NULL ? $todate : $request_timee;
		$add_times = $add_times == NULL ? $todate : $add_times;
		$add_timee = $add_timee == NULL ? $todate : $add_timee;
		$this->getView()->assign('request_times', $request_times);
		$this->getView()->assign('request_timee', $request_timee);
		$this->getView()->assign('add_times', $add_times);
		$this->getView()->assign('add_timee', $add_timee);

		if($request_times > $request_timee || $add_times > $add_timee) {
			exit("开始时间不能大于结束时间！");
		} else {
			if($request_times) {
				$request_times .= ' 00:00:00';
				$searchSql .= " AND b.request_time >= '". strtotime($request_times) . "' ";
			}
			if($request_timee) {
				$request_timee .= ' 23:59:59';
				$searchSql .= " AND b.request_time <= '". strtotime($request_timee) ."' ";
			}
			if($add_times) {
				$add_times .= ' 00:00:00';
				$searchSql .= " AND a.add_time >= '". strtotime($add_times) . "' ";
			}
			if($add_timee) {
				$add_timee .= ' 23:59:59';
				$searchSql .= " AND a.add_time <= '". strtotime($add_timee) ."' ";
			}
		}
		// Search-判断是否搜索
		if($_REQUEST['search']) {
			// Search-订单编号
			if(trim($_REQUEST['order_sn']) != '') {
				$order_sn = trim($_REQUEST['order_sn']);
				$this->getView()->assign('order_sn', $order_sn);
				$searchSql .= " AND a.order_sn LIKE '%".$order_sn."%' ";
			}
			// Search-收货人用户名
			if(trim($_REQUEST['consignee']) != '') {
				$consignee = trim($_REQUEST['consignee']);
				$this->getView()->assign('consignee', $consignee);
				$searchSql .= " AND b.consignee LIKE '%".$consignee."%' ";
			}
			// Search-收货人电话
			if(trim($_REQUEST['phone_mob']) != '') {
				$phone_mob = trim($_REQUEST['phone_mob']);
				$this->getView()->assign('phone_mob', $phone_mob);
				$searchSql .= " AND b.phone_mob LIKE '%".$phone_mob."%' ";
			}
			// Search-店铺名称
			if(trim($_REQUEST['seller_name']) != '') {
				$seller_name = trim($_REQUEST['seller_name']);
				$this->getView()->assign('seller_name', $seller_name);
				$searchSql .= " AND a.seller_name LIKE '%".$seller_name."%' ";
			}
			// Search-快递员
			if(trim($_REQUEST['emp_name']) != '') {
				$emp_name = trim($_REQUEST['emp_name']);
				$this->getView()->assign('emp_name', $emp_name);
				$searchSql .= " AND a.emp_name LIKE '%".$emp_name."%' ";
			}
			// Search-区域
			if(trim($_REQUEST['area']) != '' && $_REQUEST['area'] != 0) {
				if($_REQUEST['area'] !=''){
					$area = implode(",", $_REQUEST['area']);
				}
				$allarea = $this->order_model->getAllChildArea();
				if(count($_REQUEST['area'] != count($allarea))){
					$searchSql .= " AND b.region_id in (".$area.") ";
				}
				$parent_area = trim($_REQUEST['parent_area']);
				$this->getView()->assign('area', $area);
				$this->getView()->assign('parent_area', $parent_area);
			}
			// ORDER BY
			if(trim($_REQUEST['orderBy']) != '') {
				$orderBy = trim($_REQUEST['orderBy']);
				$orderBy = " ORDER BY a.".orderBy ."DESC ";
			}
		}
		
		// 获取全部订单
		$searchSqlBy = $searchSql.$orderBy;
		$order_array = $this->order_model->getAllOrder($this->page, $this->limit, $searchSqlBy);
		$order_summary_result = $this->order_model->getAllOrderSUM($searchSql, $this->limit);
		if($order_array['state'] == 1) {
			$orderArr = $order_array['data'];
			foreach ($orderArr as $key => $value) {
				$orderArr[$key]['order_type_str'] = $this->_statusArr['order_type'][$value['order_type']];
			}
		}
		
		$this->getView()->assign("store_summary", $order_summary_result);
		$this->getView()->assign("orderArr", $orderArr);
		$this->getView()->display("scheduling/allOrder");
	}
	
	// 调度员统计
	public function deliveryStaffAction()
	{
		$todate = date('Y-m-d');
		// Search-拼接SQL条件
		$searchSql = " 1 " ;
		$orderBy = " ORDER BY a.add_time DESC ";
		// Search-判断是否搜索
		if($_REQUEST['search']) {
			// Search-快递员
			if(trim($_REQUEST['emp_name']) != '') {
				$emp_name = trim($_REQUEST['emp_name']);
				$this->getView()->assign('emp_name', $emp_name);
				$searchSql .= " AND a.emp_name LIKE '%".$emp_name."%' ";
			}
			// Search-收货人电话
			if(trim($_REQUEST['phone_mob']) != '') {
				$phone_mob = trim($_REQUEST['phone_mob']);
				$this->getView()->assign('phone_mob', $phone_mob);
				$searchSql .= " AND b.phone_mob LIKE '%".$phone_mob."%' ";
			}
		}
		
		// 获取全部订单
		// $searchSqlBy = $searchSql.$orderBy;
		// $order_array = $this->order_model->getAllOrder($this->page, $this->limit, $searchSqlBy);
		
		$this->getView()->assign("orderArr", $orderArr);
		$this->getView()->display("scheduling/deliveryStaff");
	}
	// 调度
	public function dispatchAction()
	{
		$lgs_id		= $_REQUEST['lgs_id'];
		$order_id 	= $_REQUEST['order_id'];
		$region_id  = $_REQUEST['region_id'];
		$selectOrder  = empty($_REQUEST['selectOrder']) ? $order_id : $_REQUEST['selectOrder'];
		if(!empty($_REQUEST['selectOrder'])){
			$dispatch = "1";
			$order = explode(",",$selectOrder);
		}
		$selectResult = $this->order_model->getSelectOrder($selectOrder);
		$emp_name 	=  '';
		$emp_mobile =  '';
		$s = 'true';
		// $s = 'null';
		$region = 0;
		$empArr = get_emp_search($order_id, $emp_name, $emp_mobile, $s, $region, $_SESSION['key'],$region_id);
		// var_dump($empArr);
		$this->getView()->assign("username", $_SESSION['username']);
		$this->getView()->assign("lgs_id", $lgs_id);
		$this->getView()->assign("dispatch", $dispatch);
		$this->getView()->assign("order_id", $order_id);
		$this->getView()->assign("empArr", $empArr);
		$this->getView()->assign("selectResult", $selectResult);
		if($dispatch == "1"){
			$this->getView()->assign("order", $order[0]);
			$this->getView()->display("scheduling/dispatchEmp");
			exit;
		}
		$this->getView()->display("scheduling/dispatch");
	}

	public function getStatusAction(){
		$order_id = $_POST['order_id'];
		$status = $this->order_model->OrderStatus($order_id);
		echo json_encode($status);		
	}
	
	// 选择区域
	public function selectAreaAction()
	{
		$order_id = $_REQUEST['order_id'];
		$isNull = 0;
		$lgsInfo = $this->orderIdGetLgsInfo($order_id);
		if(count($lgsInfo) >= 1) {
			$isNull = 1;
			$this->getView()->assign("lgsInfo", $lgsInfo);
		}
		$orderInfo = $this->order_model->getOrderDetailInfo($order_id);
		$sellInfo = $this->order_model->getOrderStore($order_id);
		$sellInfo = $this->order_model->getRegionNameByStore($sellInfo['store_id']);
		$orderInfo = $this->order_model->getRegionNameById($orderInfo['region_id']);
		// $sellInfo = $this->getRegionInfo($sellInfo['region_id'], 2);
		// $orderInfo = $this->getRegionInfo($orderInfo['region_id'], 1);
		$regionArr = array(
			'region_id_S' => $sellInfo['region_id'],
			'region_name_S' => $sellInfo['region_name'],
			'region_id_E' => $orderInfo['region_id'],
			'region_name_E' => $orderInfo['region_name'],
		);
		$parent_id = $this->order_model->getUpArea($sellInfo['region_id']);
		$area_id = $this->order_model->getChildArea($parent_id);
		foreach($area_id as $areaV) {
			$selectArea[$areaV['region_id']] = $areaV['region_name'];
		}
		// if($_SESSION['uid'] == 63){
		// 	$area_id = $this-order_model->getChildArea($parent_id);
		// }

		$this->getView()->assign("isNull", $isNull);
		$this->getView()->assign("order_id", $order_id);
		$this->getView()->assign("region_id", $sellInfo['region_id']);
		$this->getView()->assign("parent_id", $parent_id);
		$this->getView()->assign("selectArea", $selectArea);
		$this->getView()->assign("regionArr", $regionArr);
		$this->getView()->display("scheduling/selectArea");
	}
	
	// 获取区域信息
	function getRegionInfo($region_id,$num=6){
		$region=$region_id;
		if(!$region){
			return false;
		}
		for($i=0;$i<$num;$i++){
			$sql='select * from ecm_region where region_id='.$region;
			$regionInfo=$this->_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
			if($regionInfo['parent_id']<1){
				return $regionInfo;
				break;
			}
			$region=$regionInfo['parent_id'];
		}
		return $regionInfo;
	}
	
	// 选择调度员
	public function selectEmpAction()
	{
		$lgs_id 	= $_REQUEST['lgs_id'];
		$order_id	= $_REQUEST['order_id'];
		$emp_id 	= $_REQUEST['emp_id'];
		$orderArr 	= $_REQUEST['orderArr'];
		//
		$orderType = $this->order_model->getDeliverType($_REQUEST['order_id']);
		if($orderArr){
			$lgsInfo = emp_batch_reassign($orderArr, $emp_id, $_SESSION['uid'], $lgs_id, $_SESSION['key']);
			$result = $lgsInfo['is_success'];
			echo $result;
			exit;
		}
		$lgsInfo = emp_reassign($order_id, $emp_id, $_SESSION['uid'], $lgs_id, $_SESSION['key']);
		$result = $lgsInfo['is_success'];
		// 众包订单再分配发送消息通知
		if($result == 1 && $orderType['deliver_type'] == 2 && $orderType['cs_order_type'] > 1){
			if($orderType['status'] == ORDER_GOTFOOD){
				$sql = "UPDATE ecm_order SET status = ".ORDER_RECEIVED.",deliver_type = 1 WHERE order_id = ".$order_id;
				$result = $this->_db->query($sql);
				$log_time = time();
				$cs = array(
					'order_id' 		=> $order_id,
					'operator' 		=> $_SESSION['uid'],
					'order_status' 	=> ORDER_GOTFOOD,
					'changed_status'=> ORDER_RECEIVED,
					'remark' 		=> '',
					'log_time' 		=> $log_time,
				);
				$ecm_order_log_region = joinKeyValue($cs);
				$this->order_model->insertRegionLog($ecm_order_log_region);
			}else{
				$sql = "UPDATE ecm_order SET deliver_type = 1 WHERE order_id = ".$order_id;
				$result = $this->_db->query($sql);
			}
			$time = date("Y-m-d H:i:s", time());
			$content = "尊敬的用户您好，您订单编号为:".$orderType['order_sn']."的订单已经在".$time."被改派，您将无需对本单进行派送";
			// $status = $this->base->api_sms->send($phone['userPhone'],$message);
			$to_users = ",".$orderType['emp_id'].",";
			$data = array(
				'title' => "订单改派通知", 
				'content' => $content, 
				'operator' => $_SESSION['uid'], 
				'time' => $time, 
				'send_type'=> 2,
				'type'=> 2,
				'to_users'=>$to_users,
				'msg_type'=> 3,
				'send_mode'=> 1,
			);
			$send = $this->order_model->sendMessage($data);
		}
		echo $result;
	}
	// 商店名称搜索
	public function searchstoreAction()
	{
		$store_name = $_POST['store_name'];
		$store_id = explode(",",$_POST['store_id']);
		$select_name = explode(",",$_POST['select_name']);
		$storearr = array_combine($store_id,$select_name);
		$where = "where store_name like '%$store_name%'";
		$store_info = $this->order_model->getStoreInfo($where);
		//$parentOptionStr .= "<li class='ms-select-all'><label><input id='ms-select-all' type='checkbox' name='selectAllstore_name'/>全选</label></li>";
		if(!empty($_POST['store_id'])){
			foreach ($storearr as $k => $v) {
			$parentOptionStr .= "<option value={$k} selected>{$v}</option>";
			}
		}
		if(empty($store_name)){
			$where = "where store_id in (13489,58169,58172)";
			$Store = $this->order_model->getStoreInfo($where);
			foreach ($Store as $k => $v) {
				$parentOptionStr .= "<option value={$v['store_id']}>{$v['store_name']}</option>";
			}
			echo $parentOptionStr;
			return false;
		}
		foreach($store_info as $k => $v) {
			//$parentOptionStr .= "<li class=''><label><input id='ms-select-all' type='checkbox' value='{$v['store_id']}' name='selectItemstore_name'/>{$v['store_name']}</label></li>";
			$parentOptionStr .= "<option value={$v['store_id']}>{$v['store_name']}</option>";
		}

		echo $parentOptionStr;
		
	}

	/**
	 * ajax搜索配送员
	 * @return array
	 */
	function ajaxSearchEmpAction() {
		$order_id 	= $_REQUEST['order_id'];
		$emp_name 	= $_REQUEST['emp_name'];
		$emp_mobile = $_REQUEST['emp_mobile'];
		$s 			= $_REQUEST['selectArea'];
		$region     = $_REQUEST['region'];
		// $s 			= 'true';
		$empArr = get_emp_search($order_id, $emp_name, $emp_mobile, $s, $region, $_SESSION['key']);
		echo json_encode($empArr);
	}
	
	/**
	 * 设置区域路线
	 * @return array
	 */
	function setAreaLineAction() {
		$order_id = $_REQUEST['order_id'];
		$areaArr = json_decode(stripslashes($_REQUEST['areaArr']), true);
		
		$this->setLgsArea($order_id, $areaArr);
		$lgsInfo = $this->orderIdGetLgsDis($order_id);
		$status = is_array($lgsInfo) ? 1 : 0;
		$result = array('status' => $status, 'data' => $lgsInfo, 'message' => '设置区域路线成功');
		echo json_encode($result);
	}
	
	/**
	 * 设置区域路线
	 * @param int	order_id	订单ID
	 * @param array	areaArr		区域ID数组
	 * @return array
	 */
	function setLgsArea($order_id, $areaArr) {
		// 获取物流调度信息
		$lgsInfo = $this->orderIdGetLgsInfo($order_id);
		$lgsLen = count($lgsInfo);
		$arrLen = count($areaArr);
		$nowTime = time();
		
		// 判断区域是否已经设置
		if(is_array($lgsInfo)) {
			$len = $lgsLen >= $arrLen ? $lgsLen : $arrLen;
			for($key=1; $key<=$len; $key++) {
				$lgsDis = $this->numGetLgsDis($order_id, $key);
				$region_id = $areaArr[$key];
				// 判断更改区域区域数的变化
				if($lgsLen > $arrLen) {
					if($arrLen >= $key) {
						if($lgsDis['region_id'] != $region_id) {
							$this->UpLgsDisValid($lgsDis['lgs_id']);
							$lgs_info = array(
								'order_id' 		=> $order_id,
								'region_id'		=> $region_id,
								'relay_number'	=> $key,
								'create_time' 	=> $nowTime,
								'select_area_time' 	=> $nowTime,
							);
							if($key == $arrLen)
								$lgs_info['is_relay_end'] = 1;
							$lgs_info_KV = joinKeyValue($lgs_info);
							$inSql = "INSERT INTO lgs_info(".$lgs_info_KV['keys'].") VALUES(".$lgs_info_KV['vals'].")";
							$this->_db_lgs->query($inSql);
						} else {
							if($lgsDis['valid'] == 1 && $key == $arrLen) {
								$this->UpLgsIsEnd($lgsDis['lgs_id']);
							}
						}
					} else {
						$this->UpLgsDisValid($lgsDis['lgs_id']);
					}
				} else {
					if($lgsLen >= $key) {
						if($lgsDis['is_relay_end'] == 1 && $key == $lgsLen) {
							$this->UpLgsNotEnd($lgsDis['lgs_id']);
						}
						if($lgsDis['region_id'] != $region_id) {
							$this->UpLgsDisValid($lgsDis['lgs_id']);
							$lgs_info = array(
								'order_id' 		=> $order_id,
								'region_id'		=> $region_id,
								'relay_number'	=> $key,
								'create_time' 	=> $nowTime,
								'select_area_time' 	=> $nowTime,
							);
							if($key == $arrLen)
								$lgs_info['is_relay_end'] = 1;
							$lgs_info_KV = joinKeyValue($lgs_info);
							$inSql = "INSERT INTO lgs_info(".$lgs_info_KV['keys'].") VALUES(".$lgs_info_KV['vals'].")";
							$this->_db_lgs->query($inSql);
						}
					} else {
						$lgs_info = array(
							'order_id' 		=> $order_id,
							'region_id'		=> $region_id,
							'relay_number'	=> $key,
							'create_time' 	=> $nowTime,
							'select_area_time' 	=> $nowTime,
						);
						if($key == $arrLen)
							$lgs_info['is_relay_end'] = 1;
						$lgs_info_KV = joinKeyValue($lgs_info);
						$inSql = "INSERT INTO lgs_info(".$lgs_info_KV['keys'].") VALUES(".$lgs_info_KV['vals'].")";
						$this->_db_lgs->query($inSql);
					}
				}
			
				/* $lgsDis = $this->numGetLgsDis($order_id, $key);
				$region_id = $areaArr[$key];
				// 判断订单该接力数是否设置
				if(is_array($lgsDis)) {
					if($lgsDis['is_relay_end'] == 1 && $lgsLen != $len) {
						$this->UpLgsNotEnd($lgsDis['lgs_id']);
					}
					if($lgsDis['region_id'] != $region_id) {
						$this->UpLgsDisValid($lgsDis['lgs_id']);
						if($key <= $arrLen) {
							$lgs_info = array(
								'order_id' 		=> $order_id,
								'region_id'		=> $region_id,
								'relay_number'	=> $key,
								'create_time' 	=> $nowTime,
								'select_area_time' 	=> $nowTime,
							);
							if($key == $arrLen)
								$lgs_info['is_relay_end'] = 1;
							$lgs_info_KV = joinKeyValue($lgs_info);
							$inSql = "INSERT INTO lgs_info(".$lgs_info_KV['keys'].") VALUES(".$lgs_info_KV['vals'].")";
							$this->_db_lgs->query($inSql);
						}
					}
				} else {
					if($key <= $arrLen) {
						$lgs_info = array(
							'order_id' 		=> $order_id,
							'region_id'		=> $region_id,
							'relay_number'	=> $key,
							'create_time' 	=> $nowTime,
							'select_area_time' 	=> $nowTime,
						);
						if($key == $arrLen)
							$lgs_info['is_relay_end'] = 1;
						$lgs_info_KV = joinKeyValue($lgs_info);
						$inSql = "INSERT INTO lgs_info(".$lgs_info_KV['keys'].") VALUES(".$lgs_info_KV['vals'].")";
						$this->_db_lgs->query($inSql);
					}
				} */
			}
		} else {
			foreach($areaArr as $key => $region_id) {
				$lgs_info = array(
					'order_id' 		=> $order_id,
					'region_id'		=> $region_id,
					'relay_number'	=> $key,
					'create_time' 	=> $nowTime,
					'select_area_time' 	=> $nowTime,
				);
				if($key == $arrLen)
					$lgs_info['is_relay_end'] = 1;
				$lgs_info_KV = joinKeyValue($lgs_info);
				$inSql = "INSERT INTO lgs_info(".$lgs_info_KV['keys'].") VALUES(".$lgs_info_KV['vals'].")";
				$this->_db_lgs->query($inSql);
			}
		}
	}
	
	/**
	 * 选择订单配送员
	 * @param int	lgs_id		物流ID
	 * @param int	order_id	订单ID
	 * @param int	emp_id		配送员ID
	 * @return 
	 */
	function setLgsEmp($lgs_id, $order_id, $emp_id) {
		$nowTime = time();
		$lgs_info = array(
			'lgs_id' 		=> $lgs_id,
			'emp_id' 		=> $emp_id,
			'create_time' 	=> $nowTime,
			'select_emp_time' 	=> $nowTime,
		);
		$lgs_infoKV = joinKeyValue($lgs_info);
		$inSql = "INSERT INTO lgs_distribution(".$lgs_infoKV['keys'].") VALUES(".$lgs_infoKV['vals'].")";
		$this->_db_lgs->query($inSql);
	}
	
	/**
	 * 获取物流调度信息
	 * @param int	order_id	订单ID
	 * @return array
	 */
	function orderIdGetLgsInfo($order_id) {
		$sql = "SELECT B.*, A.*
			FROM lgs_info A 
			LEFT JOIN lgs_distribution B ON A.lgs_id = B.lgs_id
			WHERE A.order_id = $order_id 
			AND A.valid = 1
			AND ( B.valid = 1 OR B.valid IS NULL )
			ORDER BY A.relay_number";
		$result = $this->_db_lgs->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
	 * 获取物流调度信息
	 * @param int	order_id	订单ID
	 * @return array
	 */
	function orderIdGetLgsDis($order_id) {
		$sql = "SELECT B.*, A.*, R.region_name, E.emp_name, E.emp_mobile, RI.address
			FROM lgs_info A
			LEFT JOIN lgs_distribution B ON A.lgs_id = B.lgs_id
			LEFT JOIN nowmss.ecm_region R ON A.region_id = R.region_id
			LEFT JOIN nowmss.ecm_employee E ON B.emp_id = E.emp_id 
			LEFT JOIN nowmss.ecm_region_info RI ON A.region_id = RI.region_id
			WHERE A.order_id = $order_id
			AND A.valid = 1
			AND ( B.valid = 1 OR B.valid IS NULL )";
		$result = $this->_db_lgs->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
	 * 获取物流调度信息
	 * @param int	order_id	订单ID
	 * @param int	relay_number当前接力数
	 * @return array
	 */
	function numGetLgsDis($order_id, $relay_number) {
		$sql = "SELECT B.*, A.*
			FROM lgs_info A
			LEFT JOIN lgs_distribution B ON A.lgs_id = B.lgs_id
			WHERE A.order_id = $order_id
			AND A.relay_number = $relay_number
			AND A.valid = 1
			AND ( B.valid = 1 OR B.valid IS NULL )";
		$result = $this->_db_lgs->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
	 * 根据物流ID更新物流调度数据状态信息
	 * @param int	order_id	物流ID
	 * @return 
	 */
	function UpLgsDisValid($lgs_id) {
		$sql = "UPDATE lgs_info SET valid = 0 WHERE lgs_id = $lgs_id AND valid = 1";
		$this->_db_lgs->query($sql);
		$sql = "UPDATE lgs_distribution SET valid = 0 WHERE lgs_id = $lgs_id AND valid = 1";
		$this->_db_lgs->query($sql);
	}
	
	/**
	 * 根据物流ID更新is_relay_end
	 * @param int	order_id	物流ID
	 * @return 
	 */
	function UpLgsNotEnd($lgs_id) {
		$sql = "UPDATE lgs_info SET is_relay_end = 0 WHERE lgs_id = $lgs_id AND valid = 1";
		$this->_db_lgs->query($sql);
	}
	
	/**
	 * 根据物流ID更新is_relay_end
	 * @param int	order_id	物流ID
	 * @return 
	 */
	function UpLgsIsEnd($lgs_id) {
		$sql = "UPDATE lgs_info SET is_relay_end = 1 WHERE lgs_id = $lgs_id AND valid = 1";
		$this->_db_lgs->query($sql);
	}
	
}
?>