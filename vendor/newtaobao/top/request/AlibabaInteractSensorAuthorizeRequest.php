<?php
/**
 * TOP API: alibaba.interact.sensor.authorize request
 * 
 * @author auto create
 * @since 1.0, 2014-12-02 16:49:09
 */
class AlibabaInteractSensorAuthorizeRequest
{
	
	private $apiParas = array();
	
	public function getApiMethodName()
	{
		return "alibaba.interact.sensor.authorize";
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
