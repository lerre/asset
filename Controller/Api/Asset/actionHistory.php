<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\Currency;

class actionHistory extends \MyAPP\Controller\Api
{
    public function main()
    {
        if (empty($this->userId)) {
            $this->error(1001, '用户未登录');
        }
        $userId = $this->userId;

        $output = [];

        //币种
        $coinIdList = [];

        $dbCurrency = new Currency();
        $data = $dbCurrency->getList('coin_id');
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $coinIdList[] = $v['coin_id'];
            }
        }

        //卖出
        $assetSellList = [];
        $sellProfit = 0.00;

        $dbAssetSell = new AssetSell();
        $param = [
            'user_id' => $userId
        ];
        $field = 'coin_id,profit';
        $rsAssetSellList = $dbAssetSell->getList($param, $field);
        if (!empty($rsAssetSellList)) {
            foreach ($rsAssetSellList as $k => $v) {
                $assetSellList[$v['coin_id']] = $v['profit'];
                $sellProfit += $v['profit'];
            }
        }

        //持币
        $dbAsset = new Asset();
        $param = [
            'user_id' => $userId
        ];
        $field = 'coin_id,profit,number,cost';
        $order = 'create_at DESC';
        $rsAssetList = $dbAsset->getHistory($param, $field, $order);

        $assetList = [];
        $worth = 0;
        $holdProfit = 0.00;

        if (!empty($rsAssetList)) {
            foreach ($rsAssetList as $k => $v) {
                $coinId = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $number = !empty($v['number']) ? (int)$v['number'] : 0;
                if (!in_array($coinId, $coinIdList)) { //未收录
                    $assetList[$k]['included'] = false;
                    $assetList[$k]['coin_id'] = $coinId; //币种
                    continue;
                } else {
                    $assetList[$k]['included'] = true;
                }
                $profit = !empty($v['profit']) ? (float)$v['profit'] : 0.00;
                $cost = !empty($v['cost']) ? (float)$v['cost'] : 0.00;
                if ($cost == 0.00) {
                    $cost = round($profit / $number, 2); //持币成本单价
                } else {
                    $profit = round($profit * $number, 2); //持币成本
                }
                $price = $this->getPrice($coinId); //当前价格
                if ($coinId && $number) {
                    $assetList[$k]['coin_id'] = $coinId; //币种
                    $assetList[$k]['price'] = $price; //最新价
                    $assetList[$k]['cost'] = $cost; //成本价
                    $assetList[$k]['worth'] = $this->getDecimal($price * $number); //市值
                    $worth += $assetList[$k]['worth'];
                    //持币盈亏
                    //$assetList[$k]['hold_profit'] = ($price - $cost) * $number;
                    $holdProfit += ($price - $cost) * $number;
                    //累计盈亏
                    if (isset($assetSellList[$coinId])) {
                        $assetList[$k]['accumulated_profile'] = $holdProfit + $assetSellList[$coinId];
                    } else {
                        $assetList[$k]['accumulated_profile'] = $holdProfit;
                    }
                    //累积盈亏率
                    $assetList[$k]['accumulated_profile_rate'] = !empty($profit) ? $this->getDecimal(($assetList[$k]['accumulated_profile'] - $profit) / $profit) : 0;
                }
            }
        }

        $output['list'] = array_values($assetList);

        $this->success($output);
    }

    private function getPastPrice($coinId)
    {
        return mt_rand(100, 100000);
    }
}