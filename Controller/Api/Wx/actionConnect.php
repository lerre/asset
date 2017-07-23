<?php

namespace MyAPP\Controller\Api\Wx;

use MyApp\Package\Db\User;

class actionConnect extends \MyAPP\Controller\Api
{
    CONST APP_ID = 'wx4a2620cb8f73e5c1';
    CONST APP_SECRET = '549a391c954b8fd9903fa51a859d395c';

    CONST WX_INFO = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';

    public function main()
    {
        if ($this->isPost()) {
            $raw = $this->request->getRaw();
            $code = $raw['code'];
            $encryptedData = $raw['info'];
            $iv = $raw['iv'];

            $wxInfo = $this->getWxInfo($code);
            if (!empty($wxInfo['errcode']) || empty($wxInfo['openid'])) {
                $this->error(1001, '获取微信授权信息失败，请重试');
            }

            $openId = $wxInfo['openid'];
            $sessionKey = $wxInfo['session_key'];
            $WxSpAes = new \WxSpAes();
            $decryptedData = $WxSpAes->WxBizDataCrypt(self::APP_ID, $sessionKey, $encryptedData, $iv);
            $decryptedData = json_decode($decryptedData, true);

            $data = [];

            $data['openid'] = $openId;
            $data['session_key'] = $sessionKey;
            $data['nickname'] = isset($decryptedData['nickName']) ? $decryptedData['nickName'] : '';
            $data['gender'] = isset($decryptedData['gender']) ? $decryptedData['gender'] : '';
            $data['avatar'] = isset($decryptedData['avatarUrl']) ? $decryptedData['avatarUrl'] : '';
            $data['province'] = isset($decryptedData['province']) ? $decryptedData['province'] : '';
            $data['city'] = isset($decryptedData['city']) ? $decryptedData['city'] : '';
            $data['country'] = isset($decryptedData['country']) ? $decryptedData['country'] : '';

//            $openId = 'oOCgc0ZmRvq5WeGh9JRQ4SGZtQUM+++';
//
//            $data = [
//                'openid' => $openId,
//                'session_key' => 'GPnoWG/zws7SjqTFCAUyIA==',
//                'nickname' => 'lr',
//                'gender' => 1,
//                'avatar' => 'http://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTIbxCVuZcA4ibnbL9mLwAsn3p7nksLfHN3ud9fFo1xxC0g8kh1fgs0T8J0uGRgOGtM8h4VFBTfDEKQ/0',
//                'province' => 'beijing',
//                'city' => 'beijing',
//                'country' => 'CN',
//            ];

            $today = $this->getTime();
            $ip = $this->getIp();

            $dbUser = new User();
            $param = [
                'openid' => $openId
            ];
            $user = $dbUser->getLine($param, 'id,login_times');
            if (empty($user['id'])) {
                $data['reg_time'] = $today;
                $data['last_login_time'] = $today;
                $data['last_login_ip'] = $ip;
                $data['login_times'] = 1;
                $userId = $dbUser->register($data);
                if (empty($userId)) {
                    $this->error(1003, '微信授权失败[1003]');
                }
            } else {
                $userId = $user['id'];
                $data['last_login_time'] = $today;
                $data['last_login_ip'] = $ip;
                $data['login_times'] = $user['login_times'] + 1;
                $dbUser->login($data, $userId);
            }

            if (!$userId) {
                $this->error(1002, '微信授权失败');
            }

            $accessToken = md5($userId . $openId);
            $newAccessToken = $this->base64_encode_url_safe(serialize([
                't' => time(),
                'id' => $userId,
                'token' => $accessToken
            ]));

            $this->success([
                'access_token' => $newAccessToken,
                'weixin' => $decryptedData
            ]);
        }
    }

    private function getWxInfo($code)
    {
        $url = sprintf(self::WX_INFO, self::APP_ID, self::APP_SECRET, $code);
        $res = $this->curl($url, 'GET', $timeout = 10);
        if (empty($res)) {
            return false;
        }
        return json_decode($res, true);
    }

    public function before()
    {
        //TODO
    }

    public function after()
    {
        //TODO
    }
}