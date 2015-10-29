<?php

/**
 * 简单图片处理类
 * 系统需要安装imagemagick软件包
 *
 * @author ql
 */
class SimpleImage {
    /**
     * imagemagick命令路径,默认不需要修改
     */
    public $ImageMagickPath = IMAGE_MAGICk_PATH;

    public function __construct() {
    }

    /**
     * 缩放图片
     *
     * @param string $src 原始图片路径
     * @param string $dest 图片存放路径
     * @param int $length 生成图片长边边长
     *
     * @return int 1 操作成功
     *           -1 不是图片文件
     *               -2  图片长宽小于指定长边边长
     */
    public function resize($src, $dest, $length) {
        $info = getimagesize($src);
        if ($info == false) {
            return -1;
        }

        if ($info[0] < $length && $info[1] < $length) {
            return -2;
        }

        $bin = '';
        if ($info['0'] >= $info['1']) {
            $bin = "convert -coalesce -resize '{$length}x' {$src} {$dest}";
        } else {
            $bin = "convert -coalesce -resize 'x{$length}' {$src} {$dest}";
        }

        exec($this->ImageMagickPath . '/' . $bin);

        return 1;
    }


}

