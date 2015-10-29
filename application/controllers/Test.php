<?php
class TestController extends Yaf_Controller_Abstract {
	var $_db;
	
	public function init()
	{
		$this->_db = Yaf_Registry::get("api_db");
		if(!empty($_GET)) {
			$_REQUEST = $_GET;
		} else if(!empty($_POST)) {
			$_REQUEST = $_POST;
		}
		var_dump($_SESSION);
	}
	
	public function indexAction()
	{
		// Search-拼接SQL条件
		$searchSql = " a.status = 0 ";
		$orderBy = " ORDER BY a.add_time DESC ";
		// Search-判断是否搜索
		if($_REQUEST['search']) {
			/* // Search-date
			$startdate = substr(trim($_REQUEST['startdate']), 0, 10);
			$enddate = substr(trim($_REQUEST['enddate']), 0, 10);
			$this->getView()->assign('startdate', $startdate);
			$this->getView()->assign('enddate', $enddate);
			if($startdate > $enddate && $enddate != '') {
				exit("date error");
			} else {
				if($startdate) {
					$startdate .= ' 00:00:00';
					$searchSql .= " AND a.addtime >= '". $startdate . "' ";
				}
				if($enddate) {
					$enddate .= ' 23:59:59';
					$searchSql .= " AND a.addtime <= '". $enddate ."' ";
				}
			} */
			// Search-订单编号
			if(trim($_REQUEST['order_id']) != '') {
				$order_id = trim(preg_replace("/[,，]+/", ",", preg_replace("/[^0-9,，]/", "", $_REQUEST['order_id'])), ",");
				$this->getView()->assign('order_id', $order_id);
				if($order_id != NULL)
					$searchSql .= " AND a.order_id IN ($order_id) ";
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
			LIMIT 10";
		
		$orderArr = $this->_db->getAll($orderSql, array(), DB_FETCHMODE_ASSOC);
		$this->getView()->assign("orderArr", $orderArr);
		// var_dump($orderArr);
		
		$this->getView()->assign("content", "Hello World");
		$this->getView()->display("test/index");
   }
	
	//主页的显示
	public function homeAction(){
		return 'home';
		$this->getView()->display("home");
	}

}
?>