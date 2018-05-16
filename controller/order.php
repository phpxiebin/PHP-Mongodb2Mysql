<?php

/**
 * 同步订单,保单相关表
 * User: xiebin
 * Date: 16/4/18
 * Time: 下午2:10
 */
class Order extends Base
{

    /**
     * 按用户同步保险订单信息
     * @param $old_id 老用户ID
     * @param $new_id 新用户ID
     * @param $old_name 老用户名称
     * @param $wall 区域名称
     */
    function syncOrder($old_id, $new_id, $old_name, $wall = '')
    {
        try {
            //@todo 根据条件查询订单状态
            $where = array("payStatus" => "1", "proposalMain.underwriteInd" => "6", "salemanID" => $old_name);
            $result = $this->collection->proposals->find($where);
            $result = (iterator_to_array($result));

            foreach ($result as $k => $v) {

                //@todo 公共属性
                $public = array(
                    'created_user' => "mongodb同步{$wall}",
                    'created_date' => date("Y-m-d H:i:s", $v['createTime']->sec),
                    'modified_date' => date("Y-m-d H:i:s", $v['updateTime']->sec),
                    'modified_user' => $k,
                    'is_delete' => 0,
                );

                //@todo 生成订单号
                $order_no = date("YmdHis") . str_pad(rand(0, str_repeat(9, 4)), 4, '0', STR_PAD_LEFT);

                //@todo 订单表
                $order = array(
                    "order_no" => $order_no,
                    "product_code" => !empty($v["proposalMain"]["productCode"]) ? $v["proposalMain"]["productCode"] : "0001",
                    "customer_id" => $new_id,
                    "channel_type" => $v["dataSource"],
                    "order_status" => "07",
                    "order_amount" => $v["fee"]["sumPremium"],
                    "submit_date" => date("Y-m-d H:i:s", $v["proposalMain"]["acceptDate"]->sec),
                );
                $orderId = $this->pdo->prepareInsert("prop_order", array_merge($order, $public));
                if ($orderId) {
                    //@todo 订单指定驾驶人信息表
                    if (!empty($v["insuranceObjectInfo"]["agreeDrivers"]) || count($v["insuranceObjectInfo"]["agreeDrivers"]) != 0) {
                        foreach ($v["insuranceObjectInfo"]["agreeDrivers"] as $agreeDrivers => $agreeDriver) {
                            $prop_order_driver = array(
                                'order_no' => $order_no,
                                'name' => $agreeDriver["driverName"],
                                'sex' => $agreeDriver["sex"],
                                'birthday' => !empty($agreeDriver["driverBirthday"]) ? date("Y-m-d", $agreeDriver["driverBirthday"]->sec) : null,
                                'driving_license_no' => $agreeDriver["drivingLicenseNo"],
                                'age' => $agreeDriver["driverAge"],
                                'accept_license_date' => date("Y-m-d", $agreeDriver["acceptLicenseDate"]->sec),
                            );
                            $this->pdo->prepareInsert("prop_order_driver", array_merge($prop_order_driver, $public));
                        }
                    }
                    if (!empty($v["insuranceObjectInfo"]["fakeDrivers"]) || count($v["insuranceObjectInfo"]["fakeDrivers"]) != 0) {
                        foreach ($v["insuranceObjectInfo"]["fakeDrivers"] as $fakeDrivers => $fakeDriver) {
                            $prop_order_driver = array(
                                'order_no' => $order_no,
                                'name' => $fakeDriver["driverName"],
                                'sex' => $fakeDriver["sex"],
                                'birthday' => !empty($fakeDriver["driverBirthday"]) ? date("Y-m-d", $fakeDriver["driverBirthday"]->sec) : null,
                                'driving_license_no' => $fakeDriver["drivingLicenseNo"],
                                'age' => $fakeDriver["driverAge"],
                                'accept_license_date' => date("Y-m-d", $fakeDriver["acceptLicenseDate"]->sec),
                            );
                            $this->pdo->prepareInsert("prop_order_driver", array_merge($prop_order_driver, $public));
                        }
                    }

                    //@todo 订单车型信息
                    $prop_order_vehicle_model = array(
                        "order_no" => $order_no,
                        "brand_name" => $v["insuranceObjectInfo"]["modelName"],
                        "rb_code" => $v["insuranceObjectInfo"]["hyModelCode"],
                        "vehicle_alias" => $v["insuranceObjectInfo"]["familyName"],
                        "vehicle_brand" => $v["insuranceObjectInfo"]["brandName"],
                        "vehicle_style_desc" => $v["insuranceObjectInfo"]["modelDesc"],
                        "import_flag" => $v["insuranceObjectInfo"]["importFlag"],
                        "market_date" => $v["insuranceObjectInfo"]["marketDate"],
                        "seat_count" => $v["insuranceObjectInfo"]["seatCount"],
                        "vehicle_weight" => $v["insuranceObjectInfo"]["vehicleWeight"],
                        "vehicle_tonnage" => $v["insuranceObjectInfo"]["tonnage"],
                        "exhaust_capacity" => $v["commercialResInfo"]["carModel"]["exhaustScale"],
                        "transmission_type" => $v["commercialResInfo"]["carModel"]["derailleurType"],
                        "abs_flag" => $v["commercialResInfo"]["carModel"]["absFlag"],
                        "purchase_price" => $v["insuranceObjectInfo"]["purchasePrice"],
                        "air_bag_num" => $v["insuranceObjectInfo"]["airbagNum"],
                        "alarm_flag" => $v["insuranceObjectInfo"]["alarmFlag"],
                        "is_priced" => $v["insuranceObjectInfo"]['carModel']["isPriced"],
                        "risk_flag" => $v["insuranceObjectInfo"]["riskFlag"],
                        "vehicle_class" => $v["insuranceObjectInfo"]["vehicleClass"],
                        "ref_code1" => $v["insuranceObjectInfo"]["refCode1"],
                        "ref_code2" => $v["insuranceObjectInfo"]["refCode2"],
                        "actual_value" => $v["insuranceObjectInfo"]["actualValue"],
                        "vin_code" => $v["insuranceObjectInfo"]["vinCode"],
                        "stop_flag" => $v["insuranceObjectInfo"]["stopFlag"],
                        "company_code" => $v["insuranceObjectInfo"]["companyCode"],
                        "company_name" => $v["insuranceObjectInfo"]["companyName"],
                        "family_code" => $v["insuranceObjectInfo"]["familyCode"],
                        "purchase_price_tax" => $v["insuranceObjectInfo"]["purchasePriceTax"],
                        "kindred_price" => $v["insuranceObjectInfo"]["kindredPrice"],
                        "kindred_price_tax" => $v["insuranceObjectInfo"]["kindredPriceTax"],
                        "engine_desc" => $v["insuranceObjectInfo"]["engineDesc"],
                        "batholith" => $v["insuranceObjectInfo"]["batholith"],
                        "rate" => $v["insuranceObjectInfo"]["rate"],
                        "syx_class_id" => $v["insuranceObjectInfo"]["syxClassID"],
                        "syx_class_name" => $v["insuranceObjectInfo"]["syxClassName"],
                        "jqx_class_id" => $v["insuranceObjectInfo"]["jqxClassID"],
                        "jqx_class_name" => $v["insuranceObjectInfo"]["jqxClassName"],
                        "rate_veh_risk_repair" => $v["insuranceObjectInfo"]["rateVehRiskChange"],
                        "rate_specialize_repair" => $v["insuranceObjectInfo"]["rateSpecializeRepair"],
                        "complex_search" => $v["insuranceObjectInfo"]["complexSearch"],
                        "rate_exception" => $v["insuranceObjectInfo"]["rateException"],
                        "rate_veh_risk_change" => $v["insuranceObjectInfo"]["rateVehRiskRepair"],
                        "car_name" => $v["insuranceObjectInfo"]["carName"],
                        "hy_model_code" => $v["insuranceObjectInfo"]["hyModelCode"],
                        "notice_type" => $v["insuranceObjectInfo"]["noticeType"],
                        "ecdemic_vehicle_flag" => $v["insuranceObjectInfo"]["ecdemicFlag"],
                    );
                    $this->pdo->prepareInsert("prop_order_vehicle_model", array_merge($prop_order_vehicle_model, $public));

                    //@todo 订单精友库车型信息
                    $prop_order_vehicle_jingyou = array(
                        "order_no" => $order_no,
                        "vehicle_code" => $v["insuranceObjectInfo"]["carSequenceNo"],
                        "vehicle_name" => $v["insuranceObjectInfo"]["modelName"],
                        "brand_name" => $v["insuranceObjectInfo"]["brandName"],
                        "family_name" => $v["insuranceObjectInfo"]["familyName"],
                        "price" => $v["insuranceObjectInfo"]["purchasePriceTax"],
                    );
                    $this->pdo->prepareInsert("prop_order_vehicle_jingyou", array_merge($prop_order_vehicle_jingyou, $public));

                    //@todo 订单车辆信息
                    $prop_order_vehicle = array(
                        "order_no" => $order_no,
                        "license_no" => $v["insuranceObjectInfo"]["licenseNo"],
                        "engine_no" => $v["insuranceObjectInfo"]["engineNo"],
                        "frame_no" => $v["insuranceObjectInfo"]["frameNo"],
                        "brand_name" => $v["insuranceObjectInfo"]["modelName"],
                        "rb_code" => $v["insuranceObjectInfo"]["modeCode"],
                        "enroll_date" => date("Y-m-d", $v["insuranceObjectInfo"]["enrollDate"]->sec),
                        "ecdemic_flag" => $v["insuranceObjectInfo"]["ecdemicFlag"],
                        "transfer_flag" => $v["insuranceObjectInfo"]["transferFlag"],
                        "transfer_date" => date("Y-m-d", $v["insuranceObjectInfo"]["transferDate"]->sec),
                        "travel_area_flag" => $v["insuranceObjectInfo"]["areaName"],
                        "appoint_driver_flag" => $v["insuranceObjectInfo"]["agreeDriverFlag"],
                        "car_owner" => $v["insuranceObjectInfo"]["carOwner"],
                        "car_owner_id_type" => $v["insuranceObjectInfo"]["carOwnerIdentifyType"],
                        "car_owner_id_no" => $v["insuranceObjectInfo"]["carOwnerIdentifyNumber"],
                        "use_nature_code" => $v["insuranceObjectInfo"]["useNatureCode"],
                        "actual_value" => $v["insuranceObjectInfo"]["actualValue"],
                        "purchase_price" => $v["insuranceObjectInfo"]["purchasePriceTax"],
                        "car_type_alias" => $v["insuranceObjectInfo"]["standarName"],
                        "run_mile_rate" => $v["insuranceObjectInfo"]["standarName"],
                        "is_have_gps" => $v["insuranceObjectInfo"]["isHaveGps"],
                        "family_car_count" => $v["insuranceObjectInfo"]["familyCarCount"],
                        "whole_weight" => $v["whole_weight"]["completeKerbmass"],
                    );
                    $this->pdo->prepareInsert("prop_order_vehicle", array_merge($prop_order_vehicle, $public));

                    //@todo 订单配送表
                    if (!empty($v['delivery'])) {
                        $prop_order_delivery = array(
                            "order_no" => $order_no,
                            "name" => $v['delivery']['acceptName'],
                            "mobile" => $v['delivery']['acceptTelephone'],
                            "address" => $v['delivery']['acceptAddress'],
                            "invoice_title" => $v['delivery']['invoiceTitle'],
                            "delivery_type" => $v['delivery']['deliveryType'],
                            "appointment_time" => $v['delivery']['appointmentTime'],
                        );
                        $this->pdo->prepareInsert("prop_order_delivery", array_merge($prop_order_delivery, $public));
                    }

                    //@todo 保单相关信息表
                    $this->syncPolicy($old_id, $new_id, $wall, $v, $orderId, $order_no, $public);
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**按用户同步保险保单信息
     * @param $old_id 老用户ID
     * @param $new_id 新用户ID
     * @param string $wall 区域名称
     * @param array $result 保单结果
     * @param string $order_id 订单ID
     * @param string $order_no 订单号
     * @param array $public 公共属性
     */
    function syncPolicy($old_id, $new_id, $wall = '', $result = array(), $order_id = '', $order_no = '', $public = array())
    {
        try {
            //@todo 保单信息主表 - 商业
            if (!empty($result["proposalNoBus"])) {
                $prop_policy = array(
                    "order_id" => $order_id,
                    "order_no" => $order_no,
                    "proposal_no" => $result["proposalNo"],
                    "policy_no" => $result["policyNo"],
                    "sub_proposal_no" => $result["proposalNoBus"],
                    "policy_status" => $result["proposalMain"]["underwriteInd"],
                    "is_renew" => $result["proposalMain"]["renewind"],
                    "sign_date" => date("Y-m-d", $result["proposalMain"]["issueDate"]->sec),
                    "under_write_date" => date("Y-m-d", $result["proposalMain"]["underwriteEndDate"]->sec),
                    "risk_mark" => 1,

                    "amount" => $result['insuranceInfo']['commercialInsuranceInfo']['sumMianAmount'],
                    "premium" => $result['insuranceInfo']['commercialInsuranceInfo']['sumPremium'],
                    "original_premium" => $result['insuranceInfo']['commercialInsuranceInfo']['sumBenchMarkPremium'],
                    "discount" => $result['insuranceInfo']['commercialInsuranceInfo']['sumDiscount'],

                    "start_date" => date("Y-m-d H:i:s", $result['insuranceInfo']['commercialInsuranceInfo']['startDate']->sec),
                    "end_date" => date("Y-m-d H:i:s", $result['insuranceInfo']['commercialInsuranceInfo']['endDate']->sec),

                    "under_write_msg" => $result['proposalMain']['handleText'],
                );
                $policyId = $this->pdo->prepareInsert("prop_policy", array_merge($prop_policy, $public));
                if ($policyId) {

                    //@todo 保单险种信息表-商业险
                    if (!empty($result["insuranceInfo"]["commercialInsuranceInfo"])) {
                        foreach ($result["insuranceInfo"]["commercialInsuranceInfo"]["kinds"] as $k => $v) {
                            $prop_policy_risk = array(
                                "policy_id" => $policyId,
                                "order_no" => $order_no,
                                "risk_code" => $result['insuranceInfo']['commercialInsuranceInfo']['riskCode'],
                                "coverage_code" => $v["kindCode"],
                                "coverage_name" => $v["kindName"],
                                "item_code" => $v["itemCode"],
                                "quantity" => $v["quantity"],
                                "unit_amount" => $v["unitAmount"],
                                "amount" => $v["amount"],
                                "deductable_flag" => $v["kindFlag"],
                                "value_type" => $v['flag'],
                                "bench_mark_premium" => $v["standardPremium"],
                                "discount_premium" => $v["discountPremium"],
                                "premium" => $v["premium"],
                                "base_premium" => $result["insuranceInfo"]["JQXInfo"]["basePremium"],
                                "rate" => $v['rate']
                            );
                            $this->pdo->prepareInsert("prop_policy_risk", array_merge($prop_policy_risk, $public));
                        }
                    }

                    //@todo 保单特别约定信息表
                    if (!empty($result['specialAgreement']['clauseCode'])) {
                        $prop_policy_engaged = array(
                            "policy_id" => $policyId,
                            "order_no" => $order_no,
                            "engaged_code" => $result['specialAgreement']['clauseCode'],
                            "engaged_title" => $result['specialAgreement']['clauseName'],
                            "engaged_content" => $result['specialAgreement']['clauseDesc']
                        );
                        $this->pdo->prepareInsert("prop_policy_engaged", array_merge($prop_policy_engaged, $public));
                    }
                }
            }

            //@todo 保单信息主表 - 交强
            if (!empty($result["proposalNoFoc"])) {

                $prop_policy = array(
                    "order_id" => $order_id,
                    "order_no" => $order_no,
                    "proposal_no" => $result["proposalNo"],
                    "sub_proposal_no" => $result["proposalNoFoc"],
                    "policy_no" => $result["policyNo"],
                    "policy_status" => $result["proposalMain"]["underwriteInd"],
                    "is_renew" => $result["proposalMain"]["renewind"],
                    "sign_date" => date("Y-m-d", $result["proposalMain"]["issueDate"]->sec),
                    "under_write_date" => date("Y-m-d", $result["proposalMain"]["underwriteEndDate"]->sec),
                    "risk_mark" => 0,

                    "amount" => 122000,
                    "premium" => $result['insuranceInfo']['JQXInfo']['sumPremium'],
                    "discount" => $result['insuranceInfo']['JQXInfo']['sumDiscount'],

                    "start_date" => date("Y-m-d H:i:s", $result['insuranceInfo']['JQXInfo']['JQXStartDate']->sec),
                    "end_date" => date("Y-m-d H:i:s", $result['insuranceInfo']['JQXInfo']['JQXEndDate']->sec),

                    "under_write_msg" => $result['proposalMain']['handleText'],
                );
                $policyId = $this->pdo->prepareInsert("prop_policy", array_merge($prop_policy, $public));
                if ($policyId) {
                    //@todo 保单险种信息表-交强险
                    if (!empty($result["insuranceInfo"]["JQXInfo"])) {
                        $prop_policy_risk = array(
                            "policy_id" => $policyId,
                            "order_no" => $order_no,
                            "risk_code" => $result['insuranceInfo']['JQXInfo']['riskCode'],
                            "coverage_code" => 200, //交强险险别代码
                            "amount" => 122000, //交强险固定保额
                            "discount" => $result['insuranceInfo']['JQXInfo']['sumDiscount'],
                            "premium" => $result['insuranceInfo']['JQXInfo']['sumPremium'],
                            "coverage_name" => $result['insuranceInfo']['JQXInfo']['riskName'],
                        );
                        $this->pdo->prepareInsert("prop_policy_risk", array_merge($prop_policy_risk, $public));
                    }

                    //@todo 保单特别约定信息表
                    if (!empty($result['specialAgreement']['clauseCode'])) {
                        $prop_policy_engaged = array(
                            "policy_id" => $policyId,
                            "order_no" => $order_no,
                            "engaged_code" => $result['specialAgreement']['clauseCode'],
                            "engaged_title" => $result['specialAgreement']['clauseName'],
                            "engaged_content" => $result['specialAgreement']['clauseDesc']
                        );
                        $this->pdo->prepareInsert("prop_policy_engaged", array_merge($prop_policy_engaged, $public));
                    }
                }
            }

            //@todo 投保人信息
            $prop_policy_appnt = array(
                "order_no" => $order_no,
                "name" => $result["appliInfo"]["appliName"],
                "sex" => $result["appliInfo"]["appliSex"],
                "birthday" => date("Y-m-d", $result["appliInfo"]["appliBirthday"]->sec),
                "id_type" => $result["appliInfo"]["appliIDType"],
                "mobile" => $result["appliInfo"]["appliTel"],
                "email" => $result["appliInfo"]["appliEmail"],
                "address" => $result["appliInfo"]["appliAddress"],
            );
            $this->pdo->prepareInsert("prop_policy_appnt", array_merge($prop_policy_appnt, $public));

            //@todo 被保人信息
            $prop_policy_insured = array(
                "order_no" => $order_no,
                "name" => $result['insuredInfo']['insuredName'],
                "sex" => $result['insuredInfo']['insuredSex'],
                "birthday" => date("Y-m-d", $result['insuredInfo']['insuredBirthday']->sec),
                "id_type" => $result['insuredInfo']['insuredIDType'],
                "id_no" => $result['insuredInfo']['insuredIDNo'],
                "email" => $result['insuredInfo']['insuredEmail'],
                "mobile" => $result['insuredInfo']['insuredTel'],
                "address" => $result['insuredInfo']['insuredAddress'],
            );
            $this->pdo->prepareInsert("prop_policy_insured", array_merge($prop_policy_insured, $public));

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}