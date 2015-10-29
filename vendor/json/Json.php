<?php

/**
 * json格式转换
 *
 * @param  array    data        要转换的数组
 *
 * @return json
 */
function json_code($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = json_code($value);
        }
        return json_encode($data);
        // return $data;
    }
    else {
        if ($data === null || $data === '') {
            $data = '';
            if (is_int($data)) {
                $data = 0;
            }
        }
        return $data;
    }
}

/**
 * 过滤为空的字段
 *
 * @param  array    data        要转换的数组
 *
 * @return array
 */
function perk_data($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = json_code($value);
        }
        return $data;
    }
    else {
        if ($data === null || $data === '') {
            $data = '';
            if (is_int($data)) {
                $data = 0;
            }
        }
        return $data;
    }
}
