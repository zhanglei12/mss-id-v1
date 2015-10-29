<?php
define("PUBLIC_PATH", realpath(dirname(dirname(__FILE__))));
require_once(PUBLIC_PATH . '/partner/partner.php');

class  DaZhongDianPing extends Partner {
    var $store_mod;
    var $secret;
    var $partner_key;

    public function  __construct($arr = array('api_db' => null, 'crm_db' => null)) {
        $this->api_db = $arr['api_db'];
        $this->crm_db = $arr['crm_db'];
        require_once(PUBLIC_PATH . '/meishisong/Store.php');
        $this->store_mod = new Mss_Store(array('wdb' => $this->api_db, 'rdb' => $this->api_db));
        $this->partner_key = 'MgfBzWMSzp';
        $this->secret = 'RZkycJv6CmOZ03zgpbAk';
    }

    /*生成签名
     * $content json 
     * $time 时间戳
     * return str
     * */
    function getSign($content, $time) {
        $pucontent = $content;
        $sign = $this->partner_key . 'content' . $pucontent . 'ts' . $time . $this->secret;
        $sign = strtoupper(sha1($sign));
        return $sign;
    }

    /*获取大众点评店铺状态
     * $storeIds array, mss store_id ,array(347,160)
     * return array
     *   */
    function getDzdpStoreStatus($storeIds) {
        //$storeIds=array(347,29805,30672,160,82);
        $time = time();
        $stores = array();
        foreach ($storeIds as $v) {
            if ($v > 2000000000) {
                $stores[] = $v % 1000000000;
            } else {
                $stores[] = $v;
            }
        }
        $content = json_encode($stores);
        $sign = $this->getSign($content, $time);
        $url = PARTNER_URI_DAZHONGDIANPING . "/takeaway/v1/getshopsstatus?pk=" . $this->partner_key . "&sign=" . $sign . "&ts=" . $time . "&content=" . $content;
        $re = $this->request_by_curl_get($url);
        $storeInfo = json_decode($re, true);
        if (is_array($storeInfo) && $storeInfo['status'] === 0) {
            if ($storeInfo['content']) {
                $ret = array();
                foreach ($storeInfo['content'] as $v) {
                    $ret[$v['shopId']] = $v;
                }
                return $ret;
            }
            return false;
        }
        return false;

    }


    /* 上传店铺及更新
     * $storeIds array ,mss店铺id
     *  $status str ,店铺状态，空时为店铺本身状态， Y: 店铺上架，N：店铺下架
     * 
     *  */

    function PushStores($storeInfos, $status = "") {
        //$storeIds[]=$_GET['store_id'];
        //$storeIds=array(29805,30672,160);
        $content = array();
        foreach ($storeInfos as $val) {
            $content[] = $this->getStoreInfo($val, $status);
        }
        $time = time();
        $pucontent = urldecode(json_encode($content));
        $sign = $this->partner_key . 'content' . $pucontent . 'ts' . $time . $this->secret;
        $sign = strtoupper(sha1($sign));
        $curl_url = PARTNER_URI_DAZHONGDIANPING . "/takeaway/v1/batchuploadshop?pk=" . $this->partner_key . "&sign=" . $sign . "&ts=" . $time . "&content=" . urlencode($pucontent);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $re = json_decode($data, true);
        if ($re['status'] === 0) {
            return array('status' => 1, 'message' => 'success', 'data' => $curl_url);
        }
        return array('status' => 0, 'message' => $data, 'data' => $curl_url);
    }

    public function getStoreInfo($storeInfo, $status) {
        $retion_info = $this->getRegion($storeInfo['region_id']);
        $retion_name = $retion_info ? $retion_info['region_name'] : '北京';
        $zonestr = $this->getZone($storeInfo['region_id']);
        $zonestr = substr($zonestr, 9, -2);
        $zones = explode(",", $zonestr);
        $points = array();
        foreach ($zones as $val) {
            $p = explode(" ", $val);
            $points[] = array(floatval($p[0]), floatval($p[1]));
        }
        $if_online_pay = 0;
        if (array_key_exists("if_online_pay", $storeInfo)) {
            $if_online_pay = $storeInfo['if_online_pay'];
        }
        $store_time = json_decode($storeInfo['business_time'], true);
        $starttime = $store_time['lunch_time']['start'];
        $endtime = $store_time['supper_time']['end'];
        if ($storeInfo['store_id'] > 2000000000) {
            $storeInfo['store_id'] = $storeInfo['store_id'] % 1000000000;
        }
        $pushInfo = array(
            'shopid' => $storeInfo['store_id'],
            'city' => urlencode($retion_name ? $retion_name : '北京'),
            'shopname' => urlencode($this->replaceSpecialStr($storeInfo['store_name'])),
            'address' => urlencode($this->replaceSpecialStr($storeInfo['address'])),
            'phonenumber' => '01052285085',//str_replace('/',' ',$storeInfo['tel']),
            'lat' => $storeInfo['latitude'],
            'lng' => $storeInfo['longitude'],
            'interval' => 50,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'status' => $status ? $status : ($storeInfo['state'] < 2 ? 'Y' : 'N'),
            'discount' => '100',
            'minfee' => $storeInfo['min_cost'],
            'mindeliverfee' => array_key_exists("delivery_fee", $storeInfo) ? $storeInfo['delivery_fee'] : 6.00,
            'onlinepayment' => $if_online_pay,
            'distance' => 3000,
            'coordtype' => 3
        );
        if ($storeInfo["store_logo"]) {
            $pushInfo["picture"] = "http://crm.meishisong.cn" . $storeInfo["store_logo"];
        }
        $startresttime = $store_time['lunch_time']['end'];
        $endresttime = $store_time['supper_time']['start'];
        if ($startresttime != $endresttime) {
            $pushInfo['startresttime'] = $startresttime;
            $pushInfo['endresttime'] = $endresttime;
        }
        if ($starttime == $endtime) {
            $pushInfo['starttime'] = $store_time['break_time']['start'];
            $pushInfo['endtime'] = $store_time['break_time']['end'];
        }
        $arr = array(
            'type' => 'FeatureCollection',
            'features' => array(
                array(
                    'geometry' => array(
                        'type' => 'Polygon', 'coordinates' => array(
                            $points
                        )
                    ),
                    'properties' => array('delivery_price' => $storeInfo['min_cost'], 'coordtype' => 3
                    ))));
        $pushInfo['geojson'] = json_encode($arr);
        return $pushInfo;

    }

    function updataGoods($goodsInfo) {
        if ($goodsInfo['store_id'] > 2000000000) {
            $goodsInfo['store_id'] = $goodsInfo['store_id'] % 1000000000;
        }
        $pushGoods = array(
            'dishid' => $goodsInfo['goods_id'],
            'shopid' => $goodsInfo['store_id'],
            'status' => $goodsInfo['if_show'] == 1 ? "Y" : "N",
            'price' => $goodsInfo['price'],
            'box' => intval($goodsInfo['packing_fee'])

        );
        $re = $this->updataToDzdp($pushGoods);
        $res = json_decode($re, true);
        if ($res['status'] === 0) {
            return array('status' => 1, 'message' => '成功');
        } else {
            return array('status' => 0, 'message' => $re);
        }

    }

    /*推送菜品 */
    function updataToDzdp($content) {
        set_time_limit(0);
        $time = time();
        $push = urldecode(json_encode($content));
        $sign = strtoupper(sha1($this->partner_key . 'content' . $push . 'ts' . $time . $this->secret));
        $ch = curl_init();
        $curl_url = PARTNER_URI_DAZHONGDIANPING . "/takeaway/v1/updatedish?pk=$this->partner_key&sign=" . $sign . "&ts=" . $time . "&content=" . urlencode($push);

        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }

    /*推送或更新菜品 
     * $goodsInfos array ,菜品信息
     * 
     * */
    function pushGoods($goodsInfos) {
        $sucess = 0;
        $fail = 0;
        if (count($goodsInfos) > 3) {
            return array('status' => -1, 'message' => '一次长传菜品过多');
        }
        foreach ($goodsInfos as $k => $v) {
            if ($v['store_id'] > 2000000000) {
                $v['store_id'] = $v['store_id'] % 1000000000;
            }
            $pushGoods[$k] = array(
                'dishid' => $v['goods_id'],
                'shopid' => $v['store_id'],
                'name' => urlencode($v['goods_name']),
                'category' => urlencode($v['cate_name'] ? $v['cate_name'] : '其他'),
                'price' => $v['price'],
                'box' => intval($v['packing_fee']),
                'comment' => urlencode($v['summary'] ? $v['summary'] : '可口美味的'),
                'status' => $v['if_show'] == 1 ? "Y" : "N"
            );
            if ($v['default_image'] != '') {
                $pushGoods[$k]['picture'] = 'http://crm.meishisong.cn/' . $v['default_image'];
            } else {
                $pushGoods[$k]['picture'] = 'http://crm.meishisong.cn/public/img/default.jpg';
            }
        }
        $re = $this->PushGoodsToDzdp($pushGoods);
        $res = json_decode($re, true);
        if ($res['status'] === 0) {
            return array('status' => 1, 'message' => '成功');
        } else {
            return array('status' => 0, 'message' => $re);
        }
    }

    /*推送菜品 */
    function PushGoodsToDzdp($content) {
        set_time_limit(0);
        $time = time();
        $push = urldecode(json_encode($content));
        $sign = strtoupper(sha1($this->partner_key . 'content' . $push . 'ts' . $time . $this->secret));
        $ch = curl_init();
        $curl_url = PARTNER_URI_DAZHONGDIANPING . "/takeaway/v1/batchuploaddish?pk=$this->partner_key&sign=" . $sign . "&ts=" . $time . "&content=" . urlencode($push);

        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
}
