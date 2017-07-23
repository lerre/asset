<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Asset;

class actionList extends \MyAPP\Controller\Api
{
    public function main()
    {
        if (empty($this->userId)) {
            $this->error(1001, '用户未登录');
        }
        $userId = $this->userId;

        $output = [];

        $dbAsset = new Asset();
        $param = [
            'user_id' => $userId
        ];
        $field = 'coin_id,number,cost';
        $order = 'create_at DESC';
        $rsAssetList = $dbAsset->getList($param, $field, $order);

        $assetList = [];
        if (!empty($rsAssetList)) {
            foreach ($rsAssetList as $k => $v) {
                $coinId = !empty($v['coin_id']) ? $v['coin_id'] : '';
                $number = !empty($v['number']) ? $v['number'] : 0;
                $price = $this->getPrice($coinId);
                $cost = $this->getPastPrice($coinId);
                //持币盈亏
                $holdProfile = ($price - $cost) * $number;
                if ($coinId) {
                    $assetList[$k]['coin_id'] = $coinId;
                    $assetList[$k]['price'] = $price; //最新价
                    $assetList[$k]['worth'] = $price * $number; //市值
                    $assetList[$k]['cost'] = $cost; //成本价
                    $assetList[$k]['accumulated_profile'] = $holdProfile;
                }
            }
        }

        $output['worth'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['curr_profit'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['hold_profit'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['accumulated_profit'] = round(mt_rand(100000, 100000000) . '.' . mt_rand(10, 99), 2);
        $output['accumulated_profile_rate'] = sprintf('%s%%', round(10, 99));
        $output['list'] = $assetList;

        $this->success($output);
    }

    private function getPastPrice($coinId)
    {
        return mt_rand(100, 100000);
    }
}