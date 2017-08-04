<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetBuy;
use MyApp\Package\Db\TransCount;
use MyApp\Package\Db\TransDetail;
use MyApp\Package\Db\AssetPlace;

class actionBuy extends \MyAPP\Controller\Api
{
    CONST TYPE_BUY = 1;
    CONST TYPE_SELL = 2;
    CONST BUY_COUNT_MAX = 50;

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
            $place = isset($raw['place']) ? $raw['place'] : '';
            $date = isset($raw['date']) ? $raw['date'] : '';
            $date = date('Y-m-d', strtotime($date));

            if ($price <= 0 || $number <= 0 || $date <= '1970-01-01') {
                $this->error(1001, '参数错误~');
            }

            //初始化trans_count
            $dbTransCount = new TransCount();
            $param = [
                'user_id' => $userId,
                'date' => $date
            ];
            $res = $dbTransCount->getLine($param, 'count');
            if (empty($res)) {
                $dbTransCount->insertTransCount([
                    'user_id' => $userId,
                    'date' => $date,
                    'create_at' => $currDate,
                    'update_at' => $currDate
                ]);
            } elseif ($res['count'] >= self::BUY_COUNT_MAX) {
                $this->error(1001, '今日买入已达上限，请明日再来~');
            }

            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];

            //初始化asset
            $dbAsset = new Asset();
            $rsAsset = $dbAsset->getLine($param, 'number,cost');
            if (empty($rsAsset)) {
                $dbAsset->insertAsset([
                    'user_id' => $userId,
                    'coin_id' => $coinId,
                    'create_at' => $currDate,
                    'update_at' => $currDate
                ]);
            }

            //初始化asset_buy
            $dbAssetBuy = new AssetBuy();
            $rsAssetBuy = $dbAssetBuy->getLine($param, 'number,total_cost');
            if (empty($rsAssetBuy)) {
                $dbAssetBuy->insertAssetBuy([
                    'user_id' => $userId,
                    'coin_id' => $coinId,
                    'create_at' => $currDate,
                    'update_at' => $currDate
                ]);
            }

            //买入
            $dbTransDetail = new TransDetail();
            $res = $dbTransDetail->buy($userId, $date, $coinId, $number, $price, $place);
            if (empty($res)) {
                return $this->error(1002, '买入失败');
            }

            //交易计数+1
            $dbTransCount->incrTransCount($userId);

            //更新成本均价
            $res = $dbAsset->getLine($param, 'number,cost');
            if (!empty($res) && isset($res['cost']) && $res['cost'] != '0.00') {
                $cost = !empty($res['number']) ? $this->getDecimal($rsAssetBuy['total_cost'] / $rsAssetBuy['number']) : 0.00;
                $data = [
                    'cost' => $cost
                ];
                $where = 'user_id=:user_id AND coin_id=:coin_id';
                $whereParam = [
                    ':user_id' => $userId,
                    ':coin_id' => $coinId
                ];
                $dbAsset->updateAsset($data, $where, $whereParam);
            }

            //记录交易来源
            $dbAssetPlace = new AssetPlace();
            $data = [
                'user_id' => $userId,
                'coin_id' => $coinId,
                'place' => $place,
                'number' => $number,
                'create_at' => $date,
                'update_at' => $date
            ];
            $dbAssetPlace->insertAssetPlace($data, $number, $date);

            return $this->success([
                'msg' => '买入成功'
            ]);
        }
    }
}