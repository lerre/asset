<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * price表
 */
class Price extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'price';

    public function getLine($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE date = :date AND coin_id = :coin_id';
        return $this->query($sql, $param);
    }

    public function insertPrice($data)
    {
        return $this->insert($this->tableName, $data, 'ignore');
    }
}