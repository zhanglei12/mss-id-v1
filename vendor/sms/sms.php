<?php
class _QH_Sms {
    var $Uri;
    var $Username;
    var $Password;
    var $Epid;
    var $WebEncoding;
    var $Encoding;
    var $url;

    public function send($phone, $message, $linkid = '') {
        $url = $this->Uri . '/?';
        // $message = '【美食送】'.$message;

        $count = substr_count($message, "【嗷嗷】");
        if ($count) {
            $result = str_replace("【美食送】", "", $message);
            $message = $result;
        }

        $param = array
        (
            'username' => $this->Username,
            'password' => $this->Password,
            'phone' => $phone,
            'message' => iconv($this->WebEncoding, $this->Encoding, $message),
            'epid' => $this->Epid,
            'linkid' => $linkid,
        );
        $http = $url . http_build_query($param);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $http);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $status = curl_exec($ch);
        curl_close($ch);
        return $status;
    }

    public function code($mobile, $msg) {
        $auth = MD5("qhwlqhwl555");
        $msg = iconv("UTF-8", "GBk", $msg);
        $url = "http://210.5.158.31/hy/?uid=80436&auth=" . $auth . "&mobile=" . $mobile . "&msg=" . $msg . "&expid=0";
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请据
        curl_close($curl);
        return $data;
    }
}