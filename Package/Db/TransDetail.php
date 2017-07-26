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
    protected $tableAssetSellName = 'asset_sell';

    public function getList($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE ';
        return $this->queryAll($sql, $param);
    }

    public function getPaginationList($userId, $maxId, $field = '*', $limit = 20)
    {
        $param['user_id'] = $userId;
        $sqlAppend = '';
        if (!empty($maxId)) {
            $param['id'] = $maxId;
            $sqlAppend = 'AND id<:id';
        }
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id=:user_id ' . $sqlAppend . ' ORDER BY id DESC';
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }

    public function insertTransDetail($data)
    {
        return $this->insert($this->tableName, $data);
    }

    private function incrAsset($userId, $coinId, $number, $profit)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetName . ' SET number=number+' . $number . ',profit=profit+' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    private function decrAsset($userId, $coinId, $number, $profit)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetName . ' SET number=number-' . $number . ',profit=profit-' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    private function incrAssetSell($userId, $coinId, $profit)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetSellName . ' SET profit=profit+' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    public function buy($userId, $date, $coinId, $number, $price, $place, $cost)
    {
        $currDate = date('Y-m-d H:i:s');

        //持币成本增量
        $costProfit = $cost > 0.00 ? $number * $cost : $number * $price;

        try {
            $this->beginTransaction();

            $data = [
                'user_id' => $userId,
                'date' => $date,
                'type' => self::TYPE_BUY,
                'coin_id' => $coinId,
                'number' => $number,
                'price' => $price,
                'place' => $place,
                'cost' => $cost,
                'create_at' => $currDate,
                'update_at' => $currDate
            ];
            $res = $this->insertTransDetail($data);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->incrAsset($userId, $coinId, $number, $costProfit);
            if (empty($res)) {
                $this->rollback();
                return false;
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

    public function sell($userId, $date, $coinId, $number, $price, $cost)
    {
        $currDate = date('Y-m-d H:i:s');

        //持币成本减量
        $costProfit = $cost > 0.00 ? $number * $cost : $number * $price;

        //卖出盈亏增量
        $sellProfit = $number * ($price - $cost);

        try {
            $this->beginTransaction();

            $data = [
                'user_id' => $userId,
                'date' => $date,
                'type' => self::TYPE_SELL,
                'coin_id' => $coinId,
                'number' => $number,
                'price' => $price,
                'cost' => $cost,
                'create_at' => $currDate,
                'update_at' => $currDate
            ];
            $res = $this->insertTransDetail($data);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->decrAsset($userId, $coinId, $number, $costProfit);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->incrAssetSell($userId, $coinId, $sellProfit);
            if (empty($res)) {
                $this->rollback();
                return false;
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