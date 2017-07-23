<?php

define('WxSpAesSRC', __DIR__.'/src');

include_once WxSpAesSRC.'/wxBizDataCrypt.php';

class WxSpAes
{
    public function WxBizDataCrypt($appId, $sessionKey, $encryptedData, $iv) {
        $data = null;
        $pc = new WXBizDataCrypt($appId, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);

        if ($errCode == 0) {
            //print($data . "\n");
            return $data;
        } else {
            //print($errCode . "\n");
            return false;
        }
    }
}