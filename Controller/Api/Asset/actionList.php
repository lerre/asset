<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;

class actionList extends \MyAPP\Controller\Api
{
    CONST API_URL_PRICE = 'https://api.coinmarketcap.com/v1/ticker/%s/?convert=CNY';

    public function main()
    {
        if (empty($this->userId)) {
            $this->error(1001, '用户未登录');
        }
        $userId = $this->userId;

        $output = [];

        $dbAsset = new Asset();
        $param = [
            'user_id' => $userId
        ];
        $field = 'coin_id,number,cost';
        $order = 'create_at DESC';
        $rsAssetList = $dbAsset->getList($param, $field, $order);

        $assetList = [];
        if (!empty($rsAssetList)) {
            foreach ($rsAssetList as $k => $v) {
                $coinId = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $number = !empty($v['number']) ? $v['number'] : 0;
                $price = $this->getPrice($coinId);
                $cost = $this->getCost($coinId);
                //持币盈亏
                $diff = $price - $cost;
                if ($diff > 0) {
                    $direct = 2;
                } elseif ($diff < 0) {
                    $direct = 1;
                } else {
                    $direct = 0;
                }
                $holdProfile = abs($diff) * $number;
                if ($coinId) {
                    $assetList[$k]['coin_id'] = $coinId;
                    $assetList[$k]['price'] = $price; //最新价
                    $assetList[$k]['worth'] = $price * $number; //市值
                    $assetList[$k]['cost'] = $cost; //成本价
                    $assetList[$k]['accumulated_profile'] = $holdProfile;
                }
            }
        }

        $output['worth'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['curr_profit'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['hold_profit'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['accumulated_profit'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['accumulated_profile_rate'] = sprintf('%s%%', round(10, 99));
        $output['list'] = $assetList;

        $this->success($output);
    }

    private function getPrice($coinId)
    {
        $priceData = $this->curl(sprintf(self::API_URL_PRICE, $coinId));
        $priceData = json_decode($priceData, true);
        $price = isset($priceData[0]['price_cny']) ? round($priceData[0]['price_cny'], 2) : 0.00;
        return $price;
    }

    private function getCost($coinId)
    {
        return round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
    }
}