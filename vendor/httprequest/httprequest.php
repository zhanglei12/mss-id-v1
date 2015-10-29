<?php

class PostMethod {
    /**
     * Socket版本
     * 使用方法：
     * $post_string = "app=socket&version=beta";
     * request_by_socket('facebook.cn','/restServer.php',$post_string);
     */

    function request_by_socket($remote_server, $remote_path, $post_string, $port = 80, $timeout = 30) {
        $socket = fsockopen($remote_server, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            die("$errstr($errno)");
        }
        fwrite($socket, "POST $remote_path HTTP/1.0\r\n");
        fwrite($socket, "User-Agent: Socket Example\r\n");
        fwrite($socket, "HOST: $remote_server\r\n");
        fwrite($socket, "Content-type: application/x-www-form-urlencoded\r\n");
        fwrite($socket, "Content-length: " . (strlen($post_string) + 8) . '\r\n');
        fwrite($socket, "Accept:*/*\r\n");
        fwrite($socket, "\r\n");
        fwrite($socket, "mypost=$post_string\r\n");
        fwrite($socket, "\r\n");
        $header = "";
        while ($str = trim(fgets($socket, 4096))) {
            $header .= $str;
        }
        $data = "";
        while (!feof($socket)) {
            $data .= fgets($socket, 4096);
        }
        return $data;
    }

    /**
     * Curl版本
     * 使用方法：
     * $post_string = "app=request&version=beta";
     * request_by_curl('http://facebook.cn/restServer.php',$post_string);
     */
    function request_by_curl($remote_server, $post_string) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remote_server);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        } catch (Exception $e) {
            print_r($e);
            throw new Exception("Error Net", API_ERR_NET);
        }
    }

    function request_by_curl_get($remote_server, $post_str) {
        $ch = curl_init($remote_server . '?' . $post_str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回  
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回  
        $output = curl_exec($ch);
        return $output;
    }

    /**
     * 其它版本
     * 使用方法：
     * $post_string = "app=request&version=beta";
     * request_by_other('http://facebook.cn/restServer.php',$post_string);
     */
    function request_by_other($remote_server, $post_string) {
        $context = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded' .
                    '\r\n' . 'User-Agent : Jimmy\'s POST Example beta' .
                    '\r\n' . 'Content-length:' . strlen($post_string) + 8,
                'content' => 'mypost=' . $post_string)
        );
        $stream_context = stream_context_create($context);
        $data = file_get_contents($remote_server, false, $stream_context);
        return $data;
    }
}
