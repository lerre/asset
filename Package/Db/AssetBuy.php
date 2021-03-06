<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

class AssetBuy extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'asset_buy';

    public function getLine($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id And coin_id = :coin_id';
        return $this->query($sql, $param);
    }

    public function getList($param, $field = '*', $order = '', $limit = 20)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id';
        if (!empty($order)) $sql .= ' ORDER BY ' . $order;
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }

    public function insertAssetBuy($data)
    {
        return $this->insert($this->tableName, $data, 'ignore');
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