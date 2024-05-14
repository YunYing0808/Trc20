<?php

/**
 * Trc20 monitoring class
 * Author:YunYing
 */

class Trc20
{

    /**
     * Trc20 official monitoring API address
     */
    const apiUrl = "https://apilist.tronscanapi.com/api/transfer/trc20";

    /**
     * CallBack Trc20 Order
     * Query transaction records based on Token
     * Then match related database orders
     * If the transaction order matches the database order
     * Then perform order business processing
     * @param $token
     * @return array|true
     */
    public static function trc20Callback($token)
    {
        $startTime = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $endTime = date('Y-m-d H:i:s');
        $queryParams = [
            'sort' => '-timestamp',
            'limit' => '50',
            'start' => '0',
            'direction' => '2',
            'db_version' => '1',
            'trc20Id' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            'address' => $token,
            'start_timestamp' => $startTime,
            'end_timestamp' => $endTime,
        ];
        $queryString = http_build_query($queryParams);
        $response = self::get(Trc20::apiUrl,$queryString);
        $trc20Resp = json_decode($response, true);
        if ($trc20Resp['pageSize'] <= 0) {
            return  self::Tips('1901',"No transaction records found for this address",$trc20Resp);
        }
        foreach ($trc20Resp['data'] as $transfer) {
            if ($transfer['to'] !== $token || $transfer['contract_ret'] !== "SUCCESS") {
                continue;
            }
            $decimalQuant = $transfer['amount'];
            $decimalDivisor = 1000000;
            $amount = $decimalQuant/$decimalDivisor;
            $tradeId = self::findOrder($token,$amount);
            if (!$tradeId) {
                continue;
            }
            $createTime = strtotime($tradeId['createTime']);
            if ($transfer['block_timestamp'] < $createTime) {
                return  self::Tips('1901',"Order time can't match");
            }
            self::OrderProcess($tradeId['id'],$token,$amount,$transfer['hash']);
        }
        return true;
    }

    /**
     * Find orders based on wallet address and amount
     * @param $token
     * @param $amount
     * @return string[]
     */
    public static function findOrder($token, $amount)
    {
        return [''];
    }

    /**
     * Perform final business processing
     * @param $tradeId
     * @param $token
     * @param $amount
     * @param $hash
     * @return void
     */
    public static function OrderProcess($tradeId, $token, $amount, $hash)
    {

    }

    public static function get($url,$queryString)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url."?".$queryString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            return  self::Tips('1900',"Failed to connect to Trc20's API address and failed to get transaction data");
        }
        return ($response);
    }

    /**
     * Return tips
     * @param $code
     * @param $message
     * @param $data
     * @return array
     */
    public static function Tips($code, $message, $data = null)
    {
        return ['status'=>$code,'message'=>$message,'data'=>$data];
    }

}
