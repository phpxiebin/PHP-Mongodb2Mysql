<?php

/**
 * 同步用户相关表
 * User: xiebin
 * Date: 16/4/11
 * Time: 上午9:52
 */
require WWW_ROOT . '/controller/points.php';
require WWW_ROOT . '/controller/order.php';

class Customer extends Base
{
    /**
     * 根据区域用户同步用户相关,积分,保单信息
     * @param string $wall 区域名称
     * @return string
     */
    function run($wall = "")
    {
        try {

            $points = new Points();
            $order = new Order();
            $wall = $wall;
            $where = empty($wall) || $wall == "全部" ? array("name"=>"13227444333") : array('wall' => $wall);
            $count = $this->collection->users->find($where)->count();
            $pageSize = PAGE_SIZE;
            $page = 1;//ceil($count / $pageSize);

            $str = '';
            for ($i = 0; $i < $page; $i++) {
                //todo 查询mongodb数据
                $list = $this->collection->users->find($where)->sort(['_id' => 1])->limit($pageSize)->skip($i * $pageSize);

                //总条数/成功条数/错误条数/重复条数/状态
                $times = $ok = $error = $repeat = $status = 0;
                $sTime = microtime(true);
                //todo 循环同步插入数据到mysql
                foreach ($list as $k => $v) {

                    $times++;

                    //todo 验证用户纪录是否存在
                    $id = $this->pdo->getColumn("SELECT id FROM sync_log WHERE old_id = :old_id LIMIT 1", array(':old_id' => $k));
                    if (!empty($id)) {
                        $repeat++;
                        continue;
                    }

                    if ($v['userInfo']) {
                        $item = $this->collection->userInfo->findOne(array("_id" => new MongoId("{$v['userInfo']}")));
                        $cardInfo = Help::getInfoByCardId($item['cardNo']);
                        $userinfo = array(
                            'real_name' => $item['realName'],
                            'sex' => $cardInfo['sex'],
                            'birthday' => $cardInfo['birthday'],
                            'id_type' => empty($cardInfo['sex']) ? null : "01",
                            'exchange_pwd' => $item['payPassword'],
                            'nick_name' => $item['nickName'],
                            'id_no' => $item['cardNo'],
                            'email' => $item['email'],
                            'address' => $item['address'],
                            'agent_no' => $item['agentNo'],
                            'recommend_level' => $item['level'],
                            'channel_type' => $item['channel'],

                            'flag' => intval($item['flag']),
                            'sign_days' => $item['signDays'],
                            'honour_level' => $item['honourLevel'],
                            'user_level' => $item['userLevel'],
                            'if_change' => $item['ifChange'],
                        );

                        $point = array(
                            'total_point' => intval($item['totleIntegral']),
                            'available_point' => intval($item['validIntegral']),
                            'freeze_point' => 0,
                            'total_experience' => intval($item['totleExperience']),
                            'created_user' => "mongodb同步{$wall}",
                            'created_date' => date("Y-m-d H:i:s", $v['createdAt']->sec),
                            'modified_date' => date("Y-m-d H:i:s", $v['updatedAt']->sec),
                            'modified_user' => $k,
                            'is_delete' => 0,
                            'integral_status' => $item['integralStatus'],
                            'experience_status' => $item['experienceStatus'],
                        );
                    }

                    $referee_id = null;
                    if (!empty($v['refereeId'])) {
                        $referee_id = $this->pdo->getColumn("SELECT new_id FROM sync_log WHERE old_id = :old_id", array(':old_id' => $v['refereeId']));
                        $referee_id = (!empty($referee_id)) ? $referee_id : "未导入:" . $v['refereeId'];
                    }

                    $user = array(
                        'name' => $v['name'],
                        'password' => $v['password'],
                        'type' => $v['userType'],
                        'status' => $v['isValid'],
                        'city_code' => (strlen($v['city']) == 7) ? substr($v['city'], 1, strlen($v['city'])) : $v['city'],
//                        'province_name' => $v['wall'],
                        'mobile' => $v['mobile'],
                        'referee_id' => $referee_id,
                        'is_valid' => $v['isValid'],
                        'register_date' => date("Y-m-d H:i:s", $v['createdAt']->sec),
                        'created_user' => "mongodb同步{$wall}",
                        'created_date' => date("Y-m-d H:i:s", $v['createdAt']->sec),
                        'modified_date' => date("Y-m-d H:i:s", $v['updatedAt']->sec),
                        'modified_user' => $k,
                        'is_delete' => 0
                    );

                    $customerId = $this->pdo->prepareInsert("e_customer", array_merge($userinfo, $user));
                    if ($customerId) {
                        $status = 1;
                        $ok++;

                        //todo 用户推荐人关系表
                        $recommendedInfo = $this->collection->recommendedInfo->findOne(array("userID" => $k));
                        if (!empty($recommendedInfo)) {
                            $referee_id = $this->pdo->getColumn("SELECT new_id FROM sync_log WHERE old_id = :old_id", array(':old_id' => $recommendedInfo['refereeId']));
                            $referee_id = (!empty($referee_id)) ? $referee_id : -1;
                            $recommended = array(
                                'user_id' => $customerId,
                                'recommend_user_id' => $referee_id,
                                'recommend_user_mobile' => $recommendedInfo['name'],
                                'user_recommend_type' => 1,
                                'channel_type' => 1,
                                'created_date' => date("Y-m-d H:i:s", $recommendedInfo['createTime']->sec),
                                'created_user' => "mongodb同步{$wall}",
                                'modified_date' => date("Y-m-d H:i:s", $recommendedInfo['updateTime']->sec),
                                'modified_user' => $recommendedInfo['refereeId'],
                                'is_delete' => 0
                            );
                            $this->pdo->prepareInsert("prop_user_recommend", $recommended);
                        }

                        //todo 用户积分账户表
                        $point['customer_id'] = $customerId;
                        $this->pdo->prepareInsert("prop_point_account", $point);

                    } else {
                        $error++;
                    }

                    //记录日志
                    $info = array('old_id' => $k, 'new_id' => $customerId, 'table_name' => 'e_customer', 'status' => $status);
                    $this->pdo->prepareInsert("sync_log", $info);

                    //@todo 调用同步用户关联积分相关表
                    $points->syncExchage($k, $customerId, $wall);
                    $points->syncHonour($k, $customerId, $wall);

                    //@todo 调用同步用户关联保险订单,保单相关表
                    $order->syncOrder($k, $customerId, $v['name'], $wall);
                }
                $eTime = microtime(true);

                //todo 同步省份的CODE和城市的NAME
                //$this->pdo->exec("UPDATE `e_customer` SET `city_name` = (SELECT `code_label` FROM `e_code_area` WHERE code = city_code LIMIT 1),province_code = (SELECT `code` FROM `e_code_area` WHERE code_label = province_name LIMIT 1)");
                $str .= '同步`users`表第' . ($i + 1) . "页数据,总条数:" . $times . ",成功条数:" . $ok . ",失败条数:" . $error . ",重复纪录条数:" . $repeat . ",MYSQL执行耗时" . round($eTime - $sTime, 5) . PHP_EOL;
            }
            return $str;

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 同步所有的用户信息
     */
    function syncAll()
    {
        try {
            $count = $this->collection->users->find()->count();
            $pageSize = PAGE_SIZE;
            $page = ceil($count / $pageSize);

            $str = '';
            for ($i = 0; $i < $page; $i++) {
                //todo 查询mongodb数据
                $list = $this->collection->users->find()->sort(['createTime' => 1])->limit($pageSize)->skip($i * $pageSize);
                $list = (iterator_to_array($list));

                //总条数/成功条数/错误条数/重复条数
                $times = $ok = $error = $repeat = 0;
                $sTime = microtime(true);
                //todo 循环同步插入数据到mysql
                foreach ($list as $k => $v) {
                    $times++;

                    //todo 验证用户纪录是否存在
                    $id = $this->pdo->getColumn("SELECT id FROM e_customer WHERE name = :name LIMIT 1", array(':name' => $v['name']));
                    if (!empty($id)) {
                        $repeat++;
                        continue;
                    }

                    $user = array(
                        'name' => $v['name'],
                        'password' => $v['password'],
                        'city_code' => (strlen($v['city']) == 7) ? substr($v['city'], 1, strlen($v['city'])) : $v['city'],
                        'province_name' => $v['wall'],
                        'mobile' => $v['mobile'],
                        'is_valid' => $v['isValid'],
                        'register_date' => date("Y-m-d H:i:s", $v['createdAt']->sec),
                        'created_user' => "mongodb同步全部用户信息",
                        'created_date' => date("Y-m-d H:i:s", $v['createdAt']->sec),
                        'modified_date' => date("Y-m-d H:i:s", $v['updatedAt']->sec),
                        'modified_user' => $k,
                        'is_delete' => 0
                    );

                    $result = $this->pdo->prepareInsert("e_customer", $user);
                    if ($result) {
                        $ok++;
                    } else {
                        $error++;
                    }

                }

                $eTime = microtime(true);
                $str .= '同步`users`表第' . ($i + 1) . "页数据,总条数:" . $times . ",成功条数:" . $ok . ",失败条数:" . $error . ",重复纪录条数:" . $repeat . ",MYSQL执行耗时" . round($eTime - $sTime, 5) . PHP_EOL;
                return $str;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }


    }
}