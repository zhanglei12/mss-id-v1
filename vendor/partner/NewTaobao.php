<?php
define("PUBLIC_PATH", realpath(dirname(dirname(__FILE__))));
require_once(PUBLIC_PATH . '/partner/partner.php');

class NewTaobao extends Partner {
    var $tdd;
    var $sessionKey;
    var $log;
    var $partner_id;
    var $_httprequest;

    public function __construct($arr) {
        $file = array(
            PUBLIC_PATH . '/newtaobao/TopSdk.php'
        );
        yaf_load($file);
        $data = json_decode(file_get_contents("/data/bin/sessionKey/sessionKey.json", true));
        $session_key = $data->top_session;
        $this->partner_id = 100027;
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
        return json_encode(array("state" => "true"));
        // $req = new TradeWaimaiConfirmRequest;
        // $req->setOrderId($arr['partner_order_id']);
        // $resp = $this->tdd->execute($req,$this->sessionKey);
        // if (isset($resp->ret_code)&&$resp->ret_code==1)
        // {
        // $log_info = ' TaoDianDian orderConfirm '.$arr['partner_order_id'].' succeed';
        // $result = array('state'=>1,'message'=>'确认成功','data'=>'');
        // }
        // else
        // {
        // $log_info = ' TaoDianDian orderConfirm '.$arr['partner_order_id'].' failed '.serialize($arr).' '.serialize($resp);
        // $result = array('state'=>0,'message'=>'确认失败','data'=>'');
        // }
        // $this->log->info($log_info);
        // return $result;
    }

    /**
     * 订单已取消
     *
     * @param  array $arr 订单信息
     *
     * @return array
     */
    public function orderCanceled($arr) {
        return json_encode(array("state" => "true"));
        // $req = new TradeWaimaiRefuseRequest;
        // $req->setOrderId($arr['partner_order_id']);
        // $req->setReason($arr['reason']);
        // $resp = $this->tdd->execute($req,$this->sessionKey);
        // if (isset($resp->ret_code)&&$resp->ret_code==1)
        // {
        // $log_info = ' TaoDianDian orderRefuse '.$arr['partner_order_id'].' succeed';
        // $result = array('state'=>1,'message'=>'拒绝成功','data'=>'');
        // }
        // else
        // {
        // $log_info = ' TaoDianDian orderRefuse '.$arr['partner_order_id'].' failed '.serialize($arr).' '.serialize($resp);
        // $result = array('state'=>0,'message'=>'拒绝失败','data'=>'');
        // }
        // $this->log->info($log_info);
        // return $result;
    }

    /**
     * 订单已完成
     *
     * @param  array $arr 订单信息
     *
     * @return array
     */
    public function orderComplete($arr) {
        return json_encode(array("state" => "true"));
        // $req = new TradeWaimaiRefuseRequest;
        // $req->setOrderId($arr['partner_order_id']);
        // $req->setReason($arr['reason']);
        // $resp = $this->tdd->execute($req,$this->sessionKey);
        // if (isset($resp->ret_code)&&$resp->ret_code==1)
        // {
        // $log_info = ' TaoDianDian orderRefuse '.$arr['partner_order_id'].' succeed';
        // $result = array('state'=>1,'message'=>'拒绝成功','data'=>'');
        // }
        // else
        // {
        // $log_info = ' TaoDianDian orderRefuse '.$arr['partner_order_id'].' failed '.serialize($arr).' '.serialize($resp);
        // $result = array('state'=>0,'message'=>'拒绝失败','data'=>'');
        // }
        // $this->log->info($log_info);
        // return $result;
    }

    /**
     * 获取淘宝外卖获取单个订单 (taobao.waimai.order.single.get 淘宝外卖获取单个订单)
     *
     * @author  hezhuang
     * @time    2014/12/15
     * @return    array
     */
    public function getOrderDetail($order_id) {
        $req = new WaimaiOrderSingleGetRequest;
        $req->setOrderId($order_id);
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
    }

    /**
     * 获取淘点点订单列表 (taobao.waimai.agent.orderlist.get 代送商批量查询订单)
     *
     * @author  hezhuang
     * @time    2014/11/04
     * @return    array
     */
    /* public function getOrderList($data)
    {
        $req = new WaimaiAgentOrderlistGetRequest;
        // $req->setShopId($store_id);
        $req->setStartTime("2014-12-01 00:00:00");
        // $req->setStartTime($data['startTime']);
        $req->setEndTime($data['endTime']);
        $req->setPageNo($data['pageNo']);
        $req->setPageSize($data['pageSize']);
        // $req->setOrderStatus("1");
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
        // if (!empty($resp))
        // {
            // return $resp->result->list->top_delivery_agent_order_v_o;
        // }
    } */

    /**
     * 获取淘点点订单列表 (taobao.waimai.agent.orderlist.get 代送商批量查询订单)
     *
     * @author  hezhuang
     * @time    2014/11/04
     * @return    array
     */
    public function getAllOrderList($data) {
        $this->log->info('getAllOrderList start');
        $req = new WaimaiAgentOrderlistGetRequest;
        if ($data['shopId'] != '') {
            $req->setShopId($data['shopId']);
        }
        $req->setStartTime($data['startTime']);
        $req->setEndTime($data['endTime']);
        $req->setPageNo($data['pageNo']);
        $req->setPageSize($data['pageSize']);
        if ($data['orderStatus'] != '') {
            $req->setOrderStatus($data['orderStatus']);
        }
        $resp = $this->tdd->execute($req, $this->sessionKey);
        return $resp;
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

    public function formatOrderData($orderData) {
        $time = time();
        $notify_url = 'http://gw.api.taobao.com/router/rest';
        $allOrder = array();
        foreach ($orderData as $order) {
            if ($order->order_type == 8) {
                $order_type = '货到付款';
                $if_pay = 0;
            } else {
                $order_type = '在线支付';
                $if_pay = 1;
            }
            $allOrder[$order->id] = array(
                'partner_id' => strval($this->partner_id),
                'sessionkey' => $this->sessionKey,
                'partner_order_id' => strval($order->id),
                'invoice' => "",
                'if_pay' => $if_pay,
                'order_plat' => "true",
                'push_time' => strval($time),
                'notify_url' => urlencode($notify_url),
                'total_price' => $order->total_pay,
                'add_time' => strtotime($order->create_time),
                'request_time' => strtotime($order->end_deliverytime),
                'remark' => urlencode($order->note),
                'shipping_fee' => $order->delivery_pay,
                'city' => urlencode('北京'),
                'payment_name' => urlencode($order_type),
                'shipping_name' => urlencode('定时送达'),
                'order_placed' => "true",
                'custom_info' => array(
                    'buyer_id' => strval($order->user_id),
                    'buyer_name' => urlencode($order->user_address->name),
                    'consignee' => urlencode($order->user_address->name),
                    'phone_mob' => strval($order->user_address->mobile),
                    'phone_tel' => strval($order->user_address->mobile),
                    'address' => urlencode($order->user_address->address)
                ),
            );
            foreach ($order->goods_list->order_goods as $goods) {
                $allOrder[$order->id]['order_items']['order_goods'][] = array(
                    'goods_id' => strval($goods->id),
                    'goods_name' => urlencode($goods->name),
                    'price' => $goods->real_price,
                    'quantity' => $goods->count,
                    'specification' => urlencode('份'),
                    'goods_remark' => '',
                    'garnish' => array()
                );
            }
            $allOrder[$order->id]['order_items']['store_info'] = array(
                'seller_id' => strval($order->store_id),
                'seller_name' => urlencode($order->store_name),
                'address' => urlencode($order->address),
                'tel' => $order->store_contactphone
            );
            $allOrder[$order->id]['sign'] = md5('partner_id=' . strval($this->partner_id) . '#partner_order_id=' . strval($order->id) . '#push_time=' . $time . '#notify_url=' . $notify_url . '#key=8a92292167253b1646bbc4faa4bd3f46');
        }
        return $allOrder;
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

    public function putStoreInSoa() {
        $arrs = $this->getAllStore();
        if (!$arrs) {
            echo 'no data';
            die;
        }
        $arro = array();
        foreach ($arrs as $store) {
            echo $store->shopid;
            $arro[$store->shopid] = array(
                'store_name' => urlencode($store->name),
                'itemId' => $store->shopid,
                'address' => urlencode($store->address),
                'session' => $this->sessionKey,
                'tel' => $store->phone,
                'appKey' => '21726328',
                'secretkey' => 'e90c3d29af1192ee323044d3ade61372'
            );
        }
        return $arro;
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
