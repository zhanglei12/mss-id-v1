<?php
class ChangestatusController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		$orderModel=new OrderModel();
	 	global $LOG_MAIN_CONFIG;
		$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/web/soa.meishisong.cn/soastatus_change-%s.log';
		Logger::configure($LOG_MAIN_CONFIG);
		$log = Logger::getLogger('default');
		$post_data = file_get_contents("php://input");//$GLOBALS['HTTP_RAW_POST_DATA'];\
		$post_data = urldecode($post_data);
		$post_data = str_replace('\\','\\\\',$post_data);
		set_time_limit(0);
		$ChangeInfo=json_decode($post_data,true);
		//$log->info($allStores);
		 //$session=$allStores['session'];
		$sn=md5("soa_order_id=".$ChangeInfo['soa_order_id'].'&status='.$ChangeInfo['status'].'&time='.$ChangeInfo['time'].'&from='.$ChangeInfo['from']);
		if($sn!=$ChangeInfo['sn']){
			$return=array('ret'=>'-6','msg'=>'验证未通过','data'=>$ChangeInfo);
			$res=json_encode($return);
			//$log->info($res);
			echo $res;
			return;
		}
		$sql='select * from soa.soa_order where status="receive" and order_id='.$ChangeInfo['soa_order_id'];
		$info=$orderModel->getInfo($sql);
		if(!$info){
			$return=array('ret'=>'-1','msg'=>'无对应订单','data'=>$ChangeInfo['soa_order_id']);
			$res=json_encode($return);
			//$log->info($res);
			echo $res;
			return;
		}
		if($info['to_delivery']!=$ChangeInfo['from']){
			$return=array('ret'=>'-2','msg'=>'无修改此订单权利','data'=>$ChangeInfo['soa_order_id']);
			$res=json_encode($return);
			//$log->info($res);
			echo $res;
			return;
		}
		$status=array('receive','refuse','finish');
		if(!in_array($ChangeInfo['status'],$status)){
			$return=array('ret'=>'-3','msg'=>'发送状态值错误','data'=>$ChangeInfo);
			$res=json_encode($return);
			//$log->info($res);
			echo $res;
			return;
		}
		if($info['delivery_status']==$ChangeInfo['status']){
			$return=array('ret'=>'-4','msg'=>'重复发送','data'=>$ChangeInfo['soa_order_id']);
			$res=json_encode($return);
			//$log->info($res);
			echo $res;
			return;
		}
		$upData=array('delivery_status'=>$ChangeInfo['status'],'if_push'=>0);
		$ret=$orderModel->edit($upData,'order_id ='.$info['order_id']);
		if($ret){
			$return=array('ret'=>'1','msg'=>'更新成功','data'=>$ChangeInfo);
			$res=json_encode($return);
			//$log->info($res);
			echo $res;
			return;
		}
		$return=array('ret'=>'-5','msg'=>'更新失败','data'=>$ChangeInfo);
		$res=json_encode($return);
		//$log->info($res);
		echo $res;
		return;
	
	}
	public function signature($arr)
	{
		ksort($arr);
		$str = '';
		foreach ($arr as $key => $value)
		{
			$str .= "{$key}={$value}&";
		}
		$str .= 'sk='.$this->secret_key;
		return md5($str);
	}
}
?>