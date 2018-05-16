<?php

/**
 * 帮助中心类
 * User: xiebin
 * Date: 16/4/14
 * Time: 下午4:28
 */
class Help
{
    /**
     * 根据身份证号获取生日和性别
     * @param $cardId string 身份证号
     * @return array
     */
    static function getInfoByCardId($cardId)
    {
        $arr = array('sex' => null, 'brithday' => null);
        if (self::isCreditNo($cardId)) {
            $arr['sex'] = self::getSexById($cardId);
            $arr['birthday'] = self::getBirthdayById($cardId);
        }

        return $arr;
    }

    /**
     * 根据身份证号获取性别
     * @param $cardId
     * @return string
     */
    function getSexById($cardId)
    {
        $cardId = self::getIDCard($cardId);
        $sexint = (int)substr($cardId, 16, 1);
        return $sexint % 2 === 0 ? '2' : '1';
    }

    /**
     * 根据身份证号获取性别
     * @param $cardId
     * @return string
     */
    function getBirthdayById($cardId)
    {
        $cardId = self::getIDCard($cardId);
        return substr($cardId, 6, 4) . '-' . substr($cardId, 10, 2) . '-' . substr($cardId, 12, 2);
    }


    /**
     * 把15位身份证转换成18位
     * @param $idCard
     * @return string
     */
    static function getIDCard($idCard)
    {
        // 若是15位，则转换成18位；否则直接返回ID
        if (15 == strlen($idCard)) {
            $W = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1);
            $A = array("1", "0", "X", "9", "8", "7", "6", "5", "4", "3", "2");
            $s = 0;
            $idCard18 = substr($idCard, 0, 6) . "19" . substr($idCard, 6);
            $idCard18_len = strlen($idCard18);
            for ($i = 0; $i < $idCard18_len; $i++) {
                $s = $s + substr($idCard18, $i, 1) * $W [$i];
            }
            $idCard18 .= $A [$s % 11];
            return $idCard18;
        } else {
            return $idCard;
        }
    }

    /**
     * 验证身份证号
     * @param $vStr
     * @return bool
     */
    static function isCreditNo($vStr)
    {
        $vCity = array(
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91'
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;

        if (!in_array(substr($vStr, 0, 2), $vCity)) return false;

        $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
        $vLength = strlen($vStr);

        if ($vLength == 18) {
            $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
        } else {
            $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
        }

        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
        if ($vLength == 18) {
            $vSum = 0;

            for ($i = 17; $i >= 0; $i--) {
                $vSubStr = substr($vStr, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr, 11));
            }

            if ($vSum % 11 != 1) return false;
        }

        return true;
    }

    /**
     * 获取内存占用
     * @return string
     */
    static function memory_usage() {
        $memory  = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';
        return $memory;
    }
}