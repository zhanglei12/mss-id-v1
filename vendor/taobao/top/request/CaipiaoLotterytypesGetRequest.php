<?php
/**
 * TOP API: taobao.caipiao.lotterytypes.get request
 * 
 * @author auto create
 * @since 1.0, 2014-03-28 13:19:07
 */
class CaipiaoLotterytypesGetRequest
{
	
	private $apiParas = array();
	
	public function getApiMethodName()
	{
		return "taobao.caipiao.lotterytypes.get";
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
