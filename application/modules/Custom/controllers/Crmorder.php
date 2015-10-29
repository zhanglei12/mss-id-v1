<?php
class CrmorderController extends Yaf_Controller_Abstract
{
	var $_db;
	var $_statusArr;
	var $order_model;
	var $page;
	var $limit;
	var $_search;
	var $addgoods_id;
	var $orderStatus;
	var $dispatchType;
	var $assignOrderStatus;//已分配订单状态
	var $areaUserArr;  //调度员所属区域
	var $resOrder;


	public function init()
	{
		session_start();
		header("Content-type: text/html; charset=utf-8");
		$this->order_model = new OrderModel();
		//如果url请求获取区域信息,则无需登录
		$REQUEST_URI = substr($_SERVER['REQUEST_URI'],17,7);
		if(!isset($_SESSION['username'])) {
			if($REQUEST_URI != "getArea" && $REQUEST_URI != "getCity"){
				header("Location: ".WEB_PATH."/member/member/login");
			}
		}
		$this->getView()->assign('username', $_SESSION['username']);
		$this->getView()->assign('empname', $_SESSION['empname']);
		$this->_db = Yaf_Registry::get("api_db");
		$this->_db_read = Yaf_Registry::get("api_db_read");
		if(!empty($_GET)) {
			$_REQUEST = $_GET;
		} else if(!empty($_POST)) {
			$_REQUEST = $_POST;
		}
		// 调度员所属区域
		$areaUserBrr = $this->order_model->getUserRegionId($_SESSION['username']);
		foreach($areaUserBrr as $value){
			$areaUserArray[]= $value['er_regionid'];
		}
		$this->areaUserArr = $areaUserArray;
		// 快递区域
		$parentArea = $this->order_model->getParentArea();
		// $parent_areaList[0] = '全部';
		foreach($parentArea as $areaV) {
			$parent_areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		$this->getView()->assign('parent_areaList', $parent_areaList);
		$areaArr = get_express_area();
		if($areaUserArray != ''){
			foreach($areaArr as $areaV) {
				if(is_array($areaUserArray)){
					foreach($areaUserArray as $value){
						if($value == $areaV['region_id']){
							$areaList[$areaV['region_id']] = $areaV['region_name'];
						}
					}
				}	
			}
		}else{
			$areaList[0] = '全部';
			foreach($areaArr as $areaV) {
				$areaList[$areaV['region_id']] = $areaV['region_name'];
			}
		}
		$this->getView()->assign('areaList', $areaList);
		// 搜索
		include_once(APP_PATH."/application/library/search.php");
		$this->_search = new _Search();
		// 分页
		include_once(APP_PATH."/application/modules/Store/controllers/Page.php");
		$this->page	= isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		if(isset($_REQUEST['limit']) && 0 < $_REQUEST['limit'] && $_REQUEST['limit'] <= 1000) {
			$this->limit = $_REQUEST['limit'];
		} else {
			$this->limit = 50;
		}
		// 订单类型
		$this->_statusArr['order_type'] = array(
			'1' => '电话订单',
			'2' => '网络订单',
			'3' => '手机订单',
			'4' => '其他订单',
			'5' => '微信订单',
			'6' => '积分订单',
		);
		$this->orderStatus = array(
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
			'37'=>'申请退款',
			'38'=>'同意退款',
			'39'=>'拒绝退款',
			'50'=>'已完成',
			'-100'=>'已修改',
			'-101'=>'修改菜品',
			'-200'=>'未下单',
			'-201'=>'已确认',
			'-202'=>'已下单',
			'-203'=>'驳回',
		);
		$payment_nameList = array(
			''=>'全部',
			'货到付款' => '货到付款',
			'在线支付' => '在线支付'
		);
		$csOrder = array(
			'0' => '全部',
			'1' => '直营',
			'2' => '众包认证',
			'3' => '众包普通',
			'4' => '外包',
			'5' => '代理',
		);
		$this->resOrder = array(
			'0' => RES_LOG_NOORDER,
			'1' => RES_LOG_CONFIRM,
			'2' => RES_LOG_ORDERED,
			'3' => RES_LOG_ORDERED,
			 );
		$this->assignOrderStatus = array(12, 13, 20, 22, 24);
		$orderTp = $this->_statusArr['order_type'];
		$cooperate = $this->order_model->get_cooperate();
		foreach($cooperate as $k=>$v){
			$partner[$v['appkey']] = $v['cooperate_name'];
		}
		$partnerList = $partner;
		foreach($partnerList as $k => $v) {
			$parentOptionStr .= "<option value={$k} selected>{$v}</option>";
		}
		foreach($this->orderStatus as $k => $v) {
			$selectOptionStr.= "<option value={$k} selected>{$v}</option>";
		}
		$partner[''] = '趣活美食送';
		$json_statusArr = json_encode($this->_statusArr['order_type']);
		$json_orderStatus = json_encode($this->orderStatus);
		$json_partner = json_encode($partner);
		$json_csOrder = json_encode($csOrder);
		// 订单关闭原因
		$close_reason = get_order_close_reason();
		$g_close_array = $close_reason['data'];
		$this->getView()->assign('payment_nameList', $payment_nameList);
		$this->getView()->assign('csOrder', $csOrder);
		$this->getView()->assign('g_close_array', json_encode($g_close_array));
		$this->getView()->assign('app_path', APP_PATH);
		$this->getView()->assign('web_path', WEB_PATH);
		$this->getView()->assign('WEB_SOCKET_IP', WEB_SOCKET_IP_ID);
		$this->getView()->assign('WEB_SOCKET_PORT', WEB_SOCKET_PORT_ID);
		$this->getView()->assign("partner", $partner);
		$this->getView()->assign("partnerList", $parentOptionStr);
		$this->getView()->assign('json_statusArr', $json_statusArr);
		$this->getView()->assign('json_orderStatus', $json_orderStatus);
		$this->getView()->assign("json_orderType", $json_csOrder);
		$this->getView()->assign('json_partner', $json_partner);
		$this->getView()->assign("orderStatus", $this->orderStatus);
		$this->getView()->assign("selectStatus", $selectOptionStr);
		$this->getView()->assign("orderTp", $orderTp);
	}
	
	public function indexAction()
	{
		// $this->getView()->display("crmorder/index");
	}
	public function wwwAction()
	{
		$this->getView()->display("crmorder/testt");
	}
	// 全部订单
	public function allOrderAction()
	{
		// 拼装搜索条件
		$search = $this->getData();
		$search['searchSql'] = " 1 ";
		// 调度员所属区域
		if($search['selectItemarea'] == '' || $search['selectItemarea'] == 0){
			$search['parent_area'] = 0;
			$search['selectItemarea'] = $this->areaUserArr;
		}
		$data = $this->_search->search($search);
		$this->getView()->assign('request_times', $data['request_times']);	
		$this->getView()->assign('request_timee', $data['request_timee']);
		$this->getView()->assign('add_times', $data['add_times']);
		$this->getView()->assign('add_timee', $data['add_timee']);
		$this->getView()->assign('cs_order_type', $data['csOrderType']);
		if($data['order_sn']){
			$this->getView()->assign('order_sn', $data['order_sn']);
		}
		if($data['partner_order_id']){
			$this->getView()->assign('partner_order_id', $data['partner_order_id']);
		}
		if($data['consignee']){
			$this->getView()->assign('consignee', $data['consignee']);
		}	
		if($data['phone_mob']){
			$this->getView()->assign('phone_mob', $data['phone_mob']);
		}	
		if($data['seller_name']){
			$this->getView()->assign('seller_name', $data['seller_name']);
		}	
		if($data['emp_name']){
			$this->getView()->assign('emp_name', $data['emp_name']);
		}	
		if($data['partnerList']){
			$this->getView()->assign('partnerList', $data['partnerList']);
		}
		if($data['selectpartner']){
			$this->getView()->assign('selectpartner', $data['selectpartner']);
		}
		if($data['selectStatus']){
			$this->getView()->assign('selectStatus', $data['selectStatus']);
		}
		if($data['area']){
			$this->getView()->assign('area', $data['area']);
		}
		if($data['payment_name']){
			$this->getView()->assign('payment_name', $data['payment_name']);
		}
		if($data['parent_area']){
			$this->getView()->assign('parent_area', $data['parent_area']);
		}
		$searchSql = $data['searchSql'];
		$orderBy = $data['orderBy'];
		
		// 获取全部订单
		$searchSqlBy = $searchSql.$orderBy;
		$order_array = $this->order_model->getAllOrder($this->page, $this->limit, $searchSqlBy);
		$order_summary_result = $this->order_model->getAllOrderSUM($searchSql, $this->limit);
		if($order_array['state'] == 1) {
			$orderArr = $order_array['data'];
			foreach ($orderArr as $key => $value) {
				$orderArr[$key]['order_type_str'] = $this->_statusArr['order_type'][$value['order_type']];
				$orderArr[$key]['is_new'] = $this->order_model->buyerIsnew($value['buyer_id']);
				$region = $this->order_model->getRegionNameById($value['region_id']);
				$orderArr[$key]['region_name'] = $region['region_name'];
				if($value['deliver_type'] != 1){
					$emp_name = $this->order_model->getCsEmp($value['emp_id'],$value['cs_order_type']);
					$orderArr[$key]['emp_name'] = $emp_name;
				}
			}
		}
		$this->getView()->assign("store_summary", $order_summary_result);
		$this->getView()->assign("orderArr", $orderArr);
		$this->getView()->display("crmorder/allOrder");
	}
	
	// 新订单 | 未处理订单
	public function neworderAction()
	{	
		if($this->areaUserArr){
			$area = implode(",", $this->areaUserArr);
			$this->getView()->assign('area', $area);	
		}
		$this->getView()->display("crmorder/newOrder");
	}
	
	// 已分配订单
	public function assignOrderAction()
	{
		// 拼装搜索条件
		$search = $this->getData();
		$search['searchSql'] = " 1 ";
		if($search['selectItemarea'] == '' || $search['selectItemarea'] == 0){
			$search['parent_area'] = 0;
			$search['selectItemarea'] = $this->areaUserArr;
		}
		// search-订单状态：已退回
		if($search['orderStatus'] == ''){
			$search['search'] = '搜索';
			$search['orderStatus'] = array(12, 13, 20, 22, 24);
		}
		
		$data = $this->_search->search($search);
		$this->getView()->assign('request_times', $data['request_times']);	
		$this->getView()->assign('request_timee', $data['request_timee']);
		$this->getView()->assign('add_times', $data['add_times']);
		$this->getView()->assign('add_timee', $data['add_timee']);
		$this->getView()->assign('cs_order_type', $data['csOrderType']);
		if($data['order_sn']){
			$this->getView()->assign('order_sn', $data['order_sn']);
		}
		if($data['partner_order_id']){
			$this->getView()->assign('partner_order_id', $data['partner_order_id']);
		}
		if($data['consignee']){
			$this->getView()->assign('consignee', $data['consignee']);
		}	
		if($data['phone_mob']){
			$this->getView()->assign('phone_mob', $data['phone_mob']);
		}	
		if($data['seller_name']){
			$this->getView()->assign('seller_name', $data['seller_name']);
		}	
		if($data['emp_name']){
			$this->getView()->assign('emp_name', $data['emp_name']);
		}	
		if($data['partnerList']){
			$this->getView()->assign('partnerList', $data['partnerList']);
		}
		if($data['selectpartner']){
			$this->getView()->assign('selectpartner', $data['selectpartner']);
		}
		if($data['selectStatus']){
			$this->getView()->assign('selectStatus', $data['selectStatus']);
		}
		if($data['area']){
			$this->getView()->assign('area', $data['area']);
		}
		if($data['payment_name']){
			$this->getView()->assign('payment_name', $data['payment_name']);
		}
		if($data['parent_area']){
			$this->getView()->assign('parent_area', $data['parent_area']);
		}
		$searchSql = $data['searchSql'];
		$orderBy = $data['orderBy'];

		$page = ($this->page-1)*$this->limit;
		// $sql = "SELECT a.*, b.*,c.bd_name,d.store_buildname,d.tel,e.emp_mobile,f.* ,g.emp_no,h.region_name,i.bd_name cons_bdname
		// 		FROM ecm_order a JOIN ecm_order_extm b ON a.order_id = b.order_id
		// 		LEFT JOIN ecm_building c ON b.bd_id = c.bd_id
		// 		LEFT JOIN ecm_store d ON a.seller_id = d.store_id
		// 		LEFT JOIN ecm_employee e ON a.emp_id = e.emp_id
		// 		LEFT JOIN ecm_empextm f ON a.emp_id = f.emp_id
		// 		LEFT JOIN ecm_employee g ON a.operator_id = g.emp_id
		// 		LEFT JOIN ecm_region h ON h.region_id = f.emp_region
		// 		LEFT JOIN ecm_building i ON b.bd_id = i.bd_id
		// 		LEFT JOIN ecm_member j ON a.buyer_id = j.user_id
		// 		WHERE ".$searchSql." LIMIT $page, $this->limit";
		// $result = $this->_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);

		$order_array = $this->order_model->getAllOrder($this->page,$this->limit,$searchSql);

		$order_summary_result = $this->order_model->getAllOrderSUM($searchSql, $this->limit);
		// var_dump($result);
		if($order_array['state'] == 1) {
			$orderArr = $order_array['data'];
			foreach ($orderArr as $key => $value) {
				if($value['deliver_type'] != 1){
					$emp_name = $this->order_model->getCsEmp($value['emp_id'],$value['cs_order_type']);
					$orderArr[$key]['emp_name'] = $emp_name;
				}
			}
		}
		$this->getView()->assign("order_summary",$order_summary_result);
		$this->getView()->assign("orderArr",$orderArr);
		$this->getView()->assign("time",time());
		$this->getView()->display("crmorder/assignOrder");
	}
	
	/**
	 * 众包已分配
	 */
	public function csAssignOrderAction()
	{
		// 拼装搜索条件
		$search = $this->getData();
		$search['searchSql'] = " 1 ";
		if($search['csOrder'] == ''){
			$search['csOrder'] = 'cs';
		}
		// search-订单状态：已退回
		if($search['orderStatus'] == ''){
			$search['search'] = '搜索';
			$search['orderStatus'] = array(12, 13, 20, 22, 24);
		}
		
		$data = $this->_search->search($search);
		$this->getView()->assign('request_times', $data['request_times']);	
		$this->getView()->assign('request_timee', $data['request_timee']);
		$this->getView()->assign('add_times', $data['add_times']);
		$this->getView()->assign('add_timee', $data['add_timee']);
		$this->getView()->assign('cs_order_type', $data['csOrderType']);
		if($data['order_sn']){
			$this->getView()->assign('order_sn', $data['order_sn']);
		}
		if($data['partner_order_id']){
			$this->getView()->assign('partner_order_id', $data['partner_order_id']);
		}
		if($data['consignee']){
			$this->getView()->assign('consignee', $data['consignee']);
		}	
		if($data['phone_mob']){
			$this->getView()->assign('phone_mob', $data['phone_mob']);
		}	
		if($data['seller_name']){
			$this->getView()->assign('seller_name', $data['seller_name']);
		}	
		if($data['emp_name']){
			$this->getView()->assign('emp_name', $data['emp_name']);
		}	
		if($data['partnerList']){
			$this->getView()->assign('partnerList', $data['partnerList']);
		}
		if($data['selectpartner']){
			$this->getView()->assign('selectpartner', $data['selectpartner']);
		}
		if($data['selectStatus']){
			$this->getView()->assign('selectStatus', $data['selectStatus']);
		}
		if($data['area']){
			$this->getView()->assign('area', $data['area']);
		}
		if($data['payment_name']){
			$this->getView()->assign('payment_name', $data['payment_name']);
		}
		if($data['parent_area']){
			$this->getView()->assign('parent_area', $data['parent_area']);
		}
		$searchSql = $data['searchSql'];
		$orderBy = $data['orderBy'];

		//$searchSql .= " AND a.cs_order_type in(2,3) ";
		// $page = ($this->page-1)*$this->limit;
		// $sql = "SELECT a.*, b.*,c.bd_name,d.store_buildname,d.tel,e.emp_mobile,f.* ,g.emp_no,h.region_name,i.bd_name cons_bdname
		// 		FROM ecm_order a JOIN ecm_order_extm b ON a.order_id = b.order_id
		// 		LEFT JOIN ecm_building c ON b.bd_id = c.bd_id
		// 		LEFT JOIN ecm_store d ON a.seller_id = d.store_id
		// 		LEFT JOIN ecm_employee e ON a.emp_id = e.emp_id
		// 		LEFT JOIN ecm_empextm f ON a.emp_id = f.emp_id
		// 		LEFT JOIN ecm_employee g ON a.operator_id = g.emp_id
		// 		LEFT JOIN ecm_region h ON h.region_id = f.emp_region
		// 		LEFT JOIN ecm_building i ON b.bd_id = i.bd_id
		// 		LEFT JOIN ecm_member j ON a.buyer_id = j.user_id
		// 		WHERE ".$searchSql." LIMIT $page, $this->limit";
		// $result = $this->_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		$result = $this->order_model->getAllOrder($this->page,$this->limit,$searchSql);
		$order_summary_result = $this->order_model->getAllOrderSUM($searchSql, $this->limit);
		if($order_array['state'] == 1) {
			$orderArr = $order_array['data'];
			foreach ($orderArr as $key => $value) {
				if($value['deliver_type'] != 1){
					$emp_name = $this->order_model->getCsEmp($value['emp_id'],$value['cs_order_type']);
					$orderArr[$key]['emp_name'] = $emp_name;
				}
			}
		}
		// $empInfo = $this->order_model->getEmpInfo($value['cs_order_type'], $value['emp_id']);
		// dump($result);
		// var_dump($result);
		$this->getView()->assign("order_summary",$order_summary_result);
		$this->getView()->assign("orderArr",$orderArr);
		$this->getView()->assign("time",time());
		$this->getView()->display("crmorder/csAssignOrder");
	}
	// 配送员统计
	public function empCountAction(){
		$emp_name = $_REQUEST['emp_name'];
		$emp_mobile = $_REQUEST['emp_mobile'];
		$page_emp = '';
		if($_REQUEST['selectItemarea'] == '' || $_REQUEST['selectItemarea'] == 0){
			if(!empty($this->areaUserArr)){
				$area = implode(",", $this->areaUserArr);
				$search .= " AND b.account_region in (".$area.") ";
				$this->getView()->assign('area', $area);
				$this->getView()->assign('parent_area', 0);
			}
			
		}
		if($_REQUEST['emp_name'] && $_REQUEST['emp_name'] != ''){
			$this->getView()->assign('emp_name', $emp_name);
			$search .= " AND b.emp_name LIKE '%".$emp_name."%' ";
		}
		if($_REQUEST['emp_mobile'] && $_REQUEST['emp_mobile'] != ''){
			$this->getView()->assign('emp_mobile', $emp_mobile);
			$search .= " AND b.emp_mobile LIKE '%".$emp_mobile."%' ";
		}
		if(trim($_REQUEST['selectItemarea']) != '' && $_REQUEST['selectItemarea'] != 0) {
			$area = implode(",", $_REQUEST['selectItemarea']);
			if(!($_REQUEST['parent_area'] == 0 && $_REQUEST['selectAllarea'][0] === '0')) {
				$search .= " AND b.account_region in (".$area.") ";
				$parent_area = trim($_REQUEST['parent_area']);
				$this->getView()->assign('area', $area);
				$this->getView()->assign('parent_area', $parent_area);
			}
		}

		if($_GET['orderBy']){
			$count = " count(a.emp_id) as count, ";
			$status = abs($_GET['orderBy']);
			if($_GET['orderBy'] > 0){
				$orderby = " ORDER BY count DESC";
			}else if($_GET['orderBy'] < 0){
				$orderby = " ORDER BY count ASC";
			}
			if($_GET['orderBy'] == 1 || $_GET['orderBy'] == -1){
				$status = '50,22,20,31';
			}
		}else{
			$count = '';
			$status = '50,22,20,31';
		}
		$day = time();
		$time = "DATEDIFF(FROM_UNIXTIME(a.add_time),CURDATE())=0";
		$where = $time." AND a.status in (".$status.") ".$search; 
		// 获取所有送餐员信息
		$allEmpSql = "SELECT a.emp_id 
						FROM ecm_order a 
						LEFT JOIN ecm_employee b ON a.emp_id = b.emp_id 
						WHERE DATEDIFF(FROM_UNIXTIME(a.add_time),CURDATE())=0 ".$search." AND b.emp_extention1 in(1,2) GROUP BY a.emp_id";
		$allEmp = $this->_db_read->getAll($allEmpSql, array(), DB_FETCHMODE_ASSOC);
		// var_dump($allEmp);
		if(!$allEmp[0]['emp_id']){
			unset($allEmp[0]);
		}
		// 获取排序的数组
		$sql = "SELECT ".$count."a.emp_id FROM ecm_order a LEFT JOIN ecm_employee b ON a.emp_id = b.emp_id WHERE ".$where." GROUP BY a.emp_id ".$orderby;
		$count_result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		// 二维数组转一维数组取差集
		$allEmpArr = array_column($allEmp, 'emp_id');
		$sortArr = array_column($count_result, 'emp_id');
		$diffArr = array_diff($allEmpArr,$sortArr);
		// 根据搜索的DESC跟ASC 将empid拼接
		$sortEmp = implode(",",$sortArr);
		$diffEmp = implode(",",$diffArr);
		if(empty($diffEmp)){
			$emp_id = $sortEmp;
		}else if($status > 0){
			$emp_id = $sortEmp.",".$diffEmp;
			// $emp_id = substr($emp_id, 1);
		}else if($status < 0){
			$emp_id = $diffEmp.",".$sortEmp;
			$emp_id = rtrim($emp_id, ",");
		}
		$emp = explode(",",$emp_id);
		if(!$_GET['orderBy']){
			$emp = $allEmpArr;
		}
		// 得到的empid进行分页
		$limit = 10;
		// 计算出总共的条数
		$count = count($emp);
		// 引入分页类
		$page_model = new Page($count, $limit);
		$order_summary_show = $page_model->fpage();
		// 计算page,limit
		$page = ($this->page - 1) * $limit;
		$empLimit = array_slice($emp,$page,$limit);
		// if($_SESSION['username'] == 111112){
		// 	var_dump($diffEmp);
		// 	echo '<br />';
		// 	var_dump($sortEmp);
		// 	echo '<br />';
		// 	var_dump($emp);
		// 	echo '<br />';
		// 	dump($empLimit);
		// }
		$rearr = array();
		// 经过搜索并且分页的empid获取详细信息
		foreach ($empLimit as $v) {
			$i++;
			$rearr[$i]=array(
				'emp_name' => '',
				'emp_mobile' => '',
				'today_finished' => 0,
				'today_gotfood' => 0,
				'today_received' => 0,
				'today_abnormal' => 0,
				'today_sum' => 0,
 				);
			$empsql = "SELECT a.emp_name, a.emp_mobile,b.region_name FROM ecm_employee a LEFT JOIN ecm_region b ON a.account_region = b.region_id WHERE emp_id = ".$v."";
			$empresult = $this->_db_read->getRow($empsql, array(), DB_FETCHMODE_ASSOC);
			$sql = "SELECT count(*) as count FROM ecm_order a where ".$time." AND a.emp_id = ".$v." AND a.status = 50 GROUP BY a.emp_id ";
			$count_result0 = $this->_db_read->getOne($sql);
			$sql = "SELECT count(*) as count FROM ecm_order a where ".$time." AND a.emp_id = ".$v." AND a.status = 22 GROUP BY a.emp_id ";
			$count_result1 = $this->_db_read->getOne($sql);
			$sql = "SELECT count(*) as count FROM ecm_order a where ".$time." AND a.emp_id = ".$v." AND a.status = 20 GROUP BY a.emp_id ";
			$count_result2 = $this->_db_read->getOne($sql);
			$sql = "SELECT count(*) as count FROM ecm_order a where ".$time." AND a.emp_id = ".$v." AND a.status = 30 GROUP BY a.emp_id ";
			$count_result3 = $this->_db_read->getOne($sql);
			$sql = "SELECT count(*) as count FROM ecm_order a where ".$time." AND a.emp_id = ".$v." GROUP BY a.emp_id ";
			$count_result4 = $this->_db_read->getOne($sql);
			$rearr[$i]['emp_name']       = $empresult['emp_name'];
			$rearr[$i]['emp_mobile']     = $empresult['emp_mobile'];
			$rearr[$i]['region_name']    = $empresult['region_name'];
			$rearr[$i]['today_finished'] = $count_result0;
			$rearr[$i]['today_gotfood']  = $count_result1;
			$rearr[$i]['today_received'] = $count_result2;
			$rearr[$i]['today_abnormal'] = $count_result3;
			$rearr[$i]['today_sum']      = $count_result4;
		}
		$this->getView()->assign("arr",$rearr);
		$this->getView()->assign("order_summary_show",$order_summary_show);
		$this->getView()->display("crmorder/empCount");
	}
	//从cookie中获取近期的订单操作记录
	public function getLocalOrderAction(){
		//print_r($_COOKIE);
		$time = strtotime(date("Y-m-d"));
		$this->getView()->assign("time",$time);
		$this->getView()->display("crmorder/localOrder");
	}

	// 订单详情
	public function orderAction()
	{
		// 订单类型
		$orderTypeArr = array(
			'newOrder'	=> array(11,21),
			'assignOrder'	=> array(12, 13, 20, 22, 24),
		);
		
		$order_id 	= $_REQUEST["order_id"];
		// $ecm_order = $this->order_model->ecm_order_info($order_id);
		// 获取订单详情
		$detail = $this->order_model->getOrderDetail($order_id);
		$ecm_order = $detail[0];
		$orderType = 'allorder';
		foreach($orderTypeArr as $k => $v) {
			if(in_array($ecm_order['status'], $v))
				$orderType = $k;
		}
		$this->getView()->assign("orderType", $orderType);
		
		// 上一条、下一条订单ID
		// $AOrder = $this->prev_next_order($ecm_order['order_id'], "b.request_time", $ecm_order['request_time'], $orderType, $orderTypeArr);
		// $this->getView()->assign("AOrder", $AOrder);
		
		$region_id = $detail[0]['region_id'];
		$detail[0]['parent_id'] = $this->order_model->getUpArea($region_id);
		//区域选择

		// $areaArr = get_express_area();

		// foreach($areaArr as $areaV) {
		// 	$areaList[$areaV['region_id']] = $areaV['region_name'];
		// }
		// $this->getView()->assign('areaList', $areaList);
		$parentArea = $this->order_model->getParentArea();
		foreach($parentArea as $areaV) {
			$parent_areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		$this->getView()->assign('parent_areaList', $parent_areaList);
		$areaArr = $this->order_model->getChildArea($detail[0]['parent_id']);
		// var_dump($areaArr);
		foreach($areaArr as $areaV) {
			$areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		
		$this->getView()->assign('areaList', $areaList);
		$seller_id = $detail[0]["seller_id"];
		// 获取订单内商店详情
		$storeinfo = $this->order_model->getOrderStore($order_id);
		if($detail[0]["from_partner"] == 100032){
			$storeinfo['store_name'] = $detail[0]["seller_name"];
		}
		$orderp = array(
            'actual_shippingfee'=>$detail[0]["actual_shippingfee"],
            'actual_expend'=>$detail[0]["actual_expend"],
            'management_fee'=>$detail[0]["management_fee"],
            'goods_amount'=>$detail[0]["goods_amount"],
            'actual_expend'=>$detail[0]["actual_expend"],
            'packing_fee'=>$detail[0]["packing_fee"],
            'actual_receipt_sp'=>$detail[0]["actual_receipt_sp"],
            'buy_amount'=>$detail[0]["buy_amount"],
            'order_amount'=>$detail[0]["order_amount"],
            'actual_receipt_pf'=>$detail[0]["actual_receipt_pf"],
            'prefer_fee'=>$detail[0]["prefer_fee"],
            'orignalorder_amount'=>$detail[0]["orignalorder_amount"]
        );
		$this->getView()->assign("orderp",$orderp);
		// 订单调度方式
		$this->dispatchType = array('0'=>'未识别','1'=>'短途','2'=>'长途','3'=>'接力');
		// 获取订单内商品详情
		$goodsdetail = $this->order_model->getOrderGoods($order_id);
		// 判断餐厅是否为来客
		$if_laike = $this->order_model->ifLaikeStore($storeinfo['source_store_id']);
		$detail[0]['if_laike'] = $if_laike;
		// $sqlRemark = "SELECT receipt_remark FROM ecm_order_receipt WHERE order_id = $order_id ";
		// $receipt_remark = $this->_db->getOne($sqlRemark);
		// $detail[0]["receipt_remark"] = $receipt_remark;
		// // 历史订单接口
		// $historyOrder = get_history_order($_SESSION['uid'], $_REQUEST['order_id']);
		// // 调用历史操作订单记录
		// $operateorder = $this->order_model->get_operate_order($_REQUEST['order_id']);
		// 支付类型判断，用于区别计算菜品相关金额
		$paymentStatus = $detail[0]["payment_name"] == '在线支付' ? 'online' : 'nonline';
		$this->getView()->assign("paymentStatus", $paymentStatus);
		$this->getView()->assign("dispatchType", $this->dispatchType);
		// $this->getView()->assign("oper", $operateorder);
		// $this->getView()->assign("historyOrder", $historyOrder);
		$this->getView()->assign("orderprice", $orderprice);
		$this->getView()->assign("storeinfo", $storeinfo);
		$this->getView()->assign("goodsdetail", $goodsdetail);
		$this->getView()->assign("detail", $detail);
		$this->getView()->display("crmorder/orderDetail");
	}
	public function ajaxUpdateFeeAction(){
		$order_id = $_REQUEST['order_id'];
	 	$account = $this->order_model->getOrderAmountInfo($order_id);
	 	$result['data'] = $account;
	 	echo json_encode($result);
	}
	// 调用历史操作订单记录
	public function getOperateAction(){
		$operateorder = get_order_operate($_SESSION['uid'], $_REQUEST['order_id'], $_SESSION['key'], $_REQUEST['pi'], $_REQUEST['pc']);
		echo json_encode($operateorder['data']);
	}
	
	// 调用历史订单记录
	public function getHistoryAction(){
		$historyOrder = get_order_history($_SESSION['uid'], $_REQUEST['order_id'], $_SESSION['key'], $_REQUEST['pi'], $_REQUEST['pc']);
		echo json_encode($historyOrder['data']);
	}
	// 收货人信息
	public function consigneeAction()
	{
		// 获取订单详细信息接口
		$userInfoArr = get_member_info_by_id($_SESSION['uid'], $_REQUEST['user_id'], $_SESSION['key']);
		// var_dump($userInfoArr);
		$this->getView()->assign("userInfoArr", $userInfoArr['data']);
		$this->getView()->assign("user_id", $_REQUEST['user_id']);
		$this->getView()->display("crmorder/consignee");
	}

	// 收货人地址信息
	public function consigneeAddressAction()
	{
		// 获取收货人地址信息接口
		$userInfoArr = get_consignee_address($_SESSION['uid'], $_REQUEST['user_id'], $_SESSION['key']);
		//print_r($userInfoArr);
		echo json_encode($userInfoArr['data']);
	}

	// 根据用户id获取收货人的历史订单
	public function consigneeHistoryAction()
	{
		// 获取收货人历史订单信息接口
		$userHistory = get_consignee_history($_SESSION['uid'], $_REQUEST['user_id'], $_SESSION['key'], $_REQUEST['pi'], $_REQUEST['pc']);
		//print_r($userInfoArr);
		echo json_encode($userHistory['data']);
	}
	// 获取被操作订单的信息存入本地storage
	public function getLocalOperateAction(){
		
		$result = $this->order_model->getLocalOperate($_REQUEST['order_id']);
		$result['data']['order_status'] = $this->orderStatus[$result['data']['order_status']];
		$result['data']['changed_status'] = $this->orderStatus[$result['data']['changed_status']];
		echo json_encode($result['data']);
	}

	// 修改收货信息

	public function modifyReceiptAction()
	{
		$value    = $_POST['value'];
		$type     = $_POST['type'];
		$order_id = $_POST['order_id'];
		switch ($type) {
			case 'consignee':
				$table = 'ecm_order_extm';
				$fields = "consignee = '$value'";
				$where = $order_id;
				echo $this->order_model->changeReceipt($table,$fields,$where);
				break;
			case 'phone_mob':
				$table = 'ecm_order_extm';
				$fields = "phone_mob = '$value'";
				$where = $order_id;
				echo $this->order_model->changeReceipt($table,$fields,$where);
				break;
			case 'address':
				$table = 'ecm_order_extm';
				$fields = "address = '$value'";
				$where = $order_id;
				echo $this->order_model->changeReceipt($table,$fields,$where);
				break;
			case 'kfremark':
				$where = $_SESSION['username'];
				// 获取添加备注的客服信息
				$emp = $this->order_model->getEmpname($where);
				$emp_name = $emp['emp_name'];
				$uid = $_SESSION['uid'];
				$now = date("Y-m-d H:i:s",time());
				// 获取添加备注之前的历史备注
				$arr =  $this->order_model->getRemark($fields= 'remark1',$order_id);
				$new = '时间:'.$now.'用户:['.$uid.",".$emp_name."] 内容:".$value."<br />";
				$remark = $arr['remark1'].$new;
				$table = 'ecm_order';
				$fields = "remark1 = '$remark'";
				$where = $order_id;
				$this->order_model->changeReceipt($table,$fields,$where);
				echo $remark;
				break;
			case 'ddremark':
				$where = $_SESSION['username'];
				$emp = $this->order_model->getEmpname($where);
				$emp_name = $emp['emp_name'];
				$uid = $_SESSION['uid'];
				$now = date("Y-m-d H:i:s",time());
				// 获取添加备注之前的历史备注
				$arr =  $this->order_model->getRemark($fields= 'remark2',$order_id);
				$new = '时间:'.$now.'用户:['.$uid.",".$emp_name."] 内容:".$value."<br />";
				$remark = $arr['remark2'].$new;
				$table = 'ecm_order';
				$fields = "remark2 = '$remark'";
				$where = $order_id;
				$this->order_model->changeReceipt($table,$fields,$where);
				echo $remark;
				break;
			case 'request_time':
				$request_time = strtotime($value);
				$table = 'ecm_order_extm';
				$fields = "request_time = '$request_time'";
				$where = $order_id;
				echo $this->order_model->changeReceipt($table,$fields,$where);
				break;
			case 'dispatch_type':
				$table = 'ecm_order_extm';
				$fields = "dispatch_type = '$value'";
				$where = $order_id;
				echo $this->order_model->changeReceipt($table,$fields,$where);
				break;
			case 'area':
				$region = explode(",",$value);
				// 获取区域信息
				$regioninfo = $this->order_model->getRegioninfo($order_id);
				$remark          = "修改区域：从[$regioninfo[region_id].$regioninfo[region_name]]改为[$region[0].$region[1]]";
				$log_time 		 = time();
				$table = 'ecm_order_extm';
				$fields = "region_id = '$region[0]',region_name = '$region[1]'";
				$where = $order_id;
				// 修改区域
				$this->order_model->changeReceipt($table,$fields,$where);
				// 修改区域记录插入order_log
				$ecm_order_log = array(
					'order_id' 		=> $order_id,
					'operator' 		=> $_SESSION['uid'],
					'order_status' 	=> '-100',
					'changed_status'=> '-100',
					'remark' 		=> $remark,
					'log_time' 		=> $log_time,
				);
				$ecm_order_log_region = joinKeyValue($ecm_order_log);
				echo $this->order_model->insertRegionLog($ecm_order_log_region);
		}
	}
	public function checkSuperAreaAction(){
		$city = $_REQUEST['city'];
		$address = $_REQUEST['address'];
		$partner_id = 100000;
		$sign = md5("address=".$address."city=".$city."partner_id=".$partner_id);
		$result = check_super_area($address,$city,$partner_id,$sign);
		echo json_encode($result['data']);
	}
	// 显示该店铺的菜品
	public function addGoodsAction()
	{
		$store_id = $_REQUEST['store_id'];
		$order_id = $_REQUEST['order_id'];
		$page = $_REQUEST['topage'];
		$limit = $_REQUEST['pageSize'];
		$now_goods_id = explode(',', $_REQUEST['goods_id']) ;
		$where = "store_id = '$store_id' AND price >= 0";
		$goods_array = $this->order_model->getAllGoods($limit, $where);
		if($goods_array['state'] == 1) {
			$goodsArr = $goods_array['data'];
		}
		$count = ceil(count($goodsArr) / 10);
		$this->getView()->assign("count_goods",$count);
		$this->getView()->assign("have_goods",$now_goods_id);
		$this->getView()->assign("store_id",$store_id);
		$this->getView()->assign("addgoods",$goodsArr);
		$this->getView()->assign("order_id",$order_id);
		$this->getView()->display("crmorder/addGoods");
	}
	// 添加菜品页的菜品搜索
	public function searchGoodsAction()
	{
		$goods_name = $_REQUEST['goods_name'];
		$store_id = $_REQUEST['store_id'];
		$where = "store_id = $store_id AND goods_name like '%$goods_name%' AND price >= 0";
		$goods_array = $this->order_model->getAllGoods($limit, $where);
		if($goods_array['state'] == 1) {
			$goodsArr = $goods_array['data'];
		}
		$num = count($goodsArr);
		$goods['data'] = $goodsArr;
		$goods['totalItems'] = $num;

		echo json_encode($goods);

	}
	// ajax获取菜品分页
	public function ajaxAddGoodsAction()
	{
		$store_id = $_REQUEST['store_id'];
		$where = "store_id = '$store_id' AND price >= 0";

		$pagecount = empty($_REQUEST['pc'])?10:$_REQUEST['pc'];
		$pageindex = empty($_REQUEST['pi'])?0:$_REQUEST['pi']-1;
		$pageindex = $pageindex*$pagecount;
		$limit =  ' '.$pageindex.','.$pagecount ;
		$limit = ' limit '.$limit;

		$goods_array = $this->order_model->getAllGoods($limit, $where);
		if($goods_array['state'] == 1) {
			$goodsArr = $goods_array['data'];
		}
		$num = count($goodsArr);
		$goods['data'] = $goodsArr;
		$goods['totalItems'] = $num;

		echo json_encode($goods);
	}
	// 执行添加菜品
	public function addDishesAction()
	{
		$addgoods_id = $_REQUEST['params'];
		$store_id = $_REQUEST['store_id'];
		$now_goods_id = explode(',', $_REQUEST['goods_id']) ;
		// $goods_id = join(',',$_POST['goods_id']);
		$order_id = $_REQUEST['order_id'];
		// $goods = $this->order_model->getHaveGoods($order_id);
		$goods_id = explode(",",$addgoods_id);
		// if(count($goods)){
		// 	foreach($goods as $key=>$value)
		// 	{
		//   		$have_goods[] = $value['goods_id'];
		// 	}
		// 	$addgoods_id = array_diff($goods_id,$have_goods);
		// 	$addgoods_id = implode(",",$addgoods_id);
		// }
		$addgoods_id = array_diff($goods_id,$now_goods_id);
		if(empty($addgoods_id)){
			die();
		}
		$addgoods_id = implode(",",$addgoods_id);
		$addgoods = $this->order_model->getAddGoods($addgoods_id,$store_id);
		echo json_encode($addgoods);
	}
	public function ifGetFoodAction(){
		$order_id = $_REQUEST['order_id'];
		$result = $this->order_model->getOrderOperate($order_id);
		foreach ($result['data'] as $k => $v) {
			$changed_status[] = $v['changed_status'];
		}
		if(in_array(22, $result)){
			echo 1;
		}else{
			echo 0;
		}
	}
	// 确认订单
	public function confirmOrderAction()
	{
		$uid			= $_SESSION['uid'];
		$goods			= $_POST['goods'];
		$order_id		= $_POST['order_id'];
		$consignee		= $_POST['consignee'];
		$phone_mob		= $_POST['phone_mob'];
		$address		= $_POST['address'];
		$buildingid 	= $_POST['buildingid'];
		$status			= $_POST['status'];
		$kf1			= $_POST['kf1'];
		$shipping_fee   = $_POST['shipping_fee'];
		$request_time	= $_POST['request_time'];
		$management_fee = $_POST['management_fee'] == null ? 0 : $_POST['management_fee'];
		$prefer_fee		= $_POST['prefer_fee'];
		$goods = ecm_json_encode($goods);
		$data =	 array(
			'cm'			=> $phone_mob, 
			'cn'			=> $consignee, 
			'address'		=> $address , 
			'request_time'	=> $request_time,
			'prefer_fee'	=> $prefer_fee,
			'reason'		=> '',
			'bd_id'			=> $buildingid,
			'rk'			=> $kf1,
			'data'			=> $goods,
		);
		
		// echo $uid.PHP_EOL;
		// echo $order_id.PHP_EOL;
		// echo $management_fee.PHP_EOL;
		// echo $shipping_fee.PHP_EOL;
		// echo $status.PHP_EOL;
		$result = confirm_order($uid, $order_id, $management_fee, $shipping_fee, $status, $data);
		// var_dump($result);
		if($result['status'] == -10){
			echo $result['status'];
			exit;
		}
		echo $result['is_success'];
	}
	// 确认订单前检查区域
	public function regionconfirmAction(){
		$order_id = $_REQUEST['order_id'];
		$result = $this->order_model->regionconfirm($order_id);
		echo $result['data'];
	}
	// 批量确认订单
	public function batchConfirmAction()
	{
		$uid = $_SESSION['uid'];
		$order_id = $_POST['ids'];
		$result = batch_confirm($uid,$order_id);
		if($result['status'] == -10){
			echo $result['status'];
			exit;
		}
		echo $result['is_success'];
	}
	
	// 取消订单
	public function cancelOrderAction()
	{
		$this->getView()->assign('order_id', $_REQUEST['order_id']);
		$this->getView()->assign('order_status', $_REQUEST['order_status']);
		$this->getView()->assign("username", $_SESSION['username']);
		$this->getView()->assign("empname", $_SESSION['empname']);
		$this->getView()->display("crmorder/cancelOrder");
	}
	
	// 更新订单，取消/关闭/退回 - SQL
	public function updateOrderDBAction()
	{
		// $data = array('exception_value' => $_REQUEST['remark']);
		$status = $_REQUEST['status'];
		// 更新订单接口
		$result = update_order_status($_SESSION['uid'], $_REQUEST['order_id'], $status, $_SESSION['key'], urlencode($_REQUEST['remark']));
		echo $result;
	}
	
	// 更新订单
	public function updateOrderAction()
	{
		$this->getView()->assign('order_id', $_REQUEST['order_id']);
		$this->getView()->assign('status', $_REQUEST['status']);
		$this->getView()->display("crmorder/updateOrder");
	}
	
	// 取消订单 - SQL
	public function cancelOrderDBAction()
	{
		$data = array('exception_value' => $_REQUEST['remark']);
		// 取消订单接口
		$result = update_order_status($_SESSION['uid'], $_REQUEST['order_id'], ORDER_CANCEL, $_SESSION['key'], $data);
		if($result['status'] == -10){
			echo $result['status'];
			exit;
		}
		echo $result['is_success'];
	}
	
	// 关闭订单
	public function closeOrderAction()
	{
		$this->getView()->assign('order_id', $_REQUEST['order_id']);
		$this->getView()->display("crmorder/closeOrder");
	}
	
	// 关闭订单 - SQL
	public function closeOrderDBAction()
	{
		$data = array('exception_value' => $_REQUEST['remark']);
		// 关闭订单接口
		$result = update_order_status($_SESSION['uid'], $_REQUEST['order_id'], ORDER_ABNORMAL_CLOSED, $_SESSION['key'], $data);
		if($result['status'] == -10){
			echo $result['status'];
			exit;
		}
		echo $result['is_success'];
	}
	// 改派订单
	public function dispatchAction()
	{
		$lgs_id		= $_REQUEST['lgs_id'];
		$order_id 	= $_REQUEST['order_id'];
		$region_id  = $_REQUEST['region_id'];

		$emp_name 	=  '';
		$emp_mobile =  '';
		$s = 'true';
		// $s = 'null';
		$region = 0;
		$empArr = get_emp_search($order_id, $emp_name, $emp_mobile, $s, $region, $_SESSION['key'],$region_id);
		// var_dump($empArr);

		$this->getView()->assign("lgs_id", $lgs_id);
		$this->getView()->assign("username", $_SESSION['username']);
		$this->getView()->assign("empname", $_SESSION['empname']);
		$this->getView()->assign("order_id", $order_id);
		$this->getView()->assign("empArr", $empArr);
		$this->getView()->display("scheduling/dispatch");
	}
	
	// 同意退款理由
	public function agreeRefundAction()
	{
		$this->getView()->assign('order_id', $_REQUEST['order_id']);
		$this->getView()->assign('cooperate_id', $_REQUEST['cooperate_id']);
		$this->getView()->assign('partner_order_id', $_REQUEST['partner_order_id']);
		$this->getView()->display("crmorder/agreeRefund");
	}
	// 拒绝退款理由
	public function rejectRefundAction()
	{
		$this->getView()->assign('order_id', $_REQUEST['order_id']);
		$this->getView()->assign('cooperate_id', $_REQUEST['cooperate_id']);
		$this->getView()->assign('partner_order_id', $_REQUEST['partner_order_id']);
		$this->getView()->display("crmorder/rejectRefund");
	}

	//美团订单同意申请退款
	public function agreeDrawbackAction(){
		$uporder = "UPDATE ecm_order set status = 30 where order_id=".$_REQUEST['order_id'];
		$re = $this->_db->query($uporder);

		if($re){
			$time = time();
			$pushsql = "INSERT INTO ecm_pushorder (cooperate_id, mss_order_id,partner_order_id,newstatus,oldstatus,add_time) VALUES ($_REQUEST[cooperate_id],$_REQUEST[order_id],$_REQUEST[partner_order_id],38,37,$time)";
			$this->_db->query($pushsql);
			$sql = "UPDATE ecm_order_extm SET mss_refund_status = 'Y' WHERE order_id = '$_REQUEST[order_id]' ";
			$this->_db->query($sql);
			$logInfoArr = array(
						'order_id'	=> $_REQUEST['order_id'],
						'operator'	=> $_SESSION['uid'],
						'order_status' => 37,
						'changed_status' => 38,
						'log_time' => $time,
						'remark' => $_REQUEST['remark']
				); 
			$this->order_model->insertOrderLog($logInfoArr);
			$logInfoArr['order_status'] = 38;
			$logInfoArr['changed_status'] = 31;
			$this->order_model->insertOrderLog($logInfoArr);
			// $result = $this->order_model->updateOrderStatus($_REQUEST['order_id'],$_SESSION['uid'],37,38,$time,$_REQUEST['remark']);
			echo 1;
		} else {
			echo 0;
		}
	}
	//美团订单拒绝申请退款
	public function rejectDrawbackAction(){
		$order_id = $_REQUEST['order_id'];
		$remark = $_REQUEST['remark'];
		$sql = "SELECT order_status,changed_status FROM ecm_order_log where order_id = $order_id order by log_id desc";
		$result = $this->_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		$time = time();
		$pushsql = "INSERT INTO ecm_pushorder (cooperate_id, mss_order_id,partner_order_id,newstatus,oldstatus,add_time) VALUES ($_REQUEST[cooperate_id],$_REQUEST[order_id],$_REQUEST[partner_order_id],39,37,$time)";
		$this->_db->query($pushsql);
		$sql = "UPDATE ecm_order_extm SET mss_refund_status = 'N' WHERE order_id = '$_REQUEST[order_id]' ";
		$this->_db->query($sql);
		$logInfoArr = array(
					'order_id'	=> $_REQUEST['order_id'],
					'operator'	=> $_SESSION['uid'],
					'order_status' => 37,
					'changed_status' => 39,
					'log_time' => $time,
					'remark' => $_REQUEST['remark']
			);
		$this->order_model->insertOrderLog($logInfoArr);
		$result = $this->order_model->updateOrderStatus($_REQUEST['order_id'],$_SESSION['uid'],39,$result['order_status'],$time,$_REQUEST['remark']);
		echo $result['status'];
	}
	// 保存订单
	public function saveOrderAction()
	{
		$totime = strtotime("now");
		
		$order_id	= $_REQUEST['order_id'];
		$goods		= json_decode(stripslashes($_REQUEST['value']), true);
		$goodsInfo	= $goods['goodsInfo'];
		$goodsFee	= $goods['goodsFee'];
		unset($goodsFee['purchase_price']);
		unset($goodsFee['shipping_fee']);
		
		$ecm_order = $this->order_model->ecm_order_info($order_id);
		// ecm_order_log | 订单日志
		$ecm_order_log = array(
			'order_id' 		=> $order_id,
			'operator' 		=> $_SESSION['uid'],
			'order_status' 	=> $ecm_order['status'],
			'changed_status'=> $ecm_order['status'],
			'remark' 		=> '',
			'log_time' 		=> $totime,
		);
		$ecm_order_log_KV = joinKeyValue($ecm_order_log);
		$inSql = "INSERT INTO ecm_order_log(".$ecm_order_log_KV['keys'].") VALUES(".$ecm_order_log_KV['vals'].")";
		$this->_db->query($inSql);
		// ecm_order && ecm_order_history | 订单记录
		$goodsFeeKV = joinsKeyValue($goodsFee);
		$upSql = "UPDATE ecm_order SET ".$goodsFeeKV." WHERE order_id = '".$order_id."'";
		$this->_db->query($upSql);
		if($this->_db->affectedRows() == 1) {
			$ecm_order_KV = joinKeyValue($ecm_order);
			$inSql = "INSERT INTO ecm_order_history(".$ecm_order_KV['keys'].") VALUES(".$ecm_order_KV['vals'].")";
			$this->_db->query($inSql);
		}		
		// ecm_order_goods && ecm_order_goods_history | 菜品信息
		$goods_id_arrs = array();
		foreach($goodsInfo as $goods_id => $goodsInfoV) { // goods_id add or update
			$goods_id_arrs[] = $goods_id;
			$ecm_order_goods = $this->order_model->ecm_order_goods_info($order_id, $goods_id);
			$goodsInfoArr = array(
				'quantity'		=> $goodsInfoV['quantity'],
				'price' 		=> $goodsInfoV['price'],
				'packing_fee' 	=> $goodsInfoV['packing_fee'],
				'goods_remark' 	=> $goodsInfoV['goods_remark'],
			);
			if(is_array($ecm_order_goods)) {
				$goodsInfoKV = joinsKeyValue($goodsInfoArr);
				$changeSql = "UPDATE ecm_order_goods SET ".$goodsInfoKV." WHERE rec_id = '".$ecm_order_goods['rec_id']."'";
			} else {
				$ecm_goods = $this->order_model->ecm_goods_info($goods_id);
				$goodsInfoArr += array(
					'order_id'	=> $order_id,
					'goods_id'	=> $goods_id,
					'goods_name'=> $ecm_goods['goods_name'],
					'summary' 	=> $ecm_goods['summary'],
					// 'nreceipt_discount' => $ecm_goods['nreceipt_discount'],
				);
				$goodsInfoKV = joinKeyValue($goodsInfoArr);
				var_dump($goodsInfoKV);exit;
				$changeSql = "INSERT INTO ecm_order_goods(".$goodsInfoKV['keys'].") VALUES(".$goodsInfoKV['vals'].")";
			}
			$this->_db->query($changeSql);
			if($this->_db->affectedRows() == 1) {
				$ecm_order_goods_KV = joinKeyValue($ecm_order_goods);
				$inSql = "INSERT INTO ecm_order_goods_history(".$ecm_order_goods_KV['keys'].") VALUES(".$ecm_order_goods_KV['vals'].")";
				$this->_db->query($inSql);	
			}
		}
		$order_goods_info = $this->order_model->ecm_order_goods_order_id($order_id);
		$goods_id_arr = array();
		foreach($order_goods_info as $V) {
			$goods_id_arr[] = $V['goods_id'];
		}
		$goods_id_diff = array_diff($goods_id_arr, $goods_id_arrs);
		if(is_array($goods_id_diff)) { // goods delete
			foreach($goods_id_diff as $goods_id) {
				$deSql = "DELETE FROM ecm_order_goods WHERE order_id = '".$order_id."' AND goods_id = '".$goods_id."'";
				$this->_db->query($deSql);
			}
		}
		/* // ecm_order_extm && ecm_order_extm_history | 菜品金额
		$ecm_order_extm = $this->order_model->ecm_order_extm_info($order_id);
		$goodsFeeKV = joinsKeyValue($goodsFee);
		$changeSql = "UPDATE ecm_order_extm SET ".$goodsFeeKV." WHERE order_id = '".$order_id."'";
		echo $changeSql;
		// $this->_db->query($changeSql);
		if($this->_db->affectedRows() != 1) {
			$ecm_order_extm_KV = joinKeyValue($ecm_order_extm);
			$inSql = "INSERT INTO ecm_order_extm_history(".$ecm_order_extm_KV['keys'].") VALUES(".$ecm_order_extm_KV['vals'].")";
			echo $inSql;
			// $this->_db->query($inSql);	
		} */
		
		if(is_array($goods)){
			echo 1;
		} else {
			echo 0;
		}
	}
	// 餐厅确认
	public function resConfirmAction(){
		$order_id = $_REQUEST['order_id'];
		$result = res_confirm($order_id);
		// var_dump($result);
		if($result['status'] == -10){
			echo $result['status'];
			exit;
		}
		echo $result['is_success'];
	}
	// 下单
	public function placeOrderAction(){
		$order_id = $_REQUEST['order_id'];
		$placeInfo = $this->order_model->getPlaceOrderInfo($order_id);
		$sendStatus = $this->order_model->getOrderSendStatus($order_id);
		if($sendStatus == 1){
			echo -1;
			exit;
		}
		$orderSend = array(
			'create_time' => time(), 
			'order_id' => $order_id, 
			'partner_id' => $placeInfo['partner_id'], 
			);
		$orderSendLog = array(
			'order_id' => $order_id,
			'operator' => $_SESSION['uid'],
			'order_status' => $this->resOrder[$placeInfo['res_confirm']],
			'changed_status' => RES_LOG_ORDERED,
			'remark' => '下单',
			'log_time' => time(),
			'deliver_type' => 1,
			);
		if($this->order_model->insertArr("ecm_order_send",$orderSend)){
			$this->order_model->updateResConfirm($order_id);
			echo $this->order_model->insertArr("ecm_order_log",$orderSendLog);
		}
	}
	// 保存菜品信息
	public function saveOrderGoodsAction(){
		$orderGoods		= json_decode(stripslashes($_REQUEST['orderGoods']), true);
		$order_id = $_REQUEST['order_id'];
		$uid = $_SESSION['uid'];
		$orderGoods = ecm_json_encode($orderGoods);
		$result = saveOrderGoods($order_id,$uid,$orderGoods);
		echo $result;
	}
	public function ifResConfirmAction(){
		$order_id = $_REQUEST['order_id'];
		$ifResConfirm = $this->order_model->ifResConfirm($order_id);
		if($ifResConfirm == 1){
			echo 1;
		}else{
			echo 0;
		}
	}
	public function mobileAction()
	{
		$this->getView()->assign("手机消息管理");
	}
	
	/**
	 * 更新ecm_order表status状态
	 * @param int		order_id	订单ID
	 * @param int		status		订单状态
	 * @param resource	db			数据库连接资源
	 * @return int
	 */
	public function update_ecm_order_status($order_id, $status, $db)
	{
		$sql = "UPDATE ecm_order SET status = '".$status."' WHERE order_id = '".$order_id."'";
		$db->query($sql);
		if($this->_db->affectedRows() == 1){
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * 获取订单ID的上一条和下一条记录
	 * @param int		order_id	订单ID
	 * @param int		orderBy		排序字段
	 * @param resource	orderByVal	排序字段结果
	 * @return array
	 */
	function prev_next_order($order_id, $orderBy, $orderByVal, $orderType, $orderTypeArr) {
		$searchSql = " 1 ";
		if($orderType == 'neworder' || $orderType == 'backorder') {
			$statusStr = implode(',', $orderTypeArr[$orderType]);
			$searchSql .= " AND a.status IN ($statusStr) ";
		}
		$searchPrevSql = $searchSql . " AND ((a.order_id < $order_id AND $orderBy = $orderByVal) OR $orderBy < $orderByVal) ORDER BY $orderBy DESC, a.order_id DESC LIMIT 1";
		$prevSql = "SELECT a.order_id FROM ecm_order a JOIN ecm_order_extm b ON a.order_id = b.order_id WHERE $searchPrevSql";
		$prevOrder = $this->_db->getOne($prevSql);
		
		$searchNextSql = $searchSql . " AND ((a.order_id > $order_id AND $orderBy = $orderByVal) OR $orderBy > $orderByVal) ORDER BY $orderBy ASC LIMIT 1";
		$nextSql = "SELECT a.order_id FROM ecm_order a JOIN ecm_order_extm b ON a.order_id = b.order_id WHERE $searchNextSql";
		$nextOrder = $this->_db->getOne($nextSql);
		return array('prevOrder' => $prevOrder, 'nextOrder' => $nextOrder);
	}

	/**
	 * ajax获取原因
	 * @return html
	 */
	public function ajaxGetCauseAction()
	{
		$g_close_array = array(
			array('code'=>'100','name'=>'趣活原因','children'=>array(
				array('code'=>'10010','name'=>'延时预告'),
				array('code'=>'10011','name'=>'途损'),
				array('code'=>'10012','name'=>'迟到'),
				array('code'=>'10013','name'=>'错餐'),
				array('code'=>'10014','name'=>'漏下单'),
				array('code'=>'10015','name'=>'其他'),
				array('code'=>'10016','name'=>'运力不足'),
				array('code'=>'10017','name'=>'系统原因')
			)),
			array('code'=>'101','name'=>'店铺原因','children'=>array(
				array('code'=>'10110','name'=>'出餐晚'),
				array('code'=>'10111','name'=>'漏餐'),
				array('code'=>'10112','name'=>'餐品质量'),
				array('code'=>'10113','name'=>'拒单'),
				array('code'=>'10114','name'=>'售罄'),
				array('code'=>'10115','name'=>'其他'),
				array('code'=>'10115','name'=>'餐厅失联')
			)),
			array('code'=>'102','name'=>'合作伙伴原因','children'=>array(
				array('code'=>'10210','name'=>'系统原因')
				)),
			array('code'=>'103','name'=>'客户原因','children'=>array(
				array('code'=>'10310','name'=>'客户失联'),
				array('code'=>'10311','name'=>'重复订单'),
				array('code'=>'10312','name'=>'客户取消'),
				array('code'=>'10313','name'=>'其他'),
				array('code'=>'10314','name'=>'恶意订单')
			)),
			array('code'=>'104','name'=>'测试','children'=>array())
		);
		
		$type = $_REQUEST['type'];
		$val = $_REQUEST['val'];
		
		$optionStr = "<option value='0'>请选择原因</option>";
		switch($type) {
			case "initbig":
				foreach($g_close_array as $k => $v) {
					$optionStr .= "<option value=[{$v['code']},{$v['name']}]>{$v['name']}</option>";
				}
				break;
			case "getsmall":
				foreach($g_close_array[$val]['children'] as $k => $v) {
					$optionStr .= "<option value=[{$v['code']},{$v['name']}]>{$v['name']}</option>";
				}
				break;
			default:
				break;
		}
		echo $optionStr;
	}

	//ajax 获取区域（接口 返回json）
	public function getAreaAction(){
		$parent_id = $_REQUEST['parent_id'];
		if(!$parent_id){
			$parent_id == 0;
		}
		$parent_area = $this->order_model->getChildArea($parent_id);
		foreach($parent_area as $k => $v) {
			$area[$v['region_id']] = $v['region_name'];
		}
		echo json_encode($area);
	}
	//ajax 获取城市（接口 返回json）
	public function getCityAction(){
		$parentArea = $this->order_model->getParentArea();
		$parent_areaList[0] = '全部';
		foreach($parentArea as $areaV) {
			$parent_areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		echo json_encode($parent_areaList);
	}
	// public function ajaxGetBigArea($parent_id){
	// 	$bigArea = $this->order_model->getAreaInfo($where="parent_id",$parent_id);
	// 	$parent_areaList[0] = '全部';
	// 	foreach($parentArea as $areaV) {
	// 		$parent_areaList[$areaV['region_id']] = $areaV['region_name'];
	// 	}

	// }
	//ajax 获取区域
	public function ajaxGetAreaAction($parent){
		$parent_id = $_POST['parent_id'] ? $_POST['parent_id'] : ($parent ? $parent : '');
		$type = $_POST['type'];
		$area = explode(",", $_POST['area_id']);
		$parent_area = $this->order_model->getChildArea($parent_id);
		if($type == "orderdetail"){
			$parentOptionStr .= "<option value='0' selected>请选择</option>";
			foreach($parent_area as $k => $v) {
				if(in_array($v['region_id'], $area) || $type == "neworder") {
					$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} selected>{$v['region_name']}</option>";
				} else {
					$parentOptionStr .= "<option value={$v['region_id']}{$v['name']}>{$v['region_name']}</option>";
				}
			}
			echo $parentOptionStr;
			exit;
		}
		if($type == "servicearea"){
			$uparea = $this->order_model->getUpArea($_POST['area_id']);
			if(empty($this->areaUserArr)){
				foreach($parent_area as $k => $v) {
					$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} >{$v['region_name']}</option>";
				}
				echo $parentOptionStr;
				exit;
			}else{
				foreach($parent_area as $k => $v) {
					if(in_array($v['region_id'], $this->areaUserArr)) {
						$select = "selected";		
					} else {
						$select = '';
					}
					$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} {$select}>{$v['region_name']}</option>";
				}
				echo $parentOptionStr;
				exit;
			}
		}
		if($type == "neworder"){
			$uparea = $this->order_model->getUpArea($_POST['area_id']);
			if(empty($this->areaUserArr)){
				foreach($parent_area as $k => $v) {
					$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} selected >{$v['region_name']}</option>";
				}
				echo $parentOptionStr;
				exit;
			}else{
				foreach($parent_area as $k => $v) {
					if(in_array($v['region_id'], $this->areaUserArr)) {
						$select = "selected";		
					} else {
						$select = '';
					}
					$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} {$select}>{$v['region_name']}</option>";
				}
				echo $parentOptionStr; 
				exit;
			}
		}
		if($parent_id == 0) {
			foreach($parent_area as $k => $v) {
				if(in_array($v['region_id'], $area) || $type == "neworder") {
					$select = "selected";		
				} else {
					$select = '';
				}
				if(empty($_POST['area_id']) || $_POST['area_id'] == "null") {
					$select = "selected";
				}
				$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} {$select}>{$v['region_name']}</option>";
			}
			echo $parentOptionStr;
			exit;
		}

		foreach($parent_area as $k => $v) {
			if(in_array($v['region_id'], $area) || $type == "neworder") {
				$select = "selected";
			} else {
				$select = '';
			}
			$uparea = $this->order_model->getUpArea($area[0]);
			if($type != "neworder" && $uparea != $parent_id) {
					$select = "selected";
			}
			$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} {$select}>{$v['region_name']}</option>";
		}
		echo $parentOptionStr;
	}
	//ajax 获取服务区域
	function ajaxGetServiceAreaAction(){
		$area = explode(",", $_POST['area_id']);
		$parent_id = $_POST['parent_id'];
		$type = $_POST['type'];
		// 调度员所属区域
		$areaUserBrr = $this->order_model->getUserRegionId($_SESSION['username']);
		foreach($areaUserBrr as $value){
			$areaUserArray[]= $value['er_regionid'];
		}
		$areaArr = get_express_area();
		if($areaUserArray != ''){
			foreach($areaArr as $areaV){
				if(is_array($areaUserArray)){
					foreach($areaUserArray as $value){
						if($value == $areaV['region_id']){
							if(in_array($value, $area)){
								$select = "selected";
							}elseif($type == 1){
								$select = "selected";
							}else{
								$select = '';
							}
							$parentOptionStr .= "<option value={$value} {$select}>{$areaV['region_name']}</option>";
						}
					}
				}
			}
			echo $parentOptionStr;
		}else{
			$this->ajaxGetAreaAction($parent_id);
		}
	}
	
	/**
	 * 获取搜索数据
	 * @return 	array
	 */
	function getData(){
		$search['search'] = $_REQUEST['search'];
		$search['orderBy'] = trim($_REQUEST['orderBy']);
		$search['csOrder'] = trim($_REQUEST['csOrder']);
		$search['partner'] = $_REQUEST['partner'];
		$search['order_sn'] = trim($_REQUEST['order_sn']);
		$search['emp_name'] = trim($_REQUEST['emp_name']);
		$search['consignee'] = trim($_REQUEST['consignee']);
		$search['phone_mob'] = trim($_REQUEST['phone_mob']);
		$search['add_times'] = trim($_REQUEST['add_times']);
		$search['add_timee'] = trim($_REQUEST['add_timee']);
		$search['orderStatus'] = $_REQUEST['orderStatus'];
		$search['parent_area'] = $_REQUEST['parent_area'];
		$search['seller_name'] = trim($_REQUEST['seller_name']);
		$search['payment_name'] = trim($_REQUEST['payment_name']);
		$search['selectAllarea'] = $_REQUEST['selectAllarea'];
		$search['request_times'] = trim($_REQUEST['request_times']);
		$search['request_timee'] = trim($_REQUEST['request_timee']);
		$search['selectItemarea'] = $_REQUEST['selectItemarea'];
		$search['partner_order_id'] = trim($_REQUEST['partner_order_id']);
		$search['selectAllpartner'] = $_REQUEST['selectAllpartner'];
		$search['selectAllorderStatus'] = $_REQUEST['selectAllorderStatus'];
		return $search;
	}
	/**
	 * @param int		emp_id		订单ID
	 * @return array
	 */
	public function expressDetailAction()
	{
		$empId = $_REQUEST['emp_id'];
		$order_array = $this->order_model->empOrders($empId, $this->page, $this->limit);
		$order_summary_result = $this->order_model->empOrderSUM($empId, $this->limit);
		foreach($order_array as $key => $values)
		{
			if($values['getfood_time'] == 0)
			{
				$order_array[$key]['getfood_time'] = "";
			}
		}		
		$this->getView()->assign('result', $order_array);
		$this->getView()->assign("store_summary", $order_summary_result);
		$this->getView()->display("crmorder/empOrders");
	}
}
?>