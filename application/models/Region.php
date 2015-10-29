<?php
class RegionModel extends BaseModel
{
	var $table  = 'crm_region';
	var $prikey = 'region_id';
	var $_name  = 'region';

	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db_crm');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
	}

	// 获取region信息,全部获取
	function getTree($visibility)
	{
		
		if($visibility == '1')
		{
			$sql = "select region_id,parent_id,region_name,is_visibility from ".$this->table." where is_visibility=1";
		}else
		{
			$sql = "select region_id,parent_id,region_name,is_visibility from ".$this->table;
		}

		

		return $this->getAllInfo($sql);
	}

	/*
	 *获取region信息
	*/
	function detail($region_id)
	{
		$sql = "select * from ".$this->table." WHERE  region_id = ".$region_id." and is_visibility=1";

		return $this->getInfo($sql);
	}

	/*
	 *从下至上获取region_id
	*/
	function getRegionIdByUp($region_id,&$arr_region_id)
	{
			if($region_id=='')
			{
				return false;
			}

			$sql = "select * from crm_region where region_id=".$region_id;

			$result = $this->getInfo($sql);

			if(empty($result))
			{
				//当前区域是有问题的
				echo json_encode(array('state'=>'-1','message'=>'区域有问题'));
				exit();
			}

			$arr_region_id.=$region_id.",";

			if($result['parent_id']!='0')
			{
				$this->getRegionIdByUp($result['parent_id'],&$arr_region_id);
			}else
			{
				return $arr_region_id;
			}
	}


	


	/**
	 * 得到某一个区域的所有子区域
	 */
	function getSubRegion($region_id)
	{
		$sql = "select * from ".$this->table." WHERE  parent_id = ".$region_id;
		return $this->getAllInfo($sql);
	}


	
	/*
	 * 获取下一级分类
	*/
	
	function  getChildren($region_id)
	{
		if($region_id=='')
		{
			$region_id='0';
		}
		$sql = "select * from ".$this->table." WHERE parent_id=".$region_id;
		
		$region = $this->getAllInfo($sql);
		
		if(empty($region)  || !is_array($region))
		{
			return false;
		}else
		{
			return $region;
		}
	}

}
?>