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

            $raw = $this->request->getRaw();
            $id = isset($raw['id']) ? $raw['id'] : '';
            $type = isset($raw['type']) ? $raw['type'] : '';
            $coinId = isset($raw['coin_id']) ? $raw['coin_id'] : '';

            if (empty($id)) {
                $this->error(1002, '交易ID无效');
            }
            if (!in_array($type, [self::TYPE_BUY, self::TYPE_SELL])) {
                $this->error(1003, '交易类型错误');
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
                return $this->error(1004, '交易记录不存在');
            }

            if ($type == self::TYPE_BUY) {
                //初始化asset
                $dbAsset = new Asset();
                $param = [
                    'user_id' => $userId,
                    'coin_id' => $coinId
                ];
                $res = $dbAsset->getLine($param, 'id,number');
                if (empty($res) || $res['number'] < $rsTransDetail['number']) {
                    return $this->error(1005, '币数不足，无法删除');
                }
                $rs = $dbTransDetail->transBuyDelete($userId, $coinId, $id, $rsTransDetail);
                if (empty($rs)) {
                    return $this->error(1005, '删除失败');
                }
            } else {
                //初始化asset_sell
                $dbAssetSell = new AssetSell();
                $param = [
                    'user_id' => $userId,
                    'coin_id' => $coinId
                ];
                $res = $dbAssetSell->getLine($param, 'id');
                if (empty($res)) {
                    return $this->error(1005, '卖出不存在，无法删除');
                }
                $rs = $dbTransDetail->transSellDelete($userId, $coinId, $id, $rsTransDetail);
                if (empty($rs)) {
                    return $this->error(1006, '删除失败');
                }
            }

            return $this->success([
                'msg' => '删除成功'
            ]);
        }
    }
}