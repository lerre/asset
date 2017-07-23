<?php

namespace MyAPP\Controller\Api;

class Error extends \MyAPP\Controller\Api
{
    protected function main()
    {
        echo 404;
        exit;
    }

    public function before()
    {

    }

    public function after()
    {

    }
}