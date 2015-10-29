<?php

class _QH_Base {
    /**
     * 函数joinKeyValue,把传入的数组重组为以原数组键名和键值分别以逗号相隔为值的新数组.
     *
     * @param array    arr    一个数组
     *
     * @return array
     */
    public function joinKeyValue($arr) {
        $keys = '';
        $values = '';
        $newArr = array();
        foreach ($arr as $k => $v) {
            $keys .= '`' . $k . '`,';
            $values .= "'" . addslashes($v === null ? '' : $v) . "',";
        }
        $newArr = array('keys' => trim($keys, ','),
            'vals' => trim($values, ','));
        return $newArr;
    }

    /**
     * 函数insertData2DB,为一个数据库表插入一条数据.
     *
     * @param str        table    要插入数据的表名
     * @param array        data    要插入的数据，为一个一维数组，键名为表的字段名，键值为表相应字段的值
     * @param object    api_db    数据库连接资源
     * @param bool        id        为真表示需要返回插入数据的主键ID，为假不返回，默认不返回
     *
     * @return array
     */
    public function insertData2DB($table, $data, $api_db, $id = false) {
        if (empty($table) || empty($data)) {
            return;
        }
        $arrss = $this->joinKeyValue($data);
        $sqli = "insert into " . $table . " (" . $arrss['keys'] . ") values (" . $arrss['vals'] . ")";
        $resi = $api_db->query($sqli);
        if (DB::isError($resi)) {
            throw new Exception("Error DB", API_ERR_DB);
        }
        if ($id) {
            $resid = $api_db->getOne("select last_insert_id()");
            if (DB::isError($resid)) {
                throw new Exception("Error DB", API_ERR_DB);
            }
            return $resid;
        }
    }

    /**
     * 返回json格式的数据
     *
     * @param  Unknow $data 需要格式化成json的数据
     *
     * @return json
     */
    public function returnJson($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
        elseif (is_string($data)) {
            return $data;
        }
    }

    /**
     * 删除一个数组中指定元素
     *
     * @param  array $array 一个数组
     * @param  string $key 一个key
     *
     * @return array
     */
    public function deleteArrayByKey(&$array, $key) {
        if (!is_array($key)) {
            $key = array($key);
        }
        foreach ($key as $k) {
            unset($array[$k]);
        }
        $array = array_values($array);
    }

    /**
     * 删除特殊符号
     *
     * @param  string $string 待清理的字符串
     *
     * @return string         清理完的字符串
     */
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
}
