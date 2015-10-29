<?php

class Coop {
    var $wdb;
    var $rdb;
    var $log;
    var $api_db;
    var $api_log;
    var $api_base;

    public function __construct($api_db = null) {
        if ($api_db) {
            $this->api_db = $api_db;
        }
        else {
            $this->api_db = $this->$this->registerDb();
        }
    }

    function getStore($storeId, $partner) {
        $sql = 'select * from ecm_coop_mss where belong="store" and partner =' . $partner . ' and local_itemid =' . $storeId;
        $respr = $this->api_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (DB::isError($respr)) {
            return false;
        }
        $itemId = $respr['coop_itemid'];
        return $itemId;
    }

    function getGoods($goodsId, $partner, $object = null) {

        $sql = 'select * from ecm_coop_mss where belong="goods" and partner =' . $partner . ' and local_itemid =' . $goodsId;
        $rowpr = $this->api_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (DB::isError($rowpr)) {
            return false;
        }
        $goodsInfo['coop_itemid'] = $rowpr['coop_itemid'];
        if ($rowpr['iteminfo']) {
            $iteminfo = json_decode($rowpr['iteminfo'], true);
            $goodsInfo['category_id'] = $iteminfo['category_id'];
            $goodsInfo['pic_url'] = $iteminfo['pic_url'];
        }
        else {
            if ($object && $rowpr['coop_itemid']) {
                $info = $object->getGoodsInfo($rowpr['coop_itemid']);
                $goodsInfo['category_id'] = $info->result->category_id ? $info->result->category_id : 50024765;
                $goodsInfo['pic_url'] = $info->result->pic_url ? $info->result->pic_url : 'http://img.taobaocdn.com/bao/uploaded/i4/T1irGxFupeXXcG6Cw3';
                $iteminfo = array(
                    'category_id' => $goodsInfo['category_id'],
                    'pic_url' => $goodsInfo['pic_url']
                );
                $upsql = "update ecm_coop_local set iteminfo='" . json_encode($iteminfo) . "' where id =" . $rowpr['id'];
                $res = $this->api_db->query($upsql);
            }
        }
        return $goodsInfo;
    }

    function getRegion($region) {
        $sql = 'select * from ecm_region where region_id =' . $region;
        $regionInfo = $this->api_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (DB::isError($rowpr)) {
            return false;
        }
        return $regionInfo;
    }

    function getTel($region, $num = 4) {
        if ($num < 0) {
            $num = 4;
        }
        $regionInfo = $this->getRegion($region);
        for ($i = 1; $i < $num; $i++) {
            if ($regionInfo['parent_id'] < 1) {
                break;
            }
            $regionInfo = $this->getRegion($regionInfo['parent_id']);
        }
        $tels = array(4 => '010-52285085', 176 => '021-33977577');
        $tel = $tels[$regionInfo['region_id']] ? $tels[$regionInfo['region_id']] : '010-52285085';
        return $tel;
    }
}

?>
