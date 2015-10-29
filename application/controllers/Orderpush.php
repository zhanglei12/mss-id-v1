<?php
/**
 *    给美食送推送订单
 *
 *    @author    Garbin
 *    @usage    none
 */
class OrderpushController extends Yaf_Controller_Abstract
{
 	
	/*用户获取单条订单的方法*/
	
	function detailAction()
	{
		global $LOG_MAIN_CONFIG;
		$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/web/soa.meishisong.cn/soa-push-%s.log';
		Logger::configure($LOG_MAIN_CONFIG);
		$log = Logger::getLogger('default');
		$order_id=$_POST['order_id']?$_POST['order_id']:(file_get_contents("php://input")?file_get_contents("php://input"):'');
		if(!$order_id){
			$result=array('ret'=>"-1","msg"=>"推送的订单信息未知!");
			echo json_encode($result);
		}else{
			$time=time();
			$notify_url = 'http://soa.meishisong.cn';
			$orderModel=new OrderModel();
			$orderInfo=$orderModel->getOrderinfo($order_id);
			$order= array(
					'partner_id'=>'100014',
					'partner_order_id'=>$orderInfo['order_id'],
					'invoice'=>'',
					'if_pay'=>1,
					'push_time'=>$time,
					'notify_url'=>urlencode($notify_url),
					'total_price'=>$orderInfo['order_amount'],
					'add_time'=>$orderInfo['add_time'],
					'request_time'=>$orderInfo['require_time'],
					'remark'=>urlencode($orderInfo['remark']),
					'shipping_fee'=>$orderInfo['shipping_fee'],
					'city'=>urlencode('北京'),
					'custom_info'=>array(
							'buyer_id'=>'',
							'buyer_name'=>urlencode($orderInfo['consignee']),
							'consignee'=>urlencode($orderInfo['consignee']),
							'phone_mob'=>$orderInfo['tel'],
							'phone_tel'=>$orderInfo['tel'],
							'address'=>urlencode($orderInfo['address'])
					));
				$order['prefer_fee']=$orderInfo['now_order_amount']-$orderInfo['order_amount'];
				$order['prefer_fee']=number_format($order['prefer_fee'],2);
			if($orderInfo['from_channel']=='qiaojiangnan'){
				$order['prefer_fee']=$orderInfo['now_order_amount']-$orderInfo['order_amount'];
				$order['prefer_fee']=number_format($order['prefer_fee'],2);
				$order['if_pay']=1;
			}
			foreach($orderInfo['goodsinfo'] as $goods){
				if($goods['if_del']=='Y'){
					continue;
				}
				$order['order_items']['order_goods'][]=array(
						'goods_id'=>$goods['goods_id'],
						'goods_name'=>urlencode($goods['goods_name']),
						'price'=>$goods['price'],
						'quantity'=>$goods['quantity'],
						'specification'=>urlencode('份'),
						'goods_remark'=>'',
						'garnish'=>array(
						)
				);
			}
				
			$order['order_items']['store_info']=array(
					'seller_id'=>$orderInfo['storeInfo']['store_id'],
					'seller_name'=>urlencode($orderInfo['storeInfo']['store_name']),
					'address'=>urlencode($orderInfo['storeInfo']['address']),
					'tel'=>$orderInfo['storeInfo']['tel']
			);
			$order['sign']=md5('partner_id=100014#partner_order_id='.$orderInfo['order_id'].'#push_time='.$time.'#notify_url='.$notify_url.'#key=276117f7ba1acbf9360687ff489128a7');
			$res=$this->pushOrder($order);
			$resarr=json_decode($res,true);
			if($resarr['ret']!='1'){
				$res=$this->pushOrder($order);
				$resarr=json_decode($res,true);
				if($resarr['ret']!='1'){
					$sendSms = Yaf_Registry::get('api_sms');
					$sendSms->send('13269866516,18600181931','soa单号：'.$orderInfo['order_id'].':'.$resarr['msg']);
				}
			}
			if($resarr['ret']=='1'){	
					$orderModel->editStatus($orderInfo['order_id'],array('operator'=>$_POST['operator']?$_POST['operator']:'1','status'=>'receive','delivery'=>'mess'));
			}
			$log->info($orderInfo['order_id'].':'.$res);
			echo $res;
			return;
			
	}
			
	}	
	function pushOrder($order){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,PARTNER_URI_MEISHISONG_ADD_ORDER);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res=curl_exec($ch);
		curl_exec($ch);
		return $res;
	}
}

?>

