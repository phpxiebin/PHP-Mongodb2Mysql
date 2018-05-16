<?php

/**
 * MONGODB连接类
 * User: phpxiebin
 * Date: 16/4/16
 * Time: 下午4:55
 */
class MY_MONGODB
{
    //mongodb连接对象
    static private $_mongodb = null;

    /**
     * 构造方法
     * MY_MONGODB constructor.
     * @param $host
     * @param $port
     */
    public function __construct($host, $port)
    {
        try {
            $this->mongodb = new MongoClient($host . ":" . $port);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 获取mongodb单例对象
     * @param $host
     * @param $port
     * @return MY_MONGODB|null
     */
    static public function getMongodb($host, $port)
    {
        if (!(self::$_mongodb instanceof self)) {
            self::$_mongodb = new self($host, $port);
        }
        return self::$_mongodb;
    }

    /**
     * 防止克隆
     *
     */
    private function __clone()
    {
    }

    /**
     * 获取集合对象
     * @return MongoDB
     */
    public function getCollection()
    {
        return $this->mongodb->dai;
    }

    /**
     * 销毁数据库链接
     */
    public function destruct()
    {
        return $this->mongodb->close();
    }
}