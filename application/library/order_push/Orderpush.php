<?php
class Orderpush
{
	var $_orderModel;
	
	var $url_order_state;
	
	var $_httprequest;
	
	var $log;
	
	public function __construct($arr)
	{
		$this->_orderModel = new OrderModel();
		
		$this->log = $arr['log'];
	}
		
	public function index()
	{
		$sql = "select * from soa_order where if_push=0 and delivery_status in ('receive','refuse','finish')";
		
		$res = $this->_orderModel->getAllInfo($sql);
		
		if(!$res)
		{
			return array("state"=>"-1","message"=>"没有要推送的订单");
		}
		//俏江南 and ... 
		
		$order = array();
	
		$return=array();
		
		foreach($res as $k=>$v)
		{
			
			switch ($v['from_channel'])
			{
				case 'qiaojiangnan':
					$return[]=$this->sendstates($v['partner_order_id'],$v);
					break;
				default:
					break;
			}
			
		}
		return $return;
	}
	
	public function sendstates($id,$v)
	{
		$this->url_order_state='http://www.juhaituan.com/tdd_notify.php';
		
		$this->_httprequest = new PostMethod();
		
		if($v['delivery_status']=='receive')
		{
			$v['delivery_status']="1";
		}
		
		if($v['delivery_status']=='finish')
		{
			$v['delivery_status']="3";
		}
		
		
		if($v['delivery_status']=='refuse')
		{
			$v['delivery_status']="4";
		}
		
		$arrn = array(
			'ret'=>'1',
			'mss_order_id'=>$v['order_id'],
			'partner_order_id'=>$v['partner_order_id'],
			'order_state'=>$v['delivery_status'],
			'push_time'=>time(),
			'msg'=>'处理俏江南状态发送',
		);
		
		$this->log->info($arrn);
		
		$query = http_build_query($arrn);
		
		$resu =  $this->_httprequest->request_by_curl_get($this->url_order_state,$query);
		
		$log_info = "qiaojiangnan 100014 ".$v['partner_order_id']." ".$v['delivery_status']." ".$resu;
		
		$this->log->info($log_info);
		
		$res = json_decode($resu);
		
		if($res && $res->state=="true")
		{
			
				return $this->updatePush($id);
		
		}else
		{
				
				return $this->formatResult("push_false",$res);
		
		}
		
	
	
	}
	
	public function updatePush($id)
	{
		
		$array=array(
				'if_push' => 1,
		);
		
		$res = $this->_orderModel->update("soa_order",$array,'partner_order_id='.$id);
		
		if($res)
		{
			return $this->formatResult("push_update",'push_and_update_true');
			
		}else
		{
			return $this->formatResult("update_false",'push_true_and_update_false');
		}
	
	}

	public function formatResult($result,$res)
	{
		
		if ($result == 'push_false')
		{
			return array('state'=>-2,'message'=>'推送订单失败','data'=>$res);
		}
		else if($result == 'push_true_and_update_false')
		{
			return array('state'=>-3,'message'=>'更新推送状态失败','data'=>$res);
		}else
		{
			return array('state'=>1,'message'=>'成功');
		}
	}

	
	
	

}
?>