<?php

class Mss_Goods {
    var $wdb;
    var $rdb;
    var $log;

    public function __construct($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
        $this->log = $arr['log'];
    }

    public function setDB($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
    }


    public function getGoodsDetail($goods_id) {
        if ($goods_id != null && is_int((int)$goods_id)) {
            $sql = "select * from ecm_goods where goods_id=" . $goods_id;
            $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
            if (empty($row)) {
                return false;
            }
            if ($row['default_image'] != '') {
                $row['default_image'] = 'http://www.meishisong.cn/' . $row['default_image'];
            }
            $cate_idName = $this->getGoodsCate($goods_id);
            if (empty($cate_idName)) {
                return false;
            }
            $row['cate_id'] = key($cate_idName);
            $row['cate_name'] = $cate_idName[$row['cate_id']];
            $row['goods_name'] = $this->deleteSpecialCharacters($row['goods_name']);
            return $row;
        }
    }

    public function getGoodsList($store_id, $limit, $offset) {
        if ($store_id == null || !is_int((int)$store_id) || !$store_id > 0) {
            return false;
        }
        if ($limit == null || !is_int((int)$limit) || $limit > 15) {
            $limit = 15;
        }
        if ($offset == null || !is_int((int)$offset)) {
            $offset = 0;
        }
        $sqls = "select count(1) as sumsid from ecm_goods where store_id=" . $store_id;
        $rows = $this->rdb->getone($sqls);
        $sql = "select * from ecm_goods where store_id=" . $store_id . " order by goods_id limit " . $offset . "," . $limit;
        $res = $this->rdb->query($sql);
        $arr = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            if ($row['default_image'] != '') {
                $row['default_image'] = 'http://www.meishisong.cn/' . $row['default_image'];
            }
            $row['goods_name'] = $this->deleteSpecialCharacters($row['goods_name']);
            $cate_idName = $this->getGoodsCate($row['goods_id']);
            if (empty($cate_idName)) {
                return false;
            }
            $row['cate_id'] = key($cate_idName);
            $row['cate_name'] = $cate_idName[$row['cate_id']];
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

    public function getUpdateGoodsList($limit, $offset, $appkey) {
        if ($limit == null || !is_int((int)$limit) || $limit > 15) {
            $limit = 15;
        }
        if ($offset == null || !is_int((int)$offset)) {
            $offset = 0;
        }
        if ($appkey == null || empty($appkey)) {
            return false;
        }
        $sqls = "select count(1) as sumsid from ecm_update_for_coop where belong='goods' and same_already not like '%" . $appkey . ",%'";
        $rows = $this->rdb->getone($sqls);
        $sql = "select update_id,belong,item_id from ecm_update_for_coop where belong='goods' AND same_already not like '%" . $appkey . ",%'  limit " . $offset . "," . $limit;
        $res = $this->rdb->query($sql);
        $arr = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $goods_info = $this->getGoodsDetail($row['item_id']);
            $goods_info['update_id'] = $row['update_id'];
            $arr[] = $goods_info;
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

    public function setGoodsUpdatedByPartner($appkey, $update_id) {
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

    /**
     * 通过店铺的id的查询所有的菜品数量
     */

    public function  allGoodsList($store_id) {

        $sql = "select * from ecm_goods where store_id={$store_id}";

        $row = $this->rdb->getAll($sql, array(), DB_FETCHMODE_ASSOC);

        return $row;
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

    public function getGoodsCate($goods_id) {
        if ($goods_id != null && is_int((int)$goods_id)) {
            $sql = "select cate_id from ecm_category_goods where goods_id=" . $goods_id . " limit 1";
            $cate_id = $this->rdb->getOne($sql);
            if (empty($cate_id)) {
                return false;
            }
            $sqlcn = "select cate_name from ecm_gcategory where cate_id=" . $cate_id;
            $cate_name = $this->rdb->getOne($sqlcn);
            if (empty($cate_name)) {
                return false;
            }
            return array($cate_id => $cate_name);
        }
    }
}
