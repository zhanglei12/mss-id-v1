<?php
/**
 * 用户相关操作
 * @author	hezhuang
 * @time	2014/07/01
 */
class MemberController extends Yaf_Controller_Abstract{
	// var $_baseModel;
	var $_db;
	var $_db_read;
	var $member_model;
	var $order_model;
	private function init()
	{
		session_start();
		header('Content-type: text/html;charset=UTF-8');
		
		$this->_baseModel = new BaseModel();
		$this->member_model = new MemberModel();
		$this->order_model = new OrderModel();
		$this->_db = Yaf_Registry::get("api_db");
		$this->_db_read = Yaf_Registry::get("api_db_read");
		if(!empty($_GET)) {
			$_REQUEST = $_GET;
		} else if(!empty($_POST)) {
			$_REQUEST = $_POST;
		}
		$this->getView()->assign('app_path', APP_PATH);
	}
	
	public function indexAction()
	{
		die("开发中...");
	}
	
	// 登录
	public function loginAction()
	{
		$lifeTime = 24 * 3600;
		session_set_cookie_params($lifeTime);
		session_start();
		// 客服登录接口
		if($_REQUEST['username'] != '') {
			// 客服登录接口
			$loginArr = customer_service_login($_REQUEST['username'], $_REQUEST['password']);
		//	var_dump($loginArr);exit;
			// if($loginArr['status'] == 1 && $loginArr['role'][0] == 5) {
			if($loginArr['role'][0] == 5) {
				$empArr = $this->member_model->ecm_employee_info($_REQUEST['username']);
				$_SESSION['empname'] = $empArr['emp_name'];
                $_SESSION['username'] = $_REQUEST['username'];
                $_SESSION['uid'] = $loginArr['uid'];
                $_SESSION['key'] = $loginArr['key'];
				
				header("Location: ".WEB_PATH."/custom/crmorder/neworder");
			}
			// else if($loginArr['status'] == 0) {
				// $this->getView()->display("member/login");
			// }
		}
		$this->getView()->display("member/login");
	}
	
	// 退出
	public function quitAction()
	{
		session_start();
		if(isset($_SESSION['username'])) {
			$_SESSION = array();
			/* //如果存在一个会话cookie，通过将到期时间设置为之前1个小时从而将其删除
			if(isset($_COOKIE[session_name()])){
				setcookie(session_name(),'',time()-3600);
			} */
			session_destroy();
		}
		header("Location: ".WEB_PATH."/member/member/login");
	}

	//检测是否有多项权限
	public function getEmpRoleAction()
	{
		$user_info = emp_login($_REQUEST['username'],$_REQUEST['password']);
	}

	// 测试新版登陆 -1登陆失败 -2 多项权限未选择系统 
	public function roleLoginAction()
	{
		$lifeTime = 24 * 3600;
		session_set_cookie_params($lifeTime);
		session_start();
		$choose_module = $_REQUEST['choose_module'];
		if($_REQUEST['username'] != ''){
			$key = md5($_REQUEST['username']);
			$user_info = emp_login($_REQUEST['username'],md5($_REQUEST['password']),$key);
			$role_id = $user_info['data']['role_id'];
			if($user_info['status'] != 1){
				die(json_encode(-1));
			}
			// $_SESSION['empname'] = $user_info['data']['emp_name'];
	  		// $_SESSION['username'] = $_REQUEST['username'];
	  		// $_SESSION['uid'] = $user_info['data']['emp_id'];
	  		// $_SESSION['key'] = $key;
	        $custom_sys = array(2);//客服系统
	        $scheduling_sys = array(5);//调度系统
	        $report_sys = array(3,4,6,9);//报表结账
	        $store_sys = array(7,8);//基础数据
	        $choose_module = $choose_module == '' ? $role_id : $choose_module;
	        switch ($choose_module) {
	        	case '2':// 客服
	        	if(in_array($role_id, $custom_sys)){
	        		$this->member_model->update_emp_logion($user_info['data']['emp_id'],$key);
	        		$this->saveSession($user_info['data']['emp_name'],$_REQUEST['username'],$user_info['data']['emp_id'],$key,$role_id);
	        		die('/custom/crmorder/neworder');
	        	}else{
	        		die('-2');
	        	}
	        		break;
	        	case '5':// 调度
	        	if(in_array($role_id, $scheduling_sys)){
	        		$this->member_model->update_emp_logion($user_info['data']['emp_id'],$key);
	        		$this->saveSession($user_info['data']['emp_name'],$_REQUEST['username'],$user_info['data']['emp_id'],$key,$role_id);
	        		die('/scheduling/scheduling/relayorder');
	        	}else{
	        		die('-2');
	        	}
	        		break;
	        	case '3':// 结账系统 
	        	if(in_array($role_id, $report_sys)){
	        		$this->member_model->update_emp_logion($user_info['data']['emp_id'],$key);
	        		$this->saveSession($user_info['data']['emp_name'],$_REQUEST['username'],$user_info['data']['emp_id'],$key,$role_id);
	        		die('/Reports/Accounts/ManagerAccounts?from=101&act=jzgl');
	        	}else{ 
	        		die('-2');
	        	}
	        		break;
	        	case '4':// 基础数据
	        	if(in_array($role_id, $store_sys)){
	        		$this->member_model->update_emp_logion($user_info['data']['emp_id'],$key);
	        		$this->saveSession($user_info['data']['emp_name'],$_REQUEST['username'],$user_info['data']['emp_id'],$key,$role_id);
	        		die('/store/store/index');
	        	}else{
	        		die('-2');
	        	}
	        		break;
	        	default:
	        		die('/member/member/roleLogin');
	        		break;
	        }
		}
		$this->getView()->display("member/roleLogin");
	}

	public function saveSession($emp_name,$username,$uid,$key,$role){
		$_SESSION['empname'] = $emp_name;
        $_SESSION['username'] = $username;
        $_SESSION['uid'] = $uid;
        $_SESSION['key'] = $key;
        $_SESSION['role'] = $role;
	}
	// 退出
	public function crmQuitAction()
	{
		session_start();
		if(isset($_SESSION['username'])) {
			$_SESSION = array();
			session_destroy();
		}
		header("Location: ".WEB_PATH."/member/member/roleLogin");
	}
	public function testAction()
	{
		session_start();
		// $arr = array('exception_value'=>'test remark');
		// $data = update_order_status($_SESSION['uid'], 266911, 2, $arr);
		
		$_r_data['paytype']=array(
			array('id'=>'10', 'name'=>'在线支付', 'data'=>array()),
			array('id'=>'20', 'name'=>'货到付款', 'data'=>array(
				array('id'=>'40','name'=>'现金付款'),
				array('id'=>'30','name'=>'POS付款')
			))
		);
		var_dump($_r_data);
		
		$this->getView()->display("member/test");
		exit();
		$loginArr = customer_service_login(101052, 111111);
		var_dump($loginArr);
		var_dump($data);
		exit();
		
		
		$cookie = tempnam('.', '~');
		// 初始化一个 cURL 对象
		$curl = curl_init();
		// 设置你需要抓取的URL
		curl_setopt($curl, CURLOPT_URL, 'http://www.meishisong.mobi/api/api.php?app=user&act=logindispatcher&name=101022&password=111111');
		// 设置header
		curl_setopt($curl, CURLOPT_HEADER, 1);
		// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
		$_SESSION['cookieFile'] = $cookie;
		// 运行cURL，请求网页
		$data = curl_exec($curl);
		// 关闭URL请求
		// curl_close($curl);
		
		// preg_match("/PHPSESSID=(.*?)(?:;|\r\n)/", $data, $matches);
		// echo $matches[1];

		preg_match("/\{.+/", $data, $matches);
		var_dump($matches);
		echo "<hr />";
		$val = json_decode($matches[0], true);
		// $val = $this->object_array($val);
		var_dump($val);
		exit();
	}
	
	public function testHtmlAction() {
		$this->getView()->display("member/testHtml");
	}
	
	//注册页面信息审核和写入数据库
	public function  AddAction()
	{
		$this->api_base = Yaf_Registry::get('api_base');
		$this->api_db = Yaf_Registry::get('api_db');
		$user_name=$_POST['user_name'];
		$user_passwd=$_POST['user_passwd'];
		$store_id=$_POST['store_id'];
		//openid判断用户标准
		$openid=$_POST['openid'];
		if(!$user_name){
			return array("state"=>"-1","message"=>"获取用户名称失败!");
		}
		if(!$user_passwd){
			return array("state"=>"-3","message"=>"获取用户密码失败!");
		}
		if(!$store_id){
			return array("state"=>"-4","message"=>"获取店铺信息失败!");
		}
		if(!$openid){
			return array("state"=>"-5","message"=>"获取用户openid失败!");
		}
		//判断用户输入的手机号是否正确
		$mobile_check=$this->_memberModel->check_phone($_POST['user_mobile']);
		//缺少参数
		if(!$user_name || $mobile_check != 1 || !$user_passwd || !$store_id  || !$openid){
			$result=array("state"=>"-5","message"=>"录入信息缺失!");
		}else{
			//先判断用户是否已经注册
			$res=$this->checkAction($openid);
			if($res['state']!=1){
				$result=array("state"=>"-6","message"=>"用户已经注册！");
			}else{
				//密码加密，执行数据录入
				$_POST['user_passwd']=md5($user_passwd);
				$_POST['user_check']=1;
				$_POST['user_role']=2;
				$data=array(
					"user_name"=>$_POST['user_name'],
					"user_id"=>'',
					"user_mobile"=>$_POST['user_mobile'],
					"user_passwd"=>$_POST['user_passwd'],
					"addtime"=>date("Y-m-d  h:i:s",time()),
					"user_check"=>$_POST['user_check'],
					"user_role"=>$_POST['user_role'],
					"openid"=>$openid,
				);
				$insert_id=$this->api_base->insertData2DB("soa.soa_member",$data,$this->api_db,$id=true);
				//取得插入数据的新增id
				if($insert_id){
					//插入关联数据表
					$data_array=array(
						"store_id"=>$_POST['store_id'],
						"member_id"=>$insert_id,
					);
					$this->api_base->insertData2DB("soa.soa_member_store",$data_array,$this->api_db);
					$result=array("state"=>"1","message"=>"提交信息成功!");
				}else{
					$result=array("state"=>"-7","message"=>"提交信息失败!");
				}
			}

		}
		echo json_encode($result);
	}
	
	//判断用户是否已经注册
	public function checkAction($openid)
	{
		$sql="select * from soa.soa_member where openid='".$openid."'";
		
		$res=$this->_baseModel->getInfo($sql);
		
		if($res){
			 return array("state"=>"-2","message"=>"用户已存在");
		}else{
			 return array("state"=>"1","message"=>"用户还未曾注册");
		}
	}
	
	//获取到当前店铺的总共员工和正在审核状态的员工和所有员工信息
	public function memberAction()
	{
		$openid=$_GET['openId'];
		if(!$openid){
			$result = array("state"=>"-1","message"=>"获取用户信息失败!");
			$this->getView()->assign("error_result",$result);
		}else{
			$manager_check=$this->managerAction($openid);
			if($manager_check['state']!="1"){
				$manager_error="您不是店长，无此操作权限!";
				$this->getView()->assign("manager_error",$manager_error);
			}else{
				$sql_store="select store_id from soa.soa_member_store where member_id=(select user_id from soa.soa_member where openid='".$openid."')";
				//判断该用户拥有几家餐厅
				$store=$this->_baseModel->getAllInfo($sql_store);
				$data=array();
				$member_checking=""; //正在审核中的员工
				$member_total="";  //所有的员工
				if(count($store)>1){
					//拥有多家店铺
					foreach($store as $v){
						//店铺id
						$data[$v['store_id']]['store_id']=$v['store_id'];
						$sql_store_array="select * from soa.soa_store where store_id=".$v['store_id'];
						$store_array=$this->_baseModel->getInfo($sql_store_array);
						//店铺名字
						$data[$v['store_id']]['store_name']=$store_array['store_name'];
						//当前店铺的员工
						$sql_member_id="select member_id from soa.soa_member_store where  store_id=".$v['store_id']." AND member_id !=(select user_id from soa.soa_member where openid='".$openid."')";
						$member_id=$this->_baseModel->getAllInfo($sql_member_id);
						$member_id_array="";
						foreach($member_id as $member_v){
							$member_id_array.=$member_v['member_id'].",";
							$member_total+=1;
						}
						$member_id_array=trim($member_id_array,",");
						//员工信息
						$sql_member="select * from soa.soa_member where user_id in(".$member_id_array.")";
						$member=$this->_baseModel->getAllInfo($sql_member);
						foreach($member as $mem_k=>$mem_v){
							if($mem_v['user_check']==1){
								$member_checking+=1;
							}
						}
						$data[$v['store_id']]['member_info']=$member;
					}
					
				}else{
						//店铺id
						$data[$store[0]['store_id']]['store_id']=$store[0]['store_id'];
						$sql_store_array="select * from soa.soa_store where store_id=".$store[0]['store_id'];
						$store_array=$this->_baseModel->getInfo($sql_store_array);
						//店铺名字
						$data[$store[0]['store_id']]['store_name']=$store_array['store_name'];
						//当前店铺的员工
						$sql_member_id="select member_id from soa.soa_member_store where  store_id=".$store[0]['store_id']." AND member_id !=(select user_id from soa.soa_member where openid='".$openid."')";
						$member_id=$this->_baseModel->getAllInfo($sql_member_id);
						$member_id_array="";
						foreach($member_id as $member_v){
							$member_id_array.=$member_v['member_id'].",";
							$member_total+=1;
						}
						$member_id_array=trim($member_id_array,",");
						//员工信息
						$sql_member="select * from soa.soa_member where user_id in(".$member_id_array.")";
						$member=$this->_baseModel->getAllInfo($sql_member);
						foreach($member as $mem_k=>$mem_v){
							if($mem_v['user_check']==1){
								$member_checking+=1;
							}
						}
						$data[$store[0]['store_id']]['member_info']=$member;
						
				}
			
			}

			
				$this->getView()->assign("data",$data);
				$this->getView()->assign("openid",$openid);
				$this->getView()->assign("member_checking",$member_checking);
				$this->getView()->assign("member_total",$member_total);
				$this->getView()->display("member/edit");		
		}
		

	}
	
	//员工状态启用和禁用的操作
	public function changeAction()
	{
		if(!$_POST['user_id']  || !$_POST['user_check'] || !$_POST['openId']){
			$result=array("state"=>"-1","message"=>"获取修改信息失败!");
		}else{
			$manager_check=$this->_memberModel->ifMember($_POST['openId']); //判断操作权限
			if(!$manager_check){
				$result=array("state"=>"-9","message"=>"无操作权限");
			}else{
				$data=array(
					"user_check"=>$_POST['user_check'],
				);
				$this->api_db = Yaf_Registry::get('api_db');
				$sql="update soa.soa_member set user_check=".$_POST['user_check']." where user_id=".$_POST['user_id'];
				$res=$this->api_db->query($sql);
				if($res){
					$result=array("state"=>"1","message"=>"修改信息成功!");
				}else{
					$result=array("state"=>"-2","message"=>"修改信息失败!");
				}
			
			}
			
		}
		echo json_encode($result);
	}
	
	public function testInsertAction()
	{
		$goodsInfo = array(
			'store_id' => '95',
			'goods_name' => '一元菜品',
			'spec_name_1' => '份',
			'if_show' => '0',
			'add_time' => '1416717300',
			'price' => '1',
			'receipt_discount' => '1',
			'nreceipt_discount' => '1'
		);
		$goodsInfoKV = joinKeyValue($goodsInfo);
		$inSql = "INSERT INTO ecm_goods(".$goodsInfoKV['keys'].") VALUES(".$goodsInfoKV['vals'].")";
		echo $inSql;
		// $this->_db->query($inSql);
	}
	// 调度服务区域
	public function serviceAreaAction(){
		if(!isset($_SESSION['username'])) {
			header("Location: ".WEB_PATH."/member/member/login");
		}
		$parentArea = $this->order_model->getParentArea();
		$parent_areaList[0] = '全部';
		foreach($parentArea as $areaV) {
			$parent_service_areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		$this->getView()->assign('parent_service_areaList', $parent_service_areaList);
		$areaArr = get_express_area();
		foreach($areaArr as $areaV) {
			$service_areaList[$areaV['region_id']] = $areaV['region_name'];
		}
		$this->getView()->assign('areaList', $areaList);
		$emp_no = $_SESSION['username'];
		$sql = "SELECT er_regionid FROM ecm_employeeregion WHERE er_empno = ".$emp_no;
		$er_regionid = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		foreach ($er_regionid as $k => $v) {
			$service_area .= $v['er_regionid'].",";
		}
		if(!empty($er_regionid)){
			$uparea = $this->order_model->getUpArea($er_regionid[0]['er_regionid']);
		}else{
			$uparea = '4';
		}
		$this->getView()->assign('parent_service_area', $uparea);
		$this->getView()->assign('service_area', $service_area);
		$this->getView()->display("member/serviceArea");
	}
	public function setServiceAreaAction(){
		$area = $_REQUEST['areaArr'];
		$areaArr = explode(",", $area);
		$emp_no = $_SESSION['username'];
		$sql = "DELETE FROM ecm_employeeregion WHERE er_empno=".$emp_no;
		$result = $this->_db->query($sql);
		foreach ($areaArr as $key => $value) {
			$sql = "INSERT INTO ecm_employeeregion (er_empno,er_regionid,er_type) value (".$emp_no.",".$value.",1)";
			$result = $this->_db->query($sql);
		}
		if($result){
			echo 1;
		}
	}
}
?>