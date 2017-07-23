<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * trans_count表
 */
class TransCount extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'trans_count';

    public function getLine($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id=:user_id AND date=:date LIMIT 1';
        return $this->query($sql, $param);
    }

    public function insertTransCount($data)
    {
        return $this->insert($this->tableName, $data, 'ignore');
    }

    public function incrTransCount($userId, $count = 1)
    {
        $data = 'count=count+' . $count;
        $where = 'user_id=?';
        $whereParam = $userId;
        return $this->update($this->tableName, $data, $where, $whereParam);
    }
}