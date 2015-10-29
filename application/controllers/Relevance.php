<?php
//处理推送和取消推送
class RelevanceController extends Yaf_Controller_Abstract
{
	var $store_model;

	public function init()
	{
		$this->store_model = new StoreModel();
	}

	//上架操作
	public function  uploadAction()
	{ 
		$store_ids = $_POST['store_ids'];

		$to_partner = $_POST['to_partner'];

		if(!$store_ids  || !$to_partner)
		{
			echo json_encode(array('state'=>'-1','message'=>'无接收参数'));
			exit();
		}

		$store_ids = trim($store_ids,',');
		
		$partner_sql = "select appkey from crm_partner";

		$partner_result  = $this->store_model->getAllInfo($partner_sql);

		if(empty($partner_result) || !is_array($partner_result))
		{
			echo json_encode(array('state'=>'-2','message'=>'获取合作伙伴失败'));
			exit();
		}

		$partner = array();

		foreach($partner_result as $partner_single)
		{
			$partner[] = $partner_single['appkey'];
		}

		if(!in_array($to_partner,$partner))
		{
			echo json_encode(array('state'=>'-3','message'=>'传输的合作伙伴非法'));
			exit();
		}

		$store_ids = explode(',',$store_ids);

		//已分发的
		$distribute_already = array();
		//没有分发到美食送的
		$distribute_meishisong_disabled = array();
		//分发失败的
		$distribute_failure = array();
		//分发成功的
		$distribute_success = array();

		foreach($store_ids as $store_id)
		{
			//添加，先判断店铺的状态，下架则不能推送
			$pre_sql = "select store_id,state from crm_store where store_id=".$store_id;

			$pre_result = $this->store_model->getInfo($pre_sql);

			if(!$pre_result['store_id'])
			{
				echo json_encode(array('state'=>'-9','message'=>'查询店铺状态时失败'));
				exit();
			}

			if($pre_result['state']=='2')
			{
				$distribute_failure[] = array('store_id'=>$store_id,'message'=>'店铺为下架状态,不能推送');

				continue;
			}

			//1.是否已推送到了美食送
			if($to_partner != '100000')
			{
				$distribute_check  = "select sync_id from crm_partner_store where source_partner_store_id = ".$store_id." AND to_partner_id='100000'";	

				$distribute_result = $this->store_model->getInfo($distribute_check);

				if(!empty($distribute_result) && !is_array($distribute_result))
				{
					echo json_encode(array('state'=>'-4','message'=>'获取当前店铺的分发信息失败','store_id'=>$store_id));
					exit();
				}

				if(!$distribute_result['sync_id'])
				{
					$distribute_meishisong_disabled[] = $store_id;

					continue;
				}		
			}

			//2.是否已经分发

			$check_sql = "select sync_id from crm_partner_store where source_partner_store_id = ".$store_id." AND to_partner_id='".$to_partner."'";

			$check_result = $this->store_model->getInfo($check_sql);

			if(!empty($check_result) && !is_array($check_result))
			{
				echo json_encode(array('state'=>'-5','message'=>'获取当前店铺分发信息失败','store_id'=>$store_id));
				exit();
			}

			if($check_result['sync_id'])
			{
				$distribute_already[] = $store_id;

				continue;
			}

			//3.分发
				//1.先写入关联表
			
				$source_partner_result  = $this->store_model->getInfo("select source_partner_id from crm_store where store_id=".$store_id);

				if(!empty($source_partner_result) && !is_array($source_partner_result))
				{
					echo json_encode(array('state'=>'-6','message'=>'获取店铺的来源失败'));
					exit();
				}

				

				$source_partner_id = $source_partner_result['source_partner_id'];

			mysql_query('START TRANSACTION');

			$partner_array = array(
					'source_partner_id' => $source_partner_id,
					'source_partner_store_id' => $store_id,
					'to_partner_id' => $to_partner,
					'partner_store_state'=>'1',
			);

			

			$sync_id = $this->store_model->insert($partner_array,'crm_partner_store');

			
			if(!$sync_id)
			{
				$distribute_failure[] = array('store_id'=>$store_id,'reason'=>'写入店铺分发表失败');

				 mysql_query('ROLLBACK ');

				continue;
			}

				//2.写入distribute表
				$gcategory_result = $this->store_model->getAllInfo("select cate_id from crm_gcategory where store_id=".$store_id);

				
				$goods_result = $this->store_model->getAllInfo("select goods_id from crm_goods where store_id=".$store_id);

				

				if(!empty($gcategory_result) && !is_array($gcategory_result))
				{
					mysql_query('ROLLBACK ');
					echo json_encode(array('state'=>'-7','messge'=>'获取店铺的分类失败'));
					exit();
				}

				if(!empty($goods_result) && !is_array($goods_result))
				{
					mysql_query('ROLLBACK ');
					echo json_encode(array('state'=>'-8','messge'=>'获取店铺的菜品失败'));
					exit();
				}


				if(empty($goods_result)  || empty($gcategory_result))
				{
					mysql_query('ROLLBACK ');
					$distribute_failure[] = array('store_id'=>$store_id,'message'=>'当前店铺无分类或菜品');

					continue;
				}


				foreach($gcategory_result as $cate)
				{
					$insert_array  = array(
						'belong' => 'gcategory',
						'item_id' => $cate['cate_id'],
						'act'	 => 'add',
						'to_partner_id' =>','.$to_partner.',',
					);

					$cate_distribute_id  = $this->store_model->insert($insert_array,'crm_distribute');


					if(!$cate_distribute_id)
					{
						$distribute_failure[] = array('store_id'=>$store_id,'message'=>'分发菜品分类时失败','菜品分类id'=>$cate['cate_id']);

						 mysql_query('ROLLBACK ');

						 break;
					}
				}


				foreach($goods_result as $good)
				{
					$goods_array = array(
						'belong' => 'goods',
						'item_id' => $good['goods_id'],
						'act'	  => 'add',
						'to_partner_id' =>','.$to_partner.',',
					);

					$distribute_id  = $this->store_model->insert($goods_array,'crm_distribute');

					if(!$distribute_id)
					{
						$distribute_failure[] = array('store_id'=>$store_id,'message'=>'分发菜品时失败','菜品id'=>$good['goods_id']);

						 mysql_query('ROLLBACK ');

						 break;
					}
				}


				$store_insert_array = array(
						'belong' => 'store',
						'item_id' => $store_id,
						'act'	  => 'add',
						'to_partner_id' =>','.$to_partner.',',
				);

				$store_distribute_id = $this->store_model->insert($store_insert_array,'crm_distribute');

				if(!$store_distribute_id)
				{
					$distribute_failure[] = array('store_id'=>$store_id,'message'=>'分发店铺时失败');

					 mysql_query('ROLLBACK ');

					 continue;
				}

				$distribute_success[]  = $store_id;

		}

		//统计结果
		mysql_query("COMMIT");

		mysql_query("END"); 

		echo json_encode(array('state'=>'1','message'=>'上架处理成功','distribute_already'=>$distribute_already,'distribute_meishisong_disabled'=>$distribute_meishisong_disabled,'distribute_failure'=>$distribute_failure,'distribute_success'=>$distribute_success));

		
	}


	//下架操作

	public  function store_off_loadAction()
	{
		//店铺的下架处理
		$to_partner = $_POST['to_partner'];
		$store_ids  = $_POST['store_ids'];

		if(!$to_partner || !$store_ids)
		{
			echo json_encode(array('state'=>'-1','message'=>'下架参数接收失败'));
			exit();
		}

		//1.未在下架合作伙伴的
		$undistribute_store = array();
		//2.下架美食送但是还在其他合作伙伴上架的
		$unreasonable_store = array();
		//3.下架成功的
		$undistribute_success = array();
		//4.下架失败的
		$undistribute_failure = array();


		$partner_sql = "select appkey from crm_partner";

		$partner_result  = $this->store_model->getAllInfo($partner_sql);

		if(empty($partner_result) || !is_array($partner_result))
		{
			echo json_encode(array('state'=>'-2','message'=>'获取合作伙伴失败'));
			exit();
		}

		$partner = array();

		foreach($partner_result as $partner_single)
		{
			$partner[] = $partner_single['appkey'];
		}

		if(!in_array($to_partner,$partner))
		{
			echo json_encode(array('state'=>'-3','message'=>'传输的合作伙伴非法'));
			exit();
		}

		$store_ids = trim($store_ids,',');

		$store_ids = explode(',',$store_ids);

		foreach($store_ids as $store_id)
		{
			//1.如果是从美食送下架，先看和其他合作伙伴有联系
			if($to_partner == '100000')
			{

				$check_result = $this->store_model->getInfo("select sync_id from crm_partner_store WHERE source_partner_store_id=".$store_id." AND to_partner_id !='100000'");


				if(!empty($check_result)  && !is_array($check_result))
				{
					echo json_encode(array('state'=>'-4','message'=>'查询店铺推送关系时失败'));
					exit();
				}

	
				if($check_result['sync_id'])
				{
					$unreasonable_store[] = $store_id;

					continue;
				}
			}


			//2.查询是否以前推送到了当前合作伙伴

			$no_need = $this->store_model->getInfo("select sync_id from crm_partner_store WHERE source_partner_store_id=".$store_id." AND to_partner_id ='".$to_partner."'");

			if(!empty($no_need) && !is_array($no_need))
			{
				echo json_encode(array('state'=>'-5','message'=>'查询店铺推送关系时失败'));
				exit();
			}

			if(empty($no_need['sync_id']))
			{
				$undistribute_store[] = $store_id;
				continue;
			}

			//3.取消关联
			mysql_query('START TRANSACTION');

			 //1.删除关联表
			 $del_result = $this->store_model->delete_sql("delete from crm_partner_store WHERE source_partner_store_id=".$store_id." AND  to_partner_id=".$to_partner);

			 if(!$del_result)
			 {
			 	$undistribute_failure[] = array('store_id'=>$store_id,'message'=>'删除关联表失败');

			 	mysql_query('ROLLBACK ');

			 	continue;
			 }

			 //2.写入distribute表

			 $insert_data  = array(
			 	'belong' => 'store',
			 	'item_id' => $store_id,
			 	'act'	 => 'del',
			 	'to_partner_id' => ','.$to_partner.',',
			 );

			 $insert_result  = $this->store_model->insert($insert_data,'crm_distribute');

			 if(!empty($insert_result) && is_object($insert_result))
			 {
			 		echo json_encode(array('state'=>'-6','message'=>'写入分发表时失败'));
			 		exit();
			 }

			 if(!$insert_result)
			 {
			 	$undistribute_failure[] = array('store_id'=>$store_id,'message'=>'写入分发表时失败');

			 	mysql_query('ROLLBACK ');

			 	continue;
			 }

			  $undistribute_success[] = $store_id;

		}

			//统计结果
			mysql_query("COMMIT");

			mysql_query("END"); 

			echo json_encode(array('state'=>'1','message'=>'下架处理成功','undistribute_success'=>$undistribute_success,'unreasonable_store'=>$unreasonable_store,'undistribute_failure'=>$undistribute_failure,'undistribute_store'=>$undistribute_store));



	}


}

?>