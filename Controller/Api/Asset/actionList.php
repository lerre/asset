<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetBuy;
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

        $param = [
            'user_id' => $userId
        ];

        //买入
        $assetBuyList = [];
        $assetBuyTotalCost = 0.00;
        $dbAssetBuy = new AssetBuy();
        $rsAssetBuy = $dbAssetBuy->getList($param, 'coin_id,number,total_cost');
        if (!empty($rsAssetBuy)) {
            foreach ($rsAssetBuy as $k => $v) {
                if (empty($v['coin_id'])) continue;
                $assetBuyList[$v['coin_id']]['number'] = isset($v['number']) ? (int)$v['number'] : 0;
                $assetBuyList[$v['coin_id']]['total_cost'] = isset($v['total_cost']) ? (float)$v['total_cost'] : 0.00;
                $assetBuyTotalCost += $v['total_cost'];
            }
        }

        //卖出
        $assetSellList = [];
        $assetSellTotalProfit = 0.00;
        $dbAssetSell = new AssetSell();
        $rsAssetSell = $dbAssetSell->getList($param, 'coin_id,number,total_profit');
        if (!empty($rsAssetSell)) {
            foreach ($rsAssetSell as $k => $v) {
                if (empty($v['coin_id'])) continue;
                $assetSellList[$v['coin_id']]['number'] = isset($v['number']) ? (int)$v['number'] : 0;
                $assetSellList[$v['coin_id']]['total_profit'] = isset($v['total_profit']) ? (float)$v['total_profit'] : 0.00;
                $assetSellTotalProfit += $v['total_profit'];
            }
        }

        //持币数和持币成本单价
        $dbAsset = new Asset();
        $field = 'coin_id,number,cost';
        $order = 'create_at DESC';
        $rsAssetList = $dbAsset->getList($param, $field, $order);

        $assetList = [];
        $worthTotal = 0.00;
        $costProfitTotal = 0.00;
        $holdProfitTotal = 0.00;
        $buyTotalCostTotal = 0.00;
        $accumulatedProfitTotal = 0.00;

        if (!empty($rsAssetList)) {
            foreach ($rsAssetList as $k => $v) {
                $coinId = isset($v['coin_id']) ? $v['coin_id'] : '';
                $number = isset($v['number']) ? (int)$v['number'] : 0;
                $cost = isset($v['cost']) ? (float)$v['cost'] : 0.00;
                if (empty($v['coin_id'])) {
                    continue;
                }
                $buyTotalCost = $assetBuyList[$coinId]['total_cost'];
                $buyTotalNumber = $assetBuyList[$coinId]['number'];
                $sellTotalProfit = isset($assetSellList[$coinId]['total_profit']) ? $assetSellList[$coinId]['total_profit'] : 0.00;

                //是否清仓？若已清仓，则不展示
                if ($number <= 0) {
                    continue;
                }

                $assetList[$k]['coin_id'] = $coinId; //币种
                $assetList[$k]['coin'] = isset($coinIdIndex[$coinId]) ? $coinIdIndex[$coinId] : ''; //币种缩写
                $assetList[$k]['number'] = $number; //持仓成本单价

                //是否收录？若未收录，则不计算
                if (!in_array($coinId, $coinIdList)) { //未收录
                    $assetList[$k]['included'] = false;
                    continue;
                } else {
                    $assetList[$k]['included'] = true;
                }
                //持币成本单价
                if ($cost == 0.00) {
                    $cost = !empty($buyTotalNumber) ? $this->getDecimal($buyTotalCost / $buyTotalNumber) : 0.00;
                }

                $assetList[$k]['cost'] = $cost;
                $assetList[$k]['cost'] = $cost;

                $assetList[$k]['cost'] = $cost; //持仓数
                //最新价
                $price = $this->getPrice($coinId);
                $assetList[$k]['price'] = $price; //最新价
                //市值 = 最新价 * 持币数
                $worth = $this->getDecimal($price * $number); //市值
                $assetList[$k]['worth'] = $this->getDecimal($worth, 2);
                //总市值
                $worthTotal += $worth;
                //买入总成本
                $buyTotalCostTotal += $buyTotalCost;
                //持仓成本 = 持仓成本单价 * 持仓数
                $costProfit = $this->getDecimal($cost * $number);
                //持仓总成本
                $costProfitTotal += $costProfit;
                //持仓盈亏 = (最新价 - 持仓成本单价) * 持仓数
                $holdProfit = ($price - $cost) * $number;
                $assetList[$k]['hold_profit'] = $this->getDecimal($holdProfit, 2);
                //持仓总盈亏
                $holdProfitTotal += $holdProfit;
                //持仓盈亏率 = 持仓盈亏 / 持仓成本
                $assetList[$k]['hold_profit_rate'] = !empty($costProfit) ? $this->getDecimal($holdProfit / $costProfit, 4) : 0;
                //累积盈亏 = 总市值 + 卖出交易总成本 - 买入交易总币数 * 持仓成本单价
                $accumulatedProfit = $worth + $sellTotalProfit - $buyTotalNumber * $cost;
                $assetList[$k]['accumulated_profit'] = $this->getDecimal($accumulatedProfit, 2);
                //累积总盈亏
                $accumulatedProfitTotal += $accumulatedProfit;
                //累积盈亏率
                $assetList[$k]['accumulated_profit_rate'] = !empty($buyTotalCost) ? $this->getDecimal($accumulatedProfit / $buyTotalCost, 4) : 0;

                $assetList[$k]['debug'] = [
                    'hold_profit' => $holdProfit,
                    'cost_profit' => $costProfit,
                    'buy_total_cost' => $buyTotalCost,
                ];
            }
        }

        $output['user_id'] = $userId;
        $output['worth'] = $this->getDecimal($worthTotal, 2);
        $output['cost_profit'] = $this->getDecimal($costProfitTotal, 2); //持仓总成本
        $output['hold_profit'] = $this->getDecimal($holdProfitTotal, 2); //持仓总盈亏
        $output['hold_profit_rate'] = !empty($costProfitTotal) ? $this->getDecimal($holdProfitTotal / $costProfitTotal, 4) : 0; //持仓总盈亏率
        $output['accumulated_profit'] = $this->getDecimal($accumulatedProfitTotal, 2); //累积总盈亏
        $output['accumulated_profit_rate'] = !empty($buyTotalCostTotal) ? $this->getDecimal($accumulatedProfitTotal / $buyTotalCostTotal, 4) : 0; //累计总盈亏率
        $output['list'] = array_values($assetList);

        $this->success($output);
    }
}