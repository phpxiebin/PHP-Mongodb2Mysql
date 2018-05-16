<?php
/**
 * 积分相关检测数据类
 * User: xiebin
 * Date: 16/4/18
 * Time: 下午4:29
 */
class Points extends Base
{
    /**
     * 检测用户积分明细统计值和账户积分值是否一致
     * @return string
     */
    public function run()
    {
        try {
            //获取所有用户信息
            $customers = $this->pdo->getAll("SELECT id,modified_user FROM e_customer");
            $str = '';
            foreach ($customers as $item) {
                //获取积分明细总值
                $total_point = $this->pdo->getColumn("select sum(`total_point`) as total_point from `prop_point_exchange` where `customer_id` = :customer_id", array(":customer_id" => $item['id']));
                //获取积分账户值
                $account = $this->pdo->getOne("select * from `prop_point_account` where `customer_id` = :customer_id", array(":customer_id" => $item['id']));
                $account_point = $account['total_point'] - $account['available_point'];

                if ($account_point != $total_point) {
                    $str .= "老用户ID:" . $item['modified_user'] . "\t新用户ID:" . $item['id'] . "\t明细表总兑换积分:" . intval($total_point) . "\t积分账户表兑换积分:" . $account_point . PHP_EOL;
                }
            }
            return $str;
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}