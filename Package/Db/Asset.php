<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * asset表
 */
class Asset extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'asset';

    public function getLine($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id And coin_id = :coin_id';
        return $this->query($sql, $param);
    }

    public function getList($param, $field = '*', $order = '', $limit = 0)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id';
        if (!empty($order)) $sql .= ' ORDER BY ' . $order;
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }

    public function getHistory($param, $field = '*', $order = '', $limit = 0)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id AND number = 0';
        if (!empty($order)) $sql .= ' ORDER BY ' . $order;
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }

    public function insertAsset($data)
    {
        return $this->insert($this->tableName, $data, 'ignore');
    }

    public function updateAsset($data, $where, $whereParam)
    {
        return $this->update($this->tableName, $data, $where, $whereParam);
    }

    public function deleteAll($userId, $coinId)
    {
        $where = 'user_id = :user_id And coin_id = :coin_id';
        $whereParam = [
            ':user_id' => $userId,
            ':coin_id' => $coinId
        ];
        return $this->delete($this->tableName, $where, $whereParam);
    }
}