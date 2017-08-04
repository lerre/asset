<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetBuy;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\AssetPlace;
use MyApp\Package\Db\Currency;
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
        $maxId = $this->request->getRequest()->int('max_id');
        $pageSize = $this->request->getRequest()->int('page_size', 10);
        if ($pageSize > 10) {
            $pageSize = 10;
        }

        if (empty($coinId)) {
            return $this->error(1002, '请指定币种');
        }

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
            'user_id' => $userId,
            'coin_id' => $coinId
        ];

        //持仓数和持仓成本单价
        $dbAsset = new Asset();
        $rsAsset = $dbAsset->getLine($param, 'number,cost');

        //买入
        $dbAssetBuy = new AssetBuy();
        $rsAssetBuy = $dbAssetBuy->getLine($param, 'total_cost,number');

        //卖出
        $dbAssetSell = new AssetSell();
        $rsAssetSell = $dbAssetSell->getLine($param, 'total_profit,number');

        //持仓数
        $number = isset($rsAsset['number']) ? (int)$rsAsset['number'] : 0;
        //持仓成本单价
        $cost = isset($rsAsset['cost']) ? (float)$rsAsset['cost'] : 0.00;
        //最新价
        $price = $this->getPrice($coinId);

        //买入交易总币数
        $buyNumber = isset($rsAssetBuy['number']) ? (int)$rsAssetBuy['number'] : 0;
        //买入交易总成本
        $buyTotalCost = isset($rsAssetBuy['total_cost']) ? (float)$rsAssetBuy['total_cost'] : 0.00;

        //卖出交易总币数
        $sellNumber = isset($rsAssetSell['number']) ? (int)$rsAssetSell['number'] : 0;
        //卖出交易总成本
        $sellTotalProfit = isset($rsAssetSell['total_profit']) ? (float)$rsAssetSell['total_profit'] : 0.00;

        //持币成本单价
        if ($cost == 0.00) {
            $cost = !empty($buyNumber) ? $this->getDecimal($buyTotalCost / $buyNumber) : 0.00;
        }

        //总市值: 最新价 * 持币数
        $worth = $this->getDecimal($price * $number);

        //持仓成本 = 持仓成本单价 * 持仓数
        $costProfit = $this->getDecimal($cost * $number);

        //持仓盈亏 = (最新价 - 持仓成本单价) * 持仓数
        $holdProfit = $this->getDecimal(($price - $cost) * $number);

        //累积盈亏 = 总市值 + 卖出交易总成本 - 买入交易总币数 * 持仓成本单价
        $accumulatedProfit = $worth + $sellTotalProfit - $buyNumber * $cost;

        $output['worth'] = $worth; //总市值
        $output['price'] = $price; //最新价
        $output['number'] = $number; //持币数
        $output['cost'] = $cost; //成本价
        $output['cost_profit'] = $costProfit; //持仓成本
        $output['hold_profit'] = $holdProfit; //持仓盈亏
        $output['hold_profit_rate'] = !empty($costProfit) ? $this->getDecimal($holdProfit / $costProfit) : 0; //持仓盈亏率
        $output['accumulated_profit'] = $accumulatedProfit; //累积盈亏
        $output['accumulated_profit_rate'] = !empty($costProfit) ? $this->getDecimal($accumulatedProfit / $costProfit) : 0; //累计盈亏率

        if (!in_array($coinId, $coinIdList)) {
            $output['included'] = false;
            $output['number'] = $number; //持币数
            $output['cost'] = $cost; //成本价
        } else {
            $output['included'] = true;
        }

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
                if (empty($percent)) {
                    continue;
                }
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
        $rsTransDetail = $dbTransDetail->getPaginationList($userId, $coinId, $maxId, 'id,type,coin_id,number,price,date', $pageSize);

        $idArr = [];
        $assetTransList = [];
        $assetTransSellList = [];
        if (!empty($rsTransDetail)) {
            foreach ($rsTransDetail as $k => $v) {
                $id = (int)$v['id'];
                $idArr[] = $id;
                $assetTransList[$k]['id'] = $id;
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
                $assetList[$k]['coin'] = isset($coinIdIndex[$coinId]) ? $coinIdIndex[$coinId] : ''; //币种缩写
                $assetTransList[$k]['number'] = !empty($v['number']) ? (int)$v['number'] : 0;
                $assetTransList[$k]['price'] = !empty($v['price']) ? (float)$v['price'] : 0.00;
                $assetTransList[$k]['sum'] = round($assetTransList[$k]['number'] * $assetTransList[$k]['price'], 2);
                $assetTransList[$k]['date'] = !empty($v['date']) ? date('Y-m-d', strtotime($v['date'])) : '';
                if ($type == self::TYPE_SELL) {
                    $assetTransSellList[$k] = $assetTransList[$k];
                }
            }
        }

        $output['asset_place_list'] = $assetPlaceList;
        $output['asset_trans_list'] = $assetTransList;
        if (!empty($idArr)) {
            $output['min_id'] = min($idArr);
        }

        $this->success($output);
    }
}