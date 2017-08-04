<?php

namespace MyAPP\Controller\Api\Asset;

use MyAPP\Package\Db\AssetHistory;
use MyApp\Package\Db\Currency;

class actionHistory extends \MyAPP\Controller\Api
{
    public function main()
    {
        if (empty($this->userId)) {
            $this->error(1001, '用户未登录');
        }
        $userId = $this->userId;

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
            'user_id' => $userId
        ];

        $assetHistory= [];
        $dbAssetHistory = new AssetHistory();
        $rsAssetHistory = $dbAssetHistory->getList($param, '*', 'create_at desc', 1000);
        if (!empty($rsAssetHistory)) {
            foreach ($rsAssetHistory as $k => $v) {
                $assetHistory[$k]['coin_id'] = $v['coin_id'];
                $assetHistory[$k]['coin'] = isset($coinIdIndex[$v['coin_id']]) ? $coinIdIndex[$v['coin_id']] : ''; //币种缩写
                $assetHistory[$k]['accumulated_profit'] = $this->getDecimal($v['accumulated_profit']);
                $assetHistory[$k]['accumulated_profit_rate'] = $this->getDecimal($v['accumulated_profit_rate']);
            }
        }

        $output['list'] = array_values($assetHistory);

        $this->success($output);
    }
}