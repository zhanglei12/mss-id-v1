<?php
/**
 * TOP API: taobao.waimai.shop.get request
 * 
 * @author auto create
 * @since 1.0, 2014-03-28 13:19:07
 */
class WaimaiShopGetRequest
{
	/** 
	 * 外卖店铺id
	 **/
	private $shopid;
	
	private $apiParas = array();
	
	public function setShopid($shopid)
	{
		$this->shopid = $shopid;
		$this->apiParas["shopid"] = $shopid;
	}

	public function getShopid()
	{
		return $this->shopid;
	}

	public function getApiMethodName()
	{
		return "taobao.waimai.shop.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->shopid,"shopid");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}