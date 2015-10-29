<?php
	class MeiShiSong
	{
		var $appkey;
		var $secretKey;
		var $_httprequest;
		//var $log;
		var $url_order_state;

		public function __construct($arr)
		{
			//$this->appkey = '100013';
			//$this->secretKey = 'a003a29ca40cfad37220339a31c46ee2';
			//$this->url_order_state = 'http://wx.rongchain.com/mobile.php?act=module&op=callback&name=washing&do=quhuoCallBack&weid=5';
			$this->url_order_state = CRM_STORE_UPDATE_TO_MEISHISONG;
			$this->_httprequest = new PostMethod();
			//$this->log = $arr['log'];
		}

		function sendstore($store_array,$goods_array,$store_gcategory_array)
		{
			$source_partner_id  = $store_array['source_partner_id'];

			$source_partner_store_id  = $store_array['store_id'];

			$store_array['business_time'] = json_decode($store_array['business_time']);

			$store_array['business_time'] = get_object_vars($store_array['business_time']);

			foreach($store_array['business_time'] as $k=>$v)
			{
				$store_array['business_time'][$k] = get_object_vars($v);
			}

			foreach($store_array as $k=>$v)
			{
					if($k!='business_time')
					{
						$store_array[$k] = urlencode($v);
					}
			}

			
			foreach($goods_array as $k=>$v)
			{
				foreach($v as $g_k=>$g_v)
				{
					$goods_array[$k][$g_k] = urlencode($g_v);
				}
			}
			foreach($store_gcategory_array as $k=>$v)
			{
				foreach($v as $g_k=>$g_v)
				{
					$store_gcategory_array[$k][$g_k] = urlencode($g_v);
				}
			}
			$store_array = json_encode($store_array);

			$goods_array = json_encode($goods_array);

			$store_gcategory_array = json_encode($store_gcategory_array);

			$arrn = array(
					'store_array' 		=>	$store_array,
					'goods_array'		=>	$goods_array,
					'store_gcategory_array'	=>$store_gcategory_array,
			);

			//print_r($arrn);
			$query = http_build_query($arrn);

			$update_result = $this->_httprequest->request_by_curl_get($this->url_order_state,$query);
		
			$update_result = str_replace('\\','',$update_result);

			$update_result = json_decode($update_result);
			
			if($update_result->state=='1')
			{
				//将店铺对应关系表写入crm_partner_store表
				$mss_store_id =$update_result->data;

				$crm_partner_store_insert_data = array(
					'source_partner_id' => $source_partner_id,
					'source_partner_store_id' =>$source_partner_store_id ,
					'to_partner_id'  => '100000',
					'to_store_id' => $mss_store_id,
				);

				return array('state'=> '1','message'=>'sync correct','data'=>$crm_partner_store_insert_data);

			}else
			{
				return $update_result;
			}

		}
		
	
	}


?>

//同步更新来自crm的店铺数据
	public function crmAction()
	{
		Yaf_Dispatcher::getInstance()->disableView();
		
		$data_array  = $_REQUEST;

		foreach($data_array as $k=>$v)
		{
			$data_array[$k] = urldecode($v);

			$data_array[$k] = str_replace('\\','',$data_array[$k]);

			$data_array[$k] = json_decode($data_array[$k]);

		}

		$store_array = $this->objtoarr($data_array['store_array']);

		$goods_array = $this->objtoarr($data_array['goods_array']);

		$store_gcategory_array = $this->objtoarr($data_array['store_gcategory_array']);

		foreach($store_array as $k=>$v)
		{
			${$k} = $v;
		}

		if(!$store_name || !$region_id  || !$region_name || !$tel || !$takeout_service_phone ||!$address || !$longitude || !$latitude || !$business_time)
		{
			echo json_encode(array('state'=>'-1','message'=>'param missing'));
			exit();
		}

		unset($store_id);

		mysql_query("START TRANSACTION");


		$store_insert_data = array(
			'store_name' => $store_name,
			'owner_name' => $store_name,
			'region_id'	 => $region_id,
			'region_name'=> $region_name,
			'bd_id'		 => $bd_id,
			'address'	 => $address,
			'tel'		 => $takeout_service_phone,
			'sgrade'	 => $category2,
			'add_time'	 => time(),
			'store_logo' => CRM.$store_logo,
			'min_cost'	 => $min_cost,
			'longitude' => $longitude,
			'latitude'	 => $latitude,
			'breakfast_open_time' => $business_time['break_time']['start'],
			'breakfast_close_time' => $business_time['break_time']['end'],
			'lunch_open_time' => $business_time['lunch_time']['start'],
			'lunch_close_time' => $business_time['lunch_time']['end'],
			'supper_open_time' => $business_time['supper_time']['start'],
			'supper_close_time' => $business_time['supper_time']['end'],
		);

		$this->store_model = new StoreModel();

		$insert_store_id = $this->store_model->insert('ecm_store',$store_insert_data);

		//1.先插入店铺信息

		if(!$insert_store_id)
		{
			mysql_query("ROLLBACK");
			echo json_encode(array('state'=>'-2','message'=>'store_data insert error'));
			exit();
		}

		//2.插入店铺的菜品分类信息

		$gcategory_match_id = array();

		foreach($store_gcategory_array  as $k =>$v)
		{
			foreach($v as $g_k => $g_v)
			{
				${$g_k} = $g_v;

				unset($store_id);
			}

			if(!$cate_name || $parent_id==null || $standard_time==null || !$cate_id)
			{
					mysql_query("ROLLBACK");
					echo json_encode(array('state'=>'-3','message'=>'gcategory_data param missing'));
					exit();
			}

			//依照原先的cate_id大小插入（父辈先进）

			if($parent_id !='0')
			{
				$sql ="select parent_id from ecm_gcategory where store_id=".$insert_store_id." AND cate_name='".$cate_name."'";

				$parent_id_result = $this->store_model->getInfo($sql);

				if(!$parent_id_result)
				{
					mysql_query("ROLLBACK");
					echo json_encode(array('state'=>'-301','message'=>'gcategory_parent_query failed'));
					exit();
				}
			}

			$new_parent_id = $parent_id_result['parent_id']?$parent_id_result['parent_id']:'0';

			$gcategory_insert_data = array(
				'store_id' => $insert_store_id,
				'cate_name'=> $cate_name,
				'parent_id'=> $new_parent_id,
				'if_show'  => $if_show,
				'standard_time' => $standard_time,
			);
			//echo "<pre>";print_r($gcategory_insert_data);echo "</pre>";

			$insert_cate_id = $this->store_model->insert('ecm_gcategory',$gcategory_insert_data);

			//echo "<pre>";print_r($insert_cate_id);echo "</pre>";

			if(!$insert_cate_id)
			{
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-4','message'=>'gcategory_data insert failed'));
				exit();
			}

			$gcategory_match_id[$cate_id] = $insert_cate_id;

		}

		 //echo "<pre>";print_r($gcategory_match_id);echo "</pre>";  

		//3.插入菜品信息

		foreach($goods_array as $k=>$v)
		{
			foreach($v as $g_k => $g_v)
			{
				${$g_k} = $g_v;
				unset($store_id);
			}

			if(!$goods_name || !$price || $packing_fee=='null' || !$default_image || !$spec_name || !$cate_id || !$gcategory_id)
			{
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-5','message'=>'goods_data insert failed'));
				exit();	
			}

			$summary = $summary?$summary:'';

			$new_cate_id = '';

			$cate_name ='';

			//echo "<pre>";print_r($gcategory_match_id);echo "</pre>";

			//查询新插入对应的分类id和分类名字
			foreach($gcategory_match_id as $gcategory_k => $gcategory_v)
			{
				//echo "aaa".','.$gcategory_k.",".$gcategory_id;return;
				if($gcategory_k == $gcategory_id)
				{
					$sql = "select * from ecm_gcategory where cate_id =".$gcategory_v;

					$gcategory_id_result = $this->store_model->getInfo($sql);

					//print_r($gcategory_id_result);

					if(!$gcategory_id_result || !is_array($gcategory_id_result))
					{
						mysql_query("ROLLBACK");
						echo json_encode(array('state'=>'-6','message'=>'goods gcategory match query failed'));
						exit();	
					}

					$new_cate_id = $gcategory_v;
					$cate_name = $gcategory_id_result['cate_name'];

				}
			}

			//echo $new_cate_id;echo $cate_name;echo "<br/>";

			$goods_insert_data = array(
				'store_id'   => $insert_store_id,
				'goods_name' => $goods_name,
				'price'		 => $price,
				'packing_fee' => $packing_fee,
				'cate_id'	 => $new_cate_id,
				'cate_name'	 => $cate_name,
				'spec_name_1'=> $spec_name,
				'summary'    => $summary,
				'add_time'    => time(),
				'default_image' => CRM.$default_image,
			);

			//单条插入菜品信息

			$insert_goods_id = $this->store_model->insert('ecm_goods',$goods_insert_data);

			//print_r($insert_goods_id);

			if(!$insert_goods_id)
			{
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-7','message'=>'goods_insert_data  error'));
				exit();	
			}
		}

		//4.事务完成，返回新的store_id

			mysql_query("COMMIT");
			mysql_query("END");

			//print_r(array('state'=>'1','message'=>'insert store complete','data'=>$insert_store_id));
			echo json_encode(array('state'=>'1','message'=>'insert store complete','data'=>$insert_store_id));
			
			exit();
			


		/*
		echo "<pre>";
		print_r($store_array);
		echo "</pre>";	
		*/
	 
	}

	function objtoarr($obj)
	{
		$ret = array();
		foreach($obj as $key =>$value)
		{
			if(gettype($value) == 'array' || gettype($value) == 'object')
			{
				$ret[$key] = $this->objtoarr($value);
			}
			else
			{
				$ret[$key] = $value;
			}
		}
		return $ret;
	}
