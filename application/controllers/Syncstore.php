<?php
//同步店铺（crm和mss）	
class SyncstoreController extends Yaf_Controller_Abstract
{

	var $mss_url = "api.meishisong.cn";  //api.meishisong.mobi

	var $partner_id;

    var $sn;

    var $pagesize;

    var $appkey;

    var $_httprequest;

    var $page;

    var $store_model;

    var $log;

    var $address;

	public function init()
	{
		$this->partner_id = '100000';
   		$this->pagesize = 15;
   		$this->appkey = 'fbfeea2d3f2a489d0e9da11e759fdd86';
   		$this->_httprequest = new PostMethod();
   		$this->store_model = new StoreModel();

   		global $LOG_MAIN_CONFIG;
		$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/web/test/store-%s.log';
		Logger::configure($LOG_MAIN_CONFIG);
		$this->log = Logger::getLogger('default');


		$file = array(
			LIB_PATH_PUBLIC.'/meishisong/address.php',
		);
		yaf_load($file);

		$this->address  = new Address();


	}

	public function syncAction()
	{
			//1.获取店铺信息
	
			$sql = "select * from ecm_store where store_name like '%嘉和一品·粥(五道口)%'";

			$store_array = $this->store_model->getAllInfo($sql);

      		//成功导入的店铺
      		$store_sync_complete = array();

      		//导入失败的店铺

      		$store_sync_error  = array();

      		//原先店铺信息

      		//$store_reset = array();

      		foreach($store_array as $store_v)
      		{
      		  echo '<pre>';print_r($store_v);echo '</pre>';

      		  //$this->log->info($store_v);

      		  //mysql_query("START TRANSACTION");

      		  //2.插入店铺信息
      		  $region_id   =  $store_v['region_id'];


      		  if($region_id==0 || $region_id=='')
      		  {
      		  	$store_sync_error[$store_v['store_id']] = array(
      		  		'store_name' => $store_v['store_name'],
      		  		'message'	=> 'region_id missing',
      		  	);

      		  	$this->log->info($store_sync_error[$store_v['store_id']]);
      		  	continue;
      		  }

      		  if($store_v['longitude']==''  || $store_v['latitude']=='')
      		  {
      		  	$store_sync_error[$store_v['store_id']] = array(
      		  		'store_name' => $store_v['store_name'],
      		  		'message'	=> 'jing wei du missing',
      		  	);
      		  	$this->log->info($store_sync_error[$store_v['store_id']]);
      		  	continue;
      		  }

      		  if(!$store_v['tel'])
      		  {
      		  	$store_sync_error[$store_v['store_id']] = array(
      		  		'store_name' => $store_v['store_name'],
      		  		'message'	=> 'tel  missing',
      		  	);
      		  	 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  	 continue;
      		  }

      		  if(!$store_v['address'])
      		  {
      		  	$store_sync_error[$store_v['store_id']] = array(
      		  		'store_name' => $store_v['store_name'],
      		  		'message'	=> 'address  missing',
      		  	);
      		   $this->log->info($store_sync_error[$store_v['store_id']]);
      		  	continue;
      		  }


      		
      		  //新建region_id 和 region_name
			
      		  $city_region_result = $this->new_region_id($region_id,'0');
      		  $city_region_id = $city_region_result['region_id'];
      		  $city_region_name = $city_region_result['region_name'];

      		  $county_region_result = $this->new_region_id($region_id,$city_region_id);
      		  $county_region_id = $county_region_result['region_id'];
      		  $county_region_name = $county_region_result['region_name'];

      		  $region_id = $region_id;
      		  $city = $city_region_id;
      		  $county  = $county_region_id;
			 
      		  //$region_name = $city_region_name.$county_region_name;

      		  	//反查地址和区域
      		  	//403 反查经纬度和区域的对应关系

      		 	 $region_model = new RegionModel();
		
				 $county_name_result = $region_model->detail($county);
				
				 $location = $store_v['latitude'].",".$store_v['longitude'];
				
				if(!$county_name_result || !$county_name_result['region_name'])
				{
					$store_sync_error[$store_v['store_id']] = array(
      		  			'store_name' => $store_v['store_name'],
      		  			'message'	=> 'quyu id is illegal',
      		  		);
      		  		$this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;
				}
				
				$county_result = $this->areaAction($location);
				
			
				if(!$county_result['state'] || $county_result['state']!='1')
				{
					$store_sync_error[$store_v['store_id']] = array(
      		  			'store_name' => $store_v['store_name'],
      		  			'message'	=> 'tian xie de jing wei du zhao bu dao quyu',
      		  		);
      		  		 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;
				}
				
				if($county_region_name != $county_result['message'])
				{
					$store_sync_error[$store_v['store_id']] = array(
      		  			'store_name' => $store_v['store_name'],
      		  			'message'	=> 'region id  tian xie cuo wu',
      		  		);
      		  		 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;
				}
				
					$store_sync_error[$store_v['store_id']] = array(
						'store_id'  => $store_v['store_id'],
      		  			'store_name' => $store_v['store_name'],
      		  			'message'	=> 'already legal',
      		  		);
      		  		 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;
				
				
				

  		 

      		  //重新拼接营业时间
			 
      		  $breakfast_open_time =substr($store_v['breakfast_open_time'],0,-3);
      		  $breakfast_close_time=substr($store_v['breakfast_close_time'],0,-3);
      		  $lunch_open_time=substr($store_v['lunch_open_time'],0,-3);
      		  $lunch_close_time=substr($store_v['lunch_close_time'],0,-3);
      		  $supper_open_time=substr($store_v['supper_open_time'],0,-3);
      		  $supper_close_time=substr($store_v['supper_close_time'],0,-3);

      		  $break_time = array(
					'start'=>$breakfast_open_time,
					'end'  =>$breakfast_close_time,
			  );
				
			  $lunch_time = array(
					'start'=>$lunch_open_time,
					'end'  =>$lunch_close_time,
			  );
			  $supper_time = array(
					'start'=>$supper_open_time,
					'end'  =>$supper_close_time,
			  );

			  $business_time = json_encode(array('break_time'=>$break_time,'lunch_time'=>$lunch_time,'supper_time'=>$supper_time));


			  $store_insert_data = array(
			  		'store_name' => $store_v['store_name'],
			  		'owner_name' => $store_v['owner_name'],
			  		'address'	 => $store_v['address'],
			  		'store_logo' => $store_v['store_logo'],
			  		'longitude'	 => $store_v['longitude'],
			  		'latitude'	 => $store_v['latitude'],
			  		'region_id'	 => $store_v['region_id'],
			  		'region_name'=> $store_v['region_name'],
			  		'tel'		 => '010-52285085',
			  		'state'		 => $store_v['state'],
					'bd_id'		 => $store_v['bd_id'],
			  		'visibility' => $store_v['visibility'],
			  		'min_cost'   => $store_v['min_cost'],
			  		'business_time'=> $business_time,
			  		'city'		=> $city_region_id,
			  		'county'	=> $county_region_id,
					'checkout_type' =>$store_v['balance'],
			  		'category2' => '88',
			  		'takeout_service_phone'	=> $store_v['tel'],
			  		'source_partner_id' =>'100000',
			  		'source_partner_store_id'=>$store_v['store_id'],
			  );

			  //echo "<pre>";print_r($store_insert_data);echo "</pre>";return;

			  $insert_store_id = $this->store_model->insert($store_insert_data,'crm_store');

			  if(!$insert_store_id)
			  {
			  		$store_sync_error[$store_v['store_id']]= array('message'=>'导入店铺信息失败','store_id'=>$store_v['store_id'],'store_name'=>$store_v['store_name']);
			  		 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;

			  }


			  //写入分发表(重要)

			  $partner_store_insert_data=array(
			  		'source_partner_id' => '100000',
			  		'source_partner_store_id' => $insert_store_id,
			  		'to_partner_id'	    => '100000',
			  		'to_store_id' => $store_v['store_id'],
			  );

			  $partner_store_insert_id = $this->store_model->insert($partner_store_insert_data,'crm_partner_store');

			  if(!$partner_store_insert_id)
			  {
			  		$store_sync_error[$store_v['store_id']]= array('message'=>'写入店铺分发表失败','store_id'=>$store_v['store_id'],'store_name'=>$store_v['store_name']);
			  		 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;

			  }

			  //3.修改分类下对应的store_id
			  
				//获取到原先的分类插入
				$fenlei_sql = "select cate_id,store_id,cate_name,parent_id,if_show,standard_time from  ecm_gcategory where store_id=".$store_v['store_id'];
				
				$fenlei_result = $this->store_model->getAllInfo($fenlei_sql);
				
				if(empty($fenlei_result) || !is_array($fenlei_result))
				{
					$store_sync_error[$store_v['store_id']]= array('message'=>'获取原先的店铺分类失败','store_id'=>$store_v['store_id'],'store_name'=>$store_v['store_name']);
					$this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;
				}
				foreach($fenlei_result as $fenlei_k => $fenlei_v)
				{
					$fenlei_v['state'] = '1';
					$fenlei_v['store_id'] = $insert_store_id;
					$fenlei_insert = $this->store_model->insert($fenlei_v,'crm_gcategory');
				
					if(!$fenlei_insert)
					{
						$store_sync_error[$store_v['store_id']]= array('message'=>'写入店铺分类表失败','store_id'=>$store_v['store_id'],'store_name'=>$store_v['store_name'],'cate_id'=>$fenlei_v['cate_id'],'cate_name'=>$fenlei_v['cate_name']);
						$this->log->info($store_sync_error[$store_v['store_id']]);
						continue;
					}
				}
			
						
			  /*
			  $update_store_id = $this->store_model->update("crm_gcategory",$update_store,"store_id=".$store_v['store_id']."   AND  state='0'");

			  if(!$update_store_id)
			  {
			  		$store_sync_error[$store_v['store_id']]= array('message'=>'修改分类对应的店铺失败','store_id'=>$store_v['store_id'],'store_name'=>$store_v['store_name']);

			  		 $this->log->info($store_sync_error[$store_v['store_id']]);
      		  		continue;

			  }
			  */
			
			 
			   $store_sync_complete[$store_v['store_id']] = array('message'=>'店铺信息导入成功','store_id'=>$store_v['store_id'],'store_name'=>$store_v['store_name'],'crm_store_id'=>$insert_store_id);


		  	   $this->log->info( $store_sync_complete[$store_v['store_id']]);
			   
			

    	 }
    	 
	}


	public function signature($arr,$sk)
	{
		ksort($arr);
		$str = '';
		foreach ($arr as $key => $value)
		{
			$str .= "{$key}={$value}&";
		}
		$str .= 'sk='.$this->appkey;

		return strtoupper(md5($str));
	}

	public function  getgoodsAction()
	{
	
		for($i=935;$i<970;$i++)
		{
			echo 'store_id=>'.$i;
			$sql = 'select store_id,source_partner_store_id,store_name  from crm_store  where store_id='.$i;

			$store_array = $this->store_model->getInfo($sql);

		
				$goods_error=array();


				$store_goods_sql = 'select * from ecm_goods where store_id='.$store_array['source_partner_store_id'];

				$store_goods_array = $this->store_model->getAllInfo($store_goods_sql);
			
				if(!$store_goods_array)
				{
					$array_error = array(
							'store_id'  => $store_array['store_id'],
							'store_name'=>$store_array['store_name'],
							'message'=>'get goods error',
					);

					$this->log->info($array_error);

					continue;
					
				}

				echo "<pre>";print_r($store_goods_array);echo "</pre>";


				  foreach($store_goods_array as $goods_k=>$goods_v)
				  {
				   		//判定分类

					    $cate_sql = "select cate_id from ecm_category_goods where goods_id=".$goods_v['goods_id'];

					    $cate_result = $this->store_model->getInfo($cate_sql);

					    if(!$cate_result)
					    {

					    	$array_error = array(
									'store_id'  => $store_array['store_id'],
									'store_name'=>$store_array['store_name'],
									'message'=>'goods not have gcategory',
							);

							$this->log->info($array_error);

							continue;
							
					    }

				   		$goods_insert_data = array(
				   			'source_goods_id'  => $goods_v['goods_id'],
				   			'goods_name'	   => $goods_v['goods_name'],
				   			'price'			   => $goods_v['price'],
				   			'packing_fee'	   => $goods_v['packing_fee'],
				   			'default_image'	   => $goods_v['default_image'],
				   			'store_id'		   => $store_array['store_id'],
				   			'spec_name'		   => $goods_v['spec_name_1'],
				   			'if_show'		   => $goods_v['if_show'],
				   			'summary'		   => $goods_v['summary'],
				   			'cate_id'		   => $goods_v['cate_id_1'],
				   			'gcategory_id'	   => $cate_result['cate_id'],
				   		);


				   	  $goods_insert_id = $this->store_model->insert($goods_insert_data,'crm_goods');

				   	  if(!$goods_insert_id)
				   	  {
				   	  		$goods_error= array('message'=>'insert goods error','store_id'=>$store_array['store_id'],'store_name'=>$store_array['store_name'],'goods_name'=>$goods_v['goods_name']);

				   	  		$this->log->info($goods_error);

							continue;
							
				   	  }


			   	  		$goods_success= array('message'=>'insert goods success','store_id'=>$store_array['store_id'],'store_name'=>$store_array['store_name'],'goods_name'=>$goods_v['goods_name']);

			   	  		$this->log->info($goods_success);

					}

		}
	}
	
	/*店铺处理图片*/

	public function  imageAction()
	{
		set_time_limit(0);
		for($i=935;$i<=969;$i++)
		{
			echo $i;

			/*
			if(in_array($i,arrray(3,15,26,44,63,131,158,172,1241,1337,1361)))
			{
				continue;
			}
			*/
			
			$sql = 'select store_logo,store_name,store_id,source_partner_store_id  from crm_store where store_id='.$i;

			$store_logo_result = $this->store_model->getInfo($sql);
			
			echo '<pre>';print_r($store_logo_result);echo '</pre>';

			if($store_logo_result['store_logo'] == '')
			{
				$store_logo_error = array(
					'store_id'=> $i,
					'store_name'=>$store_logo_result['store_name'],
					'message' => 'store_logo is empty',
				);

				$this->log->info($store_logo_error);
				continue;
			}

			$store_logo_jiewei = explode('.',$store_logo_result['store_logo']);

			$store_logo_jiewei = $store_logo_jiewei[1];

			$filename = '/data/web/crm.meishisong.cn/public/img/store/store/store_'.$store_logo_result['store_id'].'/store_'.$store_logo_result['store_id'].'.'.$store_logo_jiewei;
			//移动图片
			$store_logo_path =explode('data/files', $store_logo_result['store_logo']);
			$store_logo_path = $store_logo_path[1];
			//建立目录
			system("mkdir /data/web/crm.meishisong.cn/public/img/store/store/store_".$store_logo_result['store_id'],$mkdir_return);
			//移动图片
			system("cp /data/web/crm.meishisong.cn/public/img/".$store_logo_path."  ".$filename,$cp_result);
		   
			/*
			$hander = curl_init();
			$fp = fopen($filename,'wb');

			curl_setopt($hander,CURLOPT_URL,'crm.meishisong.cn/public/img/'.$store_logo_path);
			curl_setopt($hander,CURLOPT_FILE,$fp);
			curl_setopt($hander,CURLOPT_HEADER,0);
			curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
			$result = curl_exec($hander);
			curl_close($hander);
			fclose($fp);
			*/
				
			$result_array = array(
					'store_id'=>$store_logo_result['store_id'],
					'mss_store_id' => $store_logo_result['source_partner_store_id'],
					'store_name' =>$store_logo_result['store_name'],
					'image_handle_result' => $cp_result,
			);	

		    $this->log->info($result_array);
			
		    if($cp_result ==1)
		    {
		    	 $update_data = array(
			    	'store_logo' => '/public/img/store/store/store_'.$store_logo_result['store_id'].'/store_'.$store_logo_result['store_id'].'.'.$store_logo_jiewei,
			    );

		    	$update_result = $this->store_model->update('crm_store',$update_data,'store_id='.$i);

		    	if($update_result)
		    	{
		    		 $result_array = array(
				    		'store_id'=>$store_logo_result['store_id'],
				    		'mss_store_id' => $store_logo_result['source_partner_store_id'],
				    		'store_name' =>$store_logo_result['store_name'],
				    		'image_handle_result' => 'update store_logo success',
				    );	

		    		$this->log->info($result_array);
		    	}else
		    	{
		    		 $result_array = array(
				    		'store_id'=>$store_logo_result['store_id'],
				    		'mss_store_id' => $store_logo_result['source_partner_store_id'],
				    		'store_name' =>$store_logo_result['store_name'],
				    		'image_handle_result' => 'update store_logo failed',
				    );	

		    		$this->log->info($result_array);
		    	}
		    }
			

		}
	}
	

	/*商品处理图片*/
	
	public function  goodsimageAction()
	{
		
		$store_sql = "select * from crm_goods where default_image !='' and default_image not like '%crm%'  and store_id>=934";

		$store_exits_result = $this->store_model->getAllInfo($store_sql);

		foreach($store_exits_result as $i)
		{
			//echo $i;echo "<br/>";
			$sql = "select goods_id,goods_name,store_id,default_image  from crm_goods where goods_id=".$i['goods_id'];

			$goods_logo_result = $this->store_model->getInfo($sql);
			
			echo '<pre>';print_r($goods_logo_result);echo '</pre>';
		
			if($goods_logo_result['default_image'] == '')
			{
				$goods_logo_error = array(
					'store_id'=> $goods_logo_result['store_id'],
					'goods_id'=>$goods_logo_result['goods_id'],
					'goods_name'=>$goods_logo_result['goods_name'],
					'message' => 'no default_image',
				);

				$this->log->info($goods_logo_error);
				continue;
			}
			

			//查询原先的店铺id

			$store_id_sql = 'select source_partner_store_id from crm_store where store_id='.$goods_logo_result['store_id'];

			$store_id_result = $this->store_model->getInfo($store_id_sql);


			$goods_logo_jiewei = explode('.',$goods_logo_result['default_image']);

			$goods_logo_jiewei = $goods_logo_jiewei[1];

			$filename = '/data/web/crm.meishisong.cn/public/img/store/store/store_'.$goods_logo_result['store_id'].'/goods_'.$goods_logo_result['goods_id'].'.'.$goods_logo_jiewei;

			//移动图片
			$goods_logo_path =explode('data/files', $goods_logo_result['default_image']);

			$goods_logo_path = $goods_logo_path[1];
			
			//建立目录
			system("mkdir /data/web/crm.meishisong.cn/public/img/store/store/store_".$goods_logo_result['store_id'],$mkdir_return);
			//移动图片
			system("cp /data/web/crm.meishisong.cn/public/img/".$goods_logo_path."  ".$filename,$cp_result);
			
		  	$hander_result = array(
		  		'store_id' => $goods_logo_result['store_id'],
		  		'goods_id' => $goods_logo_result['goods_id'],
		  		'goods_name' => $goods_logo_result['goods_name'],
		  		'handle_result' => $cp_result,
		  	);

		  	$this->log->info($hander_result);

		  	if($cp_result ==1)
		    {
		    	 $update_data = array(
			    	'default_image' => '/public/img/store/store/store_'.$goods_logo_result['store_id'].'/goods_'.$goods_logo_result['goods_id'].'.'.$goods_logo_jiewei,
			    );

		    	$update_result = $this->store_model->update('crm_goods',$update_data,'goods_id='.$goods_logo_result['goods_id']);

		    	if($update_result)
		    	{
		    		 $result_array = array(
				    		'store_id'=>$goods_logo_result['store_id'],
				    		'goods_name' =>$goods_logo_result['goods_name'],
				    		'image_handle_result' => 'update goods image success',
				    );	

		    		$this->log->info($result_array);
		    	}else
		    	{
		    		 $result_array = array(
				    		'store_id'=>$goods_logo_result['store_id'],
				    		'goods_name' =>$goods_logo_result['goods_name'],
				    		'image_handle_result' => 'update goods image error',
				    );	

		    		$this->log->info($result_array);
		    	}
		    }



		}
	}
	
	/*
	 * 通过旧的region_id获取新的标准的region_id
	*/
	
	public function new_region_id($region_id,$parent_id='0')
	{
		
		$sql = 'select parent_id,region_id,region_name from crm_region where region_id='.$region_id;

		$region_result = $this->store_model->getInfo($sql);

		if($region_result['parent_id']!=$parent_id)
		{
			return $this->new_region_id($region_result['parent_id'],$parent_id);
		}else
		{
			return $region_result;
		}
	}

	public function logoAction()
	{
		$sql = "select * from crm_goods where default_image!=''";

		$result = $this->store_model->getAllInfo($sql);

		foreach($result as $v)
		{
			$update_sql = "update crm_goods set default_image=(select default_image from nowmss.ecm_goods where goods_id=".$v['source_goods_id'].")";

			mysql_query($update_sql);
		}
	}
	public function areaAction($loc)
	{
		$location = $loc;
		
		$position_result = $this->address->getLocationFromBaidu($location);
		
		if($position_result)
		{
			return array('state'=>'1','message'=>$position_result);
		}else
		{
			return array('state'=>'-2','message'=>'区域获取失败');
		}
	
	}
	/*
	public function cateAction()
	{
		for($i=1;$i<=1360;$i++)
		{
			  echo $i;echo "<br/>";
			  $sql = "select source_partner_store_id,store_name from crm_store where store_id=".$i;

			  $source_store_id = $this->store_model->getInfo($sql);

			  $store_id = $source_store_id['source_partner_store_id'];

			  $store_name = $source_store_id['store_name'];

			  $update_store = array(
				'store_id'=>$i,
				'state'	  =>'1',
			  );

			  $update_store_id = $this->store_model->update("crm_gcategory",$update_store,"store_id=".$store_id."   AND  state='0'");

			  if(!$update_store_id)
			  {
			  		$store_sync_error= array('message'=>'修改分类对应的店铺失败','store_id'=>$store_id,'store_name'=>$store_name);

			  		 $this->log->info($store_sync_error);
      		  		continue;
			  }else
			  {

			  	  $store_sync_error= array('message'=>'修改分类对应的店铺成功!','store_id'=>$store_id,'store_name'=>$store_name);

			  		 $this->log->info($store_sync_error);
			  }
		}
	}
	
	function databaseAction()
	{
		$sql = "select goods_id,source_goods_id from nowmss.ecm_goods where if_show=0 and source_goods_id>0";

		$result = $this->store_model->getAllInfo($sql);

		//echo '<pre>';print_r($result);echo '</pre>';return;

		foreach($result as $k=>$v)
		{
			echo '<pre>';print_r($v);echo '</pre>';

			$update_data= array(
				'if_show' => 0,
			);
			//echo 'aaaaa';
			$update_result = $this->store_model->update("crm_goods",$update_data,'goods_id='.$v['source_goods_id']);

			print_r($update_result);break;
		}
	}
  */
  /*
	function storeAction()
	{
		$sql = "select store_id,source_partner_store_id from crm_store where store_id>=935";

		$result = $this->store_model->getAllInfo($sql);

		foreach($result as $k=>$v)
		{
			$update_array = array('source_store_id'=>$v['store_id']);

			//$update_result = $this->store_model->update('nowmss.ecm_store',$update_array,'store_id='.$v['source_partner_store_id']);
			$update_result = mysql_query("update nowmss.ecm_store set source_store_id=".$v['store_id']."  WHERE store_id=".$v['source_partner_store_id']);

			echo "<pre>";echo $v['store_id'];print_r($update_result);echo "</pre>";
		}
	}
   */
	/*
	function goodsAction()
	{
		$sql = "select goods_id,source_goods_id from crm_goods where store_id>=935";

		$result = $this->store_model->getAllInfo($sql);

		foreach($result as $k=>$v)
		{
			$update_result = mysql_query("update nowmss.ecm_goods set source_goods_id=".$v['goods_id']."  WHERE goods_id=".$v['source_goods_id']);

			echo "<pre>";echo $v['goods_id'];print_r($update_result);echo "</pre>";
		}
	}
	*/
}


?>