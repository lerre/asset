<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetBuy;
use MyApp\Package\Db\AssetHistory;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\TransDetail;

class actionSell extends \MyAPP\Controller\Api
{
    CONST TYPE_BUY = 1;
    CONST TYPE_SELL = 2;

    public function main()
    {
        if ($this->isPost())
        {
            if (empty($this->userId)) {
                $this->error(1001, '用户未登录');
            }

            $userId = $this->userId;
            $currDate = date('Y-m-d H:i:s');

            $raw = $this->request->getRaw();
            $coinId = isset($raw['coin_id']) ? $raw['coin_id'] : '';
            $number = isset($raw['number']) ? $raw['number'] : '';
            $price = isset($raw['price']) ? $raw['price'] : '';
            $date = isset($raw['date']) ? $raw['date'] : '';
            $date = date('Y-m-d', strtotime($date));

            if ($price <= 0 || $number <= 0 || $date <= '1970-01-01') {
                return $this->error(1001, '参数错误~');
            }

            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];

            //初始化asset，拦截
            $dbAsset = new Asset();
            $rsAsset = $dbAsset->getLine($param, 'number,cost');
            if (empty($rsAsset)) {
                return $this->error(1002, '币数不足');
            } elseif ($rsAsset['number'] < $number) {
                return $this->error(1003, '币数不足');
            }

            //初始化asset_sell
            $dbAssetSell = new AssetSell();
            $rsAssetSell = $dbAssetSell->getLine($param, 'number,total_profit');
            if (empty($rsAssetSell)) {
                $dbAssetSell->insertAssetSell([
                    'user_id' => $userId,
                    'coin_id' => $coinId,
                    'create_at' => $currDate,
                    'update_at' => $currDate
                ]);
            }

            //卖出
            $dbTransDetail = new TransDetail();
            $res = $dbTransDetail->sell($userId, $date, $coinId, $number, $price);
            if (empty($res)) {
                return $this->error(1004, '卖出失败');
            }

            //清仓处理
            if ($rsAsset['number'] == $number) {
                //买入
                $dbAssetBuy = new AssetBuy();
                $rsAssetBuy = $dbAssetBuy->getLine($param, 'number,total_cost');
                $buyTotalCost = $rsAssetBuy['total_cost'];
                $buyNumber = $rsAssetBuy['number'];
                //最新价
                $currPrice = $this->getPrice($coinId);
                //总市值: 最新价 * 持币数
                $worth = ($rsAsset['number'] - $number) * $currPrice;
                $sellTotalProfit = $rsAssetSell['total_profit'] + $number * $price;
                //持币成本单价
                if (isset($rsAsset['cost']) && $rsAsset['cost'] == '0.00') {
                    $cost = !empty($buyNumber) ? $this->getDecimal($buyTotalCost / $buyNumber) : 0.00;
                }
                //持仓成本 = 持仓成本单价 * 持仓数
                $costProfit = $this->getDecimal(($rsAsset['number'] - $number) * $cost);
                //累积盈亏
                $accumulatedProfit = $worth + $sellTotalProfit - $buyNumber * $cost;
                //累积盈亏率
                $accumulatedProfitRate = !empty($costProfit) ? $this->getDecimal($accumulatedProfit / $costProfit) : 0; //累计盈亏率

                //清仓
                $dbAssetHistory = new AssetHistory();
                $data = [
                    'user_id' => $userId,
                    'coin_id' => $coinId,
                    'accumulated_profit' => $accumulatedProfit,
                    'accumulated_profit_rate' => $accumulatedProfitRate
                ];
                $dbAssetHistory->insertAssetHistory($data);

                $dbAssetBuy = new AssetBuy();
                $dbAssetBuy->deleteAll($userId, $coinId);

                $dbAsset->deleteAll($userId, $coinId);
                $dbAssetSell->deleteAll($userId, $coinId);
            }

            return $this->success([
                'msg' => '卖出成功'
            ]);
        }
    }
}