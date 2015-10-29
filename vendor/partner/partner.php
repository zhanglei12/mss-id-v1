<?php

class  Partner {
    var $api_db;
    var $db_crm;
    /* 	public function  __construct($param)
        {
            $this->db_crm = $param['db_crm'];
            
        } */
    /*向上查找父区域  */
    function getRegion($region_id, $num = 6) {
        $region = $region_id;

        for ($i = 0; $i < $num; $i++) {
            $sql = 'select * from ecm_region where region_id=' . $region;
            $regionInfo = $this->api_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);

            if ($regionInfo['parent_id'] < 1) {
                return $regionInfo;
                break;
            }
            $region = $regionInfo['parent_id'];
        }
        return $regionInfo;
    }

    /*向下查找子区*/
    function get_region_by_lv($region_deep = 3, $parent_id = 0) {
        $region_table = 'ecm_region';
        $area_list = array();
        $region_join_sql = '';
        $region_orderby_sql = 'r1.`sort_order`,';
        for ($i = 2; $i <= $region_deep; $i++) {
            $region_join_sql .= "JOIN `{$region_table}` r{$i} ON (r" . ($i - 1) . ".`region_id`=r{$i}.`parent_id`) ";
            $region_orderby_sql .= "r{$i}.`sort_order`, ";
        }
        $region_orderby_sql = substr($region_orderby_sql, 0, -2);
        $sql = "SELECT r{$region_deep}.`region_id`, r{$region_deep}.`region_name`,r{$region_deep}.`parent_id`,r{$region_deep}.`sort_order` FROM `{$region_table}` r1 " .
            $region_join_sql .
            " WHERE r1.`parent_id`=" . $parent_id .
            " ORDER BY " .
            $region_orderby_sql;
        $area_list[$region_deep] = $this->api_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
        if (DB::isError($area_list[$region_deep])) {
            throw new Exception("Error DB");
        }
        return $area_list[$region_deep];
    }

    /* 查找区域范围 */
    function getZone($region_id) {
        $region = $this->getRegion($region_id, 2);
        $sql = 'select * from ecm_zone where region_id=' . $region['region_id'];
        $regionInfo = $this->api_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (DB::isError($regionInfo)) {
            throw new Exception("Error DB");
        }
        if (!empty($regionInfo)) {
            return $regionInfo['ploygongeo'];
        }
        return false;
    }

    /*模拟get提交数据  */
    function request_by_curl_get($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);
        return $output;
    }

    /*特殊字符处理  */
    function replaceSpecialStr($text) {
        return strtr(stripslashes($text), array(
            "\r" => '', "\n" => '', "\t" => '', "\b" => '',
            "\x00" => '', "\x01" => '', "\x02" => '', "\x03" => '',
            "\x04" => '', "\x05" => '', "\x06" => '', "\x07" => '',
            "\x08" => '', "\x0b" => '', "\x0c" => '', "\x0e" => '',
            "\x0f" => '', "\x10" => '', "\x11" => '', "\x12" => '',
            "\x13" => '', "\x14" => '', "\x15" => '', "\x16" => '',
            "\x17" => '', "\x18" => '', "\x19" => '', "\x1a" => '',
            "\x1b" => '', "\x1c" => '', "\x1d" => '', "\x1e" => '',
            "\x1f" => ''
        ));

    }
}
