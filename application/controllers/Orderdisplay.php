<?php
class OrderdisplayController extends BaseController
{
	 var $_orderMode;
	 var $_goodsMode;
	 var $_memberMode;
	 var $_StatusRequest;
	 public function init()
	 {
		$this->_orderMode= new OrderModel();
		$this->_goodsMode= new GoodsModel();
		$this->_memberMode= new MemberModel();
		$file = array(
				'/data/web/public/library/soa/TradeStatusRequest.php'
		);
		yaf_load($file);
		$this->_StatusRequest=new TradeStatusRequest();
	 }
	/*
	*每个操作验证用户权限
	*返回为false 或 用户的信息(info)
	*/
	public function checkAction($openid)
	{
		$result=$this->_memberMode->ifMember($openid);
		return $result;
	
	}
	public  function  indexAction()
	{
		$openid=$_GET['openId'];
		if(!$openid){
			$error_result=array("state"=>"-1","message"=>"获取用户权限信息失败!");
			$this->getView()->assign("error_result",$error_result);
		}else{
			$res=$this->checkAction($openid); //验证用户的权限
			if(!$res){
				$manager_error=array("state"=>"-2","message"=>"你无此操作权限!");
				$this->getView()->assign("manager_error",$manager_error);
			}else{
				$storeList=$this->_memberMode->getStores($openid);
				if(!$storeList){
					$store_error=array("state"=>"-2","message"=>"无法找到管理餐厅");
					$this->getView()->assign("store_error",$store_error);
				}else{
					$data=array(
						'stores'=>$storeList,
						'startime'=>12345,
						'endtime'=>200000000000,
					);
					$data_array=array();
					$orders=$this->_orderMode->getOrderlist($data,"*",0,"require_time");
					if($orders){
						$status=array();
						$require_time=array();  //排序规则
						foreach($orders as $k=>$v)
						{
							$order_info = $v;
							$data_array[$v['order_id']] = $order_info;
							$require_time[$k] = $v['require_time'];
							$data_array[$v['order_id']]['require_time_day'] = date("Y-m-d",$data_array[$v['order_id']]['require_time']);
							$data_array[$v['order_id']]['require_time_hour'] = date("H:i:s",$data_array[$v['order_id']]['require_time']);
							$status[$k] = $v['status'];
						}
						array_multisort($status,SORT_DESC,$require_time,SORT_ASC,$data_array);
			
					}else{
						$data_array['status']=-1;
					}
					
					$sql="select count(*) as count from soa.soa_order where store_id in(".$storeList.")";
					
					$res=$this->_orderMode->getInfo($sql);
					
					$this->getView()->assign("openid",$openid);
					$this->getView()->assign("store",$storeList);
					$this->getView()->assign("count",$res['count']);
					$this->getView()->assign("order_info",$data_array);
					
				
				}
				
			}
			
			
		}
		
			$this->getView()->display("order/index");
	}
	/**
	*  编辑单条订单时的订单显示
	*/
	
	public function editAction()
	{
		try{   
			$order_id=$_GET['order_id'];
			$openid = $_GET['openId'];
			if(!$order_id || !$openid){
				$result=array("state"=>"-1","message"=>"获取订单所需信息失败!");
				$this->getView()->assign("result",$result);
			}else{
				$manager_check=$this->checkAction($openid);
				if(!$manager_check){
					$result=array("state"=>"-2","message"=>"无此操作权限!");
				}else{
					$order_info=$this->_orderMode->getOrderinfo($order_id);
					$order_info['dizhi_status']=$this->addressAction($order_info['address']);  //获取当前地址配送信息
					if(!$order_info){
						$result=array("state"=>"-3","message"=>"获取订单信息异常！");
						$this->getView()->assign("result",$result);
					}else{
						$order_info['require_time']=@date("Y-m-d H:i:s",$order_info['require_time']);
						$this->getView()->assign("order_info",$order_info);
						$this->getView()->assign("openid",$openid);
					}
				}
				
				$this->getView()->display("order/edit");
			}
		}catch (Exception $e) {   
				die(json_encode(array('state'=>"-4",'message'=>'订单数据异常','data'=>$e->getMessage())));
		}   
	}
	/*
	* 编辑界面时新订单的个数
	*  return 新订单个数和订单信息
	*/
	public function ordernewAction()
	{
		$time=time();
		
		$time_old=time()-60*60*20*24;
		
		$store_id = $_POST['store_id'];
		
		$sql="select count(*) as count from soa.soa_order where store_id=".$store_id." AND status='untreated' AND  add_time>=".$time_old." AND add_time<=".$time;
		
		$res=$this->_orderMode->getInfo($sql);
		
		if($res['count']==0){
		
		  $result=array("state"=>"-1","message"=>"当前没有新订单");
		}else{
	
		  $result=array("state"=>"1","message"=>"有新订单","data"=>$res['count']);
		}
		
		echo json_encode($result);
	}
	/*
	* 单条新订单的显示
	* return array("state"=>,"data"=>"新订单信息")
	*/
	public function newshowAction()
	{
		$store_id = $_POST['store_id'];
		
		$sql = "select order_id from soa.soa_order where  store_id=".$store_id." AND status='untreated' order by add_time limit 1";
		
		$res=$this->_orderMode->getInfo($sql);
		
		if(!$res){
		
			$result=array("state"=>"-1","message"=>"当前已没有新订单！");
			
		}else{
		
			$order_info=$this->_orderMode->getOrderinfo($res['order_id']);
			
			$order_info['dizhi_status']=$this->addressAction($order_info['address']);  //获取当前地址配送信息
			
			if(!$order_info){
			
				$result=array("state"=>"-2","message"=>"获取订单信息异常！");
				
			}else{
				
				$result=array("state"=>"1","message"=>"成功获取到订单","data"=>$order_info);
			}	
		}
				
				echo json_encode($result);
	}
	/*
	* 获取当前订单的配送地址是否在美食送的配送范围内
	*/
	public function addressAction($address)
	{
		$opts = array(
			"http"=>array(
				"method"=>"GET",
				"timeout"=>4
			)
		);
		$context = stream_context_create($opts);
		$fp = file_get_contents("http://www.meishisong.cn/map_api/address.php?address=".$address,false,$context);
		$res=json_decode($fp,true);
		return $res['status'];
	}	
	/*
	*订单信息修改
	*/
	public function changeAction()
	{
	  try{
		 if(!$_POST['order_id'] || !$_POST['order_change'] ||!$_POST['order_amount'] || !$_POST['openId'] ){	
				
				$result=array("status"=>"-1","message"=>"获取修改订单失败!");
				
			}else{
			
				//验证用户权限
				
				$manager_check = $this->checkAction($_POST['openId']);
				
				if(!$manager_check){
						
					$result=array("status"=>"-9","message"=>"无操作权限!");
				
				}else{
					$order_id=$_POST['order_id'];
				
					$order_change=$_POST['order_change'];
				
					$user_id=$manager_check['user_id'];
					
					$data=array();
					
					foreach($order_change as $k=>$v){
						if($v['i']=="0"){
							$data[$k]['goods_id']=="";
						}
						$data[$k]['goods_id']=$v['i'];
						
						$data[$k]['goods_name']=$v['n'];
						
						$data[$k]['price']=$v['p'];
						
						$data[$k]['quantity']=$v['r']+$v['q'];
						
						$data[$k]['packing']=$v['k'];
						
						$data[$k]['dis_num']=$v['q'];
						
					}
					/*
					echo "<pre>";
					var_dump($data);
					echo "</pre>";
					*/
					$result=$this->_goodsMode->updataGoods($order_id,$data,$user_id);
			
					if($result['status']  && $result['status']=="1")
					{
		
						$order_amount_now=$result['data']['now_order_amount'];
						
						if($_POST['order_amount'] == $order_amount_now){
							
							//确定修改的变化的订单价格无异议,修改状态
							$data['status'] = "receive";
							
							$data['operator']=$user_id;
							
							$data['delivery']=$_POST['delivery'];
							
							$res=$this->_orderMode->editStatus($order_id,$data);
							
							if(!res){
								$result = array ("status"=>"-4","message"=>"修改订单状态失败!");
							}else{
								$result = array ("status"=>"1","message"=>"订单修改成功!");
							}
						
						}else{
							//若价格不符合，则冻结该账单
							
							$data['status'] = "unusual";
							
							$data['operator']=$user_id;
							
							$res=$this->_orderMode->editStatus($order_id,$data);
							
							if(!res){
							
								$result=array("status"=>"-5","message"=>"冻结账单失败!");
								
							}else{
							
								$result=array("status"=>"-6","message"=>"价格不符冻结账单!");
							}
						
						}
				
					}else{
						
							$result=array("status"=>"-2","message"=>"修改订单菜品和价格失败!");
					}
			
				}
				
			}
				echo json_encode($result);
				
		}catch(Exception $e){
			die(json_encode(array('state'=>"-21",'message'=>'获取修改订单数据异常','data'=>$e->getMessage())));
		}
	
	
	}
	/*订单状态管理 */
	function manageStatus($arr){
		$pname=array('taodiandian'=>'淘点点');
		$partner=array('taodiandian');
		$pinfo=$this->_orderMode->getPstoreInfo($arr['order_id']);
		if(!$pinfo){
			$result=array("state"=>-2,"message"=>"获取总店信息失败!");
			return $result;
		}
		$pinfo['reason']=$arr['reason']?$arr['reason']:'';
		if(in_array($pinfo['channel'], $partner)){
		if($arr['status']=='refuse'){
			$res=$this->_StatusRequest->orderCancel($pinfo);
			$res['name']=$pname[$pinfo['channel']];
			return $res;
		}
		if($arr['status']=='receive'){
			$res=$this->_StatusRequest->orderConfirm($pinfo);
			$res['name']=$pname[$pinfo['channel']];
			return $res;
		}
		}
		return array('state'=>2,'message'=>'无需修改');
	}
	function testAction(){
		$arr=array('order_id'=>28,'status'=>'refuse');
		$re=$this->manageStatus($arr);
		var_dump($re);
	}
	/*取消订单*/
	public function orderdelAction()
	{
		try {   
			$order_id = $_POST['order_id'];
			$reason   = $_POST['reason'];
			$openid   = $_POST['openId'];
			if(!$order_id || !$reason || !$openid){
				$result=array("state"=>"-1","message"=>"获取取消订单数据失败!");
			}else{
				$res=$this->checkAction($openid);
				if(!$res){
					$result=array("state"=>"-2","message"=>"用户无此操作权限!");
				}else{	
					$re=$this->manageStatus(array('order_id'=>$order_id,'status'=>'refuse'));				
					$data['status']="refuse";
					$data['reason']=$reason;
					$data['operator']=$res['user_id'];
					$res=$this->_orderMode->editStatus($order_id,$data);
					if(!$res){
						$result=array("state"=>"-2","message"=>"取消订单失败!");
					}else{
						$result=array("state"=>"1","message"=>"取消订单成功!",'smessage'=>'');
						if($re['state']<1){
							$result['smessage']=$re['name'].'取消异常，请后台确认！';
						}
					}
				}
				
			}
			echo json_encode($result);
		}catch (Exception $e) {   
				die(json_encode(array('state'=>"-3",'message'=>'订单数据异常','data'=>$e->getMessage())));
		}   
	
	}
	
	/*
	* 	修改订单的状态
	*/
	
	public function statusAction()
	{
		$data=array();
		$order_id = $_POST['order_id'];
		$data['status'] = $_POST['status'];
		$openid = $_POST['openId'];
		$data['delivery']=$_POST['delivery'];
		if(!$order_id || !$data['status'] || !$openid  || !$data['delivery']){
			$result=array("state"=>"-1","message"=>"获取修改订单信息缺失!");
		}else{
			$manager_check=$this->checkAction($openid);
			
			if(!$manager_check){
				$result=array("state"=>"-2","message"=>"无此操作权限!");
			}else{
				$data['operator']=$manager_check['user_id'];
				if($data['status']=='receive'){
					$re=$this->manageStatus(array('order_id'=>$order_id,'status'=>'refuse'));
				}
				$res=$this->_orderMode->editStatus($order_id,$data);
				if(!$res){
					$result = array("state" =>"-2","message"=>"修改订单状态失败!");
				}else{
					$result = array("state" =>"1","message"=>"修改订单状态成功!");
					if($re&&$re['state']<1){
						$result['smessage']=$re['name'].'订单异常，请后台确认！';
					}
				}
			}
		}
		
			echo json_encode($result);
	
	}
	
	/*搜索指定订单*/
	
	public function searchAction()
	{
	
	  $name=$_GET['name'];	
	  
	  $store=$_GET['store'];
	  
	  $openid=$_GET['openId'];
	  
	  if(!$name || !$store || ! $openid){
	  
		$result = array("status"=>"-1","message"=>"缺失搜索条件");
	  
	  }else{
	  
		$manager_check=$this->checkAction($openid);
		
		if(!$manager_check){
			
			 $manager_error = array("state"=>"-2","message"=>"无此操作权限!");
			
			 $this->getView()->assign("result",$result);
		
		}else{
		
			$sql="select * from soa.soa_order where store_id in(".$store.") AND (consignee like '%".$name."%' or tel like '%".$name."%')";
		
			$res=$this->_orderMode->getAllInfo($sql);
			
			if(!res){
			
				$result = array("status"=>"-3","message" =>"未找到相应的订单");
				
			}else{
			
				$result=array("status"=>"1","message"=>"订单获取成功","data"=>$res);
			}
		
		
		}
	 
	  }	
	  /*
	  echo "<pre>";
	  var_dump($result);
	  echo "</pre>";
	  */
	  $this->getView()->assign("result",$result);
	  
	  $this->getView()->assign("openid",$openid);
	  
	  $this->getView()->assign("store",$store);
	  
	  $this->getView()->display("order/show");
	
	}
	
	
	

}

?>