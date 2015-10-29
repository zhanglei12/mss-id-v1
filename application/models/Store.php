<?php
class StoreModel extends BaseModel
{
	var $table  = 'crm_store';
	var $prikey = 'store_id';
	var $_name  = 'store';
	var $api_db;
	
	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db_crm');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
	}
	
	function select($page,$limit,$where='')
	{
		
		try
		{
			$page = ($page-1)*$limit;

			$sql = "select * from ".$this->table." ".$where."order by store_id limit ".$page." ,".$limit;
			
			$store_array = $this->getAllInfo($sql);
			
			if(empty($store_array)  || !is_array($store_array))
			{
				return array('state'=>'-1','message'=>'get store list failed');
				
				exit();
			}


			foreach($store_array as $store_k=>$store)
			{
				//店铺分类不存在
				if(!$store['category2'])
				{
					$store_array[$store_k]['category_name'] = '';
				}else
				{
					
					$category2 = $store['category2'];
					$category2 = trim($category2,",");
					$category2 = explode(',',$category2);
					foreach($category2 as $cate_v)
					{
						$gcategory2_result = $this->get_cate_name($cate_v);
						
						if(empty($gcategory2_result) || !is_array($gcategory2_result))
						{
						  return array('state'=>'-2','message'=>'get store category2 failed');
						  exit();
						}
						
						$store_array[$store_k]['category_name'].=$gcategory2_result['cate_name'].',';
					}				
				}

				//店铺的下架原因
				if($store['off_reason'])
				{
					$sql = 'select * from crm_pull_off_shelves_reason where id='.$store['off_reason'];

					$result = $this->getInfo($sql);

					$store_array[$store_k]['off_reason'] = $result['reason'];

				}

				//crm系统在mss系统下的店铺id
				$this->match_model =  new MatchModel();

				$mss_store_id_array = $this->match_model->mss($store['store_id']);

				if($mss_store_id_array['state']=='-2')
				{
					$store_array[$store_k]['mss_id'] = null;
				}else
				{
					$store_array[$store_k]['mss_id'] = $mss_store_id_array['mss_store_id'];
				}

			}	
				return array('state'=>'1','message'=>'get store success','data' => $store_array);
		}catch(Exception $e)
		{
			die(json_encode(array('state'=>"-2",'message'=>'数据异常','data'=>$e->getMessage())));
		}
	}

	
	
	
	/*
	 *	店铺编辑--显示一个店铺信息
	*/
	
	function edit_show($store_id)
	{
		$sql ="select * from ".$this->table." where store_id=".$store_id;
		
		$store_result = $this->getInfo($sql);
		
		if(empty($store_result)  || !is_array($store_result))
		{
			return false;
		}
		
		$time = json_decode($store_result['business_time']);
		
		$store_result['time']  = $time;
		
		
		$store_result['zao_start'] = $time->break_time->start;
		$store_result['zao_close'] = $time->break_time->end;
		
		$store_result['zhong_start'] = $time->lunch_time->start;
		$store_result['zhong_close'] = $time->lunch_time->end;
		
		$store_result['wan_start'] = $time->supper_time->start;
		$store_result['wan_close'] = $time->supper_time->end;
		
		//获取图片名字
		
		$store_result['image_name'] = end(explode("/",$store_result['store_logo']));
	
		return $store_result;
		
	}
	
	
	/*
	 *获取cate_name
	*/
	
	function get_cate_name($cate_id)
	{
		$sql = "select cate_name from crm_scategory where cate_id=".$cate_id;
		
		return $this->getInfo($sql);
	}
	

	/*添加店铺时验证店铺名称唯一性(自己的店铺)*/

	function check_store_name($store_name)
	{
		
		$sql ="select * from ".$this->table." where store_name ='".$store_name."' AND source_partner_id='100000'";
	
		$store_name_check = $this->getInfo($sql);
		
		if($store_name_check['store_id'])
		{
			return "false";
		}else
		{
			return "true";
		}
	}


	
	
	/*添加店铺时验证店铺名称唯一性(自己的店铺) 返回数据*/
	
	function check_store_name_goods($store_name)
	{
		
		$sql ="select * from ".$this->table." where store_name ='".$store_name."' AND source_partner_id='100000'";
	
		$store_name_check = $this->getInfo($sql);
		
		if($store_name_check['store_id'])
		{
			return array('state'=>'-1','message'=>'store_name is  exists','data'=>$store_name_check);
		}else
		{
			
			return array('state'=>'1','message'=>'store_name is not exists');
		}
	}

	
	/*编辑店铺时验证店铺名称唯一性
	 *param store_name,store_id
	 *return json('state'=>'','message'=>'','data'=>'');
	 */
	
	function check_store_name_edit($partner_id='100000',$store_name,$store_id)
	{
		
		$sql ="select * from ".$this->table." where source_partner_id=".$partner_id."  AND    store_name ='".$store_name."' AND store_id !=".$store_id;
		
		$store_name_check  = $this->getInfo($sql);
			
		if(!empty($store_name_check) && $store_name_check['store_id'])
		{
			return array('state'=>'-1','message'=>'store_name already exists');
		}else
		{
			return array('state'=>'1','message'=>'店铺名称合格');
		}
	}
	
	/*
	*验证电话号码(手机,电话)
	*param tel
	*return json(array('state'=>'','message'=>''));
	*/
	
	function check_phone($tel)
	{
		if(!$tel)
		{
			return json_encode(array('state'=>'-1','message'=>'验证参数缺失!'));
		}else
		{
			if(preg_match("/^\(?0\d{2,3}\)?[ -]?\d{7,8}$/",$tel) || preg_match("/^1[3,4]{1}[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0,1,2,3,5,6,7,8,9]{1}[0-9]{8}$|17[0,7]{1}[0-9]{8}$/",$tel) || preg_match("/^(4|8)00-\d{3,5}-\d{3,5}$/",$tel))
			{
					return array("state"=>"1","message"=>"号码验证通过!");
			}else
			{
					return array("state"=>"-2","message"=>"号码输入错误!");

			}
		}
	}
	
	
	
	/*检查店铺是否存在*/
	
	
	function store_edit_check($store_id)
	{
		$sql = "select * from ".$this->table." where store_id=".$store_id;
		
		$store_exit_result = $this->getInfo($sql);
		
		return $store_exit_result;
	}


	
	
	/*模糊store_name查询返回store_id
	 *param store_name
	 *return array;
	 */
	
	function check_store_ids($store_name)
	{
		
		$sql ="select * from ".$this->table." where store_name like '%".$store_name."%'";
	
		$store_name_check = $this->getAllInfo($sql);
		
		return $store_name_check;
	}
	


	/*
	 *整合店铺区域
	 */	
	public function store_region()
	{

		$sql = "select region_id,region_name,parent_id from crm_region where is_visibility=1";

		$region_result = $this->getAllInfo($sql);
		//print_r($region_result);return;

		$region_reset = array();

		foreach($region_result as $k=>$v)
		{
			$region_reset[$v['region_id']] = $v;

			$region_reset[$v['region_id']]['region_name'] = urlencode($region_reset[$v['region_id']]['region_name']);
		}

		$region_reset = $this->genTree($region_reset); 

		foreach($region_reset as $k=>$v)
		{
			unset($region_reset[$k]['parent_id']);

			foreach($region_reset[$k]['son'] as $one_k=>$one_v)
			{
				unset($region_reset[$k]['son'][$one_k]['parent_id']);

				if(isset($one_v['son']))
				{
					foreach($one_v['son'] as $two_k=>$two_v)
					{
						unset($region_reset[$k]['son'][$one_k]['son'][$two_k]['parent_id']);

						if(isset($two_v['son']))
						{
							foreach($two_v['son'] as $three_k=>$three_v)
							{
								unset($region_reset[$k]['son'][$one_k]['son'][$two_k]['son'][$three_k]['parent_id']);
							}
						}
					}
				}
			}
		}

		$region_json = json_encode($region_reset);


		$region_json = str_replace("region_id","v",$region_json);

		$region_json = str_replace("region_name","n",$region_json);

		$region_json = str_replace("son","s",$region_json);
		
		return $region_json;
	
	} 

  public function genTree($items) 
  {  
    $tree = array();  
    foreach ($items as $item)
    {
    	$items[$item['id']]=$item;
        if (isset($items[$item['parent_id']]))
        {
        	 $items[$item['parent_id']]['son'][] = &$items[$item['region_id']];  
        	
        }else
        {
        	$tree[] = &$items[$item['region_id']];  
        }  
    }
    	return $tree;  
   }

}
?>