<?php
/**
 * TOP API: taobao.alitrip.orderinfo.airbook request
 * 
 * @author auto create
 * @since 1.0, 2014-12-02 16:49:09
 */
class AlitripOrderinfoAirbookRequest
{
	/** 
	 * AtsrBookArrangerInfoDO
	 **/
	private $bookArrangerInfo;
	
	/** 
	 * AtsrBookFlightSegmentDO
	 **/
	private $bookFlightSegmentList;
	
	/** 
	 * AtsrBookTravelerInfoDO
	 **/
	private $bookTravelerList;
	
	/** 
	 * channel_name
	 **/
	private $channelName;
	
	/** 
	 * extra
	 **/
	private $extra;
	
	/** 
	 * fee
	 **/
	private $fee;
	
	/** 
	 * password
	 **/
	private $password;
	
	/** 
	 * payment
	 **/
	private $payment;
	
	/** 
	 * reservation_code
	 **/
	private $reservationCode;
	
	/** 
	 * sale_price
	 **/
	private $salePrice;
	
	/** 
	 * tax
	 **/
	private $tax;
	
	/** 
	 * total_money
	 **/
	private $totalMoney;
	
	private $apiParas = array();
	
	public function setBookArrangerInfo($bookArrangerInfo)
	{
		$this->bookArrangerInfo = $bookArrangerInfo;
		$this->apiParas["book_arranger_info"] = $bookArrangerInfo;
	}

	public function getBookArrangerInfo()
	{
		return $this->bookArrangerInfo;
	}

	public function setBookFlightSegmentList($bookFlightSegmentList)
	{
		$this->bookFlightSegmentList = $bookFlightSegmentList;
		$this->apiParas["book_flight_segment_list"] = $bookFlightSegmentList;
	}

	public function getBookFlightSegmentList()
	{
		return $this->bookFlightSegmentList;
	}

	public function setBookTravelerList($bookTravelerList)
	{
		$this->bookTravelerList = $bookTravelerList;
		$this->apiParas["book_traveler_list"] = $bookTravelerList;
	}

	public function getBookTravelerList()
	{
		return $this->bookTravelerList;
	}

	public function setChannelName($channelName)
	{
		$this->channelName = $channelName;
		$this->apiParas["channel_name"] = $channelName;
	}

	public function getChannelName()
	{
		return $this->channelName;
	}

	public function setExtra($extra)
	{
		$this->extra = $extra;
		$this->apiParas["extra"] = $extra;
	}

	public function getExtra()
	{
		return $this->extra;
	}

	public function setFee($fee)
	{
		$this->fee = $fee;
		$this->apiParas["fee"] = $fee;
	}

	public function getFee()
	{
		return $this->fee;
	}

	public function setPassword($password)
	{
		$this->password = $password;
		$this->apiParas["password"] = $password;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setPayment($payment)
	{
		$this->payment = $payment;
		$this->apiParas["payment"] = $payment;
	}

	public function getPayment()
	{
		return $this->payment;
	}

	public function setReservationCode($reservationCode)
	{
		$this->reservationCode = $reservationCode;
		$this->apiParas["reservation_code"] = $reservationCode;
	}

	public function getReservationCode()
	{
		return $this->reservationCode;
	}

	public function setSalePrice($salePrice)
	{
		$this->salePrice = $salePrice;
		$this->apiParas["sale_price"] = $salePrice;
	}

	public function getSalePrice()
	{
		return $this->salePrice;
	}

	public function setTax($tax)
	{
		$this->tax = $tax;
		$this->apiParas["tax"] = $tax;
	}

	public function getTax()
	{
		return $this->tax;
	}

	public function setTotalMoney($totalMoney)
	{
		$this->totalMoney = $totalMoney;
		$this->apiParas["total_money"] = $totalMoney;
	}

	public function getTotalMoney()
	{
		return $this->totalMoney;
	}

	public function getApiMethodName()
	{
		return "taobao.alitrip.orderinfo.airbook";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkMaxListSize($this->bookFlightSegmentList,20,"bookFlightSegmentList");
		RequestCheckUtil::checkNotNull($this->bookTravelerList,"bookTravelerList");
		RequestCheckUtil::checkMaxListSize($this->bookTravelerList,20,"bookTravelerList");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
