<?php

class Mss_Store {
    var $wdb;
    var $rdb;
    var $log;
    var $base;

    public function __construct($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
        $this->log = $arr['log'];
        $this->base = $arr['base'];
    }

    public function setDB($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
    }

    public function getStoreList($limit, $offset) {
        if ($limit == null || !is_int((int)$limit) || $limit > 15) {
            $limit = 15;
        }
        if ($offset == null || !is_int((int)$offset)) {
            $offset = 0;
        }
        $sqls = "select count(1) as sumsid from ecm_store where visibility='Y' and state=1 and store_name not like '%sd%'";
        $rows = $this->rdb->getone($sqls);
        $sql = "select store_id,store_name,address,tel,state,visibility,store_logo,description,breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time,longitude,latitude,star_level,region_id,min_cost from ecm_store where visibility='Y' and state=1 and store_name not like '%sd%' order by store_id limit " . $offset . "," . $limit;
        $res = $this->rdb->query($sql);
        $arr = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            if ($row['store_logo'] != '') {
                $row['store_logo'] = 'http://www.meishisong.cn/' . $row['store_logo'];
            }
            $close_time = str_replace(':', '', $row['supper_close_time']);
            if ($close_time > 213000) {
                $row['supper_close_time'] = '21:30:00';
            }
            $row['dist_zone'] = $this->getStoreDistRange($row['region_id']);
            $row['store_name'] = $this->deleteSpecialCharacters($row['store_name']);
            $row['address'] = $this->deleteSpecialCharacters($row['address']);
            $arr[] = $row;
        }
        $arra = array(
            'list' => $arr,
            'summary' => array(
                'all' => $rows,
                'pagesize' => $limit,
                'page' => $offset / $limit
            )
        );
        return $arra;
    }


    public function getStoreDetail($store_id, $type = 'less') {
        if ($store_id != null && is_int((int)$store_id)) {
            //$sql = "select store_id,store_name,address,tel,state,visibility,store_logo,description,breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time,longitude,latitude,star_level,region_id,min_cost from ecm_store where store_id='".$store_id."' and visibility='Y' and state=1";
            if ($type == 'less') {
                $sql = "select store_id,store_name,address,close_reason,tel,state,visibility,store_logo,description,breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time,longitude,latitude,star_level,region_id,min_cost from ecm_store where store_id=" . $store_id . " and visibility='Y' and store_name not like '%sd%'";
            }
            elseif ($type == 'more') {
                $sql = "select store_id,store_name,address,tel,state,close_reason,visibility,store_logo,description,breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time,longitude,latitude,star_level,region_id,min_cost from ecm_store where store_id=" . $store_id;
            }
            $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
            if (empty($row)) {
                return false;
            }
            if ($row['store_logo'] != '') {
                $row['store_logo'] = 'http://www.meishisong.cn/' . $row['store_logo'];
            }
            $close_time = str_replace(':', '', $row['supper_close_time']);
            if ($close_time > 213000) {
                $row['supper_close_time'] = '21:30:00';
            }
            $row['	'] = $this->getStoreDistRange($row['region_id']);
            $row['province'] = $this->getRegionList($row['region_id'], 4);
            $row['county'] = $this->getRegionList($row['region_id'], 3);
            $row['aoi'] = $this->getRegionList($row['region_id'], 2);
            $row['takeout_area'] = $this->getRegionList($row['region_id'], 1);
            $row['business_state'] = $this->stateProcess($store_id);
            $row['shop_time'] = $this->arr_opentime($store_id);
            $row['store_name'] = $this->deleteSpecialCharacters($row['store_name']);
            $row['address'] = $this->deleteSpecialCharacters($row['address']);
            $row['region_name'] = $this->getStoreRegionname($row['region_id']);
            $row['category1'] = '餐饮';
            $row['phone'] = $this->phoneProcess($store_id);
            return $row;
        }
    }

    public function getStoreDistRange($region_id) {
        $sqlpr = "select parent_id from ecm_region where region_id=" . $region_id;
        $respr = $this->rdb->query($sqlpr);
        if (DB::isError($respr)) {
            return false;
        }
        $rowpr = $respr->fetchRow(DB_FETCHMODE_ASSOC);
        $parent_id = $rowpr['parent_id'];
        $sqldr = "select ploygongeo from ecm_zone where region_id=" . $parent_id;
        $resdr = $this->rdb->query($sqldr);
        if (DB::isError($resdr)) {
            return false;
        }
        $rowdr = $resdr->fetchRow(DB_FETCHMODE_ASSOC);
        return $rowdr['ploygongeo'];
    }

    public function getStoreRegionname($region_id) {
        $sqlpr = "select parent_id from ecm_region where region_id=" . $region_id;
        $respr = $this->rdb->query($sqlpr);
        if (DB::isError($respr)) {
            return false;
        }
        $rowpr = $respr->fetchRow(DB_FETCHMODE_ASSOC);
        $parent_id = $rowpr['parent_id'];
        $sqldr = "select region_name from ecm_zone where region_id=" . $parent_id;
        $resdr = $this->rdb->query($sqldr);
        if (DB::isError($resdr)) {
            return false;
        }
        $rowdr = $resdr->fetchRow(DB_FETCHMODE_ASSOC);
        return $rowdr['region_name'];
    }

    /*
    public function getUpdateStoreList($limit,$offset,$appkey){
    
        if($limit==NULL || !is_int((int)$limit) || $limit>15)
        {
        
            $limit=15;
        }
        
        if ($offset==NULL||!is_int((int)$offset))
        {
            $offset = 0;
        }
        
        $sqls =	"select count(item_id) as sumsid from ecm_update_for_coop where belong='store' and same_already not like '%".$appkey.",%'";
        
        $rows = $this->rdb->query($sqls);
        
        var_dump($rows);
    }
    */

    public function getUpdateStoreList($limit, $offset, $appkey) {
        if ($limit == null || !is_int((int)$limit) || $limit > 15) {
            $limit = 15;
        }
        if ($offset == null || !is_int((int)$offset)) {
            $offset = 0;
        }
        if ($appkey == null || empty($appkey)) {
            return false;
        }
        $sqls = "select count(1) as sumsid from ecm_update_for_coop where belong='store' and same_already not like '%" . $appkey . ",%'";
        $rows = $this->rdb->getone($sqls);
        $sql = "select update_id,belong,item_id from ecm_update_for_coop where belong='store' AND same_already not like '%" . $appkey . ",%' limit " . $offset . "," . $limit;
        $res = $this->rdb->query($sql);
        $arr = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $store_info = $this->getStoreDetail($row['item_id']);
            $store_info['update_id'] = $row['update_id'];
            $arr[] = $store_info;
        }
        $arra = array(
            'list' => $arr,
            'summary' => array(
                'all' => $rows,
                'pagesize' => $limit,
                'page' => $offset / $limit
            )
        );
        return $arra;
    }

    public function setStoreUpdatedByPartner($appkey, $update_id) {
        try {
            if ($appkey == null || empty($appkey)) {
                return false;
            }
            if ($update_id == null || empty($update_id)) {
                return false;
            }
            if (count(explode(',', $update_id)) > 15) {
                return false;
            }
            $sql = "update ecm_update_for_coop set same_already=concat(same_already,'" . $appkey . ",') where update_id in (" . $update_id . ")";
            $res = $this->wdb->query($sql);
            if (DB::isError($resi)) {
                return array('state' => -2, 'message' => '数据库异常', 'data' => $e->getMessage());
            }
            return array('state' => 1, 'message' => '更新成功', 'data' => '');
        } catch (Exception $e) {
            return array('state' => -1, 'message' => '未知异常', 'data' => $e->getMessage());
        }
    }

    public function deleteSpecialCharacters($string) {
        //去除字符串 首尾 空白等特殊符号或指定字符序列
        $string = trim($string);
        //去掉 HTML、XML 以及 PHP 的标签
        $string = strip_tags($string, "");
        //去掉TAB切换产生的符号
        $string = ereg_replace("\t", "", $string);
        //去掉换行 通常是两个enter造成
        $string = ereg_replace("\r\n", "", $string);
        //去掉enter换行
        $string = ereg_replace("\r", "", $string);
        //去掉换行
        $string = ereg_replace("\n", "", $string);
        //去掉空白
        $string = ereg_replace(" ", " ", $string);
        //处理从数据库或 HTML 表单中取回数据包含的特殊符号
        $string = stripslashes($string);
        //删除bom标记
        $string = preg_replace('/^(\xef\xbb\xbf)/', '', $string);
        return $string;
    }

    //用户所在地方查询
    function  getRegionList($region_id, $num = 4) {
        $region['region_id'] = $region_id;
        for ($i = $num; $i > 0; $i--) {
            $region = $this->getRegion($region['region_id']);
        }
        return $region['region_name'];
    }

    function getRegion($id) {
        if ($id != '' && is_numeric($id)) {
            $sql = "select parent_id, region_id, region_name from ecm_region where region_id=" . $id;
            $arr = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
            return array("region_id" => $arr['parent_id'], 'region_name' => $arr['region_name']);
        }
    }

    //处理餐饮时间
    function  arr_opentime($store_id) {
        $opentime = array();//存放时间
        $sql = "select breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time 
      			from  ecm_store where  store_id='{$store_id}'";
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (preg_match('/^[0-9]*:[0-9]*/', $row['breakfast_open_time'])) {
            $breakfast['start'] = substr($row['breakfast_open_time'], 0, 5);
        }

        if (preg_match('/^[0-9]*:[0-9]*/', $row['breakfast_close_time'])) {
            $breakfast['end'] = substr($row['breakfast_close_time'], 0, 5);
        }

        if (preg_match('/^[0-9]*:[0-9]*/', $row['lunch_open_time'])) {
            $lunch['start'] = substr($row['lunch_open__time'], 0, 5);
        }

        if (preg_match('/^[0-9]*:[0-9]*/', $row['lunch_close_time'])) {
            $luch['end'] = substr($row['lunch_close_time'], 0, 5);
        }

        if (preg_match('/^[0-9]*:[0-9]*/', $row['supper_open_time'])) {
            $supper['start'] = substr($row['supper_open_time'], 0, 5);
        }

        if (preg_match('/^[0-9]*:[0-9]*/', $row['supper_close_time'])) {
            $supper['end'] = substr($row['supper_close_time'], 0, 5);
        }
        //判断营业时间的连续性
        //定义几个状态
        $breakfasts = 0;
        $lunchs = 0;
        $suppers = 0;
        //早餐最后时间与晚餐的最后时间一致
        if ($breakfast['start'] != $breakfast['end']) {
            $breakfasts = 1;
            if ($breakfast['end'] == $lunch['start']) {
                $breakfast['end'] = $lunch['end'];
                $lunchs = 1;
                if ($lunch['end'] == $supper['end']) {
                    $breakfast['end'] = $supper['end'];
                    $suppers = 1;
                    $opentime = $breakfast;
                }
            }
        }

        //午餐最晚时间和晚餐的最晚时间一致
        if ($lunchs = 0) {
            if ($lunch['start'] != $lunch['end']) {
                if ($lunch['end'] == $supper['start']) {
                    $lunch['end'] = $supper['end'];
                    $suppers = 1;
                    $opentime = $lunchs;
                }
            }
        }

        // 晚餐时间与午餐的最晚时间不一致
        if ($suppers == 0) {
            if ($supper['start'] != $supper['end']) {
                $opentime = $supper;
            }
        }
        return $opentime;
    }

    //对电话号码进行处理函数
    function  phoneProcess($store_id) {
        $sql = "select store_id,region_id from ecm_store where store_id=" . $store_id . " and visibility='Y' and store_name not like '%sd%'";
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        $province = $this->getRegionList($row['region_id'], 4);//获取所在省份
        $phone = $row['tel'];
        if (strlen($phone) <= 8) {
            if ($province == '北京市') {
                $phone = '010-' . $phone;
            }
            if ($province == '上海市') {
                $phone = '021-' . $phone;
            }
        }
        else {
            $phone = '010-52285085';
        }
        if (strlen($phone) > 8) {
            $arrp = array();//用来存分割出来的数组
            $phonearr = explode("/", $phone);
            if ($province == '北京市') {
                foreach ($phonearr as $v) {
                    if ($v[0] == 0 && $v[1] == 1 && $v[2] == 0) {
                        $arrp[] = $v;
                    }
                    else {
                        $arrp[] = "010-" . $v;
                    }
                }
            }
            else if ($province == '上海市') {
                foreach ($phonearr as $v) {
                    if ($v[0] == 0 && $v[1] == 2 && $v[2] == 1) {
                        $arrp[] = $v;
                    }
                    else {
                        $arrp[] = "021-" . $v;
                    }
                }
            }
            $phone = implode(" ", $arrp);
        }
        else {
            $phone = '010-52285085';
        }
        return $phone;

    }

    //状态改变的的处理
    function  stateProcess($store_id) {
        $sql = "select store_id,state from ecm_store where store_id=" . $store_id . " and visibility='Y' and store_name not like '%sd%'";
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if ($row['state'] == 1) {
            $business_state = 1;
        }
        else {
            $business_state = 3;
        }

        return $business_state;
    }

}
