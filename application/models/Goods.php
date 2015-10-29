<?php
class GoodsModel extends BaseModel
{
	var $table  = 'crm_goods';
	var $prikey = 'goods_id';
	var $_name  = 'goods';
	var $store_model;
	var $api_db;
	var $gcategoty_model;
	
	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db_crm');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
		$this->store_model = new StoreModel();
		$this->gcategory_model = new GcategoryModel();
	}
	
	/*
	 *商品信息表
	*/
	function select($page,$limit,$where='')
	{
	   try{
			$page = ($page-1)*$limit;

			$sql = "select * from ".$this->table." ".$where." limit ".$page." ,".$limit;	
	
			$goods_array = $this->getAllInfo($sql);

			if(!$goods_array  || ! is_array($goods_array))
			{
				return array('state'=>'-1','message'=>'check goods error!');
				exit();
			}else
			{
			
				foreach($goods_array as $k=>$v)
				{
					//1.查询店铺名,当前店铺分类名和区域
					$store_name_result = $this->store_model->store_edit_check($v['store_id']);
					if(!$store_name_result)
					{
						return array('state'=>'-2','message'=>'store_name_get_error');
						exit();
					}
					$cate_name_result = $this->gcategory_model->check_store_gcategory($v['store_id'],$v['gcategory_id']);
				
					if(!$cate_name_result)
					{
						$goods_array[$k]['cate_name'] = '';
					}else
					{
						$goods_array[$k]['cate_name'] = $cate_name_result['cate_name'];
					}
					
					$goods_array[$k]['store_name'] = $store_name_result['store_name'];
				
				}
			
			
				return array('state'=>'1','message'=>'查询成功!','data'=>$goods_array);
			}
		}catch(Exception $e)
		{   
			die(json_encode(array('state'=>"-1",'message'=>'ERROR DB','data'=>$e->getMessage())));
		}
	}
	
	/*查询商品id(添加时)*/
	function check_source_goods_id($store_id,$goods_id)
	{
		$sql ="select * from ".$this->table ." where store_id=".$store_id." AND source_goods_id=".$goods_id;
		
		return $this->getInfo($sql);
	}
	
	
	/*查询商品名字(添加时)*/
	function check_name($store_id,$goods_name)
	{
		$sql = "select * from ".$this->table." where store_id =".$store_id." AND goods_name= '".$goods_name."'";
		
		$goods_name_result = $this->getInfo($sql);
		
		return $goods_name_result;
	}
	
	/*查询商品名字(网页编辑时)*/
	function check_name_edit_web($store_id,$goods_name,$goods_id)
	{
		$sql = "select * from ".$this->table." 	WHERE store_id=".$store_id." AND goods_name='".$goods_name."' AND goods_id !=".$goods_id;
		
		return $this->getInfo($sql);
	}
	/*查询商品名字(接口编辑时)*/
	
	function check_name_edit($store_id,$goods_name,$goods_id)
	{
		$sql = "select * from ".$this->table." 	WHERE store_id=".$store_id." AND goods_name='".$goods_name."' AND source_goods_id !=".$goods_id;
		
		return $this->getInfo($sql);
	}
	
	//修改验证菜品在店铺内的唯一性 
	function check_goods_name($goods_name,$store_name,$goods_id){
		$sql = "select store_id from crm_store where store_name='".$store_name."'";

		$res = $this->getInfo($sql);

		$rsql = "select * from crm_goods where goods_id=".$res['store_id']." and goods_name='".$goods_name."' and goods_id!=".$goods_id;
		$result = $this->getAllInfo($rsql);
		return $result;
	}


	/*
	*统计店铺总数,分页
	*param 
	*/
	public function goods_summary($where)
	{
		$sql ="select count(*) as count from ".$this->table." ".$where;
		
		$count_result = $this->getInfo($sql);
		
		if(!$count_result)
		{
			die(array("state"=>"-1","message"=>"无店铺信息"));
		}
		
		$page_model = new Page($count_result['count']);
		
		$goods_summary_show = $page_model->fpage();
		
		return $goods_summary_show;
	}
	/*
	*统计店铺总数
	*param 
	*/
	public function goods_count($store_id)
	{
		$sql = "select count(*)  as count from ".$this->table." WHERE store_id=".$store_id;
		
		return $this->getInfo($sql);
	}
	/*
	 *goods详细信息
	*/
	
	public function detail($goods_id)
	{
		$sql = "select * from ".$this->table." where goods_id=".$goods_id;
		
		$goods_array = $this->getInfo($sql);
		
		if(!$goods_array)
		{
			die(json_encode(array('state'=>'-1','message'=>'goods_detail getting error')));
		}
		
		//1.查询店铺名,当前店铺分类名
		$store_name_result = $this->store_model->store_edit_check($goods_array['store_id']);
		if(!$store_name_result)
		{
			die(json_encode(array('state'=>'-2','message'=>'store_name_get_error')));
		}
		
		$cate_name_result = $this->gcategory_model->check_store_gcategory($goods_array['store_id'],$goods_array['gcategory_id']);
		
	
		if(!$cate_name_result['cate_name'])
		{
			die(json_encode(array('state'=>'-3','message'=>'cate_name_get_error')));
		}
		
		
		$goods_array['store_name'] = $store_name_result['store_name'];
		
		$goods_array['cate_name'] = $cate_name_result['cate_name'];
		
		return $goods_array;
		
	}
	
	/*菜品的详细信息*/
	
	function  goods_detail($goods_id = '')
	{
		$sql = 'select crm_goods.goods_id,crm_goods.store_id,crm_goods.goods_name,crm_goods.price,crm_goods.packing_fee,crm_goods.nreceipt_discount,crm_goods.spec_name,crm_goods.default_image,crm_goods.gcategory_id,crm_goods.receipt_discount,crm_goods.summary,crm_goods.if_show,crm_gcategory.cate_id,crm_gcategory.cate_name from crm_goods left join crm_gcategory on crm_goods.gcategory_id = crm_gcategory.cate_id where crm_goods.goods_id='.$goods_id;

		$goods_detail = $this->getInfo($sql);

		if(empty($goods_detail) || !is_array($goods_detail))
		{
			echo json_encode(array('state'=>'-2','message'=>'获取商品编辑信息失败!'));
			exit();
		}


		$cate_sql = 'select * from crm_gcategory where cate_id ='.$goods_detail['gcategory_id'];
		
		$cate_result = $this->store_model->getInfo($cate_sql);

		if(empty($cate_result) || !is_array($cate_result))
		{
			echo json_encode(array('state'=>'-3','message'=>'获取商品分类失败!'));
			exit();
		}

		$goods_detail['gcategory_name'] = $cate_result['cate_name'];
		
		echo json_encode(array('state'=>'1','message'=>'获取商品编辑信息成功!','data'=>$goods_detail));
	
	
	}
	
	
}
?>