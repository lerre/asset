<?php

namespace MyAPP\Controller\Api\Asset;

use MyApp\Package\Db\Currency;
use MyApp\Package\Db\Price;

class actionPrice extends \MyAPP\Controller\Api
{
    public function main()
    {
        $dbCurrency = new Currency();
        $rsCurrency = $dbCurrency->getList('coin_id');
        if (!empty($rsCurrency)) {
            $dbPrice = new Price();
            foreach ($rsCurrency as $k => $v) {
                if(!empty($v['coin_id'])) {
                    $coinId = $v['coin_id'];
                    $price = $this->getPrice($coinId);
                    $data = [
                        'date' => date('Y-m-d'),
                        'coin_id' => $coinId,
                        'price' => $price
                    ];
                    $dbPrice->insertPrice($data);
                }

            }
        }
    }
}