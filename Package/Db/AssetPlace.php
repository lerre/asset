<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * AssetPlace表
 */
class AssetPlace extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'asset_place';

    public function getList($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName;
        return $this->queryAll($sql, $param);
    }

    public function insertAssetPlace($data, $number, $date)
    {
        $appendSql = 'ON DUPLICATE KEY UPDATE number=number+' . $number . ',update_at="' . $date . '"';
        return $this->insert($this->tableName, $data, '', $appendSql);
    }
}