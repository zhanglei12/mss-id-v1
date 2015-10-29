<?php

/**
 * App class loader class
 *
 * @author N.S.
 */
class AppClassLoader {
    /**
     * Add a class path
     *
     * @param string|array include path to add(single path or an array )
     */
    public static function addClassPath($path) {
        $includes = explode(PATH_SEPARATOR, get_include_path());
        if (!is_array($path)) {
            $path = array($path);
        }
        foreach ($path as $p) {
            if (!in_array($p, $includes)) {
                array_unshift($includes, $p);
            }
        }
        @set_include_path(implode(PATH_SEPARATOR, $includes));
    }

    /**
     * Check the class or object has implemented the interface
     *
     * @param mixed $obj_or_class
     * @param string $interface
     *
     * @return boolean
     */
    public static function is_implements($obj_or_class, $interface) {
        return class_exists($obj_or_class) && in_array($interface, class_implements($obj_or_class));
    }

    /**
     * Load given php class
     *
     * @param string $clazz class to load.
     */
    public static function loadClass($clazz) {
        if (empty($clazz) || class_exists($clazz, false) || interface_exists($clazz, false)) {
            return;
        }
        $full_path = str_replace('_', '/', $clazz) . '.php';
        @include($full_path);
    }

    public static function load_files_from_dir($files, $prefix_dir = null, $add_extension = '.php') {
        ini_set("display_errors", "On");
        if (empty($files)) {
            return;
        }
        if (!is_array($files)) {
            $files = array($files);
        }
        foreach ($files as $file_path) {
            $full_path = empty($prefix_dir) ? $file_path : $prefix_dir . '/' . $file_path;
            if ($add_extension) {
                $full_path .= $add_extension;
            }
            include($full_path);
        }
    }

    /**
     * Load conf file from APP_CONF_DIR
     *
     * @param $files array config path to load. file name, don't include .php extension
     */
    public static function loadConfigs($files) {
        self::load_files_from_dir($files, APP_CONF_DIR);
    }

    public static function loadVendorFile($files) {
        self::load_files_from_dir($files, LIB_VENDOR_DIR);
    }

    public static function loadLibraryFile($files) {
        self::load_files_from_dir($files, CONF_PATH_LIBRARY);
    }

    public static function loadPEAR($files) {
        self::load_files_from_dir($files, LIB_PEAR_DIR);
    }

    /**
     * Load files from class search paths
     *
     * @param $files
     */
    public static function loadAll($files) {
        self::load_files_from_dir($files);
    }
}

/**
 * Load php file
 *
 * @param $files
 *
 * @deprecated
 */
function yaf_load($files) {
    if (!is_array($files)) {
        $files = array($files);
    }
    try {
        foreach ($files as $value) {
            if (file_exists($value)) {
                require_once($value);
            }
            else {
                throw new Exception('file is not exists');
            }
        }
    } catch (Exception $exception) {
        die(json_encode(array('state' => -10, 'message' => '配置文件或库文件加载异常', data => $exception->getMessage())));
    }
}

/**
 * 格式化输出数据
 *
 * @param any
 *
 * @return void
 * @deprecated
 */
function dump($arr) {
    try {
        echo '<pre>';
        array_walk(func_get_args(), create_function('&$item, $key', 'print_r($item);'));
        echo '</pre>';
        exit();
    } catch (Exception $exception) {
        die(json_encode(array('state' => -11, 'message' => '自定义方法有误', data => $exception->getMessage())));
    }
}
