<?php
class OrderputinController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{	$orderModel=new OrderModel();
		$baseModel=new BaseModel();
		global $LOG_MAIN_CONFIG;
		$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/web/soa.meishisong.cn/soa-putin-%s.log';	
		Logger::configure($LOG_MAIN_CONFIG);
		$log = Logger::getLogger('default');
		$post_data = file_get_contents("php://input");//$GLOBALS['HTTP_RAW_POST_DATA'];\
		$post_data = urldecode($post_data);
		$post_data = str_replace('\\','\\\\',$post_data);
		set_time_limit(0);
		if (empty($post_data)){
			$return['ret'] = "-5";
			$return['msg'] = "未得到可处理数据";
			$res=json_encode($return);
			$log->info($res);
			echo $res;
			return;
		}

		$all_data=json_decode($post_data,true);
		$order=array(
				"consignee"=>$all_data['custom_info']['consignee'],
				"tel"=>$all_data['custom_info']['phone_tel'],
				'address'=>$all_data['custom_info']['address'],
				'add_time'=>$all_data['add_time'],
				'require_time'=>$all_data['request_time'],
				'packing_fee'=>$all_data['packing_fee']?$all_data['packing_fee']:0,
				'shipping_fee'=>$all_data['shipping_fee'],
				'partner_order_id'=>$all_data['partner_order_id'],
				'remark'=>$all_data['remark']?$all_data['remark']:'',
				'receipt'=>$all_data['receipt']?$all_data['receipt']:''
						
				);
			$partner_id=$all_data['partner_id'];
			//不需要审核直接推送的合作伙伴
			$directPush=array('20000010','20000011');
/* 			if(in_array($partner_id,$directPush)){
				$order['to_delivery']='mess';
				$order['status']='receive';
			} */
			if(empty($order['address']) || empty($order['address'])||empty($order['partner_order_id'])||empty($partner_id)){
				$return['ret'] = "-6";
				$return['msg'] = "数据不全";
				$return['data'] = $order;
				$res=json_encode($return);
				$log->info($res);
				echo $res;
				return;
			}
			$memsql='select * from soa_parent where	status=1 and partner_id ="'.$partner_id.'"';
			$meminfo=$baseModel->getInfo($memsql);
			if(!$meminfo){
				$return=array('ret'=>'-8','msg'=>'总店铺不存在','data'=>'partner_id:'.$partner_id);
				$res=json_encode($return);
				$log->info($res);
				echo $res;
				return;
			}
			$order['from_partner']=$partner_id;
			$order['from_channel']=$meminfo['channel'];
			$itemId=$all_data['order_items']['store_info']['seller_id'];
			$storsql='select * from soa.soa_store where	state=1 and parent_id='.$meminfo['store_id'].'  and item_id ="'.$itemId.'"';
			$storeInfo=$baseModel->getInfo($storsql);
			//$log->info($storeInfo);
			if($storeInfo){
				$order['store_id']=$storeInfo['store_id'];
			}else{
				$return=array('ret'=>'-9','msg'=>'子店铺不存在','data'=>'order_id:'.$order['partner_order_id'].'item_id:'.$itemId);
				$res=json_encode($return);
				$log->info($res);
				echo $res;
				return;
			}
			$sql='select * from soa_order where from_partner='.$order['from_partner'].' and partner_order_id='.$order['partner_order_id'];
			$info=$orderModel->getInfo($sql);
			if($info){
				$return=array('ret'=>'-4','msg'=>'不能重复发送订单','data'=>'order_id:'.$order['partner_order_id']);
				$res=json_encode($return);
				$log->info($res);
				echo $res;
				return;
			}
			$order['goods_amount']=0;
			foreach($all_data['order_items']['order_goods'] as $goodsinfo){
				$goods[]=array(
						'goods_name'=>$goodsinfo['goods_name'],
						'quantity'=>$goodsinfo['quantity'],
						'price'=>$goodsinfo['price']
						);
				$order['goods_amount']+=$goodsinfo['quantity']*$goodsinfo['price'];
			}
			$order['order_amount']=$order['now_order_amount']=$order['goods_amount']+$order['shipping_fee']+$order['packing_fee'];
			//$log->info($order);
			$return=$orderModel->insertOrder(array('order'=>$order,'goods'=>$goods));
			if($return['ret']=='1'&&in_array($partner_id,$directPush)){
				$ch = curl_init();
				//$curl_url = "http://soa.meishisong.cn/orderpush/detail";
				curl_setopt($ch,CURLOPT_URL,PARTNER_URI_SOA_ORDER_PUSH);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $return['msg']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$res=curl_exec($ch);
				echo $res;
				$log->info($partner_id.':'.$order['partner_order_id'].":".$res);
				return;
			}				
			$res=json_encode($return);
			echo $res;
			$log->info($res);
			return;
			
	}
}
?>