<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetSell;

class actionList extends \MyAPP\Controller\Api
{
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
        $field = 'coin_id,profit,number,cost';
        $order = 'create_at DESC';
        $rsAssetList = $dbAsset->getList($param, $field, $order);

        $assetList = [];
        $currProfit = 0.00;
        $holdProfit = 0.00;
        $sellProfit = 0.00;
        $accumulatedProfit = 0.00;

        if (!empty($rsAssetList)) {
            foreach ($rsAssetList as $k => $v) {
                $coinId = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $profit = !empty($v['profit']) ? (float)$v['profit'] : 0.00;
                $number = !empty($v['number']) ? (int)$v['number'] : 0;
                $cost = !empty($v['cost']) ? (float)$v['cost'] : 0.00;
                if ($number <= 0) {
                    continue;
                }
                if ($cost == 0.00) {
                    $cost = round($profit / $number, 2); //持币成本
                }
                $price = $this->getPrice($coinId); //当前价格
                $pastPrice = $this->getPastPrice($coinId); //凌晨价格
                if ($coinId && $number) {
                    $assetList[$k]['coin_id'] = $coinId;
                    $assetList[$k]['price'] = $price; //最新价
                    $assetList[$k]['cost'] = $cost; //成本价
                    $assetList[$k]['worth'] = $price * $number; //市值
                    //当日盈亏
                    $assetList[$k]['curr_profit'] = ($price - $pastPrice) * $number;
                    $currProfit += $assetList[$k]['curr_profit'];
                    //持币盈亏
                    $assetList[$k]['hold_profit'] = ($price - $cost) * $number;
                    $holdProfit += $assetList[$k]['hold_profit'];
                    $assetList[$k]['accumulated_profile'] = $holdProfit;
                }
            }
        }

        $dbAssetSell = new AssetSel();
        $param = [
            'user_id' => $userId
        ];
        $field = 'coin_id,profit';
        $rsAssetSellList = $dbAssetSell->getList($param, $field);
        if (!empty($rsAssetSellList)) {
            foreach ($rsAssetSellList as $k => $v) {
                $sellProfit += $v['profit'];
            }
        }

        $accumulatedProfit = $holdProfit + $sellProfit;

        $output['worth'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['curr_profit'] = $currProfit;
        $output['hold_profit'] = $holdProfit;
        $output['accumulated_profit'] = $accumulatedProfit;
        $output['accumulated_profile_rate'] = sprintf('%s%%', round(10, 99));
        $output['list'] = array_values($assetList);

        $this->success($output);
    }

    private function getPastPrice($coinId)
    {
        return mt_rand(100, 100000);
    }
}