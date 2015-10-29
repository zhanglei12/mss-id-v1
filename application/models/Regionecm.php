<?php
class RegionecmModel extends BaseModel
{
	var $table  = 'ecm_region';
	var $prikey = 'region_id';
	var $_name  = 'region';
	var $api_db;
	
	function __construct()
	{
		parent::__construct();
	}

	// public function __construct()
	// {
		// $this->api_db = Yaf_Registry::get('api_db');
		// $this->api_mail = Yaf_Registry::get('api_mail');
		// $this->api_log = Yaf_Registry::get('api_log');
		// $this->api_sms = Yaf_Registry::get('api_sms');
		// $this->api_base = Yaf_Registry::get('api_base');
	// }
	
	/*
	 *获取region信息
	*/
	function detail($region_id)
	{
		$sql = "select * from ".$this->table." WHERE  region_id = ".$region_id;
		return $this->getInfo($sql);
	}
	
	/**
	 * 从下至上获取region树
	 */
	function getRegionTreeByUp($region_id,&$arr_region_id)
	{
		if ($region_id=='')
			return false;
		$region = $this->detail($region_id);
		if (!is_array($region)||empty($region))
			return false;
		else
			$arr_region_id[] = array('parent_id'=>$region['parent_id'],'region_id'=>$region['region_id'],'region_name'=>$region['region_name']);
		$this->getRegionTreeByUp($region['parent_id'],&$arr_region_id);
	}

	/**
	 * 从上至下获取region树
	 */
	function getRegionTreeByDown($region_id,&$arr_region_id,&$level=0)
	{
		if ($region_id=='')
			return false;
		$sub_zone_array = $this->getSubRegion($region_id);
		if ($level==0)
		{
			$zone_array = $this->detail($region_id);
			$arr_region_id[$region_id] = $zone_array;
		}
		$level++;
		if (count($sub_zone_array)>0)
		{
			foreach ($sub_zone_array as $zone)
			{
				$arr_region_id[$region_id]['subzone'][$zone['region_id']] = $zone;
				$this->getRegionTreeByDown($zone['region_id'],&$arr_region_id[$region_id]['subzone'],&$level);
			}
		}
	}

	/**
	 * 得到所有城市
	 */
	function getCity()
	{
		return $this->getSubRegion(0);
	}

	/**
	 * 得到某一个区域的所有子区域
	 */
	function getSubRegion($region_id)
	{
		$sql = "select * from ".$this->table." WHERE  parent_id = ".$region_id;
		return $this->getAllInfo($sql);
	}

	/**
	 * 得到一个城市下所有大区
	 */
	function getCityZone($city_id)
	{
		$arr_zone_tree = array();
		$this->getRegionTreeByDown($city_id,&$arr_zone_tree);
		foreach ($arr_zone_tree[$city_id]['subzone'] as $zone)
		{
			foreach ($zone['subzone'] as $subzone)
			{
				echo '<pre>';
				print_r($subzone);
				echo '<br>';
				echo '</pre>';
			}
		}
	}
}
?>