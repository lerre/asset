<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetPlace;
use MyApp\Package\Db\TransDetail;

class actionDetail extends \MyAPP\Controller\Api
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

        $dbAsset = new Asset();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $assetData = $dbAsset->getLine($param, 'number,cost');
        $cost = isset($assetData['cost']) ? (float)$assetData['cost'] : 0.00;
        $number = isset($assetData['number']) ? (int)$assetData['number'] : 0;

        $priceData = $this->curl(sprintf(self::API_URL_PRICE, $coinId));
        $priceData = json_decode($priceData, true);
        $price = isset($priceData[0]['price_cny']) ? round($priceData[0]['price_cny'], 2) : 0.00;

        //持币总值
        $worth = round($number * $price, 2);

        //成本单价
        if ($cost == 0.00) {
            $cost = round($price / $number, 2);
        }

//        //当日盈亏 TODO 昨日凌晨价格
//        $pastPrice = mt_rand(-100, 100);
//        $diff = $price - $pastPrice;
//        if ($diff > 0) {
//            $direct = 2; //正值
//        } elseif ($diff < 0 ) {
//            $direct = 1; //负值
//        } else {
//            $direct = 0;
//        }
//        $todayProfit = round($number * abs($diff), 2);
//
//        //持仓盈亏
//        //累计盈亏

        $dbAssetPlace = new AssetPlace();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $rsAssetPlace = $dbAssetPlace->getList($param, 'place,number');

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
                    $assetPlaceList[$v['place']] = $percent;
                } else {
                    $assetPlaceList['未知渠道'] = $percent;
                }
            }
        }

        $dbTransDetail = new TransDetail();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $rsTransDetail = $dbTransDetail->getLatest($param, 'type,coin_id,price,number,create_at', 'create_at DESC', 20);

        $assetTransList = [];
        if (!empty($rsTransDetail)) {
            foreach ($rsTransDetail as $k => $v) {
                $type = !empty($v['type']) ? (int)$v['type'] : 0;
                switch ($type) {
                    case 1:
                        $assetTransList[$k]['type'] = '买入';
                        break;
                    case 2:
                        $assetTransList[$k]['type'] = '卖出';
                        break;
                    default:
                        $assetTransList[$k]['type'] = '未知';
                        break;
                }
                $assetTransList[$k]['coin_id'] = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $assetTransList[$k]['price'] = !empty($v['price']) ? (float)$v['price'] : 0.00;
                $assetTransList[$k]['number'] = !empty($v['number']) ? (int)$v['price'] : 0;
                $assetTransList[$k]['create_at'] = !empty($v['create_at']) ? date('Y-m-d', strtotime($v['create_at'])) : '';
            }
        }

        $output = [
            'worth' => $worth,
            'price' => $price,
            'number' => $number,
            'cost' => $cost,
            'curr_profile' => round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2),
            'hold_profile' => round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2),
            'accumulated_profile' => round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2),
            'asset_place_list' => $assetPlaceList,
            'asset_trans_list' => $assetTransList
        ];

        $this->success($output);
    }
}