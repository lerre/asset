<?php

namespace MyAPP\Controller\Api\Trans;

use MyApp\Package\Db\TransCount;
use MyApp\Package\Db\Asset;
use MyApp\Package\Db\TransDetail;
use MyApp\Package\Db\AssetPlace;

class actionDelete extends \MyAPP\Controller\Api
{
    public function main()
    {
        if ($this->isPost())
        {
            return $this->success([
                'msg' => '删除成功'
            ]);
        }
    }
}