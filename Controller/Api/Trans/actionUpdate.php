<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\TransCount;
use MyApp\Package\Db\Asset;
use MyApp\Package\Db\TransDetail;
use MyApp\Package\Db\AssetPlace;

class actionUpdate extends \MyAPP\Controller\Api
{
    public function main()
    {
        if ($this->isPost())
        {
            return $this->success([
                'msg' => '编辑成功'
            ]);
        }
    }
}