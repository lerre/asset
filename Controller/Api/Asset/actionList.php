<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\Currency;

class actionList extends \MyAPP\Controller\Api
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
        $coinIdIndex = [];

        $dbCurrency = new Currency();
        $data = $dbCurrency->getList('coin_id,coin');
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $coinIdList[] = $v['coin_id'];
                $coinIdIndex[$v['coin_id']] = $v['coin'];
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
        $rsAssetList = $dbAsset->getList($param, $field, $order);

        $assetList = [];
        $worth = 0;
        $costProfit = 0.00;
        $holdProfit = 0.00;

        if (!empty($rsAssetList)) {
            foreach ($rsAssetList as $k => $v) {
                $coinId = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $number = !empty($v['number']) ? (int)$v['number'] : 0;
                if ($number <= 0) { //不展示
                    continue;
                }
                if (!in_array($coinId, $coinIdList)) { //未收录
                    $assetList[$k]['included'] = false;
                    $assetList[$k]['coin_id'] = $coinId; //币种
                    $assetList[$k]['coin'] = isset($coinIdIndex[$coinId]) ? $coinIdIndex[$coinId] : ''; //币种缩写
                    continue;
                } else {
                    $assetList[$k]['included'] = true;
                }
                $profit = !empty($v['profit']) ? (float)$v['profit'] : 0.00;
                $cost = !empty($v['cost']) ? (float)$v['cost'] : 0.00;
                if ($cost <= 0.00) {
                    $cost = !empty($number) ? $this->getDecimal($profit / $number) : 0.00; //持币成本单价
                }
                $price = $this->getPrice($coinId); //当前价格
                if ($coinId && $number) {
                    $assetList[$k]['coin_id'] = $coinId; //币种
                    $assetList[$k]['coin'] = isset($coinIdIndex[$coinId]) ? $coinIdIndex[$coinId] : ''; //币种缩写
                    $assetList[$k]['price'] = $price; //最新价
                    $assetList[$k]['cost'] = $cost; //成本价
                    $assetList[$k]['worth'] = $this->getDecimal($price * $number); //市值
                    $worth += $assetList[$k]['worth'];
                    //持仓成本
                    $costProfit += $profit;
                    //持仓盈亏
                    $assetList[$k]['hold_profit'] = ($price - $cost) * $number;
                    $holdProfit += ($price - $cost) * $number;
                    //持仓盈亏率
                    $assetList[$k]['hold_profit_rate'] = !empty($profit) ? $this->getDecimal($assetList[$k]['hold_profit'] / $profit) : 0;
                    //累计盈亏
                    if (isset($assetSellList[$coinId])) {
                        $assetList[$k]['accumulated_profile'] = $holdProfit + $assetSellList[$coinId];
                    } else {
                        $assetList[$k]['accumulated_profile'] = $holdProfit;
                    }
                    //累积盈亏率
                    $assetList[$k]['accumulated_profile_rate'] = !empty($profit) ? $this->getDecimal($assetList[$k]['accumulated_profile'] / $profit) : 0;
                }
            }
        }

        //累计盈亏
        $accumulatedProfit = $worth + $sellProfit - $costProfit;

        $output['user_id'] = $userId;
        $output['worth'] = $worth;
        $output['cost_profit'] = $costProfit; //持仓成本
        $output['hold_profit'] = $holdProfit; //持仓盈亏
        $output['hold_profit_rate'] = !empty($costProfit) ? $this->getDecimal($holdProfit / $costProfit) : 0; //持仓盈亏率
        $output['accumulated_profit'] = $accumulatedProfit; //累计盈亏
        $output['accumulated_profile_rate'] = !empty($costProfit) ? $this->getDecimal($accumulatedProfit / $costProfit) : 0; //累计盈亏率
        $output['list'] = array_values($assetList);

        $this->success($output);
    }
}