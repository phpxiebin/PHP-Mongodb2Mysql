<?php

/**
 * 同步积分相关表
 * User: xiebin
 * Date: 16/4/18
 * Time: 下午2:10
 */
class Points extends Base
{
    /**
     * 按用户同步积分兑换信息
     * @param $old_id 老用户ID
     * @param $new_id 新用户ID
     * @param $wall 区域名称
     */
    public function syncExchage($old_id, $new_id, $wall = '')
    {
        try {

            $where = array('exchangeStatus' => "1", "userID" => $old_id);
            $count = $this->collection->integralExchageRecords->find($where)->count();
            $pageSize = 1000;
            $page = ceil($count / $pageSize);

            for ($i = 0; $i < $page; $i++) {
                //todo 查询mongodb数据
                $documents = $this->collection->integralExchageRecords->find($where)->sort(['_id' => 1])->limit($pageSize)->skip($i * $pageSize);
                $documents = (iterator_to_array($documents));

                //todo 循环同步插入数据到mysql
                foreach ($documents as $k => $v) {

                    //todo 临时生成订单号
                    $order_no = date("YmdHis") . str_pad(rand(0, str_repeat(9, 8)), 8, '0', STR_PAD_LEFT);

                    $exchange = array(
                        'exchange_type' => $v['exchangeType'],
                        'hy_order_id' => $v['merchantOrderId'],
                        'product_name' => $v['exchangeProName'],
                        'num' => $v['exchangeNum'],
                        'exchange_order_no' => $order_no,
                        'mobile' => $v['mobileNo'],
                        'total_point' => $v['integralNum'],
                        'face_value' => ceil($v['integralNum'] / $v['exchangeNum']),
                        'customer_id' => $new_id,
                        'order_status' => 2,
                        'channel_type' => $v['dataSource'],
                        'submit_time' => date("Y-m-d H:i:s", $v['exchangeTime']->sec),
                        'deal_time' => date("Y-m-d H:i:s", $v['exchangeTime']->sec),
                        'created_user' => "mongodb同步{$wall}",
                        'created_date' => date("Y-m-d H:i:s", $v['createTime']->sec),
                        'modified_date' => date("Y-m-d H:i:s", $v['updateTime']->sec),
                        'modified_user' => $k,
                        'is_delete' => 0
                    );
                    $this->pdo->prepareInsert("prop_point_exchange", $exchange);

                    $history = array(
                        'bussiness_type' => 5,
                        'consume_points' => $v['integralNum'],
                        'bussiness_code' => $order_no,
                        'customer_id' => $new_id,
                        'channel_type' => $v['dataSource'],
                        'remark' => $v['exDescription'],
                        'created_user' => "mongodb同步{$wall}",
                        'created_date' => date("Y-m-d H:i:s", $v['createTime']->sec),
                        'modified_date' => date("Y-m-d H:i:s", $v['updateTime']->sec),
                        'modified_user' => $k,
                        'is_delete' => 0
                    );
                    $this->pdo->prepareInsert("prop_point_history", $history);
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 按用户同步积分获取信息
     * @param $old_id 老用户ID
     * @param $new_id 新用户ID
     * @param $wall 区域名称
     */
    public function syncHonour($old_id, $new_id, $wall = '')
    {
        try {

            $where = array("userID" => $old_id);
            $count = $this->collection->honourRecords->find($where)->count();
            $pageSize = 1000;
            $page = ceil($count / $pageSize);

            for ($i = 0; $i < $page; $i++) {
                //todo 查询mongodb数据
                $documents = $this->collection->honourRecords->find($where)->sort(['_id' => 1])->limit($pageSize)->skip($i * $pageSize);
                $documents = (iterator_to_array($documents));

                //todo 循环同步插入数据到mysql
                foreach ($documents as $k => $v) {
                    $history = array(
                        'bussiness_type' => 4,
                        'bussiness_code' => $v['policyNo'],
                        'awarded_points' => $v['extralIntegral'] + $v['integral'],
                        'customer_id' => $new_id,
                        'channel_type' => $v['dataSource'],
                        'experience' => $v['extralExperience'] + $v['experience'],
                        'remark' => $v['taskDescription'],
                        'created_user' => "mongodb同步{$wall}",
                        'created_date' => date("Y-m-d H:i:s", $v['createTime']->sec),
                        'modified_date' => date("Y-m-d H:i:s", $v['updateTime']->sec),
                        'modified_user' => $k,
                        'is_delete' => 0,
                    );
                    $this->pdo->prepareInsert("prop_point_history", $history);
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}


