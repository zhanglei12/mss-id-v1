<?php

class Address {
    var $_httprequest;
    var $_db;

    function __construct($newHttp = null, $newDb = null) {
        if (!$newHttp && !$newDb) {
            $this->_httprequest = new PostMethod();
            $this->_db = $this->registerDb();
        }
        elseif (!$newHttp) {
            $this->_httprequest = new PostMethod();
            $this->_db = $newDb;
        }
        elseif (!$newDb) {
            $this->_httprequest = $newHttp;
            $this->_db = $this->registerDb();
        }
        else {
            $this->_httprequest = $newHttp;
            $this->_db = $newDb;
        }
    }

    public function seachRegion($address, $city) {
        $lnt = $this->getRenderFromBaidu($address, $city);
        if (!$lnt) {
            return array(
                "status" => -1,
                "message" => "地址无法解析"
            );
        }
        $region = $this->getRegion($lnt);
        if ($region) {
            return array(
                "status" => 1,
                "message" => "成功",
                "data" => $region
            );
        }
        return array(
            "status" => -2,
            "message" => "地址不在区域内",
            "data" => $region
        );
    }

    /**
     * 判断地址是否在某区域内
     *
     * @param string $address
     * @param int $region_id
     *
     * @return array
     */
    public function ifAddressInRegion($address, $region_id) {
        try {
            $city = $this->getCityFromRegion($region_id);
            $lnt = $this->getRenderFromBaidu($address, $city);

            if (!$lnt) {
                return array(
                    "status" => -1,
                    "message" => "地址无法解析"
                );
            }
            $region = $this->getRegion($lnt);
            if ($region) {
                if ($region["region_id"] == $region_id) {
                    return array(
                        "status" => 1,
                        "message" => "地址在区域内"
                    );
                }
            }
            return array(
                "status" => -2,
                "message" => "地址不在区域内"
            );
        } catch (Exception $e) {
            return array(
                "status" => -3,
                "message" => $e
            );
        }
    }

    public function getRegion($localtion) {
        $lng = $localtion['lng'];
        $lat = $localtion['lat'];
        $select_sql = "SELECT *,substring(ploygongeo,10,length(ploygongeo)-11) as `ploygongeo` from `ecm_zone` where myWithin(PolygonFromText('Point(" . $lng . " " . $lat . ")'),PolygonFromText(ploygongeo))>0";
        $result = $this->_db->getRow($select_sql, array(), DB_FETCHMODE_ASSOC);
        return $result;
    }

    public function registerDb() {
        $api_dsn = DATABASE_48_TYPE . "://" . DATABASE_48_USERNAME . ":" . DATABASE_48_PASSWORD . "@" . DATABASE_48_HOST . ":" . DATABASE_48_PORT . "/" . DATABASE_48_NAME;
        $db = new DB;
        $api_db = $db->connect($api_dsn, false);
        if (DB::isError($api_db)) {
            die($api_db->getMessage());
        }
        $api_db->query("SET NAMES utf8");
        return $api_db;
    }

    /**
     * 根据订单编号获取该订单地址的经纬度
     *
     * @param string $sn
     *
     * @return array
     */
    function getRender($sn) {
        if (!$sn) {
            return array("ret" => "-1", "msg" => "ORDER NOT EXIST", "info" => array());
        }
        set_time_limit(0);
        $paramsstr = '';
        $order_info = $this->getOrderInfo($sn);
        if (!$order_info) {
            return array("ret" => "-1", "msg" => "ORDER NOT EXIST", "info" => array());
        }
        $address = $order_info['address'];
        $region_id = $order_info['region_id'];
        if ((!$address) || (!$region_id)) {
            return array("ret" => "0", "msg" => "NO RESULT", "info" => array());
        }
        $city = $this->getCityFromRegion($region_id);
        $return = $this->getRenderFromBaidu($address, $city);
        if (!$return) {
            return array("ret" => "0", "msg" => "NO RESULT", "info" => array());
        }
        $info['lng'] = round($return['lng'], 4);
        $info['lat'] = round($return['lat'], 4);
        return array("ret" => "1", "msg" => "SUCCESS", "info" => $info);
    }

    function getOrderInfo($sn) {
        $order_sql = "SELECT o.`order_id`,oe.`region_id`,oe.`address` FROM `ecm_order` o LEFT JOIN `ecm_order_extm` oe ON o.`order_id`=oe.`order_id` WHERE o.`order_sn`='" . $sn . "'";
        $order_info = $this->getInfo($order_sql);
        if (!$order_info) {
            return false;
        }
        return $order_info;
    }

    /**
     *根据经纬度返回地址的详细信息
     *param string $location
     */
    function  getLocationFromBaidu($location) {
        //echo $location;return;
        if (!$location) {
            return false;
        }

        $map_url = "http://api.map.baidu.com/geocoder/v2/";

        $params = array(
            "ak" => "4871f6529108253b471ed784192abae9",
            "output" => "json",
            "location" => $location,
        );
        $paramsstr = '';
        foreach ($params as $key => $value) {
            $paramsstr .= $key . '=' . $value . '&';
        }
        rtrim($paramsstr, '&');

        $renderOption_json = $this->_httprequest->request_by_curl_get($map_url, $paramsstr);

        $renderOption = json_decode($renderOption_json, true);

        //print_r($renderOption);

        if ($renderOption['status'] == '0') {
            return $renderOption['result']['addressComponent']['district'];
        }
        else {
            return false;
        }
    }

    /**
     * 根据百度地图接口返回地址的经纬度
     *
     * @param string $address
     * @param string $city
     *
     * @return boolean|array
     */
    function getRenderFromBaidu($address, $city = '') {
        if (!$address) {
            return false;
        }
        $map_url = "http://api.map.baidu.com/geocoder?address";
        $city = ($city) ? $city : "北京市";
        $params = array(
            "ak" => "4871f6529108253b471ed784192abae9",
            "output" => "json",
            "address" => $address,
            "city" => $city,
        );
        $paramsstr = '';
        foreach ($params as $key => $value) {
            $paramsstr .= $key . '=' . urlencode($value) . '&';
        }
        rtrim($paramsstr, '&');
        $renderOption_json = $this->_httprequest->request_by_curl_get($map_url, $paramsstr);
        $renderOption = json_decode($renderOption_json, true);
        //获取到结果
        if ($renderOption['result']) {
            return $renderOption['result']['location'];
        }
        return false;
    }

    function getCityFromRegion($regionId) {
        if (!$regionId) {
            return '北京市';
        }
        $pa_sql = "SELECT * FROM nowmss.ecm_region WHERE `region_id`='" . $regionId . "'";
        $pa_info = $this->getInfo($pa_sql);
        if ($pa_info) {
            if ($pa_info['parent_id'] == 0) {
                return $pa_info['region_name'];
            }
            else {
                return $this->getCityFromRegion($pa_info['parent_id']);
            }
        }
        else {
            return '北京市';
        }
    }

    function getInfo($sql) {
        return $this->_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
    }

    function getAllInfo($sql) {
        return $this->_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    }

    /**
     * 对输入的字符串进行匹配，取出其中包含的建筑名称或别称的建筑ID
     *
     * @author Lessbom
     *
     * @param  string $address 要进行匹配的地址
     * @param  int $region_id 所匹配的区域ID，将使用此区域ID下所有的建筑进行匹配，如此项为0，则以全部建筑进行匹配
     *
     * @return array                   返回建筑ID数组的匹配结果
     */
    function matchBuildingFromOrder($sn) {
        $orderinfo = $this->getOrderInfo($sn);
        if (!$orderinfo) {
            return array("ret" => "-1", "msg" => "ORDER NOT EXIST", "info" => array());
        }
        $address = $orderinfo['address'];
        $region_id = $orderinfo['region_id'];
        if ((!$address) || (!$region_id)) {
            return array("ret" => "-2", "msg" => "NO REGIONS", "info" => array());
        }
        $address_render = $this->getRender($sn);
        if ($address_render['ret'] != "1") {
            return array("ret" => "-3", "msg" => "NO RENDER", "info" => array());
        }
        $address_lng = $address_render['info']['lng'];
        $address_lat = $address_render['info']['lat'];
        $regions = $this->getParentRegins($region_id);
        if (!$regions) {
            return array("ret" => "-2", "msg" => "NO REGIONS", "info" => array());
        }
        $match_condition = " `region_id1` " . $this->db_create_in($regions) . " OR `region_id2` " . $this->db_create_in($regions) . " OR `region_id3` " . $this->db_create_in($regions) . " OR `region_id4` " . $this->db_create_in($regions);
        $render_condition = " `bd_latitude` IS NOT NULL AND `bd_longitude` IS NOT NULL";
        $build_sql = "SELECT `bd_id`,`bd_latitude`,`bd_longitude` FROM `ecm_building` WHERE " . $match_condition . " AND " . $render_condition;
        $builds = $this->getAllInfo($build_sql);
        $builds = array_filter($builds, array($this, "check_empty"));
        if (!$builds) {
            return array("ret" => "-3", "msg" => "NO BUILDINGS", "info" => array());
        }
        foreach ($builds as $b_k => $b_v) {
// 			$builds[$b_k]['address_latitude'] = $address_lat;
// 			$builds[$b_k]['address_longitude'] = $address_lng;
            $builds[$b_k]['distance'] = $this->getDistance($address_lat, $address_lng, $b_v['bd_latitude'], $b_v['bd_longitude']);
        }
        usort($builds, array($this, "my_sort"));
        if ($builds && is_array($builds) && is_array(current($builds))) {
            $buildInfo = current($builds);
            return array("ret" => "1", "msg" => "SUCCESS", "info" => $buildInfo);
        }
        else {
            return array("ret" => "-3", "msg" => "NO BUILDINGS", "info" => array());
        }
    }

    function my_sort($a, $b) {
        if ($a['distance'] == $b['distance']) {
            return 0;
        }
        return ($a['distance'] < $b['distance']) ? -1 : 1;
    }

    /**
     * 判断一个字段值是否为空，去掉false，FALSE，null以及“”的值
     *
     * @param any_type $val
     *
     * @return boolean,如果返回值为false，则为空，如果为true则不会空
     */
    function check_empty($val) {
        if (in_array($val['bd_latitude'], array("FALSE", "NULL", "", "TRUE", "false", "null", "true", "0.000000")) || in_array($val['bd_longitude'], array("FALSE", "NULL", "", "TRUE", "false", "null", "true", "0.000000"))) {
            return false;
        }
        return true;
    }

    function getParentRegins($region_id) {
        if (!$region_id) {
            return false;
        }
        $pa_sql = "SELECT * FROM `ecm_region` WHERE `region_id`=" . $region_id;
        $pa_info = $this->getInfo($pa_sql);
        if ($pa_info) {
            if ($pa_info['parent_id']) {
                $parents = array();
                $tmp = $this->getParentRegins($pa_info['parent_id']);
                $ids = array_merge($parents, $tmp);
                $ids[] = $pa_info['parent_id'];
            }
            $ids[] = $region_id;
        }
        $un_pa = array_unique($ids);
        return $un_pa;
    }

    /**
     * 创建像这样的查询: "IN('a','b')";
     *
     * @access   public
     *
     * @param    mix $item_list 列表数组或字符串,如果为字符串时,字符串只接受数字串
     * @param    string $field_name 字段名称
     *
     * @author   wj
     * @return   void
     */
    function db_create_in($item_list, $field_name = '') {
        if (empty($item_list)) {
            return $field_name . " IN ('') ";
        }
        else {
            if (!is_array($item_list)) {
                $item_list = explode(',', $item_list);
                foreach ($item_list as $k => $v) {
                    $item_list[$k] = intval($v);
                }
            }

            $item_list = array_unique($item_list);
            $item_list_tmp = '';
            foreach ($item_list AS $item) {
                if ($item !== '') {
                    $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
                }
            }
            if (empty($item_list_tmp)) {
                return $field_name . " IN ('') ";
            }
            else {
                return $field_name . ' IN (' . $item_list_tmp . ') ';
            }
        }
    }

    /**
     * @desc 根据两点间的经纬度计算距离
     *
     * @param float $lat 纬度值
     * @param float $lng 经度值
     */
    function getDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6367000; //approximate radius of earth in meters
        /*
         Convert these degrees to radians
         to work with the formula
         */
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        /*
        Using the
        Haversine formula
        http://en.wikipedia.org/wiki/Haversine_formula
        calculate the distance
        */
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }

    /** 根据经纬度计算距离 */
    function newgetdistance($longitude1, $latitude1, $longitude2, $latitude2) {
        $theta = $longitude1 - $longitude2;
        $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        //return compact('miles', 'feet', 'yards', 'kilometers', 'meters');
        return $meters;
    }

}
