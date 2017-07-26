<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;

/**
 * Class actionCost
 *
 * 币种成本编辑
 */
class actionCost extends \MyAPP\Controller\Api
{
    public function main()
    {
        if ($this->isPost()) {

            if (empty($this->userId)) {
                return $this->error(1001, '用户未登录');
            }

            $userId = $this->userId;

            $raw = $this->request->getRaw();
            $coinId = isset($raw['coin_id']) ? $raw['coin_id'] : '';
            $cost = isset($raw['coin_id']) ? (float)$raw['cost'] : 0.00;

            if (empty($coinId)) {
                return $this->error(1002, '请指定币种');
            }
            if (empty($cost)) {
                return $this->error(1002, '参数不合法');
            }

            $dbAsset = new Asset();
            $data = [
                'cost' => round($cost, 2)
            ];
            $where = 'user_id = :user_id AND coin_id = :coin_id';
            $whereParam = [
                'user_id' => $userId,
                'coin_id' => $coinId
            ];
            $dbAsset->updateAsset($data, $where, $whereParam);

            return $this->success([
                'msg' => '操作成功'
            ]);
        }
    }
}