<?php
class DialogController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		phpinfo();
		// $this->getView()->display("crmorder/index");
	}
	
	/**
	 * 确认订单
	 * @param int	orderid	订单ID
	 * @return
	 */
	public function confirmOrderAction()
	{
		$this->_db = Yaf_Registry::get("api_db");
		$orderSql = "SELECT a. * , b.consignee, b.phone_mob, b.address, b.region_name, b.request_time
			FROM ecm_order a
			JOIN ecm_order_extm b ON a.order_id = b.order_id
			WHERE 1
			LIMIT 2";
		$orderArr = $this->_db->getAll($orderSql, array(), DB_FETCHMODE_ASSOC);
		// var_dump($orderArr);
		
		$this->getView()->assign("orderArr", $orderArr);
		$this->getView()->display("dialog/confirmOrder");
	}
	
	/**
	 * 取消订单
	 * @param int	orderid	订单ID
	 * @return
	 */
	public function cancelOrderAction()
	{
		$this->getView()->display("dialog/cancelOrder");
	}
	
	public function allOrderAction()
	{
		$this->_db = Yaf_Registry::get("api_db");
		
		if(!empty($_GET)) {
			$_REQUEST = $_GET;
		} else if(!empty($_POST)) {
			$_REQUEST = $_POST;
		}
		
		$todate = date('Y-m-d');
		
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
			exit("time error: Start time is greater than the end time");
		} else {
			if($request_times) {
				$request_times .= ' 00:00:00';
				$searchSql .= " AND a.request_time >= '". $request_times . "' ";
			}
			if($request_timee) {
				$request_timee .= ' 23:59:59';
				$searchSql .= " AND a.request_time <= '". $request_timee ."' ";
			}
			if($add_times) {
				$add_times .= ' 00:00:00';
				$searchSql .= " AND a.add_time >= '". $add_times . "' ";
			}
			if($add_timee) {
				$add_timee .= ' 23:59:59';
				$searchSql .= " AND a.add_time <= '". $add_timee ."' ";
			}
		}
		// Search-拼接SQL条件
		$searchSql = " a.status = 0 ";
		$orderBy = " ORDER BY a.add_time DESC ";
		// Search-判断是否搜索
		if($_REQUEST['search']) {
			// Search-订单编号
			if(trim($_REQUEST['order_sn']) != '') {
				$order_sn = trim(preg_replace("/[,，]+/", ",", preg_replace("/[^0-9,，]/", "", $_REQUEST['order_sn'])), ",");
				$this->getView()->assign('order_sn', $order_sn);
				if($order_sn != NULL)
					$searchSql .= " AND a.order_sn IN ($order_sn) ";
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
				$searchSql .= " AND b.phone_mob = '".$phone_mob."' ";
			}
			// Search-店铺名称
			if(trim($_REQUEST['seller_name']) != '') {
				$seller_name = trim($_REQUEST['seller_name']);
				$this->getView()->assign('seller_name', $seller_name);
				$searchSql .= " AND a.seller_name LIKE '%".$seller_name."%' ";
			}
		}
		
		$orderSql = "SELECT a. * , b.consignee, b.phone_mob, b.address, b.region_name, b.request_time
			FROM ecm_order a
			JOIN ecm_order_extm b ON a.order_id = b.order_id
			WHERE $searchSql
			LIMIT 100";
		$orderArr = $this->_db->getAll($orderSql, array(), DB_FETCHMODE_ASSOC);
		foreach ($orderArr as $key => $value) {
			# code...
		}
		$this->getView()->assign("orderArr", $orderArr);
		$this->getView()->display("crmorder/allOrder");
		
	}
	
	/**
	 * 更新ecm_order表status状态
	 * @param int		order_sn	订单ID
	 * @param int		status		订单状态
	 * @param resource	db			数据库连接资源
	 * @return
	 */
	public function update_ecm_order_status($order_sn, $status, $db)
	{
		$sql = "UPDATE ecm_order SET status = '".$status."' WHERE order_sn = '".$order_sn."'";
		$db->query($sql);
	}
}
?>