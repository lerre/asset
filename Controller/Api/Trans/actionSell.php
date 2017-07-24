<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\Asset;
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

            $raw = $this->request->getRaw();
            $coinId = $raw['coin_id'];
            $number = $raw['number'];
            $price = $raw['price'];
            $date = $raw['date'];
            $date = date('Y-m-d', strtotime($date));

            if ($price <= 0 || $number <= 0 || $date <= '1970-01-01') {
                $this->error(1001, '参数错误~');
            }

            $dbAsset = new Asset();
            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];
            $res = $dbAsset->getLine($param, 'number,cost');
            if (empty($res)) {
                $this->error('币数不足');
            } elseif ($res['number'] < $number) {
                $this->error('币数不足');
            }
            $cost = isset($res['cost']) ? (float)$res['cost'] : 0.00;

            $dbTransDetail = new TransDetail();
            $res = $dbTransDetail->sell($userId, $date, $coinId, $number, $price, $cost);
            if (empty($res)) {
                $this->error('卖出失败');
            }

            //更新成本均价
            $res = $dbAsset->getLine($param, 'number,cost');
            if ($res) {
                if ($res['cost'] > 0.00 && $res['number'] > $number) {
                    $total = round($res['number'] * $res['cost'] - $number * $price, 2);
                    $data = [
                        'cost' => round($total / ($res['number'] - $number), 2)
                    ];
                } else {
                    $data = [
                        'cost' => 0.00
                    ];
                }
                $where = 'user_id=:user_id AND coin_id=:coin_id';
                $whereParam = [
                    ':user_id' => $userId,
                    ':coin_id' => $coinId
                ];
                $dbAsset->updateAsset($data, $where, $whereParam);
            }

            $this->success([
                'msg' => '卖出成功'
            ]);
        }
    }
}