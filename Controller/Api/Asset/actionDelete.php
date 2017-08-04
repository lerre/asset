<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
use MyApp\Package\Db\AssetBuy;
use MyApp\Package\Db\AssetHistory;
use MyApp\Package\Db\AssetPlace;
use MyApp\Package\Db\AssetSell;
use MyApp\Package\Db\TransCount;
use MyApp\Package\Db\TransDetail;

/**
 * Class actionDelete
 *
 * 币种资产删除
 */
class actionDelete extends \MyAPP\Controller\Api
{
    public function main()
    {
        if ($this->isPost()) {
            if (empty($this->userId)) {
                $this->error(1001, '用户未登录');
            }
            $userId = $this->userId;

            $raw = $this->request->getRaw();
            $type = isset($raw['type']) ? $raw['type'] : '';
            $coinId = isset($raw['coin_id']) ? $raw['coin_id'] : '';

            if ($type == 'history') {
                $this->deleteAssetHistory($userId, $coinId);
            } else {
                $this->deleteAsset($userId, $coinId);
            }

            return $this->success([
                'msg' => '删除成功'
            ]);
        }
    }

    private function deleteAsset($userId, $coinId)
    {
        $dbAsset = new Asset();
        $dbAsset->deleteAll($userId, $coinId);

        $dbAssetSell = new AssetBuy();
        $dbAssetSell->deleteAll($userId, $coinId);

        $dbAssetPlace = new AssetPlace();
        $dbAssetPlace->deleteAll($userId, $coinId);

        $dbAssetSell = new AssetSell();
        $dbAssetSell->deleteAll($userId, $coinId);

        $dbTransCount = new TransCount();
        $dbTransCount->deleteAll($userId);

        $dbTransDetail = new TransDetail();
        $dbTransDetail->deleteAll($userId, $coinId);
    }

    private function deleteAssetHistory($userId, $coinId)
    {
        $dbAsset = new AssetHistory();
        $dbAsset->deleteAll($userId, $coinId);
    }
}