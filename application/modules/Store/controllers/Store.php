<?php
class StoreController extends Yaf_Controller_Abstract
{
	var $store_model;
	var $_address;
	var $limit;
	var $_upload;
	var $url_store_insert;
	var $scategory_model;
	var $region_model;
	public function init()
	{
		
		session_start();
        header("Content-type: text/html; charset=utf-8");
        
		$role_array = array(7,8); //基础数据
		if(!isset($_SESSION['username']) || !in_array($_SESSION['role'],$role_array))
		{
			header("Location: ".WEB_PATH."/member/member/roleLogin");
		}
		

		$this->store_model = new StoreModel();
		$this->scategory_model = new ScategoryModel();
		$this->region_model   = new RegionModel();
		$file = array(
			LIB_PATH_PUBLIC.'/meishisong/address.php',
			APP_PATH."/application/modules/Store/controllers/Upload.php",
		);

		yaf_load($file);
		
		$this->getView()->assign("app_path",APP_PATH);

		$this->_address  = new Address();

		$this->limit = 50;

		//公用的头部信息
		$region_array = $this->region_model->getChildren($region_id='0');
		$foods_type = $this->scategory_model->getChildren($parent_id='0');
		
		$sql = "select appkey,partner_name from crm_partner where has_right=1";
		$friend_result = $this->store_model->getAllInfo($sql);
		if(!empty($friend_result)  && is_array($friend_result))
		{
			$friend_list  = $friend_result;
		}

		$this->getView()->assign("friend_list",$friend_list);
		$this->getView()->assign("foods_type",$foods_type);
		$this->getView()->assign("region_array",$region_array); 
	}
	
	/*
	*返回头部信息
	*/
	public function indexAction()
	{
		try
		{
			//由编辑页面执行的跳转到首页的操作
			if($_GET)
			{
				foreach($_GET as $k=>$v)
				{
					${$k} = $v;
					$this->getView()->assign("{$k}",$v);
				}
			}
			
			$this->getView()->display("store/index");
		}catch(Exception $e)
		{   
			die(json_encode(array('state'=>"-1",'message'=>'数据异常','data'=>$e->getMessage())));
		}   
	
	}
	
	public function where($_POST)
	{

		$this->page = $_POST['page'];
	
		$where ="";
		
		if(!empty($_POST['store_name']) || !empty($_POST['store_id']) || !empty($_POST['mss_store_id'])  || !empty($_POST['partner']) || !empty($_POST['region'])  || !empty($_POST['cate']))
		{	
			foreach($_POST as $k=>$v)
			{
				trim($v);  //去除左右的空格
				if($v=='')
				{
					unset($_POST[$k]);
				}
				
			}
		
			$where = 'where ';
			foreach($_POST as $k=>$v)
			{
				${$k} = $v;

				if($k=='store_name')
				{
					$where.="{$k}  like '%".$v."%' AND ";
				}else if($k=='store_id')
				{
					$where.="store_id ='".$v."' AND ";
				}else if($k=='mss_store_id')
				{
					//以ecmall的店铺id来查看
					$this->match_model = new MatchModel();
					$store_id  = $this->match_model->index($v);
					$where.="store_id =".$store_id." AND ";
				}else if($k=='partner')
				{
				
					$partner = trim($partner,',');
					//查询推送到合作伙伴的店铺
					
					$sql = "select distinct source_partner_store_id from crm_partner_store where to_partner_id in(".$partner.")";
					
					$store_id_array = $this->store_model->getAllInfo($sql);
					
					$store_ids="";
					
					foreach($store_id_array as $store)
					{
						$store_ids.=$store['source_partner_store_id'].",";
					}
					
					$store_ids = trim($store_ids,',');
				
					
					$where.=" store_id in(".$store_ids.")  AND ";
					
					
				}else if($k=='region')
				{
					
					$region = trim($region,',');

					$sql = "select * from crm_region where region_id in(".$region.")  or region_id in(select region_id from crm_region where parent_id in(".$region."))";
					
					$region_result = $this->store_model->getAllInfo($sql);
					
					$region_ids = "";
					
					if(!empty($region_result) && is_array($region_result))
					{
							foreach($region_result as $region_detail)
							{
								$region_ids.=$region_detail['region_id'].",";
							}
							$region_ids=trim($region_ids,',');
							$where.=" region_id in(".$region_ids.") AND ";
					}
						
					
				}else if($k=='cate')
				{
					
					$cate = trim($cate,',');
				
					$cate = explode(',',$cate);
					$where.= " (";
					foreach($cate as $cate_v)
					{
						$where.="category2 like '%".$cate_v."%' or ";
					}
					
					$where = trim($where);
					$where = trim($where,"or");
					
					$where.=" )  AND ";
					
				}
			
				else if($k=='page')
				{
					$where.=' ';
				}else
				{
					$where.="{$k}={$v} AND ";
				}
			}
		
		}
		
		$where = trim($where);
		$where = trim($where,"AND");
		
		return $where;
				
	}
	
	
	public function storeAction()
	{
		try
		{

			$where = $this->where($_POST);

			$store_array = $this->store_model->select($this->page,$this->limit,$where);

			$store_count_sql = "select count(*) as count from crm_store ".$where;

			$store_count_result = $this->store_model->getInfo($store_count_sql);
			
			$count = $store_count_result['count'];
			
			$store_count_result  = ceil($store_count_result['count']/50);	

			$sql = 'select * from crm_pull_off_shelves_reason';

			$result = $this->store_model->getAllInfo($sql);	
			
			if($store_array['state'] !=1)
			{
				echo    json_encode(array('state'=>'-1','message'=>'获取店铺信息失败!'));
				exit();
			}
				echo   json_encode(array('state'=>'1','message'=>'获取店铺信息成功!','store_array'=>$store_array['data'],'store_summary_result'=>$store_count_result,'page'=>$this->page,'search_count'=>$count,'pull_off_reason'=>$result));	
		
		}catch(Exception $e)
		{   
			die(json_encode(array('state'=>"-1",'message'=>'数据异常','data'=>'未知错误')));
		}   
	
	}

	/*
	*  店铺首页的上架操作
	*/
	public function  put_on_saleAction()
	{
		$store_id = $_POST['store_id'];
		if(!$store_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取店铺id失败'));
			exit();
		}

		$update_data = array(
			'state'=>'1',
		);
		
		
		$result = $this->store_model->update("crm_store",$update_data,'store_id='.$store_id);

		if(!$result)
		{
			echo json_encode(array('state'=>'-2','message'=>'店铺上架失败'));
			exit();
		}
			$sql = 'select * from crm_pull_off_shelves_reason';

			$reson_result  = $this->store_model->getAllInfo($sql);

			echo json_encode(array('state'=>'1','message'=>'店铺上架成功','data'=>$reson_result));

	}


	/*
	*  店铺首页的下架操作
	*/
	public function  put_off_saleAction()
	{
		$store_id = $_POST['store_id'];
		$off_reason  = $_POST['off_reason'];
		
		if(!$store_id  || !$off_reason)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取下架参数失败'));
			exit();
		}
		
		$update_data = array(
			'state'=>'2',
			'off_reason'=>$off_reason,
		);
		mysql_query("START TRANSACTION");
		
		$update_result = $this->store_model->update("crm_store",$update_data,'store_id='.$store_id);

		if(!$update_result)
		{
			mysql_query("ROLLBACK");
			echo json_encode(array('state'=>'-2','message'=>'店铺下架失败'));
			exit();
		}
		//取消到合作伙伴的推送
		
		$sql = "select source_partner_store_id,to_partner_id from crm_partner_store where source_partner_store_id=".$store_id." AND to_partner_id !='100000'";
		
		$result = $this->store_model->getAllInfo($sql);
		
		
		if(!empty($result) && !is_array($result))
		{	
			mysql_query("ROLLBACK");
			echo json_encode(array('state'=>'-3','message'=>'查询店铺的推送状态时失败'));
			exit();
		}
		
		if(!empty($result) && is_array($result))
		{
			$to_partner_id = ",";
			
			foreach($result as $distribute)
			{
				
				$to_partner_id.=$distribute['to_partner_id'].",";
			}
			
			$insert_data  = array(
				'belong' => 'store',
				'item_id' => $store_id,
				'act'	=> 'del',
				'to_partner_id' => $to_partner_id,
			);
			
			$insert_result = $this->store_model->insert($insert_data,'crm_distribute');
			
			if(!$insert_result)
			{
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-4','message'=>'写入分发表时失败'));
				exit();
			}
			
			$to_partner_id = trim($to_partner_id,',');
			
			
			$delete_sql = "delete from crm_partner_store where source_partner_store_id=".$store_id."  AND to_partner_id in(".$to_partner_id.")";
			
			
			$delete_result = $this->store_model->delete_sql($delete_sql);
			
			if(!$delete_result)
			{	
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-5','message'=>'删除推送记录时失败'));
				exit();
			}
		}
		
				mysql_query("COMMIT");
				mysql_query("END");
		
		echo json_encode(array('state'=>'1','message'=>'店铺下架成功'));

	}


	/*
	 *	写入数据库店铺信息
	 */
	public function insertAction()
	{                          
	
		if(!$_POST)
		{
			echo json_encode(array('state'=>'-1','message'=>'未接收到数据'));
			exit();
		}
		foreach($_POST as $k=>$v)
		{
			${$k} = $v;
			${$k} = str_replace(' ','',$v);
		}
		
		if(!$store_name_add)
		{
			echo json_encode(array('state'=>'-201','message'=>'店铺名字为空'));
			exit();
		}
		
		if(!$city_add || !$county_add || !$street_add)
		{
			echo json_encode(array('state'=>'-202','message'=>'区域填写不完整'));
			exit();
		}
		
		if(!$address_add)
		{
			echo json_encode(array('state'=>'-203','message'=>'地址获取失败'));
			exit();
		}
		
		if(!$longitude_add  || !$latitude_add)
		{
			echo json_encode(array('state'=>'-204','message'=>'经纬度获取失败'));
			exit();
		}
		
		if(!$tel_add)
		{
			echo json_encode(array('state'=>'-205','message'=>'电话获取失败'));
			exit();
		}
		if(!$category2_add)
		{
			echo json_encode(array('state'=>'-206','message'=>'分类获取失败'));
			exit();
		}
		if(!$min_cost_add)
		{
			echo json_encode(array('state'=>'-207','message'=>'起送价获取失败'));
			exit();
		}
		if($delivery_fee_add==null)
		{
			echo json_encode(array('state'=>'-208','message'=>'配送费获取失败'));
			exit();
		}
		if(!$receipt_add)
		{
			echo json_encode(array('state'=>'-209','message'=>'是否能开发票获取失败'));
			exit();
		}
		if(!$state_add)
		{
			echo json_encode(array('state'=>'-210','message'=>'上下架获取失败'));
			exit();
		}

		if(!$visibility_add)
		{
			echo json_encode(array('state'=>'-211','message'=>'显示隐藏获取失败'));
			exit();
		}
		
		if($_FILES['license_add']['error'] == 4)
		{
			echo json_encode(array('state'=>'-212','message'=>'营业执照获取失败'));
			exit();
		}
		
		if($_FILES['file_add']['error'] == 4)
		{
			echo json_encode(array('state'=>'-213','message'=>'图片获取失败'));
			exit();
		}

		if(!$registration_mark_add)
		{
			echo json_encode(array('state'=>'-214','message'=>'营业执照注册号失败'));
			exit();
		}

		//1.验证店铺名字唯一
		
		$store_name_check = $this->store_model->check_store_name($store_name_add);
		
		if(!$store_name_check)
		{
			echo json_encode(array('state'=>'-3','message'=>'店铺名已经存在'));
			exit();
		}
	
	
		//2.验证电话正确性
		
		$tel_check_result = $this->check_phone($tel_add);
		
		if($tel_check_result['state']!=1)
		{
			echo json_encode(array('state'=>'-4','message'=>'电话填写有误'));
			exit();
		}
		
		//3.处理分类
		 $category2 = trim($category2_add,',');
		 
		//4.地址，经纬度对应
		$position_result = $this->_address->getRenderFromBaidu($address_add,$city_add);
		if(!$position_result)
		{
			echo json_encode(array('state'=>'-501','message'=>'地址获取经纬度失败!'));
			exit();
		}
		 	$lng = $position_result['lng'];
			$lat = $position_result['lat'];

			
		if((intval($lng)!=intval($longitude_add)) || intval($lat)!=intval($latitude_add))
		{
				echo json_encode(array('state'=>'-502','message'=>'地址和经纬度不对应!'));
				exit();
		}
			
		//5.判断时间有效性
		if($zhong_close_add == '00:00' && $wan_start_add=='00:00')
		{
			echo json_encode(array('state'=>'-601','message'=>'没有填写时间!'));
			exit();
		}
		//时间填写重复
		/*
		if($zhong_close_add == $wan_start_add)
		{
			echo json_encode(array('state'=>'-602','message'=>'请将重复时间段填写为同一时间段!'));
			exit();
		}
		*/
		 //时间填写时间段不符合常理
		 $zao_start_first = explode(':',$zao_start_add);$zao_start_first = $zao_start_first[0];
		 $zao_close_first = explode(':',$zao_close_add);$zao_close_first = $zao_close_first[0];
		 
		 $zhong_start_first = explode(':',$zhong_start_add);$zhong_start_first = $zhong_start_first[0];
		 $zhong_close_first = explode(':',$zhong_close_add);$zhong_close_first = $zhong_close_first[0];
		 
		 $wan_start_first = explode(':',$wan_start_add);$wan_start_first = $wan_start_first[0];
		 $wan_close_first = explode(':',$wan_close_add);$wan_close_first = $wan_close_first[0];
		 
		 if((intval($zao_start_first)>intval($zao_close_first))  || (intval($zao_close_first)>intval($zhong_start_first)) || (intval($zhong_start_first)>intval($zhong_close_first)) || (intval($zhong_close_first)>intval($wan_start_first)) || (intval($wan_start_first)>intval($wan_close_first)))
		 {
			echo json_encode(array('state'=>'-602','message'=>'填写的时间段不符合业务逻辑!'));
			exit();
		 }

		 if($wan_start_first<12 || $wan_close_first<12)
		 {
		 	echo json_encode(array('state'=>'-603','message'=>'请填写24小时制的时间!'));
			exit();
		 }
		 
		
		 
		
		$break_time = array(
			'start'=>$zao_start_add,
			'end'  =>$zao_close_add,
		);
		
		$lunch_time = array(
			'start'=>$zhong_start_add,
			'end'  =>$zhong_close_add,
		);

		$supper_time = array(
			'start'=>$wan_start_add,
			'end'  =>$wan_close_add,
		);
		
		$business_time = json_encode(array('break_time'=>$break_time,'lunch_time'=>$lunch_time,'supper_time'=>$supper_time));
		
	
		$region_id_bak = $_POST['street_add']?$_POST['street_add']:$_POST['county_add'];

		$region_id = $_POST['building_add']?$_POST['building_add']:$region_id_bak;

		$bd_id = $_POST['bd_id_add']?$_POST['bd_id_add']:'';

		if(!$announce_add)
		{
			$announce_add = "";
		}
		

		//6.先插入数据
		$insert_data = array(
			'store_name' =>$store_name_add,
			'owner_name' =>$store_name_add,
			'address'=>$address_add,
			'tel'=>'010-52285085',
			'state' => $state_add,
			'visibility' => $visibility_add,
			'longitude'=>$longitude_add,
			'latitude'=>$latitude_add,
			'city'=>$city_add,
			'county'=>$county_add,
			'bd_id' => $bd_id_add,
			'region_id'=>$region_id,
			'region_name'=>$region_name_add,
			'min_cost'	=>$min_cost_add,
			'delivery_fee'=>$delivery_fee_add,
			'receipt'=>$receipt_add,
			'announce' => $announce_add,
			'business_time'=>$business_time,
			'category2'    =>$category2_add,
			'takeout_service_phone' =>$tel_add,
			'checkout_type' => $checkout_type_add,
			'registration_mark'=>$registration_mark_add,
			'source_partner_id' =>'100000',
			'source_partner_store_id' =>'0',
		);
	
		//7.插入数据，返回store_id(事务操作)
		mysql_query("START TRANSACTION");
		
		$store_id = $this->store_model->insert($insert_data,'crm_store');
		
		$insert_check_sql = "select * from crm_store where store_id=".$store_id;
	
		$insert_check_result = $this->store_model->getInfo($insert_check_sql);

		if(!is_array($insert_check_result) || !$insert_check_result['store_id'])
		{
			echo json_encode(array('state'=>'-7','message'=>'插入店铺失败'));
			exit();
		}
		
		 //8.图片验证和上传
		
		 $store_image_dir = "store_".$store_id;
		
		 $this->_upload   = new UpLoad(10,false,APP_PATH.'/public/img/store/store/'.$store_image_dir);
		  //营业执照名称
		 $license_img = "license_".$store_id;
		 $upload_license  = $this->_upload->upLoadFile($_FILES['license_add'],$license_img);

		 $upload_result  = $this->_upload->upLoadFile($_FILES['file_add'],$store_image_dir);
		 
		 if(!$upload_result)
		 {
			mysql_query("ROLLBACK");
			echo json_encode(array('state'=>'-8','message'=>'上传图片失败'));
			exit();
		 }

		 if(!$upload_license)
		 {
			mysql_query("ROLLBACK");
			echo json_encode(array('state'=>'-9','message'=>'上传营业执照失败'));
			exit();
		 }

		 $store_logo =array(
			'store_logo' => '/public/img/store/store/'.$store_image_dir."/".$store_image_dir.".".$upload_result,
			'license_pic' => '/public/img/store/store/'.$store_image_dir."/".$license_img.".".$upload_license,
		 );

		 $store_logo_result = $this->store_model->update('crm_store',$store_logo,'store_id='.$insert_check_result['store_id']);

		 if(!$store_logo_result)
		 {
			//图片保存路径失败则删除存储图片路径和路径下的图片
			@unlink("/public/img/store/store".$store_image_dir."/".$store_image_dir.".".$upload_result);

			@unlink("/public/img/store/store/".$store_image_dir."/".$license_img.".".$upload_license);

			mysql_query("ROLLBACK");
			echo json_encode(array('state'=>'-9','message'=>'图片路径保存失败'));
			exit();
			
		 }


			mysql_query("COMMIT");
	 
			mysql_query("END");
				
		    echo json_encode(array('state'=>'1','message'=>'新增店铺成功'));
	}
	
	
	//查询省级下的城市。第三级
	public function city_selectAction()
	{
		$id = $_REQUEST['id'];
		
		$sql = "select region_id,region_name from crm_region where parent_id in(select region_id from crm_region where parent_id=".$id.")";
		
		$city_result = $this->region_model->getAllInfo($sql);
		
		echo json_encode($city_result);
	}

	
	/*
	 *编辑展示信息收集
	 */
	public function editAction()
	{
		$store_id= $_POST['store_id'];
		$page    = $_POST['page'];
		$store_all_id = rtrim($_POST['store_all_id'],',');

		if(empty($store_id)  || empty($page) || empty($store_all_id))
		{
			echo json_encode(array('state'=>"-1","message"=>"获取店铺信息参数缺失!"));
			exit();
		}
		
		//获取搜索条件店铺的信息
		
		$sql = "select store_id,store_name from crm_store where store_id in(".$store_all_id.")";
		
		$store_list = $this->store_model->getAllInfo($sql);
	
		if(empty($store_list) || !is_array($store_list))
		{
			echo json_encode(array('state'=>"-2","message"=>"获取搜索店铺列表失败!"));
			exit();
		}
		
		$store_detail = $this->store_model->edit_show($store_id);
		
		if(!$store_detail['store_id'])
		{
			echo json_encode(array('state'=>"-3","message"=>"获取搜索店铺列表失败!"));
			exit();
		}
		
		//获取店铺的合作伙伴
		
		$parnter_sql = 'select a.to_partner_id,b.partner_name from crm_partner_store a left join crm_partner b on a.to_partner_id=b.appkey where a.source_partner_store_id='.$store_id;
		
		
		$partner_result = $this->store_model->getAllInfo($parnter_sql);
		
		if(!empty($partner_result) &&  !is_array($partner_result))
		{	
			echo json_encode(array('state'=>"-4","message"=>"获取合作伙伴失败!"));
			exit();
		}else if(empty($partner_result))
		{
			$partner_list='';
		}else
		{
			foreach($partner_result as $partner)
			{
				$partner_list.=$partner['partner_name'].",";
			}
		}

		//查询ecmall的store_id

		$this->match_model = new MatchModel();

		$mss_store_result = $this->match_model->mss($store_id);

		if($mss_store_result['state']=='-2')
		{
			$store_detail['ecmall_store_id'] = '未推送';
		}else
		{
			$store_detail['ecmall_store_id'] = $mss_store_result['mss_store_id'];
		}



		echo json_encode(array('state'=>'1','message'=>'获取店铺信息成功','page'=>$_POST['page'],'data'=>$store_list,'partner_result'=>$partner_result,'store_detail'=>$store_detail));
		
	}


	/*
	 *  店铺编辑
	*/ 
	
	public function updateAction()
	{

		if(!$_POST)
		{
			echo json_encode(array('state'=>'-1','message'=>'未接收到数据'));
			exit();
		}
		foreach($_POST as $k=>$v)
		{
			${$k} = $v;
			${$k} = str_replace(' ','',$v);
		}
		
		if(!$store_name_edit)
		{
			echo json_encode(array('state'=>'-201','message'=>'店铺名字为空'));
			exit();
		}
		//编辑区域的情况下
		if((!$region_name && !$city)  || (!$region_name && !$county)  || (!$region_name && !$street) || (!$region_name && !$region_name_edit))
		{
			echo json_encode(array('state'=>'-202','message'=>'区域填写不完整'));
			exit();
		}
		
		if(!$address)
		{
			echo json_encode(array('state'=>'-203','message'=>'地址获取失败'));
			exit();
		}
		
		if(!$longitude  || !$latitude)
		{
			echo json_encode(array('state'=>'-204','message'=>'经纬度获取失败'));
			exit();
		}
		
		if(!$tel)
		{
			echo json_encode(array('state'=>'-205','message'=>'电话获取失败'));
			exit();
		}
		if(!$category2)
		{
			echo json_encode(array('state'=>'-206','message'=>'分类获取失败'));
			exit();
		}
		if(!$min_cost)
		{
			echo json_encode(array('state'=>'-207','message'=>'起送价获取失败'));
			exit();
		}
		if($delivery_fee == null)
		{
			echo json_encode(array('state'=>'-207','message'=>'获取配送费失败'));
			exit();
		}
		if($receipt===false)
		{
			echo json_encode(array('state'=>'-207','message'=>'获取发票信息失败'));
			exit();
		}
		if(!$state)
		{
			echo json_encode(array('state'=>'-208','message'=>'上下架获取失败'));
			exit();
		}

		if(!$visibility)
		{
			echo json_encode(array('state'=>'-209','message'=>'显示隐藏获取失败'));
			exit();
		}

		if(!$basic_store_id)
		{
			echo json_encode(array('state'=>'-210','message'=>'获取编辑的店铺id失败'));
			exit();
		}

		$store_id = $basic_store_id;
		
		$sql = 'select license_pic,store_logo,store_id from crm_store where store_id='.$store_id;
		
		$result = $this->store_model->getInfo($sql);
	
		if(empty($result) || !is_array($result))
		{
			echo json_encode(array('state'=>'-211','message'=>'获取当前店铺的默认图片失败'));
			exit();
		}
		
		$default_image = $result['store_logo'];

		$license_pic   = $result['license_pic'];

		//验证图片上传没有（在上架的情况下）
		if($state == '1')
		{				
			if(!$default_image && !$_FILES['file'])
			{
				//原先没有默认图片的情况下（没有上传图片）
				echo json_encode(array('state'=>'-212','message'=>'请上传图片'));
				exit();
			}
			/*
			if(!$license_pic  &&  !$_FILES['licence_file'])
			{
				//原先没有营业执照的情况下（没有营业执照）
				echo json_encode(array('state'=>'-213','message'=>'请上传营业执照'));
				exit();
			}
			*/
		}
		/*
		if(!$registration_mark)
		{
			echo json_encode(array('state'=>'-214','message'=>'请填写营业执照序列号'));
			exit();
		}
		*/
		
		$store_name = $store_name_edit;
	
		
		//1.验证店铺名字唯一
		
		$store_name_check = $this->store_model->check_store_name_edit('100000',$store_name,$store_id);
	
		if($store_name_check['state']!='1')
		{
			echo json_encode(array('state'=>'-3','message'=>'店铺名已经存在'));
			exit();
		}
	
		//2.验证电话正确性
		
		$tel_check_result = $this->check_phone($tel);
		
		if($tel_check_result['state']!=1)
		{
			echo json_encode(array('state'=>'-4','message'=>'电话填写有误'));
			exit();
		}
		
		//3.处理分类
		 $category2 = trim($category2,',');
		 
		//4.地址，经纬度对应(在编辑区域的情况下)
		if(!$region_name)
	    {
			 $position_result = $this->_address->getRenderFromBaidu($address,$city);
			 if(!$position_result)
			 {
				echo json_encode(array('state'=>'-501','message'=>'地址获取经纬度失败!'));
				exit();
			 }

		 	 $lng = $position_result['lng'];
			 $lat = $position_result['lat'];

			
			 if((intval($lng)!=intval($longitude)) || intval($lat)!=intval($latitude))
			 {
				echo json_encode(array('state'=>'-502','message'=>'地址和经纬度不对应!'));
				exit();
			 }
			 			 
		}
		 
		//5.判断时间有效性
		if($zhong_close == '00:00' && $wan_start=='00:00')
		{
			echo json_encode(array('state'=>'-601','message'=>'没有填写时间!'));
			exit();
		}


		 //时间填写时间段不符合常理
		 $zao_start_first = explode(':',$zao_start);$zao_start_first = $zao_start_first[0];
		 $zao_close_first = explode(':',$zao_close);$zao_close_first = $zao_close_first[0];
		 
		 $zhong_start_first = explode(':',$zhong_start);$zhong_start_first = $zhong_start_first[0];
		 $zhong_close_first = explode(':',$zhong_close);$zhong_close_first = $zhong_close_first[0];
		 
		 $wan_start_first = explode(':',$wan_start);$wan_start_first = $wan_start_first[0];
		 $wan_close_first = explode(':',$wan_close);$wan_close_first = $wan_close_first[0];
		 
		 if((intval($zao_start_first)>intval($zao_close_first))  || (intval($zao_close_first)>intval($zhong_start_first)) || (intval($zhong_start_first)>intval($zhong_close_first)) || (intval($zhong_close_first)>intval($wan_start_first)) || (intval($wan_start_first)>intval($wan_close_first)))
		 {
			echo json_encode(array('state'=>'-602','message'=>'填写的时间段不符合业务逻辑!'));
			exit();
		 }

		 if($wan_start_first<12 || $wan_close_first<12)
		 {
		 	echo json_encode(array('state'=>'-603','message'=>'请填写24小时制的时间!'));
			exit();
		 }
		 
		 
		$break_time = array(
			'start'=>$zao_start,
			'end'  =>$zao_close,
		);
		
		$lunch_time = array(
			'start'=>$zhong_start,
			'end'  =>$zhong_close,
		);
		$supper_time = array(
			'start'=>$wan_start,
			'end'  =>$wan_close,
		);
		
		$business_time = json_encode(array('break_time'=>$break_time,'lunch_time'=>$lunch_time,'supper_time'=>$supper_time));

		if(!$announce)
		{
			$announce = "";
		}

		if(!$registration_mark)
		{
			$registration_mark = "";
		}	
		if($region_name)
		{
			//没有修改区域的情况下
			//6.修改数据
				$update_data = array(
					'store_name' =>$store_name,
					'owner_name' =>$store_name,
					'address'=>$address,
					'longitude'=>$longitude,
					'latitude'=>$latitude,
					'takeout_service_phone' =>$tel,
					'category2'=>$category2,
					'min_cost'	=>$min_cost,
					'delivery_fee' =>$delivery_fee,
					'receipt' =>$receipt,
					'state' => $state,
					'visibility' => $visibility,
					'checkout_type' => $checkout_type,
					'announce' => $announce,
					'business_time'=>$business_time,
					'registration_mark'=>$registration_mark,
				);
		}else
		{
				$region_name = $region_name_edit;
				$region_id_bak = $_POST['street']?$_POST['street']:$_POST['county'];
				$region_id = $_POST['building']?$_POST['building']:$region_id_bak;
				$bd_id = $_POST['bd_id']?$_POST['bd_id']:'';
				$update_data = array(
					'store_name' =>$store_name,
					'owner_name' =>$store_name,
					'address'=>$address,
					'longitude'=>$longitude,
					'latitude'=>$latitude,
					'takeout_service_phone' =>$tel,
					'category2'=>$category2,
					'min_cost'	=>$min_cost,
					'delivery_fee' =>$delivery_fee,
					'receipt' =>$receipt,
					'state' => $state,
					'visibility' => $visibility,
					'checkout_type' => $checkout_type,
					'business_time'=>$business_time,
					'city'=>$city,
					'county'=>$county,
					'bd_id' => $bd_id,
					'region_id'=>$region_id,
					'region_name'=>$region_name,
					'registration_mark'=>$registration_mark,
				);
		}		
	
		//7.插入数据，返回store_id(事务操作)
		mysql_query("START TRANSACTION");
		
		$update_result = $this->store_model->update('crm_store',$update_data,'store_id='.$store_id);
		
		if(!$update_result)
		{
			echo json_encode(array('state'=>'-7','message'=>'修改店铺失败'));
			exit();
		}

		
		 //8.图片验证和上传
	 
		 if($_FILES['file'] && $_FILES['file']['error']!=4)
		 {
			 //先删除原先的图片(在原先图片存在的情况下)
			 if($default_image)
			 {
				@unlink($default_image);
			 }
			 
			  $store_image_dir = "store_".$store_id;
	
			  $this->_upload   = new UpLoad(10,false,APP_PATH.'/public/img/store/store/'.$store_image_dir);
		  
			  $upload_result  = $this->_upload->upLoadFile($_FILES['file'],$store_image_dir);
			  
			  if(!$upload_result)
			  {
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-8','message'=>'上传图片失败'));
				exit();
			  }
			  
			  $store_logo =array(
					'store_logo' => '/public/img/store/store/'.$store_image_dir."/".$store_image_dir.".".$upload_result,
			  );
			  
			  $store_logo_result = $this->store_model->update('crm_store',$store_logo,'store_id='.$store_id);
			  
			  if(!$store_logo_result)
			  {
				 @unlink("/public/img/store/store".$store_image_dir."/".$store_image_dir.".".$upload_result);
				 mysql_query("ROLLBACK");
				 echo json_encode(array('state'=>'-9','message'=>'图片路径保存失败'));
				 exit();
			  }

			   $default_image  = '/public/img/store/store/'.$store_image_dir."/".$store_image_dir.".".$upload_result;
			
		 }


	  	 if($_FILES['licence_file'] && $_FILES['licence_file']['error']!=4)
		 {
			 //先删除原先的图片(在原先图片存在的情况下)
			 if($license_pic)
			 {
				@unlink($license_pic);
			 }
			 
			  $store_image_dir = "store_".$store_id;

			  if(!$this->_upload)
			  {
			  	 $this->_upload   = new UpLoad(10,false,APP_PATH.'/public/img/store/store/'.$store_image_dir);
			  }
	  
			  $license_img = "license_".$store_id;

		 	  $license_upload_result = $this->_upload->upLoadFile($_FILES['licence_file'],$license_img);
			  
			  if(!$license_upload_result)
			  {
				mysql_query("ROLLBACK");
				echo json_encode(array('state'=>'-8','message'=>'上传营业执照照片失败'));
				exit();
			  }
			  
			  $license_data =array(
					'license_pic' => '/public/img/store/store/'.$store_image_dir."/". $license_img.".".$license_upload_result,
			  );
			  
			  $license_pic_result = $this->store_model->update('crm_store',$license_data,'store_id='.$store_id);
			  
			  if(!$license_pic_result)
			  {
				 @unlink("/public/img/store/store".$store_image_dir."/". $license_img.".".$license_pic_result);

				 mysql_query("ROLLBACK");
				 echo json_encode(array('state'=>'-9','message'=>'营业执照图片路径保存失败'));
				 exit();
			  }

			   $license_pic  = '/public/img/store/store/'.$store_image_dir."/". $license_img.".".$license_upload_result;
			
		 }
			//如果为下架,则取消推送关系
			if($state=='2')
			{
				$add_sql = "select source_partner_store_id,to_partner_id from crm_partner_store where source_partner_store_id=".$store_id." AND to_partner_id !='100000'";
		
				$add_result = $this->store_model->getAllInfo($add_sql);
				
				if(!empty($add_result) && !is_array($add_result))
				{	
					mysql_query("ROLLBACK");
					echo json_encode(array('state'=>'-9','message'=>'查询店铺的推送状态时失败'));
					exit();
				}
				
				if(!empty($add_result) && is_array($add_result))
				{
					$to_partner_id = ",";
					
					foreach($add_result as $distribute)
					{
						
						$to_partner_id.=$distribute['to_partner_id'].",";
					}
					
					$add_insert_data  = array(
						'belong' => 'store',
						'item_id' => $store_id,
						'act'	=> 'del',
						'to_partner_id' => $to_partner_id,
					);
					
					$add_insert_result = $this->store_model->insert($add_insert_data,'crm_distribute');
					
					if(!$add_insert_result)
					{
						mysql_query("ROLLBACK");
						echo json_encode(array('state'=>'-10','message'=>'写入分发表时失败'));
						exit();
					}
					
					$to_partner_id = trim($to_partner_id,',');
					
					
					$delete_sql = "delete from crm_partner_store where source_partner_store_id=".$store_id."  AND to_partner_id in(".$to_partner_id.")";
					
					
					$delete_result = $this->store_model->delete_sql($delete_sql);
					
					if(!$delete_result)
					{	
						mysql_query("ROLLBACK");
						echo json_encode(array('state'=>'-11','message'=>'删除推送记录时失败'));
						exit();
					}
				}
			}
			
			
			
			mysql_query("COMMIT");
			mysql_query("END");
			echo json_encode(array('state'=>'1','message'=>'编辑店铺成功','default_image'=>$default_image,'license_pic'=>$license_pic));	   
	}
	

	
	/*首页的总的区域*/
	public function region_allAction()
	{
		$region_mod = new RegionModel();
		
		$region_array = array();
		
		$sql = "select region_id,region_name from crm.crm_region where parent_id in(select region_id from crm.crm_region where parent_id=0)";
		
		$region_result = $region_mod->getAllInfo($sql);
		
		if(!$region_result)
		{
			die(json_encode(array('state'=>'-1','message'=>'checking region error')));
		}
		
		return $region_result;
	}
	
	
	/*
	*验证电话号码(手机,电话)
	*param tel
	*return json(array('state'=>'','message'=>''));
	*/
	
	function check_phone($tel)
	{
			$tel_array = explode("/",$tel);
			
			$str_check = "true";
			foreach($tel_array as $k=>$tel)
			{
				$check_phone_result = $this->store_model->check_phone($tel);
			
				if($check_phone_result['state']=="-2")
				{
					$str_check = "false";
					break;
				}
				
			}
			
			if($str_check=="false")
			{
				return array("state"=>"-2","message"=>"号码填写有误");
			}else
			{
				return array("state"=>"1","message"=>"号码验证通过");
			}
			
	}	
	
	
	public function regionAction()
	{
		$region_result = $this->store_model->store_region();
		
		echo urldecode($region_result);
	}
	
	public function regionrest()
	{
		$region_result = $this->store_model->store_region();
		
		return  urldecode($region_result);
	}
	
	
	//获取店铺的分类
	public function scategoryAction()
	{
		$scategory_result = $this->scategory_model->getChildren();

		if(!$scategory_result)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取店铺分类失败'));
		}else
		{
			echo json_encode(array('state'=>'1','message'=>'获取店铺分类成功','data'=>$scategory_result));
		}
	}

	/*
	 *获取区域下的建筑物
	 */
	public function buildAction()
	{
		$parent_region_id = $_POST['parent_id'];
		if(!$parent_region_id)
		{
			die(json_encode(array('state'=>'-1','message'=>'param missing')));
		}

		$sql = "select * from crm_building where parent_region_id=".$parent_region_id;

		$building_result = $this->store_model->getAllInfo($sql);

		if(!$building_result || !is_array($building_result))
		{
			die(json_encode(array('state'=>'-2','message'=>'no result')));
		}

		die(json_encode(array('state'=>'1','message'=>'success','data'=>$building_result)));

	}
	
	/*验证店铺名字的唯一性(美食送的店铺)  店铺添加时*/
	function  store_checkAction()
	{
		$store_name = $_POST['store_name'];
		
		if(!$store_name)
		{
			echo json_encode(array('state'=>'-1','message'=>'接收店铺名称失败'));
			exit();
		}
		
		$store_check_result =  $this->store_model->check_store_name_goods($store_name);
		
		if($store_check_result['state']=='-1')
		{	
			echo json_encode(array('state'=>'-2','message'=>'店铺名已经存在'));
		}else
		{
			echo json_encode(array('state'=>'1','message'=>'店铺名合法'));
		}
		
	}
	
	
	/*验证店铺名字的唯一性(美食送的店铺)  店铺编辑时*/
	
	function  store_check_editAction()
	{
		$store_name = $_POST['store_name'];
		$store_id   = $_POST['store_id'];
		
		if(!$store_name || !$store_id)
		{
			echo json_encode(array("state"=>'-1','message'=>'获取修改店铺的信息失败'));
			exit();
		}
		
		$store_check= $this->store_model->check_store_name_edit('100000',$store_name,$store_id);
		
		if($store_check['state']=='-1')
		{
			echo json_encode(array('state'=>'-1','message'=>'店铺名称已经存在'));
		}else
		{
			echo json_encode(array('state'=>'1','message'=>'店铺名称合格'));
		}
	}
	
	/*
	*通过地址返回经纬度
	*param address
	*
	*/

	public function positionAction($ci,$add)
	{
		$address = $_REQUEST['address']?$_REQUEST['address']:$add;
		
		$city = $_REQUEST['city']?$_REQUEST['city']:$ci;

		if(!$address || !$city)
		{
			echo json_encode(array("state"=>'-1',"message"=>"查询参数缺失"));
		}else
		{
			$position_result = $this->_address->getRenderFromBaidu($address,$city);
			
			if(!$position_result)
			{
				echo json_encode(array("state"=>'-2',"message"=>"地址填写错误,无相应的经纬度返回."));
			}else
			{
				if($ci)
				{
					return array('state'=>'1','message'=>$position_result);
				}else
				{	
					echo json_encode(array("state"=>'1',"message"=>"获取","data"=>$position_result));
				}
			}
		}
	}
	/*
	 *通过经纬度返回地址所在的区域
	*/
	public function areaAction($loc)
	{
		$location = $_REQUEST['location']?$_REQUEST['location']:$loc;
		
		if(!$location)
		{
			echo json_encode(array("state"=>'-1',"message"=>"查询参数缺失"));
		}else
		{
			$position_result = $this->_address->getLocationFromBaidu($location);
			//print_r($position_result);
			
			if(!$position_result)
			{
				echo json_encode(array("state"=>'-2',"message"=>"经纬度错误,未查询到所在区域"));
			}else
			{
				if($loc)
				{
					return array('state'=>'1','message'=>$position_result);
				}
				echo json_encode(array("state"=>'1',"message"=>"获取","data"=>$position_result));
			}
		}
	}
	
	//编辑页面下的菜品展示
	
	public function goodsAction()
	{
		$store_id = $_POST['store_id'];
		
		if(!$store_id)
		{
			die(json_encode(array('state'=>'-1','message'=>'没有店铺id')));
		}
		
		$sql = 'select crm_goods.goods_id,crm_goods.goods_name,crm_goods.price,crm_goods.packing_fee,crm_goods.nreceipt_discount,crm_goods.receipt_discount,crm_goods.if_show,crm_goods.default_image,crm_store.region_name,crm_gcategory.cate_id,crm_gcategory.cate_name from crm_goods left join crm_store on crm_goods.store_id = crm_store.store_id left join crm_gcategory on crm_goods.gcategory_id = crm_gcategory.cate_id  where crm_goods.store_id = '.$store_id.' order by  crm_goods.if_show  desc,crm_gcategory.cate_id';
		
		$goods_array = $this->store_model->getAllInfo($sql);
		
		if(empty($goods_array)  || !is_array($goods_array))
		{
			die(json_encode(array('state'=>'-2','message'=>'查询当前店铺的菜品失败!')));
		}
		
			echo json_encode(array('state'=>'1','message'=>'获取店铺菜品成功','data'=>$goods_array));
	}


	/*
	 * 编辑页面下的菜品修改
	*/
	public function goods_editAction()
	{
		$goods_id = $_REQUEST['goods_id'];
		if(!$goods_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'goods_id 非法'));
			exit();
		}
		$this->goods_model = new GoodsModel();
		$this->goods_model->goods_detail($goods_id);
	}
	
	//编辑页面下菜品添加名字验证
	
	public function goods_name_addAction()
	{
		$store_id = $_POST['store_id'];
		$goods_name = $_POST['goods_name'];
		
		if(!$store_id || !$goods_name)
		{
			echo json_encode(array('state'=>'-1','message'=>'验证菜品名字唯一的条件缺失'));
			exit();
		}
		
		$sql = "select * from crm_goods where store_id=".$store_id." AND goods_name='".$goods_name."'";
		
		$result = $this->store_model->getInfo($sql);
		
		if($result['goods_name'])
		{
			echo json_encode(array('state'=>'-2','message'=>'当前店铺下的菜品已经存在'));
			exit();
		}
			echo json_encode(array('state'=>'1','message'=>'菜品名字合格'));
	}
	
	//编辑页面下菜品编辑名字验证
	
	public function goods_name_editAction()
	{
		$store_id = $_POST['store_id'];
		$goods_id = $_POST['goods_id'];
		$goods_name = $_POST['goods_name'];
		
		if(!$store_id || !$goods_id || !$goods_name)
		{
			echo json_encode(array('state'=>'-1','message'=>'验证菜品名称唯一的信息缺失'));
			exit();
		}
		
		$sql = "select * from crm_goods WHERE store_id = ".$store_id." AND goods_name = '".$goods_name."'  AND goods_id !=".$goods_id;
		
		$result = $this->store_model->getInfo($sql);
		
		if(!empty($result)  && $result['goods_id'])
		{
			echo json_encode(array('state'=>'-2','message'=>'菜品名字已经存在'));
			exit();
		}
		
			echo json_encode(array('state'=>'1','message'=>'菜品名字合格'));
	}
	
	/*
	 *编辑页面下的合作伙伴
	*/

	public function  partnerAction()
	{
		$store_id = $_POST['store_id'];
		if(!$store_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取店铺id失败'));
			exit();
		}

		$sql = "select id,partner_name,appkey from crm_partner";

		$result  = $this->store_model->getAllInfo($sql);

		if(empty($result)  || !is_array($result))
		{
			echo json_encode(array('state'=>'-1','message'=>'获取合作伙伴失败'));
			exit();
		}

		foreach($result as $k=>$v)
		{
			$store_sql = "select * from crm_partner_store where source_partner_store_id=".$store_id." AND to_partner_id=".$v['appkey'];

			$store_result = $this->store_model->getInfo($store_sql);

			if(!empty($store_result)  && !is_array($store_result))
			{
				echo json_encode(array('state'=>'-2','message'=>'获取分发情况失败'));
				exit();
			}

			if(empty($store_result))
			{
				$result[$k]['distribute'] = 'false';
			}else
			{
				$result[$k]['distribute'] = 'true';
			}
		}

			echo json_encode(array('state'=>'1','message'=>'获取分发情况成功','data'=>$result));

	}

	/*
	 *编辑页面下合作伙伴的推送处理
	*/

	public function distributeAction()
	{
		$store_id = $_POST['store_id'];
		$status   = $_POST['status'];
		$partner_id = $_POST['partner_id'];

		if(!$store_id || !$status  || !$partner_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取推送条件失败'));
			exit();
		}

		if($status=='false')
		{
			//取消推送关系
			$sql = 'delete  from crm_partner_store where source_partner_store_id='.$store_id.' AND to_partner_id='.$partner_id;
			$result = mysql_query($sql);
			
			if(!$result)
			{
				echo json_encode(array('state'=>'-2','message'=>'取消推送失败'));
				exit();
			}
				echo json_encode(array('state'=>'1','message'=>'取消推送成功'));
		}else if($status=='true')
		{
			//推送
			$sql = 'select source_partner_id from crm_store where store_id='.$store_id;

			$result = $this->store_model->getInfo($sql);

			if(empty($result) || !is_array($result))
			{
				echo json_encode(array('state'=>'-2','message'=>'获取店铺的来源失败'));
				exit();
			}

			$source_partner_id = $result['source_partner_id'];
			$insert_data=array(
				'source_partner_store_id'=>  $store_id,
				'to_partner_id'			=> $partner_id,
				'source_partner_id'		=> $source_partner_id,
			);

			$insert_result = $this->store_model->insert($insert_data,'crm_partner_store');

			if(!$insert_result)
			{
				echo json_encode(array('state'=>'-3','message'=>'推送失败'));
				exit();
			}
				echo json_encode(array('state'=>'1','message'=>'推送成功'));
		}
	}

	/*
	*编辑页面下合作伙伴的已推送状态下的上下架操作
	*/

	public  function partner_stateAction()
	{
		foreach($_POST as $k=>$v)
		{
			${$k} = $v;
		}

		if(!$store_id || !$to_partner_id || !$store_state)
		{
			echo json_encode(array('state'=>'-1','message'=>'缺少修改上下架的参数'));
			exit();
		}


		if($store_state=='上架')
		{
			//则此时为下架
			$store_state='2';
		}else
		{
			//则此时为上架
			$store_state='1';
		}

		$update_data = array(
				'partner_store_state'=>$store_state,
		);

		$update_result = $this->store_model->update('crm_partner_store',$update_data,'source_partner_store_id='.$store_id.' AND to_partner_id='.$to_partner_id);
		
		
		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'修改状态失败'));
			exit();
		}
		echo json_encode(array('state'=>'1','message'=>'修改状态成功'));
	}



	/*
	 * 导出店铺的操作
	*/

	//统计要导出的数据
	public function export_countAction()
	{
		if(!$_POST)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取参数失败'));
			exit();
		}

		$where = $this->where($_POST);

		$count_result  = $this->store_model->getInfo("select count(*) as count from crm_store ".$where);

		if($count_result['count']=='0' || !is_array($count_result))
		{
			echo json_encode(array('state'=>'-2','message'=>'统计导出店铺的数量失败'));
			exit();
		}

			echo json_encode(array('state'=>'1','message'=>'统计导出店铺的数量成功','count'=>$count_result['count']));




	}

	//执行导出
	public function exportAction()
	{

		if(!$_POST)
		{
			echo json_encode(array('state'=>'-101','message'=>'获取参数失败'));
			exit();
		}

		$where = $this->where($_POST);

		$store_array= $this->store_model->getAllInfo("select * from crm_store ".$where);

		if(empty($store_array))
		{
			echo json_encode(array('state'=>'-1','message'=>'当前的搜索条件下没有要导出的店铺'));

			exit();
		}

		if(!empty($store_array)  && !is_array($store_array))
		{
			echo json_encode(array('state'=>'-2','message'=>'查询店铺信息失败'));
		}

		$fp = fopen(APP_PATH."/public/img/store.csv","w");

		$data_str = pack('H*','EFBBBF');

		$file_name = "/public/img/store.csv";

		if(!$fp)
		{
			echo json_encode(array('state'=>'-3','message'=>'打开文件时失败'));
			exit();
		}


		$data_array = array(
			"店铺ID",
			"店主",
			"店铺名称",
			"店铺二级类目",
			"是否有Logo",
			"是否可见",
			"所在地",
			"状态",
			"最低消费",
			"详细地址",
			"联系电话",
			"结算方式",
			"早餐开始时间",
			"早餐结束时间",
			"午餐开始时间",
			"午餐结束时间",
			"晚餐开始时间",
			"晚餐结束时间",
			"所在经度",
			"所在纬度",
			"建筑名称",
			"建筑经度",
			"建筑纬度"
		);


		$data_array = implode(",",$data_array);

		$data_str.= $data_array.PHP_EOL;

		foreach($store_array as $k=>$store)
		{
				//建筑物和二级分类
			if(!$store['category2'])
			{
				$store['category_name'] = '';
			}else
			{
					
					$category2 = $store['category2'];

					$category2 = trim($category2,',');

					$category2 = explode(',',$category2);

					foreach($category2 as $cate_v)
					{
						$gcategory2_result = $this->store_model->getInfo('select cate_name from crm_scategory where cate_id='.$cate_v);
						
						if(empty($gcategory2_result) || !is_array($gcategory2_result))
						{
						    echo json_encode(array('state'=>'-3','message'=>'查询店铺分类时失败'));

						    exit();
						}
						
						$store['category_name'].=$gcategory2_result['cate_name'].'/';
					}				
			}

			if($store['bd_id'])
			{
				$bd_result  = $this->store_model->getInfo("select bd_name,bd_longitude,bd_latitude from crm_building where bd_id=".$store['bd_id']);

				if(empty($bd_result) || !is_array($bd_result))
				{
					echo json_encode(array('state'=>'-4','message'=>'查询店铺建筑物时失败'));
					exit();
				}  
	
				$store['bd_name']  = $bd_result['bd_name'];
				$store['bd_longitude'] = $bd_result['bd_longitude'];
				$store['bd_latitude']  = $bd_result['bd_latitude'];
			}

			//时间

			$time = json_decode($store['business_time']);
			
			$store['zao_start'] = $time->break_time->start;
			$store['zao_close'] = $time->break_time->end;
			
			$store['zhong_start'] = $time->lunch_time->start;
			$store['zhong_close'] = $time->lunch_time->end;
			
			$store['wan_start'] = $time->supper_time->start;
			$store['wan_close'] = $time->supper_time->end;

			//结算方式

			if($store['checkout_type'] == '1')
			{
				$store['checkout_type'] = '现结';
			}else if($store['checkout_type'] == '2')
			{
				$store['checkout_type'] = '月结';
			}else if($store['checkout_type'] == '3')
			{
				$store['checkout_type'] = '预付';
			}

			//店铺logo

			if($store['store_logo'])
			{
				$store['store_logo'] = '有';
			}else
			{
				$store['store_logo'] = '无';
			}

			//是否可见

			if($store['visibility'] =='Y')
			{
				$store['visibility'] = "是";
			}else
			{
				$store['visibility'] = "否";
			}

			//状态

			if($store['state']=='1')
			{
				$store['state'] = '开启';
			}else
			{
				$store['state'] = '不开启';
			}

			
			$store_single = array(

				$store['store_id'],
				$store['owner_name'],
				$store['store_name'],
				$store['category_name'],
				$store['store_logo'],
				$store['visibility'],
				$store['region_name'],
				$store['state'],
				$store['min_cost'],
				$store['address'],
				$store['takeout_service_phone'],
				$store['checkout_type'],
				$store['zao_start'],
				$store['zao_close'],
				$store['zhong_start'],
				$store['zhong_close'],
				$store['wan_start'],
				$store['wan_close'],
				$store['longitude'],
				$store['latitude'],
				$store['bd_name'],
				$store['bd_longitude'],
				$store['bd_latitude']
			);

			$store_single = implode(",",$store_single);

			$data_str.=$store_single.PHP_EOL;

		}

		if (fwrite($fp,$data_str) === FALSE)
		{
       		 echo json_encode(array('state'=>'-5','message'=>'写入文件时失败'));
        	 exit();
   		}
		

		fclose($fp);
		
		echo json_encode(array('state'=>'1','message'=>'写入文件成功','data'=>$file_name));
	}
}
?>