<?php
/*
*微信单店的员工管理操作类
*主要有员工的注册，审核和删除
*/
class MemberController extends Yaf_Controller_Abstract{
	// var $_baseModel;
	var $_db;
	
	private function init()
	{
		header('Content-type: text/html; charset=utf-8');
		// $this->_baseModel = new BaseModel();
		// $this->_memberModel = new MemberModel();
		$this->_db = Yaf_Registry::get("api_db");
		if(!empty($_GET)) {
			$_REQUEST = $_GET;
		} else if(!empty($_POST)) {
			$_REQUEST = $_POST;
		}
	}
	
	public function indexAction()
	{
		/* //获取到店铺的信息
		$open_id=$_GET['openId'];
		if(!$open_id){
			$result = array("state"=>"-1","message"=>"获取用户的open_id失败!");
			$this->getView()->assign("result",$result);
		}else{
			$sql="select store_id,store_name from soa.soa_store where state=1";
			$store=$this->_baseModel->getAllInfo($sql);
			$this->getView()->assign("store",$store);
			$this->getView()->assign("openid",$open_id);
		} */
		session_start();
		// 客服登录接口
		if($_REQUEST['username'] != '') {
			$loginArr = file_get_contents("http://www.meishisong.mobi/api/api.php?app=user&act=logindispatcher&name=".$_REQUEST['username']."&password=".$_REQUEST['password']);
			$loginArr = json_decode($loginArr, true);
			if($loginArr['status'] == 1) {
                $_SESSION['username'] = $_REQUEST['username'];
				header("Location: ".WEB_PATH."/custom/crmorder/neworder");
				// $this->getView()->setScriptPath("/")->display(APP_PATH."/application/modules/Custom/views/crmorder/allOrder.phtml");
				//echo '<br>sp:'.$this->getView()->getScriptPath();
				//$this->getView()->display("custom/views/crmorder/allorder");
				//echo '<br>';
				// $this->getView()->display(APP_PATH."/application/modules/custom/views/crmorder/allorder");
			} else if($loginArr['status'] == 0) {
				$this->getView()->display("member/login");
			}
		}
		$this->getView()->display("member/login");
	}
	
	//针对于微信开发判断执行每一步操作时先判断是否有此操作权限
	/*
	public function usercheckAction($openid)
	{
		//判断是否为本店通过的已经启用的人员
		$sql="select * from soa.soa_member where openid=".$openid;
		$store=$this->_baseModel->getInfo($sql);
		if($store['user_check']==1){
			
		}	
	}
	*/
	//点击管理列表时判断该用户有无权限，是否为店长
	public function managerAction($openid){
		$sql="select * from soa.soa_member where openid='".$openid."'";
		$store=$this->_baseModel->getInfo($sql);
		if($store['user_check']==1 || $store['user_role']==2){
			return array("state"=>"-1","message"=>"不是店长,无操作权限!");
		}else{
			return array("state"=>"1","message"=>"审核通过，执行店长操作权限!");
		}
		
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

	public function  checkAction($openid)
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
	

	
	


}

?>