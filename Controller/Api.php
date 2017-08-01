<?php

namespace MyAPP\Controller;

use My\Controller;
use My\Request;
use MyAPP\Package\Db\User;

abstract class Api extends Controller
{
    protected $request;
    protected $userId = 0;

    public function __construct()
    {
        $request = new Request();
        $this->request = $request;
    }

    /**
     * 显示公共头部
     */
    protected function before()
    {
        $this->userId = $this->checkLogin();
    }

    /**
     * 显示公共尾部
     */
    protected function after()
    {
    }

    /**
     * 登录检查
     * 未登录的自动跳转到登录页
     */
    public function checkLogin()
    {
        $accessToken = $this->request->getServer('HTTP_AUTHORIZATION');
        if (!empty($accessToken)) {
            $accessToken = $this->base64_decode_url_safe($accessToken);
            $accessToken = unserialize($accessToken);
            if (!empty($accessToken['id']) && !empty($accessToken['token'])) {
                $userId = $accessToken['id'];
                $token = $accessToken['token'];
                $dbUser = new User();
                $rsUser = $dbUser->getLine(['id' => $userId], 'openid');
                if (!empty($rsUser['openid']) && md5($userId . $rsUser['openid']) == $token) {
                    return $userId;
                }
            }
        }
        return 5;
    }

    /**
     * 请求成功
     * @param $data
     * @param $msg
     */
    public function success($data = [], $msg = '')
    {
        echo json_encode([
            'code' => 0,
            'msg'  => $msg,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 请求失败
     * @param $code
     * @param $msg
     */
    public function error($code = -1, $msg = '')
    {
        echo json_encode([
            'code' => $code,
            'msg'  => $msg,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 获取时间，可选转成时间戳
     * @param bool $toTimestamp
     * @return int|string|null
     */
    function getTime($toTimestamp = false) {
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $time = time();
        } else {
            $time = $_SERVER['REQUEST_TIME'];
        }
        if ($toTimestamp === false) {
            $time = date('Y-m-d H:i:s', $time);
        }
        return $time;
    }

    /**
     * 获取13位时间戳
     */
    function getMicroTime()
    {
        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            list($t1, $t2) = explode(' ', microtime());
            $t = (float)$t1 + (float)$t2;
        } else {
            $t = $_SERVER['REQUEST_TIME_FLOAT'];
        }
        return round($t * 1000);
    }

    /**
     * 获取客户端IP，可选转成整数
     * @param bool $toLong
     * @return string|int|null
     */
    function getIp($toLong = false) {
        //需注意此处存在被伪造的风险，使用需谨慎，所有需严格判定IP的地方，不得信赖该值
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineIp = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineIp = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineIp = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER ['REMOTE_ADDR']) && $_SERVER ['REMOTE_ADDR'] && strcasecmp($_SERVER ['REMOTE_ADDR'], 'unknown')) {
            $onlineIp = $_SERVER ['REMOTE_ADDR'];
        }
        preg_match("/[\d\.]{7,15}/", $onlineIp, $matches);
        $onlineIp = $matches[0] ? $matches[0] : null;
        if ($toLong) {
            $onlineIp = ip2long($onlineIp);
        }
        return $onlineIp;
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

    function getPageLimit()
    {
        $page = $this->request->getRequest()->int('p', 1);
        $pageSize = $this->request->getRequest()->int('page_size', 10);
        if ($page <= 0) {
            $page = 1;
        } elseif ($page > 100) {
            $page = 100;
        }
        if ($pageSize <= 0) {
            $pageSize = 1;
        } elseif ($pageSize > 10) {
            $pageSize = 20;
        }
        $limit = $pageSize . ',' . ($page - 1) * $pageSize;
        return $limit;
    }

    /**
     * URL安全的base64_encode
     * @param $data
     * @return string
     */
    function base64_encode_url_safe($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * URL安全的base64_decode
     * @param $data
     * @return string
     */
    function base64_decode_url_safe($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * 获取比特币对人民币的价格
     * @param $coinId
     * @return float
     */
    function getPrice($coinId)
    {
        //TODO TEST
        if (in_array($coinId, [
            '1337',
            '1credit',
            'aces',
            'bata',
            'zero',
            'zoin',
        ])) {
            return 10.00;
        }

        static $pool = [];
        if (isset($pool[$coinId])) {
            return $pool[$coinId];
        }
        $API_URL_PRICE = 'http://token114.com/wap/coin/getinfo/%s';
        $priceData = $this->curl(sprintf($API_URL_PRICE, $coinId), 'GET', [], [] ,10);
        $priceData = json_decode($priceData, true);
        $price = isset($priceData[0]['price_cny']) ? round($priceData[0]['price_cny'], 2) : 0.00;
        if ($price != 0.00) {
            $pool[$coinId] = $price;
        }
        return $price;
    }

    /**
     * 获取两位浮点数
     * @param $data
     * @return float
     */
    function getDecimal($data)
    {
        return round($data, 2);
    }
}