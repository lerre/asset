<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\AssetPlace;

class actionPlace extends \MyAPP\Controller\Api
{
    CONST API_URL_PRICE = 'https://api.coinmarketcap.com/v1/ticker/%s/?convert=CNY';

    public function main()
    {
        if (empty($this->userId)) {
            $this->error(1001, '用户未登录');
        }
        $userId = $this->userId;
        $coinId = $this->request->getRequest()->string('coin_id');

        $output = [];

        $priceData = $this->curl(sprintf(self::API_URL_PRICE, $coinId));
        $priceData = json_decode($priceData, true);
        $price = isset($priceData[0]['price_cny']) ? round($priceData[0]['price_cny'], 2) : 0.00;

        $dbAssetPlace = new AssetPlace();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $rsAssetPlace = $dbAssetPlace->getList($param, 'id,place,number');

        $totalNumber = 0;
        if (!empty($rsAssetPlace)) {
            foreach ($rsAssetPlace as $k => $v) {
                $number = !empty($v['number']) ? $v['number'] : 0;
                $totalNumber += $number;
            }
        }

        $assetPlaceList = [];
        if (!empty($rsAssetPlace)) {
            foreach ($rsAssetPlace as $k => $v) {
                $number = !empty($v['number']) ? $v['number'] : 0;
                $percent = sprintf('%s%%', round($number * 100 / $totalNumber));
                if (!empty($v['place'])) {
                    $assetPlaceList[$k]['place'] = $v['place'];
                } else {
                    $assetPlaceList[$k]['place'] = '未知渠道';
                }
                $assetPlaceList[$k]['percent'] = $percent;
                $assetPlaceList[$k]['worth'] = round($number * $price, 2);;
            }
        }

        $output['list'] = $assetPlaceList;

        $this->success($output);
    }
}