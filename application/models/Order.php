<?php
class OrderModel extends BaseModel
{
	var $_db;
	var $_db_read;
	var $table  = 'soa_order';
	var $prikey = 'order_id';
	var $_name  = 'Order';

	function __construct()
	{
		parent::__construct();
		$this->_db = Yaf_Registry::get("api_db");
		$this->_db_read = Yaf_Registry::get("api_db_read");

	}
	/*@查询订单订单列表*/
	public function getOrderlist($Conditions,$fields="*",$limit=array(),$order='',$role=""){
		$condition=array(
				'status'=>$Conditions['status'],
				'stores'=>$Conditions['stores'],  //订单所属的store_id ,like "12,34,45"
				'startime'=>$Conditions['startime'],
				'endtime'=>$Conditions['endtime']
				
		);
		$beginpage=$limit['beginpage']?$limit['beginpage']:0;
		$pagenum=$limit['limit'];
		$limits="";
		if($pagenum){
			$limits=' LIMIT '.$beginpage.','.$pagenum;
		}
		$fields=$fields?$fields:'*';
		$orders=$order?" order by ".$order:" order by order_id desc";
		$conditions="";
		if($condition['status']=='untreated'||$condition['status']=='receive'||$condition['status']=='refuse'){
			$conditions.=' and status ="'.$condition['status'].'"';
		}
		$conditions;
		if($condition['stores']){
			$conditions.=' and store_id in('.$condition['stores'].')';
		}
		if($condition['startime']){
			$conditions.=' and add_time >'.$condition['startime'];
		}
		if($condition['endtime']){
			$conditions.=' and add_time <'.$condition['endtime'];
		}
		 $sql='select '.$fields.' from '.$this->table.' where 1=1 '.$conditions.$orders.$limits;
		return $this->getAllInfo($sql);
	}
	//获取订单操作历史
	function get_operate_order($orderid){
		$sql = "SELECT a.*,b.emp_no,b.emp_name FROM ecm_order_log AS a LEFT JOIN ecm_employee AS b ON a.operator = b.emp_id WHERE a.order_id = '$orderid'";
		$operateorder = $this->_db_read->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		return $operateorder;
	}
	/*获取单张订单详情*/
	function getOrderinfo($orderid){
		$sql='select * from '.$this->table.' where order_id='.$orderid;
		$orderinfo=$this->getInfo($sql);
		$gsql='select * from soa.soa_order_goods where order_id='.$orderid .' AND if_del="N"';
		$orderinfo['goodsinfo']=$this->getAllInfo($gsql);
		$storeSql='select * from soa.soa_store where store_id='.$orderinfo['store_id'];
		$orderinfo['storeInfo']=$this->getInfo($storeSql);
		return $orderinfo;
	}
	/* 根据orderid获得总店铺的信息 */
	function getPstoreInfo($orderId){
		$sql='select o.partner_order_id,p.store_name,p.partner_id,p.partner_key,p.appKey,p.secretkey,p.sessionKey,p.channel from '.$this->table.' as o left join soa_parent as p on o.from_partner=p.partner_id where p.status=1 and o.order_id='.$orderId;
		$info=$this->getInfo($sql);
		return $info;
	}
	/* 插入订单 */
 	function insertOrder($data){
 		$order=array(
 				"consignee"=>$data['order']['consignee'],
				"tel"=>$data['order']['tel'],
				'address'=>$data['order']['address'],
				'add_time'=>$data['order']['add_time'],
 				'require_time'=>$data['order']['require_time'],
				'order_amount'=>$data['order']['order_amount'],
 				'now_order_amount'=>$data['order']['now_order_amount'],
				'goods_amount'=>$data['order']['goods_amount'],
				'packing_fee'=>$data['order']['packing_fee'],
				'shipping_fee'=>$data['order']['shipping_fee'],
				'from_partner'=>$data['order']['from_partner'],
 				'from_channel'=>$data['order']['from_channel'],
				'partner_order_id'=>$data['order']['partner_order_id'],
				'store_id'=>$data['order']['store_id'],
				'remark'=>$data['order']['remark'],
				'receipt'=>$data['order']['receipt']?$data['order']['receipt']:''
 				);
 		if($data['order']['to_delivery']){
 			$order['to_delivery']=$data['order']['to_delivery'];
 		}
 		if($data['order']['status']){
 			$order['status']=$data['order']['status'];
 		}
		$orderid=$this->insertInfo($order);
		$goodsMode= new GoodsModel();
		$statusMode= new StatusModel();
		if($orderid){
			if(!$data['goods']){
				return array('ret'=>'-4','msg'=>'菜品不能为空');
			}
			 foreach($data['goods'] as $gdata){
				$ingoods=$gdata;
				$ingoods['order_id']=$orderid;
				$res=$goodsMode->insertInfo($ingoods);
				if(!$res){
					return array('ret'=>'-1','msg'=>'插入菜品出错');
				}
			} 
			 $arstatus=array(
			 		'add_time'=>Date("Y-m-d H:i:s"),
					"order_id"=>$orderid,
					'status'=>$order['status']?$order['status']:"untreated");
			$restatus=$statusMode->insertInfo($arstatus);
			if(!$restatus){
				return array('ret'=>'-2','msg'=>'插入订单状体出错');
			}
				return array('ret'=>'1','msg'=>$orderid);
		} 
		return array('ret'=>'-3','msg'=>'订单插入出错');
	} 
	/*	修改订单状态
	 *   $id 需修改的订单order_id
	 * $data  array('operator'=> , //操作人,用户id
	 		* 				'status'=>'' //unusual 时无需下面字段
	 		* 				'reason'=>'' //拒绝理由 ，与delivery选一
	 		* 				'delivery'=>'' //'own'自己，mess美食送
	 		* 				)
	 		* 
	* */
	function editStatus($id,$data){
		if(!$id){
			return false;
		}
		$statusMode= new StatusModel();
		$where='order_id ='.$id;
		if($data['operator']){
			$sData['operator_id']=$data['operator'];
			$sData['status']=$data['status'];
			$oData['status']=$data['status'];
			$toData=array("own","mess");
			if($data['status']){
				if($data['status']!='unusual'){
				if($data['status']=='receive'){
					if(in_array($data['delivery'],$toData)){
						$oData['to_delivery']=$data['delivery'];
					}else{
						return false;
					}
				}else if($data['status']=='refuse'){
					$oData['refuse_reason']=$data['reason']?$data['reason']:'无理由';
				}else{
					return false;
				}
				}
			}
			if(!empty($oData)){
				$re=$this->edit($oData,$where);
				if($sData&&$re){
  				return $statusMode->edit($sData, $where);
				}
			
		}
		}
	}
	/*更新订单信息 ，菜品信息修改时应用 */
	function editOrder($order_id){
		$orderInfo=$this->getOrderinfo($order_id);
		if($orderInfo){
			$edData=array(
					'now_order_amount'=>0,
					'goods_amount'=>0,
					'packing_fee'=>0,
					'com_fee'=>0
			);
			foreach($orderInfo['goodsinfo'] as $goodsInfo){
				$edData['goods_amount']+=$goodsInfo['quantity']*$goodsInfo['price'];
				$edData['packing_fee']+=$goodsInfo['quantity']*$goodsInfo['packing'];
			}
			$edData['now_order_amount']=$edData['goods_amount']+$edData['packing_fee']+$orderInfo['shipping_fee'];
			$res=$this->edit($edData, 'order_id ='.$order_id);
			if($res){
				$edData['order_amount']=$orderInfo['order_amount'];
				return array('status'=>1,'data'=>$edData);
			}
			return array(
					'status'=>false,
					'message'=>'更新订单价格信息出错',
					);
		}
	}
	/*生成验证码  */
	public function signature($arr)
	{
		ksort($arr['param']);
		$str = '';
		foreach ($arr['param'] as $key => $value)
		{
			$str .= "{$key}={$value}&";
		}
		$str .= 'sk='.$arr['sk'];
		return strtoupper(md5($str));
		}
	function insertInfo($data){
		return $this->insert($data);
	}
	function edit($data,$where){
		return $this->update($this->table, $data,$where);
	}

	
	/**
	 * 获取全部订单
	 * @param int		page	页码
	 * @param int		limit	条目数
	 * @param str		where	where条件
	 * @return array 
	 */
	public function getAllOrder($page, $limit, $where) {
	   try{
			$page = ($page-1)*$limit;
/*
			$orderSql = "SELECT a.order_id,a.order_sn,a.add_time,a.partner_order_id,a.emp_id,a.emp_name,a.seller_name,a.cs_order_type,a.status,a.from_partner,a.order_type,a.deliver_type,b.region_name,b.request_time,b.consignee,b.region_id,b.address,a.buyer_id,c.emp_no
				FROM ecm_order a
				JOIN ecm_order_extm b ON a.order_id = b.order_id
				JOIN ecm_member j ON a.buyer_id = j.user_id
				LEFT JOIN ecm_employee c ON a.operator_id = c.emp_id
				WHERE $where
				LIMIT $page, $limit";
*/
			$orderSql = "SELECT a.order_id,a.order_sn,a.add_time,a.partner_order_id,a.emp_id,a.emp_name,a.seller_name,a.cs_order_type,a.status,a.from_partner,a.order_type,a.deliver_type,b.region_name,b.request_time,b.consignee,b.region_id,b.address,a.buyer_id
				FROM ecm_order a
				JOIN ecm_order_extm b ON a.order_id = b.order_id
				WHERE $where
				LIMIT $page, $limit";
				// echo $orderSql;
			$orderArr = $this->_db_read->getAll($orderSql, array(), DB_FETCHMODE_ASSOC);
			return array('state'=>'1', 'message'=>'查询成功!', 'data'=>$orderArr);
		} catch(Exception $e) {
			die(json_encode(array('state'=>"-2", 'message'=>'数据异常', 'data'=>$e->getMessage())));
		}
	}
	
	/**
	 * 获取全部订单的订单个数
	 * @param str		where	where条件
	 * @return int
	 */
	public function getAllOrderSUM($where, $limit='50') {
/*
		$orderSql = "SELECT COUNT(a.order_id) count
			FROM ecm_order a
			JOIN ecm_order_extm b ON a.order_id = b.order_id
			JOIN ecm_member j ON a.buyer_id = j.user_id
			WHERE $where";
*/
		$orderSql = "SELECT COUNT(a.order_id) count
			FROM ecm_order a
			JOIN ecm_order_extm b ON a.order_id = b.order_id
			WHERE $where";
		// echo $orderSql;
		$count_result = $this->_db_read->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
		if(!$count_result) {
			return array("state"=>"-1", "message"=>"无店铺信息");
		}
		$page_model = new Page($count_result['count'], $limit);
		$order_summary_show = $page_model->fpage();
		return $order_summary_show;
	}
	public function getCsEmp($user_id,$cs_order_type){
		if($cs_order_type == 2 || $cs_order_type == 3){
			$sql = "SELECT real_name FROM cs.cs_user_extm WHERE user_id = ".$user_id;
		}
		$result = $this->_db_read->getOne($sql);
		return $result;
	}
	/**
	 * 获取当天的配送员信息
	 */
	public function getSameDayEmp($search){
		$allEmpSql = "SELECT a.emp_id 
						FROM ecm_order a 
						LEFT JOIN ecm_employee b ON a.emp_id = b.emp_id 
						WHERE DATEDIFF(FROM_UNIXTIME(a.add_time),CURDATE())=0 ".$search." GROUP BY a.emp_id";
		// $allEmpSql = "SELECT a.emp_id 
		// 				FROM ecm_order a 
		// 				LEFT JOIN ecm_employee b ON a.emp_id = b.emp_id 
		// 				WHERE a.add_time > 1395504000 AND a.add_time < 1395676800 ".$search." GROUP BY a.emp_id";
		$allEmp = $this->_db_read->getAll($allEmpSql, array(), DB_FETCHMODE_ASSOC);
		return $allEmp;
	}
	// 获取排序后的数组
	public function getSortEmp($count,$where,$orderby){
		$sql = "SELECT ".$count."a.emp_id FROM ecm_order a LEFT JOIN ecm_employee b ON a.emp_id = b.emp_id WHERE ".$where." GROUP BY a.emp_id ".$orderby;
		$count_result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $count_result;
	}
	// 获取对应的empid信息
	public function getEmpDetail($v){
		$empsql = "SELECT a.emp_name, a.emp_mobile,b.region_name FROM ecm_employee a LEFT JOIN ecm_region b ON a.account_region = b.region_id WHERE emp_id = ".$v."";
		$empresult = $this->_db_read->getRow($empsql, array(), DB_FETCHMODE_ASSOC);
		return $empresult;
	}
	// 获取对应的empid信息
	public function getEmpOrder($time,$v){
		$sql = "SELECT emp_id,status FROM ecm_order a where ".$time." AND a.emp_id = ".$v." AND a.status in(20,22,30,50)";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	// 获取对应的empid信息
	public function ifLaikeStore($source_store_id){
		$sql = "SELECT id FROM crm.crm_store_attribute where store_id = ".$source_store_id." AND partner_id = 100039 AND is_linked = 'Y'";
		$if_laike = $this->_db->getOne($sql);
		return $if_laike;
	}
	/**
	* 根据送餐员的来源并获取对应的信息
	*/
	public function getEmpInfo($order_id){
		$sql = "SELECT emp_id FROM ecm_order WHERE order_id = $order_id";
		$emp_id = $this->_db_read->getOne($sql);
		if(empty($emp_id)){
			return array('emp_name'=>'','emp_phone'=>'');
		}
		$sql = "SELECT user_name as emp_name,user_phone as emp_phone FROM cs.cs_user WHERE user_id = $emp_id";
		return $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
	}
	/**
	 * 获取全部订单的订单个数
	 * @param str		where	where条件
	 * @return int
	 */
	public function getEmpSUM($where, $limit='50') {
		$sql = "SELECT a.emp_id FROM ecm_order a LEFT JOIN ecm_employee b ON a.emp_id = b.emp_id WHERE a.status in (50,22,20,31) AND a.add_time > ".strtotime("2014-10-11 00:00:00")." AND a.add_time < ".strtotime("2014-10-11 23:59:59")." ".$where."GROUP BY a.emp_id";
		$count_result = $this->_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		// foreach ($count_result as $key => $value) {
		// 	$empArr .= $value['emp_id'].",";
		// }
		// $emp_id = rtrim($empArr,",");
		$page_model = new Page(count($count_result), $limit);
		$order_summary_show = $page_model->fpage();
		// $order_summary_show['emp_id'] = $emp_id;
		return $order_summary_show;
	}

	/**
	 * 获取合作商
	 */
	public function get_cooperate(){
		$sql = "SELECT cooperate_name,appkey FROM ecm_cooperate";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
	 * 获取订单信息
	 * @param int		order_id	订单ID
	 * @return array
	 */
	public function ecm_order_info($order_id) {
		$result = array();
		if($order_id > 0 ) {
			$orderSql = "SELECT * FROM ecm_order WHERE order_id = '".$order_id."' LIMIT 1";
			$result = $this->_db_read->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
		}
		return $result;
	}
	
	/**
	 * 获取订单商品信息
	 * @param int		order_id	订单ID
	 * @param int		goods_id	商品ID
	 * @return array
	 */
	public function ecm_order_goods_info($order_id, $goods_id) {
		$result = array();
		if($order_id > 0 && $goods_id > 0) {
			$orderSql = "SELECT * FROM ecm_order_goods WHERE order_id = '".$order_id."' AND goods_id = '".$goods_id."' LIMIT 1";
			$result = $this->_db_read->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
		}
		return $result;
	}
	
	/**
	 * 获取订单商品信息
	 * @param int		order_id	订单ID
	 * @return array
	 */
	public function ecm_order_goods_order_id($order_id) {
		$result = array();
		if($order_id > 0) {
			$orderSql = "SELECT * FROM ecm_order_goods WHERE order_id = '".$order_id."'";
			$result = $this->_db_read->getAll($orderSql, array(), DB_FETCHMODE_ASSOC);
		}
		return $result;
	}
	
	/**
	 * 获取订单信息扩展信息
	 * @param int		order_id	订单ID
	 * @return array
	 */
	public function ecm_order_extm_info($order_id) {
		$result = array();
		if($order_id > 0 ) {
			$orderSql = "SELECT * FROM ecm_order_extm WHERE order_id = '".$order_id."' LIMIT 1";
			$result = $this->_db_read->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
		}
		return $result;
	}
	
	/**
	 * 获取商品信息
	 * @param int		goods_id	商品ID
	 * @return array
	 */
	public function ecm_goods_info($goods_id) {
		$result = array();
		if($goods_id > 0 ) {
			$orderSql = "SELECT * FROM ecm_goods WHERE goods_id = '".$goods_id."' LIMIT 1";
			$result = $this->_db_read->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
		}
		return $result;
	}

	/**
	 * 获取该店铺的全部菜品
	 * @param int		page	页码
	 * @param int		limit	条目数
	 * @param str		where	where条件
	 * @return array
	 */
	public function getAllGoods($limit, $where) {
	   try{
			// $page = ($page-1)*$limit;

			$goodSql = "SELECT * FROM ecm_goods WHERE $where $limit";
			$goodArr = $this->_db_read->getAll($goodSql, array(), DB_FETCHMODE_ASSOC);
			return array('state'=>'1', 'message'=>'查询成功!', 'data'=>$goodArr);	
		} catch(Exception $e) {
			die(json_encode(array('state'=>"-2", 'message'=>'数据异常', 'data'=>$e->getMessage())));
		}
	}

	/**
	 * 修改收货信息
	 * @param int		order_id	订单ID
	 * @return 
	 */
	public function changeReceipt($table,$fields,$where) {
		$sql = "UPDATE ".$table." SET ".$fields." WHERE order_id = '".$where."'";
		$result = $this->_db->query($sql);
		return $result;
	}

	/**
	 * 获取添加备注的客服信息
	 * @param int		emp_no	客服ID
	 * @return 
	 */

	public function getEmpname($where) {
		$sql = "SELECT emp_name FROM ecm_employee WHERE emp_no = '$where'";
		$result = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	/**
	 * 获取添加备注之前的历史备注
	 * @param int	order_id	订单id
	 * @return 
	 */
	public function getRemark($fields,$where) {
		$sql = "SELECT ".$fields." FROM ecm_order WHERE order_id = '$where'";
		$result = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	/**
	 * 获取之前的订单区域
	 * @param int	order_id	订单id
	 * @return 
	 */
	public function getRegioninfo($where) {
		$sql_region = "SELECT region_id,region_name FROM ecm_order_extm WHERE order_id = '$where'";
		$result = $this->_db_read->getRow($sql_region, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	/**
	 * 修改区域的记录插入order_log
	 * @param int	order_id	订单id
	 * @return 
	 */
	public function insertRegionLog($ecm_order_log_region) {
		$sql = "INSERT INTO ecm_order_log (".$ecm_order_log_region['keys'].") VALUES (".$ecm_order_log_region['vals'].")";
		$result = $this->_db->query($sql);
		return $result;
	}

	/**
	 * 插入
	 * @param varchar	table	表名
	 * @param array	arr	表名
	 * @return 
	 */
	public function insertArr($table,$arr) {
		$reArr = joinKeyValue($arr);
		$sql = "INSERT INTO ".$table." (".$reArr['keys'].") VALUES (".$reArr['vals'].")";
		$result = $this->_db->query($sql);
		return $result;
	}
	/**
	 * 获取订单已有的菜品
	 * @param int	goods_id	菜品id
	 * @return array
	 */
	public function getHaveGoods($where) {
		$sqlg = "SELECT goods_id FROM ecm_order_goods WHERE order_id = '$where'";
		$result = $this->_db_read->getAll($sqlg, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	/**
	 * 获取添加菜品
	 * @param int	goods_id	菜品id
	 * @return array
	 */
	public function getAddGoods($goods_id,$store_id) {
		$sql = "SELECT * 
			FROM ecm_goods
			WHERE goods_id in ($goods_id)
			AND store_id = '$store_id'";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	public function getSelectOrder($order_id) {
		$sql = "SELECT a.order_id,a.order_sn, a.add_time,a.status,a.seller_name,c.address,c.request_time,c.assign_time
				FROM ecm_order a 
				LEFT JOIN ecm_store b ON a.seller_id = b.store_id
				LEFT JOIN ecm_order_extm c ON a.order_id = c.order_id
				WHERE a.order_id in ($order_id)";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	/**
	 * 获取订单详情
	 * @param int	where	订单id
	 * @return array
	 */
	public function getOrderDetail($where) {
		$sql = "SELECT a.*, b.* 
			FROM ecm_order AS a 
			LEFT JOIN ecm_order_extm AS b ON a.order_id = b.order_id 
			WHERE a.order_id = $where";
		$detail = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $detail;
	}

	/**
	 * 获取订单内的店铺信息
	 * @param int	where	订单id
	 * @return array
	 */
	public function getOrderStore($where) {
		$sql = "SELECT a.seller_id, b.* 
			FROM ecm_order AS a 
			LEFT JOIN ecm_store AS b ON a.seller_id = b.store_id 
			WHERE a.order_id = $where";
		$storeinfo = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $storeinfo;
	}

	/**
	 * 获取订单内的商品信息
	 * @param int	where	订单id
	 * @return array
	 */
	public function getOrderGoods($where) {
		$sql = "SELECT a.*,b.member_price,b.nreceipt_discount,b.receipt_discount,b.default_spec 
			FROM ecm_order_goods AS a 
			LEFT JOIN ecm_goods AS b ON a.goods_id = b.goods_id 
			WHERE a.order_id = '$where'";
		$goodsdetail = $this->_db_read->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		return $goodsdetail;
	}

	/**
	 * 获取所有父级区域
	 * @return array
	 */
	public function getParentArea() {
		$parentArea = $this->getAreaInfo($where="parent_id",$data="0");
		return $parentArea;
	}

	/**
	 * 获取对应的区域
	 * @param int	$where	条件	
	 * @return array
	 */
	public function getAreaInfo($where,$data) {
		$sql = "SELECT * FROM ecm_region WHERE $where in ($data)";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	/**
	 * 将数组转换成以逗号分割的字符串	
	 * @param array	$array
	 * @param str   $fields
	 * @return array
	 */
	public function ImplodeArray($array,$fields) {
		foreach($array as $key=>$value) {
		  	$new_array[] = $value["$fields"];
		}
		$result_array = implode(",",$new_array);
		return $result_array;
	}

	/**
	 * 获取所有的子区域	
	 * @return array
	 */
	public function getAllChildArea() {
		$result1 = $this->getAreaInfo($where="parent_id",$data="0");
		$result_array1 = $this->ImplodeArray($result1,$fields="region_id");
		$restul2 = $this->getAreaInfo($where="parent_id",$result_array1);
		$result_array2 = $this->ImplodeArray($restul2,$fields="region_id");
		$result3 = $this->getAreaInfo($where="parent_id",$result_array2);
		return $result3;
	}

	/**
	 * 根据子区域获取其父区域
	 * @param int	$region_id	区域id	
	 * @return int
	 */
	public function getUpArea($region_id) {
		if(!$region_id){
			return $region_id;
		}
		$result1 = $this->getAreaInfo($where="region_id",$region_id);
		$result2 = $this->getAreaInfo($where="region_id",$result1[0]['parent_id']);
		$upArea = $result2[0]['parent_id'];
		return $upArea;
	}

	/**
	 * 获取子区域
	 * @param int	$parent_id	父级区域id	
	 * @return array
	 */
	public function getChildArea($parent_id) {
		if($parent_id == 0) {
			$parent_area = $this->getAllChildArea();
			return $parent_area;
		}
		$result1 = $this->getAreaInfo($where="parent_id",$parent_id);
		$result_array1 = $this->ImplodeArray($result1,$fields="region_id");
		$parent_area = $this->getAreaInfo($where="parent_id",$result_array1);
		return $parent_area;
	}
	/**
	 * 获取建筑物
	 * @param int
	 * @return array
	 */
	public function getBuildingInfo() {
		$sql = "SELECT bd_id,bd_name FROM ecm_building";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		foreach ($result as $k => $v) {
			$building[$v['bd_id']] = $v['bd_name'];
		}
		return $building;
	}
	/**
	 * 获取商店名称
	 */
	public function getStoreInfo($where) {
		$sql = "SELECT store_id,store_name FROM ecm_store $where limit 0,10";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
	 * 获取订单详情
	 * @param int	where	订单id
	 * @return array
	 */
	public function getOrderDetailInfo($order_id) {
		$sql = "SELECT a.*, b.* 
			FROM ecm_order AS a 
			LEFT JOIN ecm_order_extm AS b ON a.order_id = b.order_id 
			WHERE a.order_id = $order_id";
		$detail = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $detail;
	}

	/**
	 * 判断用户是否为新用户
	 * @param int	buyer_id	用户id
	 * @return int
	 */
	public function buyerIsnew($buyer_id) {
		$sql = "SELECT COUNT(a.buyer_id) buyer_num FROM ecm_order a WHERE a.buyer_id = $buyer_id";
		$result = $this->_db_read->getOne($sql);
		return $result;
	}

	/**
	 * 根据区域id 获取区域名称
	 * @param int	region_id	区域id
	 * @return int
	 */
	public function getRegionNameById($region_id) {
		if(!$region_id){
			return array();
		}
		$sql = "SELECT region_id,region_name FROM ecm_region WHERE region_id = $region_id";
		$result = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	/**
	 * 根据店铺id 获取店铺所在区域名称
	 * @param int	store_id	店铺id
	 * @return int
	 */
	public function getRegionNameByStore($store_id) {
		if(!$store_id){
			return array();
		}
		$sql = "SELECT b.parent_id from ecm_store as a left join ecm_region as b on a.region_id = b.region_id where a.store_id = $store_id";
		$parent_id = $this->_db_read->getOne($sql);
		$region_info = $this->getRegionNameById($parent_id);
		return $region_info;
		// if($parent_id){
		// 	$region_name = $this->getRegionNameById($parent_id);
		// }else{
		// 	$region_name = "隐藏区";
		// }
		// $result[$parent_id] = $region_name;
	}

	/**
	 * 根据区域id 获取完整的城市区域信息 例如 北京市 朝阳区 酒仙桥
	 * @param int	region_id	区域id
	 * @return array 返回region_id为键值 region_name为键名的数组
	 */
	public function getIntegrateRegion($region_id) {
		$result1 = $this->getAreaInfo($where="region_id",$region_id);
		$result2 = $this->getAreaInfo($where="region_id",$result1[0]['parent_id']);
		$result3 = $this->getAreaInfo($where="region_id",$result2[0]['parent_id']);
		$regionInfo[$result3[0]['region_id']] = $result3[0]['region_name'];
		$regionInfo[$result2[0]['region_id']] = $result2[0]['region_name'];
		$regionInfo[$result1[0]['region_id']] = $result1[0]['region_name'];
		return $regionInfo;
	}
	/**
	 * 根据订单id 获取订单操作历史
	 * @param int	order_id	区域id
	 * @return array 
	 */
	public function getOrderOperate($order_id) {
		try {
			$sql = "SELECT changed_status FROM ecm_order_log WHERE order_id = '$order_id'";
			$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
			return array('status'=>'1','message'=>'查询成功','data'=>$result);
		} catch (Exception $e) {
			return array('status'=>'-1','message'=>'查询失败','data'=>NULL);
		}
	}
	/**
	 * 根据订单id 获取订单的信息存入本地操作记录
	 * 
	 */
	public function getLocalOperate($order_id) {
		try {
			$sql = "SELECT a.order_sn,b.consignee,b.phone_mob,c.order_status,c.changed_status,c.remark from ecm_order as a left join ecm_order_extm as b on a.order_id = b.order_id left join ecm_order_log as c on a.order_id = c.order_id where a.order_id = '$order_id' order by c.log_id desc";
			$result = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
			return array('status'=>'1','message'=>'查询成功','data'=>$result);
		} catch (Exception $e) {
			return array('status'=>'-1','message'=>'查询失败','data'=>NULL);
		}
	}

	public function regionconfirm($order_id){
		try {
			$sql = "SELECT region_id FROM ecm_order_extm WHERE order_id = '$order_id'";
			$result = $this->_db_read->getOne($sql);
			return array('status'=>'1','message'=>'查询成功','data'=>$result);
		} catch (Exception $e) {
			return array('status'=>'-1','message'=>'查询失败','data'=>NULL);
		}
	}
	//修改订单插入log表
	public function insertOrderLog($arr){
		$array = joinKeyValue($arr);
		$insertsql = "INSERT INTO ecm_order_log (".$array['keys'].") VALUES(".$array['vals'].")";
		return $this->_db->query($insertsql);
	}
	/**
	 * 更改订单状态
	 * 
	 */
	public function updateOrderStatus($order_id,$operator,$oldstatus,$newstatus,$time,$remark) {
		try {
			$sql = "UPDATE ecm_order SET status = $newstatus WHERE order_id = $order_id ";
			$result = $this->_db->query($sql);
			$logInfo = array(
					'order_id'	=> $order_id,
					'operator'	=> $operator,
					'order_status' => $oldstatus,
					'changed_status' => $newstatus,
					'log_time' => $time,
					'remark' => $remark,
			);
			$result = $this->insertOrderLog($logInfo);
			return array('status'=>'1','message'=>'查询成功','data'=>$result);
		} catch (Exception $e) {
			return array('status'=>'-1','message'=>'查询失败','data'=>NULL);
		}
	}
	public function getDeliverType($order_id){
		$sql = "SELECT order_sn,status,emp_id,cs_order_type,deliver_type FROM ecm_order where order_id = $order_id";
		return $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
	} 
	// 获取合作伙伴及店铺id
	public function getPlaceOrderInfo($order_id){
		$sql = "SELECT a.seller_id,a.from_partner,a.res_confirm,c.partner_id 
				FROM nowmss.ecm_order a 
				LEFT JOIN nowmss.ecm_store b ON a.seller_id = b.store_id
				LEFT JOIN crm.crm_store_attribute c ON c.store_id = b.source_store_id WHERE a.order_id = ".$order_id;
		return $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
	}
	public function updateResConfirm($order_id){
		$sql = "UPDATE ecm_order SET res_confirm = ".RES_ORDERED." WHERE order_id = ".$order_id;
		return $this->_db->query($sql);
	}
	public function getOrderSendStatus($order_id){
		$sql = "SELECT send_status FROM ecm_order_send where order_id = ".$order_id;
		return $this->_db_read->getOne($sql);
	}
	public function ifResConfirm($order_id){
		$sql = "SELECT res_confirm FROM ecm_order where order_id = ".$order_id;
		return $this->_db_read->getOne($sql);
	}
	function getSign($url,$arr){
      $str='';
      ksort($arr);
      foreach($arr as $k=>$val){
        $str.=$k.'='.$val.'&';
      }
      $str=trim($str,'&');
      $re=$url.'?'.$str.'fbfeea2d3f2a489d0e9da11e759fdd86';
      return md5(strtolower($re));
  	}
 	function encodeArr($arr){
      if ($arr && is_array($arr)) {
          $str='';
          foreach($arr as $key=>$val){
              $str.=$key.'='.urlencode($val).'&' ;
          }
          $str=rtrim($str,"&") ;
          return $str;
      }
      return false;
  	}
 	// function sendMessage($post_data) {
  //     $url = 'http://cs-api.meishisong.mobi/api/Message/sendMessage';
  //     $sign = $this->getSign($url,$post_data);
  //     $post_data['sign'] = $sign;
  //     $post_str = $this->encodeArr($post_data);
  //     $res = curl_post_file_get_contents($url,$post_str);
  //     return $res;
  // 	}
  	function sendMessage($post_data) {
	      $url = CS_API_URL.'/api/Message/sendMessage';
	      $sign = $this->getSign($url,$post_data);
	      $post_data['sign'] = $sign;
	      $post_str = $this->encodeArr($post_data);
	      $res = curl_post_file_get_contents($url,$post_str);
	      return $res;
	}
	
	/**
	 * 获取调度员的区域信息
	 * @param int	uid	  操作员ID
	 * @return array 
	 */
	public function getUserRegionId($uid){
		$sql = 'SELECT a.`er_regionid`,a.`er_empno`
		FROM nowmss.`ecm_employeeregion` a
		LEFT JOIN nowmss.`ecm_emprole` b ON a.`er_empno` = b.`emp_no`
		WHERE a.`er_empno`='.$uid.' AND b.`role_id` = 5';
		return $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
	}

	/**
	 * 获取订单详情
	 * @param  Int   empId   订单ID
	 * @return Array    
	 */
	public function empOrders($empId, $page, $limit)
	{	
		$page = ($page-1)*$limit;
		$sql = "SELECT a.order_id,a.order_sn,a.seller_name,a.status,a.add_time,b.order_id,b.address,b.assign_time,b.getfood_time 
				FROM nowmss.ecm_order as a
				LEFT JOIN nowmss.ecm_order_extm as b ON a.order_id = b.order_id
		 		WHERE   a.emp_id ='".$empId."' AND a.status NOT in(0,30,50)
		 		LIMIT $page, $limit";
		$result = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	public function empOrderSUM($empId, $limit = '50') {
		$orderSql ="SELECT COUNT(a.order_id) count
				FROM nowmss.ecm_order as a
				LEFT JOIN nowmss.ecm_order_extm as b ON a.order_id = b.order_id
		 		WHERE   a.emp_id ='".$empId."' AND a.status NOT in(0,30,50)";
		$count_result = $this->_db_read->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
		$page_model = new Page($count_result['count'], $limit);
		$order_summary_show = $page_model->fpage();
		return $order_summary_show;
	}
	// 获取订单金额
	public function getOrderAmountInfo($order_id){
		$sql = "SELECT actual_receipt,actual_expend,actual_receipt_sp,actual_packingfee,goods_amount,packing_fee,order_amount,prefer_fee,buy_amount FROM nowmss.ecm_order where order_id = ".$order_id;
		$result = $this->_db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
		return $result;
	}

	//获取订单的状态
	public function OrderStatus($order_id){
		$sql = "SELECT status FROM nowmss.ecm_order WHERE order_id=".$order_id;
		$res = $this->_db_read->getOne($sql);
		return $res;
	}
}
?>