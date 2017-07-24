<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\AssetPlace;
use MyApp\Package\Db\TransDetail;

class actionDetail extends \MyAPP\Controller\Api
{
    CONST TYPE_BUY = 1;
    CONST TYPE_SELL = 2;

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
        //成本价
        $cost = isset($assetData['cost']) ? (float)$assetData['cost'] : 0.00;
        //持币数
        $number = isset($assetData['number']) ? (int)$assetData['number'] : 0;
        //当前价
        $price = $this->getPrice($coinId);
        //成本价
        if ($cost == 0.00) {
            $cost = !empty($number) ? round($price / $number, 2) : 0.00;
        }
        //持币总值: 当前价*持币数
        $worth = round($price * $number, 2);

        //当日盈亏 TODO 昨日凌晨价格
        $pastPrice = $this->getPastPrice($coinId);
        $currProfile = $this->getDecimal(($price - $pastPrice) * $number);

        //持仓盈亏
        $holdProfile = $this->getDecimal(($price - $cost) * $number, 2);

        //累积盈亏
        $dbAssetSell = new AssetSell();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $rsAssetSell = $dbAssetSell->getLine($param, 'profit');
        $sellProfile = isset($rsAssetSell['profit']) ? $rsAssetSell['profit'] : 0.00;
        $accumulatedProfile = $holdProfile + $sellProfile;

        $output['worth'] = $worth;
        $output['price'] = $price;
        $output['number'] = $number;
        $output['cost'] = $cost;
        $output['curr_profile'] = $currProfile;
        $output['hold_profile'] = $holdProfile;
        $output['accumulated_profile'] = $accumulatedProfile;

        //资产分布
        $dbAssetPlace = new AssetPlace();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $rsAssetPlace = $dbAssetPlace->getList($param, 'place,number');

        $totalNumber = 0;
        if (!empty($rsAssetPlace)) {
            foreach ($rsAssetPlace as $k => $v) {
                $number = !empty($v['number']) ? (int)$v['number'] : 0;
                $totalNumber += $number;
            }
        }

        $assetPlaceList = [];
        if (!empty($rsAssetPlace)) {
            foreach ($rsAssetPlace as $k => $v) {
                $place = !empty($v['place']) ? $v['place'] : '';
                $number = !empty($v['number']) ? (int)$v['number'] : 0;
                $percent = !empty($totalNumber) ? sprintf('%s%%', round($number * 100 / $totalNumber)) : 0;
                $assetPlaceList[$k]['percent'] = $percent;
                if ($place) {
                    $assetPlaceList[$k]['place'] = $place;
                } else {
                    $assetPlaceList[$k]['place'] = '未知渠道';
                }
            }
        }

        //交易记录
        $dbTransDetail = new TransDetail();
        $param = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        $rsTransDetail = $dbTransDetail->getLatest($param, 'type,coin_id,number,price,create_at', 'create_at DESC', 20);

        $assetTransList = [];
        $assetTransSellList = [];
        if (!empty($rsTransDetail)) {
            foreach ($rsTransDetail as $k => $v) {
                $type = !empty($v['type']) ? (int)$v['type'] : 0;
                switch ($type) {
                    case self::TYPE_BUY:
                        $assetTransList[$k]['type'] = '买入';
                        break;
                    case self::TYPE_SELL:
                        $assetTransList[$k]['type'] = '卖出';
                        break;
                    default:
                        $assetTransList[$k]['type'] = '未知';
                        break;
                }
                $assetTransList[$k]['coin_id'] = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $assetTransList[$k]['number'] = !empty($v['number']) ? (int)$v['number'] : 0;
                $assetTransList[$k]['price'] = !empty($v['price']) ? (float)$v['price'] : 0.00;
                $assetTransList[$k]['sum'] = round($assetTransList[$k]['number'] * $assetTransList[$k]['price'], 2);
                $assetTransList[$k]['create_at'] = !empty($v['create_at']) ? date('Y-m-d', strtotime($v['create_at'])) : '';
                if ($type == self::TYPE_SELL) {
                    $assetTransSellList[$k] = $assetTransList[$k];
                }
            }
        }

        $output['asset_place_list'] = $assetPlaceList;
        $output['asset_trans_list'] = $assetTransList;

        $this->success($output);
    }

    private function getPastPrice($coinId)
    {
        return mt_rand(100, 100000);
    }
}