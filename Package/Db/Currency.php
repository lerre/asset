<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * currency表
 */
class Currency extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'currency';

    public function getList($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName;
        return $this->queryAll($sql, $param);
    }

    public function insertCurrency($data)
    {
        return $this->insert($this->tableName, $data);
    }
}