<?php
class RegionalModel extends BaseModel
{
	var $table  = 'crm_region';
	var $prikey = 'region_id';
	var $_name  = 'region';
	/*
	function __construct()
	{
		parent::__construct();
	}
	*/
	var $api_db;
	
	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db_crm');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
	}
	
	public function indexAction(){
		echo "1111";
	}

	



}
?>