<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * asset_place表
 */
class AssetPlace extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'asset_place';

    public function getList($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id And coin_id = :coin_id';
        return $this->queryAll($sql, $param);
    }

    public function insertAssetPlace($data, $number, $date)
    {
        $appendSql = 'ON DUPLICATE KEY UPDATE number=number+' . $number . ',update_at="' . $date . '"';
        return $this->insert($this->tableName, $data, '', $appendSql);
    }

    public function deleteAll($userId, $coinId)
    {
        $where = 'user_id = :user_id And coin_id = :coin_id';
        $whereParam = [
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        return $this->delete($this->tableName, $where, $whereParam);
    }
}