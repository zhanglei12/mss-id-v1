<?php

class Base {
    var $tdd;
    var $sessionKey;
    var $log;
    var $partner_id;
    var $_httprequest;

    public function setTdd($arr) {
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', '1');
        $file = array(
            '/data/web/public/library/taobao/TopSdk.php'
        );
        yaf_load($file);
        $this->partner_id = 100012;
        $this->tdd = new TopClient;
        $this->tdd->format = 'json';
        $this->tdd->appkey = 21726328;
        $this->tdd->secretKey = 'e90c3d29af1192ee323044d3ade61372';
        $this->sessionKey = '6102400819d17deb3ba1c27044c726ded25fa532dc34cd01898649869';
        //$this->sessionKey = '6100e090e20b34f76102e48ba08f48868cb2ab947a0090f2027317997';
        $this->log = $arr['log'];
    }

    public function changeUser($arr) {
        if (!isset($arr['appkey']) || empty($arr['appkey'])) {
            return array('state' => -201, 'message' => '没有appkey', 'data' => '');
        }
        if (!isset($arr['secretKey']) || empty($arr['secretKey'])) {
            return array('state' => -202, 'message' => '没有secretKey', 'data' => '');
        }
        if (!isset($arr['sessionKey']) || empty($arr['sessionKey'])) {
            return array('state' => -203, 'message' => '没有sessionKey', 'data' => '');
        }
        $this->tdd->appkey = $arr['appkey'];
        $this->tdd->secretKey = $arr['secretKey'];
        $this->sessionKey = $arr['sessionKey'];
    }

    public function signature() {

    }

    /**
     * 订单已确认
     *
     * @param  array $arr 订单信息
     *
     * @return array
     */
    public function orderConfirmed($arr) {
        $req = new TradeWaimaiConfirmRequest;
        $req->setOrderId($arr['partner_order_id']);
        $resp = $this->tdd->execute($req, $this->sessionKey);
        if (isset($resp->ret_code) && $resp->ret_code == 1) {
            $log_info = ' TaoDianDian orderConfirm ' . $arr['partner_order_id'] . ' succeed';
            $result = array('state' => 1, 'message' => '确认成功', 'data' => '');
        } else {
            $log_info = ' TaoDianDian orderConfirm ' . $arr['partner_order_id'] . ' failed ' . serialize($arr) . ' ' . serialize($resp);
            $result = array('state' => 0, 'message' => '确认失败', 'data' => '');
        }
        $this->log->info($log_info);
        return $result;
    }

    /**
     * 订单已取消
     *
     * @param  array $arr 订单信息
     *
     * @return array
     */
    public function orderCanceled($arr) {
        $req = new TradeWaimaiRefuseRequest;
        $req->setOrderId($arr['partner_order_id']);
        $req->setReason($arr['reason']);
        $resp = $this->tdd->execute($req, $this->sessionKey);
        if (isset($resp->ret_code) && $resp->ret_code == 1) {
            $log_info = ' TaoDianDian orderRefuse ' . $arr['partner_order_id'] . ' succeed';
            $result = array('state' => 1, 'message' => '拒绝成功', 'data' => '');
        } else {
            $log_info = ' TaoDianDian orderRefuse ' . $arr['partner_order_id'] . ' failed ' . serialize($arr) . ' ' . serialize($resp);
            $result = array('state' => 0, 'message' => '拒绝失败', 'data' => '');
        }
        $this->log->info($log_info);
        return $result;
    }

    public function getOrder($store_id) {
        if (empty($store_id)) {
            return array('state' => -204, 'message' => '没有商店ID', 'data' => '');
        }
        $req = new TradeWaimaiGetRequest;
        $req->setMaxSize(20);
        $req->setStoreId($store_id);
        $req->setIsAll("true");
        $resp = $this->tdd->execute($req, $this->sessionKey);
        if (!empty($resp)) {
            return $resp->result->result_list->takeout_third_order;
        }
    }

    /* 	public function getAllOrderByStore()
        {
            $arrs = $this->getAllStore();
            $arro = array();
            foreach ($arrs as $store)
            {
                $rowo = $this->getOrder($store->shopid);
                foreach ($rowo as $order)
                {
                    $arro[] = $order;
                }
            }
            $this->log->info('getAllOrder end');
            return $arro;
        } */
    public function getAllOrder() {
        $arro = $this->getOrder('123');
        $this->log->info('getAllOrder end');
        return $arro;
    }

    public function getAllStore() {
        $req = new WaimaiShopListRequest;
        $req->setPageSize(15);
        $arr = array();
        for ($i = 1; ; $i++) {
            $req->setPage($i);
            $resp = $this->tdd->execute($req, $this->sessionKey);
            foreach ($resp->result->takeout_summary_infos->takeout_shop_summary_info as $store) {
                $arr[] = $store;
            }
            if ($i == $resp->result->total_page) {
                break;
            }
        }
        return $arr;
    }

    public function getGoods($store_id) {
        $req = new WaimaiItemlistGetRequest;
        $req->setShopid($store_id);
        $req->setSalesStatus(1);
        $req->setPageNo(1);
        $req->setPageSize(20);
        $req->setFields("itemid,title,price");
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
    }

    function updateStore($storeInfo) {
        $req = new WaimaiShopUpdateRequest;
        $req->setName($storeInfo['store_name']);
        $req->setAddress($storeInfo['address']);
        $req->setPhone($storeInfo['tel']);
        $req->setPosx(ceil($storeInfo['longitude'] * 100000));
        $req->setPosy(ceil($storeInfo['latitude'] * 100000));
        $req->setShopid($storeInfo['shopId']);
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
    }

    function addGoods($infos) {
        $req = new WaimaiItemAddRequest;
        $req->setTitle($infos['goodsname']);
        $req->setPrice($infos['price']);
        $req->setQuantity(10000);
        $req->setPicurl($infos['tpimgdir']);
        $req->setAuctionstatus(0);
        $req->setCategoryid($infos['cate']);
        $req->setAuctiondesc($infos['description']);
        $req->setShopids($infos['shopid']);
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
    }

    function updataGoods($goodsInfo) {
        $req = new WaimaiItemUpdateRequest;
        $req->setTitle($goodsInfo['goods_name']);
        $req->setPrice($goodsInfo['price']);
        $req->setOriprice($goodsInfo['price']);
        $req->setQuantity(999);
        $req->setPicurl($goodsInfo['pic_url']);
        $if_show = $goodsInfo['if_show'] == 1 ? 0 : -2;
        $req->setAuctionstatus($if_show);
        $req->setCategoryid($goodsInfo['category']);
        $summary = $goodsInfo['summary'] ? $goodsInfo['summary'] : '可口美味的';
        $req->setAuctiondesc($summary);
        $req->setInShopId($goodsInfo['shopId']);
        $req->setItemId($goodsInfo['item_id']);
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
    }

    function getGoodsInfo($itemId) {
        $req = new WaimaiItemGetRequest;
        $req->setItemId($itemId);
        $req->setFields("pic_url,category_id");
        $resp = $this->tdd->execute($req);
        return $resp;
    }

    /* 淘宝店铺的开启与关闭 */
    function storeIfopen($item, $type) {
        $req = $type == 'Y' ? new WaimaiShopOpenRequest : ($type == 'N' ? new WaimaiShopCloseRequest : null);
        if ($req) {
            $req->setShopid($item);
            $resp = $this->tdd->execute($req, $this->sessionKey);
            return $resp;
        }
        return false;
    }
}