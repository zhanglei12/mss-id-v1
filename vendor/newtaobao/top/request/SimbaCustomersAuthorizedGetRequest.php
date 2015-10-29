<?php
/**
 * TOP API: taobao.simba.customers.authorized.get request
 * 
 * @author auto create
 * @since 1.0, 2014-12-02 16:49:09
 */
class SimbaCustomersAuthorizedGetRequest
{
	
	private $apiParas = array();
	
	public function getApiMethodName()
	{
		return "taobao.simba.customers.authorized.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
