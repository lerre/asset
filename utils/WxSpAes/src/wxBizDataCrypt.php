<?php

include_once WxSpAesSRC.'/wxBizErrorCode.php';
include_once WxSpAesSRC.'/pkcs7Encoder.php';

/**
 * 对微信小程序用户加密数据的解密示例代码.
 *
 * @copyright Copyright (c) 1998-2014 Tencent Inc.
 */
class WXBizDataCrypt
{
    private $appid;
	private $sessionKey;

	/**
	 * 构造函数
     * @param $appId string 小程序的appid
	 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
	 */
	public function WXBizDataCrypt( $appId, $sessionKey )
	{
        $this->appid = $appId;
		$this->sessionKey = $sessionKey;
	}

	/**
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
     *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptData( $encryptedData, $iv, &$data )
	{
		if (strlen($this->sessionKey) != 24) {
			return WXBizErrorCode::$IllegalAesKey;
		}
		$aesKey=base64_decode($this->sessionKey);
        
		if (strlen($iv) != 24) {
			return WXBizErrorCode::$IllegalIv;
		}
		$aesIV=base64_decode($iv);

		$aesCipher=base64_decode($encryptedData);

		$pc = new Prpcrypt($aesKey);
		$result = $pc->decrypt($aesCipher,$aesIV);

		if ($result[0] != 0) {
			return $result[0];
		}

        $dataObj=json_decode( $result[1] );
        if( $dataObj  == NULL )
        {
            return WXBizErrorCode::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return WXBizErrorCode::$IllegalBuffer;
              var_dump($result);exit;  }

		$data = $result[1];
		return WXBizErrorCode::$OK;
	}
}