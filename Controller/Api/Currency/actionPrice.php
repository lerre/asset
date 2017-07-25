<?php

namespace MyAPP\Controller\Api\Currency;

class actionPrice extends \MyAPP\Controller\Api
{
    public function main()
    {
        $coinId = $this->request->getRequest()->string('coin_id');
        $data = $this->getPrice($coinId);
        return $this->success($data);
    }
}