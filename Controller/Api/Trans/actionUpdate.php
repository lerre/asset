<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\TransDetail;

class actionUpdate extends \MyAPP\Controller\Api
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
            $id = isset($raw['id']) ? $raw['id'] : '';
            $type = isset($raw['type']) ? $raw['type'] : '';
            $coinId = isset($raw['coin_id']) ? $raw['coin_id'] : '';
            $number = isset($raw['number']) ? $raw['number'] : '';
            $price = isset($raw['price']) ? (float)$raw['price'] : 0.00;

            if (empty($id) || $price <= 0.00 || $number <= 0) {
                $this->error(1002, '参数错误~');
            }

            //初始化asset
            $dbAsset = new Asset();
            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];
            $res = $dbAsset->getLine($param, 'profit,number,cost');
            if (empty($res)) {
                $dbAsset->insertAsset([
                    'user_id' => $userId,
                    'coin_id' => $coinId,
                    'create_at' => $currDate,
                    'update_at' => $currDate
                ]);
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
            $param = [
                'id' => $id,
                'user_id' => $userId,
                'coin_id' => $coinId,
                'type' => $type
            ];
            $rsTransDetail = $dbTransDetail->getById($param, 'number,price');
            if (empty($rsTransDetail)) {
                return $this->error(1003, '交易记录不存在');
            }

            if ($type == self::TYPE_BUY) {
                if (empty($res) || $res['number'] < $rsTransDetail['number']) {
                    return $this->error(1005, '资产受限，无法操作');
                }
                $rs = $dbTransDetail->transBuyUpdate($userId, $coinId, $number, $price, $id, $rsTransDetail);
                if (empty($rs)) {
                    return $this->error(1004, '编辑失败');
                }
            } else {
                $rs = $dbTransDetail->transSellUpdate($userId, $coinId, $number, $price, $id, $rsTransDetail);
                if (empty($rs)) {
                    return $this->error(1005, '编辑失败');
                }
            }

            return $this->success([
                'msg' => '编辑成功'
            ]);
        }
    }
}