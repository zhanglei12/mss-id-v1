<?php
class ZoneecmModel extends BaseModel
{
	var $table  = 'ecm_zone';
	var $prikey = 'id';
	var $_name  = 'zone';
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
	 *获取zone信息
	*/
	function detail($zone_id)
	{
		$sql = "select * from ".$this->table." WHERE  id = ".$zone_id;
		return $this->getInfo($sql);
	}
	function detailRegion($region_id)
	{
		$sql = "select * from ".$this->table." WHERE  region_id = ".$region_id;
		return $this->getInfo($sql);
	}

	/**
	 * 得到某一个城市的所有子区域
	 */
	function getSubZone($region_id)
	{
		$sql = "select * from ".$this->table." WHERE  parent_region_id = ".$region_id;
		return $this->getAllInfo($sql);
	}
}
?>