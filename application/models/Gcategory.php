<?php
class GcategoryModel extends BaseModel
{
	var $table  = 'crm_gcategory';
	var $prikey = 'cate_id';
	var $_name  = 'gcategory';
	
	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db_crm');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
	}

    function  select($store_id)
	{
		$sql = "select cate_id,store_id,cate_name,parent_id from ".$this->table." where store_id=".$store_id;
		
		return $this->getAllInfo($sql);
	}
	
	
	
	/*
	 *查询store_id 和 gcategory_id的对应关系
	*/
	function check_store_gcategory($store_id,$cate_id)
	{
		$sql = "select * from ".$this->table." WHERE cate_id=".$cate_id." AND store_id=".$store_id;
		
		return $this->getInfo($sql);
	}
	
	/*网站分类详情*/
	function  detail($cate_id)
	{
		$sql ="select * from ".$this->table." WHERE cate_id=".$cate_id." AND parent_id=0";
		
		return $this->getInfo($sql);
	}
	
	
	/*出餐时间分类*/
	
	function pub()
	{
		$sql = "select * from ".$this->table." WHERE store_id=0";
		
		return $this->getAllInfo($sql);
	}
	
}
?>