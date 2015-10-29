<?php
class DemoController extends Yaf_Controller_Abstract
{	
	var $_db;
	var $_statusArr;
	var $order_model;
	var $page;
	var $limit;
	var $addgoods_id;
	var $orderStatus;
	var $dispatchType;
	var $assignOrderStatus;//已分配订单状态
	
	public function init()
	{
		session_start();
		header("Content-type: text/html; charset=utf-8");
		$this->order_model = new TestModel();
		if(!isset($_SESSION['username']))
		{
			if($REQUEST_URI != "getArea" && $REQUEST_URI != "getCity")
			{
				header("Location:".WEB_PATH."/member/member/login");
			}
		}
		$this->getView()->assign('username',$_SESSION['username']);
		$this->getView()->assign('empname',$_SESSION['empname']);
		$this->_db = Yaf_Registry::get("api_db");
		if(!empty($_GET))
		{
			$_REQUEST = $_GET;
		} else if(!empty($_POST))
		{
			$_REQUEST =$_POST;
		}
		//分页
		include_once(APP_PATH."/application/modules/Store/controllers/Page.php");
		$this->page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		if(isset($_REQUEST['limit']) && 0 < $_REQUEST['limit'] && $_REQUEST['limit'] <= 1000)
		{
			$this->limit = $_REQUEST['limit'];
		}else
		{
			$this->limit = 50;
		}
		$partner[''] = '趣活美食送';
		$this->getView()->assign('app_path', APP_PATH);
		$this->getView()->assign('web_path', WEB_PATH);
		$this->getView()->assign('WEB_SOCKET_IP', WEB_SOCKET_IP);
		$this->getView()->assign('WEB_SOCKET_PORT', WEB_SOCKET_PORT);

	}
	
	public function indexAction()
	{
		//$this->getView()->display("crmorder/index");
	}
	
	public function demoAllOrderAction()
	{	
		// $this->getView()->display("crmorder/demoOrder");
		$today = date('Y-m-d');
		//sql
		$searchSql = " 1 ";
		$orderBy = " ORDER BY add_time DESC ";
		// 获取全部订单
		$searchSqlBy = $searchSql.$orderBy;
		$order_array = $this->order_model->getAllOrder($this->page, $this->limit, $searchSqlBy);
		$order_summary_result = $this->order_model->getAllOrderSUM($searchSql, $this->limit);
				
		// if($order_array['state'] == 1) {
			// $orderArr = $order_array['data'];
			// foreach ($orderArr as $key => $value) {
				// $orderArr[$key]['order_type_str'] = $this->_statusArr['order_type'][$value['order_type']];
				// $orderArr[$key]['is_new'] = $this->order_model->buyerIsnew($value['buyer_id']);
				// $region = $this->order_model->getRegionNameById($value['region_id']);
				// $orderArr[$key]['region_name'] = $region['region_name'];
			// }
		// }
		// $this->getView()->assign("store_summary", $order_summary_result);
		// $this->getView()->assign("orderArr", $orderArr);
		// $this->getView()->display("crmorder/demoOrder");
		var_dump($order_array['data']);
	}
	public function newOrderAction()
	{	
		$this->getView()->display("crmorder/demoOrder");
	}
}	

?>