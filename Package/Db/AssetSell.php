<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * asset_sell表
 */
class AssetSell extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'asset_sell';

    public function getLine($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName;
        return $this->query($sql, $param);
    }

    public function getList($param, $field = '*', $order = '', $limit = 20)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName;
        if (!empty($order)) $sql .= ' ORDER BY ' . $order;
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }
}