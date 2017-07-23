<?php

$url = 'https://api.damabtc.com/rate/date';

curl($url);

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
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
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
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            break;
        case 'DELETE':
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            break;
    }

    $rs = curl_exec($ch);
    curl_close($ch);
    return $rs;
}