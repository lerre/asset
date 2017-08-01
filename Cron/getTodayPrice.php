<?php

set_time_limit(0);

require_once 'Db.php';

/**
 * 获取实时价格
 * @param $coinId
 * @return float
 */
function getPrice($coinId)
{
    $API_URL_PRICE = 'https://api.coinmarketcap.com/v1/ticker/%s/?convert=CNY';
    $priceData = curl(sprintf($API_URL_PRICE, $coinId), 'GET', [], [], 10);
    $priceData = json_decode($priceData, true);
    $price = isset($priceData[0]['price_cny']) ? round($priceData[0]['price_cny'], 2) : 0.00;
    return $price;
}

/**
 * curl请求(支持 GET / POST / PUT / DELETE 请求方式)
 * @param string $url
 * @param string $type
 * @param array $fields
 * @param array $headers
 * @param int $timeout
 * @return mixed
 */
function curl($url = '', $type = 'GET', $fields = [], $headers = [], $timeout = 10)
{
    $ch = curl_init();
    $https = stripos($url, 'https://') === 0 ? true : false;
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    switch ($type) {
        case 'GET':
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            break;
    }

    $rs = curl_exec($ch);
    curl_close($ch);
    return $rs;
}

/**
 * DB操作
 */
$db = new Db();

$date = date('Y-m-d');

$values = '';

$sql = 'SELECT coin_id FROM currency';
$rsCurrency = $db->queryAll($sql);

if (!empty($rsCurrency)) {
    foreach ($rsCurrency as $k => $v) {
        echo $k . PHP_EOL;
        if (!empty($v['coin_id'])) {
            $coinId = $v['coin_id'];
            $price = getPrice($coinId);
            $values .= '("' . date('Y-m-d') . '","' . $coinId . '","' . $price . '"),';
        }
    }
}

echo $values;

if (!empty($values)) {
    $values = rtrim($values, ',');
    $sql = 'INSERT INTO price(date,coin_id,price) VALUES ' . $values;
    echo $sql;
    $db->exec('INSERT', $sql);
}

echo 'finish';
exit;
