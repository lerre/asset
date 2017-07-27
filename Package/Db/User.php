<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * user表
 */
class User extends Db
{
    protected $databaseName = 'asset';
    protected $tableName = 'user';
    protected $fields = [
        'id',
        'openid',
        'session_key',
        'nickname',
        'avatar',
        'gender',
        'country',
        'province',
        'city',
        'reg_time',
        'last_login_time',
        'last_login_ip',
        'login_times',
    ];
    protected $primaryKey = 'id';

    public function getLine($param, $field)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE id = :id';
        return $this->query($sql, $param);
    }

    public function checkExist($openId, $field)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE openid = ' . $openId . ' LIMIT 1';
        return $this->query($sql);
    }

    public function register($data)
    {
        return $this->insert($this->tableName, $data, 'ignore');
    }

    public function login($data, $userId)
    {
        $where = 'id=:id';
        $whereParam = [':id' => $userId];
        return $this->update($this->tableName, $data, $where, $whereParam);
    }
}