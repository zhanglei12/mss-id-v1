<?php
/*
 *分发店铺:1.店铺信息  2.菜品信息  3.菜品分类信息
 *分发后写入本地的对应表里面
 */
class SendstoreController extends Yaf_Controller_Abstract
{
	var $goods_model;
	var $store_model;
	var $gcategory_model;
	var $send_store_url;
	public function init()
	{
		$this->goods_model = new GoodsModel();
		$this->store_model = new StoreModel();
		$this->gcategory_model = new GcategoryModel();
		$this->send_store_url = CRM_STORE_UPDATE_TO_MEISHISONG;
	}
	public function indexAction()
	{
		$store_id = $_REQUEST['store_id'];
		
		if(!$store_id)
		{
			die(json_encode(array('state'=>'-1','message'=>'param missing')));
		}
		
		$sql = "select * from crm_store where store_id = ".$store_id;
		
		$store_result = $this->store_model->getInfo($sql);
		
		if(!$store_result)
		{
			die(json_encode(array('state'=>'-2','message'=>'store query failed')));
		}
		
	    $goods_sql = "select * from crm_goods where store_id=".$store_id;
		
		$goods_array_result = $this->goods_model->getAllInfo($goods_sql);
		
		if(!$goods_array_result)
		{
			die(json_encode(array('state'=>'-3','message'=>'goods query failed')));
		}
		
		$gcategory_sql = "select * from ecm_gcategory where store_id = ".$store_id;
		
		$gcategory_array_result = $this->gcategory_model->getAllInfo($gcategory_sql);
		
		if(!$gcategory_array_result)
		{
			die(json_encode(array('state'=>'-4','message'=>'gcategory query failed')));
		}
		$data_array = array(
			'store_name'=> urlencode($store_result['store_name']),
			'address'  => urlencode($store_result['address']),
			'store_logo' => urlencode($store_result['store_logo']),
			'longitude' => $store_result['longitude'],
			'latitude' => $store_result['latitude'],
			'region_name' => urlencode($store_result['region_name']),
			'tel' => $store_result['takeout_service_phone'],
			'min_cost' => $store_result['min_cost'],
		);
		
		$ch = curl_init();
		
		curl_setopt($ch,CURLOPT_URL,$this->send_store_url);
		
		curl_setopt($ch,CURLOPT_POST, 1);
		
		curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data_array));
		
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		
		$mss_insert_return = curl_exec($ch);
		
		if($mss_insert_return && is_int($mss_insert_return))
		{
			$insert_array = array(
				'source_partner_id'=> $store_result['source_partner_id'],
				'source_partner_store_id' => $store_result['source_partner_store_id'],
				'to_partner_id'			=>  '100000',
				'to_store_id'			=> $mss_insert_return,
			);
			
			$this->insert_to_crm_store_partner($insert_array);
		}
	
	}
	
	public function insert_to_crm_store_partner($insert_array)
	{
		if(!$insert_array || !is_array($insert_array))
		{
			
		}
	}
}
?>