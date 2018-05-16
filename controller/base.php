<?php

/**
 * 控制器基类
 * User: xiebin
 * Date: 16/4/20
 * Time: 下午2:22
 */
class Base
{
    var $pdo;
    var $mongodb;
    var $collection;

    function __construct()
    {
        $this->pdo = MY_PDO::getPdo(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DBNAME, MYSQL_CHARSET);
        $this->mongodb = MY_MONGODB::getMongodb(MONGODB_HOST, MONGODB_PORT);
        $this->collection = $this->mongodb->getCollection();

        //todo 清除表数据

//        $truncateTables = array(
//            'e_customer', 'prop_point_account', 'sync_log', 'prop_user_recommend',
//            'prop_point_exchange', 'prop_point_history',
//            'prop_order', 'prop_order_driver', 'prop_order_vehicle_model', 'prop_order_vehicle_jingyou', 'prop_order_vehicle', 'prop_order_delivery',
//            'prop_policy', 'prop_policy_appnt', 'prop_policy_insured', 'prop_policy_risk','prop_policy_engaged',
//        );
//        foreach ($truncateTables as $v) {
//            $this->pdo->exec("TRUNCATE TABLE {$v}");
//        }

    }
}