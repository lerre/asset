<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\Asset;
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
            $coinId = $raw['coin_id'];
            $number = $raw['number'];
            $price = $raw['price'];
            $date = $raw['date'];
            $date = date('Y-m-d', strtotime($date));

            if ($price <= 0 || $number <= 0 || $date <= '1970-01-01') {
                return $this->error(1001, '参数错误~');
            }

            $dbAsset = new Asset();
            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];
            $res = $dbAsset->getLine($param, 'profit,number,cost');
            if (empty($res)) {
                return $this->error(1002, '币数不足');
            } elseif ($res['number'] < $number) {
                return $this->error(1003, '币数不足');
            }

            //初始化asset_sell
            $dbAssetSell = new AssetSell();
            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];
            $res = $dbAssetSell->getLine($param, 'profit');
            if (empty($res)) {
                $dbAssetSell->insertAssetSell([
                    'user_id' => $userId,
                    'coin_id' => $coinId,
                    'create_at' => $currDate,
                    'update_at' => $currDate
                ]);
            }

            $dbTransDetail = new TransDetail();
            $res = $dbTransDetail->sell($userId, $date, $coinId, $number, $price);
            if (empty($res)) {
                return $this->error(1004, '卖出失败');
            }

            return $this->success([
                'msg' => '卖出成功'
            ]);
        }
    }
}