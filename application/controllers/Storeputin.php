<?php
class StoreputinController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		 $baseModel=new BaseModel();
		 $storeModel=new StoreModel();
	 	global $LOG_MAIN_CONFIG;
		$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/web/soa.meishisong.cn/soaStore-%s.log';
		Logger::configure($LOG_MAIN_CONFIG);
		$log = Logger::getLogger('default');
		$post_data = file_get_contents("php://input");//$GLOBALS['HTTP_RAW_POST_DATA'];\
		$post_data = urldecode($post_data);
		$post_data = str_replace('\\','\\\\',$post_data);
		set_time_limit(0);
		$StoreInfo=json_decode($post_data,true);
		//$log->info($allStores);
		 //$session=$allStores['session'];
		$sql='select store_id from soa.soa_parent where partner_id="'.$StoreInfo['partner_id'].'"';
		$info=$baseModel->getInfo($sql);
		if(!$info){
			$return=array('ret'=>'-2','msg'=>'无总店信息','data'=>$StoreInfo['partner_id']);
			$res=json_encode($return);
			$log->info($res);
			echo $res;
			return;
		}
		$storeSql='select * from soa.soa_store where parent_id='.$info['store_id'].'  and item_id ="'.$StoreInfo['itemId'].'"';
		$ifStore=$baseModel->getInfo($storeSql);
		if($ifStore){
			$intoStore=array(
					'store_name'=>$StoreInfo['store_name'],
					'address'=>$StoreInfo['address'],
					'tel'=>$StoreInfo['tel'],
			);
			$re=$baseModel->update('soa_store',$intoStore,'store_id ='.$ifStore['store_id']);
			if($re){
				$return=array('ret'=>'2','msg'=>'更新成功','data'=>$ifStore['store_id']);
				$res=json_encode($return);
				$log->info($res);
				echo $res;
				return;
			}
			$return=array('ret'=>'-1','msg'=>'更新失败','data'=>$intoStore);
					$res=json_encode($return);
					$log->info($res);
					echo $res;
					return;
		}
		//if($ifStore){ 
			$intoStore=array(
					'store_name'=>$StoreInfo['store_name'],
					'address'=>$StoreInfo['address'],
					'tel'=>$StoreInfo['tel'],
					'parent_id'=>$info['store_id'],
					'item_id'=>$StoreInfo['itemId']
					);
			$log->info($intoStore);
			
			$re=$baseModel->api_base->insertData2DB('soa.soa_store',$intoStore,$storeModel->api_db,True);//insert($intoStore,'soa.soa_store');
			if($re){
				$return=array('ret'=>'1','msg'=>'添加成功','data'=>$re);
				$res=json_encode($return);
				$log->info($res);
				echo $res;
				return;
			}
			$return=array('ret'=>'-3','msg'=>'添加失败','data'=>$intoStore);
			$res=json_encode($return);
			$log->info($res);
			echo $res;
			return;
	}
}
?>