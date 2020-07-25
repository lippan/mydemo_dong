<?php
/**
 * Created by PhpStorm.
 * User: VolitationXiaoXing
 * Date: 2018/11/14
 * Time: 上午11:42
 */

namespace App\Helpers;
/**
 * Singleton类
 */
trait Singleton{

    protected static $_instance;

    /**
     * 通过延迟加载（用到时才加载）获取实例
     * @return self
     */
    final public static function getInstance() {
        if(!isset(self::$_instance)) {
            self::$_instance = new static();
        }

        return self::$_instance;
    }

    /**
     * 构造函数私有，不允许在外部实例化
     *
     */
    private function __construct()
    {
    }

    /**
     * 防止对象实例被克隆
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * 防止被反序列化
     *
     * @return void
     */
    private function __wakeup()
    {
    }

}