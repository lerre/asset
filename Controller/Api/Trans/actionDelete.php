<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\TransDetail;

class actionDelete extends \MyAPP\Controller\Api
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

            if (empty($id)) {
                $this->error(1002, '交易ID错误~');
            }
            if (!in_array($type, [self::TYPE_BUY, self::TYPE_SELL])) {
                $this->error(1003, '交易类型错误~');
            }

            $dbTransDetail = new TransDetail();
            $param = [
                'user_id' => $userId,
                'coin_id' => $coinId,
                'type' => $type
            ];
            $rsTransDetail = $dbTransDetail->getLine($param, 'number,price,place,cost');
            if (empty($rsTransDetail)) {
                return $this->error(1003, '交易记录不存在');
            }

            if ($type == self::TYPE_BUY) {
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
                $rs = $dbTransDetail->transBuyDelete($userId, $coinId, $id, $rsTransDetail);
                if (empty($rs)) {
                    return $this->error(1003, '编辑失败');
                }
            } else {
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
                $rs = $dbTransDetail->transSellDelete($userId, $coinId, $id, $rsTransDetail);
                if (empty($rs)) {
                    return $this->error(1003, '编辑失败');
                }
            }

            return $this->success([
                'msg' => '买入成功'
            ]);
        }
    }
}