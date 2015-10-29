<?php
class ScategoryModel extends BaseModel
{
	var $table  = 'crm_scategory';
	var $prikey = 'cate_id';
	var $_name  = 'scategory';
	var $api_db;
	
	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db_crm');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
	}

	/*
	 *  获取所有的分类
	*/
	function select($weixin)
	{
		
			if($weixin=='1')
			{
				$sql ="select * from ".$this->table." where weixin=1";
			}else
			{
				$sql = "select * from ".$this->table;
			}

				return $this->getAllInfo($sql);
			
	}
	/*获取正常可选择的分类*/
	function  getChildren()
	{	
		
		$sql = 'select cate_id,cate_name from '.$this->table.' where parent_id in(select cate_id from '.$this->table.' where parent_id=0 and weixin=1)  and weixin=1';
		$result = $this->getAllInfo($sql);
		
		if(empty($result)  || !is_array($result))
		{
			return false;
		}else
		{
			return  $result;
		}
	}

	/*验证名字唯一*/

	function check_name($parent_id,$cate_name)
	{
		$sql = "select * from ".$this->table." WHERE parent_id=".$parent_id." AND cate_name='".$cate_name."'";

		return $this->getInfo($sql);
	}
	
}
?>