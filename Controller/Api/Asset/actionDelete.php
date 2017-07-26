<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;
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
            $coinId = $this->request->getRequest()->string('coin_id');

            $dbAsset = new Asset();
            $dbAsset->deleteAll($userId, $coinId);

            $dbAssetPlace = new AssetPlace();
            $dbAssetPlace->deleteAll($userId, $coinId);

            $dbAssetSell = new AssetSell();
            $dbAssetSell->deleteAll($userId, $coinId);

            $dbTransCount = new TransCount();
            $dbTransCount->deleteAll($userId);

            $dbTransDetail = new TransDetail();
            $dbTransDetail->deleteAll($userId, $coinId);

            return $this->success([
                'msg' => '删除成功'
            ]);
        }
    }
}