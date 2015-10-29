<?php
class MapbdController extends Yaf_Controller_Abstract
{
	var $storeecm_model;
	var $zoneecm_model;
	var $regionecm_model;
	var $page;
	var $limit;
	var $arrp;
	
	public function init()
	{
		$this->storeecm_model = new StoreecmModel();
		$this->zoneecm_model = new ZoneecmModel();
		$this->regionecm_model = new RegionecmModel();
		$this->arrp = $_REQUEST;
	}

	public function testAction()
	{
		//$arr_region_id = array();
		//$this->regionecm_model->getRegionTreeByDown(4,&$arr_region_id);
		$sub_zone = $this->zoneecm_model->getSubZone(4);
		echo "<pre>";
		print_r($sub_zone);
		echo "</pre>";
	}

	public function indexAction()
	{
		//得到所有城市
		$city_id = array(0);
		$city_name = array('');
		$city_selected;
		$city_array = $this->regionecm_model->getCity();
		$zone_array = array();
		$zone_array[0] = array('region_id'=>0,'region_name'=>'全部','subzone'=>array());
		foreach ($city_array as $city)
		{
			$city_id[] = $city['region_id'];
			$city_name[] = $city['region_name'];
			$zone_bi_array = $sub_zone = $this->zoneecm_model->getSubZone($city['region_id']);
			$subzone_array = array();
			$subzone_array[0] = array('region_id'=>0,'region_name'=>'全部','region_coord'=>array());
			foreach ($zone_bi_array as $zone)
			{
				preg_match('/(?=[0-9]).+?(?=\))/',$zone['ploygongeo'],$coord);
				$coord = str_replace(',',';',$coord[0]);
				$coord = str_replace(' ',',',$coord);
				$subzone_array[$zone['region_id']] = array('region_id'=>$zone['region_id'],'region_name'=>$zone['region_name'],'region_coord'=>$coord);
			}
			$zone_array[$city['region_id']] = array('region_id'=>$city['region_id'],'region_name'=>$city['region_name'],'subzone'=>$subzone_array);
		}
		//从客服系统订单详情页面接收送餐地址 粘贴到查询地址处
		$this->getView()->assign('order_address',$_GET['order_address']);
		$this->getView()->assign('zone_array',json_encode($zone_array));
		$this->getView()->assign("city_id",$city_id);
		array_shift($city_id);
		$this->getView()->assign("city_id_real",$city_id);
		$this->getView()->assign("city_name",$city_name);
		array_shift($city_name);
		$this->getView()->assign("city_name_real",$city_name);
		$this->getView()->assign("city_selected",$city_selected);
		$this->getView()->display("mapbd/index");
	}

	public function searchstorebynameAction()
	{
		if (empty($this->arrp['term']))
		{
			die(json_encode(array('state'=>-101,'message'=>'店铺名称为空','data'=>'')));
		}
		$this->page = 1;
		$this->limit = 10;
		$where = "where store_name like '%".$this->arrp['term']."%'";
		$store_array = $this->storeecm_model->select($this->page,$this->limit,$where);
		$all_store = array();
		foreach ($store_array['data'] as $store)
		{
			//得到区域
			$arr_region_id = array();
			$store['zone'] = '';
			if (is_numeric($store['region_id'])&&$store['region_id']>0)
			{
				$this->regionecm_model->getRegionTreeByUp($store['region_id'],&$arr_region_id);
				//得到商圈坐标
				if (count($arr_region_id)>0&&is_numeric($arr_region_id[count($arr_region_id)-3]['region_id'])&&$arr_region_id[count($arr_region_id)-3]['region_id']>0)
				{
					$zone_array = $this->zoneecm_model->detailRegion($arr_region_id[count($arr_region_id)-3]['region_id']);
					preg_match('/(?=[0-9]).+?(?=\))/',$zone_array['ploygongeo'],$zone);
					$szone = str_replace(',',';',$zone[0]);
					$szone = str_replace(' ',',',$szone);
					$store['zone'] = $szone;
				}
			}
			$store['point'] = '';
			if (is_numeric($store['longitude'])&&is_numeric($store['latitude']))
				$store['point'] = $store['longitude'].','.$store['latitude'];
			$store['label'] = $store['store_name'].'('.$store['store_id'].')';
			$all_store[] = $store;
		}
		die(json_encode($all_store));
	}


	public function getsubzoneAction()
	{
		if (empty($this->arrp['region_id'])||!is_numeric($this->arrp['region_id']))
		{
			die(json_encode(array('state'=>-102,'message'=>'请选择一个正确的区域','data'=>'')));
		}
		$zone_bi_array = $sub_zone = $this->zoneecm_model->getSubZone($this->arrp['region_id']);
		$zone_array = array();
		$zone_array[] = array('region_id'=>'','region_name'=>'全部');
		foreach ($zone_bi_array as $zone)
		{
			preg_match('/(?=[0-9]).+?(?=\))/',$zone['ploygongeo'],$coord);
			$coord = str_replace(',',';',$coord[0]);
			$coord = str_replace(' ',',',$coord);
			$zone_array[] = array('region_id'=>$zone['region_id'],'region_name'=>$zone['region_name'],'region_coord'=>$coord);
		}
		die(json_encode(array('state'=>1,'message'=>'成功获取','data'=>$zone_array)));
	}
}
?>