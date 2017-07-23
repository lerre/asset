<?php

namespace MyAPP\Package\Db;

use My\Package\Db;

/**
 * trans_detail表
 */
class TransDetail extends Db
{
    CONST TYPE_BUY = 1;
    CONST TYPE_SELL = 2;

    protected $databaseName = 'asset';
    protected $tableName = 'trans_detail';
    protected $tableAssetName = 'asset';

    public function getList($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName;
        return $this->queryAll($sql, $param);
    }

    public function getLatest($param, $field = '*', $order = '', $limit = 20)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName;
        if (!empty($order)) $sql .= ' ORDER BY ' . $order;
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }

    public function insertTransDetail($data)
    {
        return $this->insert($this->tableName, $data);
    }

    private function incrAsset($userId, $coinId, $number, $date)
    {
        $sql = 'UPDATE ' . $this->tableAssetName . ' SET number=number+' . $number . ',update_at="' . $date . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    private function decrAsset($userId, $coinId, $number, $date)
    {
        $sql = 'UPDATE ' . $this->tableAssetName . ' SET number=number-' . $number . ',update_at="' . $date . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    public function buy($userId, $coinId, $number, $price, $place, $cost, $date)
    {
        try {
            $this->beginTransaction();

            $res = $this->incrAsset($userId, $coinId, $number, $date);
            if (empty($res)) {
                $this->rollback();
            }

            $data = [
                'user_id' => $userId,
                'type' => self::TYPE_BUY,
                'coin_id' => $coinId,
                'number' => $number,
                'price' => $price,
                'place' => $place,
                'cost' => $cost,
                'create_at' => $date,
                'update_at' => $date
            ];
            $res = $this->insertTransDetail($data);
            if (empty($res)) {
                $this->rollback();
            }

            $this->commit();
            $this->endTransaction();
        } catch (\PDOException $e) {
            $this->rollback();
            $this->endTransaction();
            return false;
        }

        return true;
    }

    public function sell($userId, $coinId, $number, $price, $cost, $date)
    {
        try {
            $this->beginTransaction();

            $res = $this->decrAsset($userId, $coinId, $number, $date);
            if (empty($res)) {
                $this->rollback();
            }

            $data = [
                'user_id' => $userId,
                'type' => self::TYPE_SELL,
                'coin_id' => $coinId,
                'number' => $number,
                'price' => $price,
                'cost' => $cost,
                'create_at' => $date,
                'update_at' => $date
            ];
            $res = $this->insertTransDetail($data);
            if (empty($res)) {
                $this->rollback();
            }

            $this->commit();
            $this->endTransaction();
        } catch (\PDOException $e) {
            $this->rollback();
            $this->endTransaction();
            return false;
        }

        return true;
    }
}