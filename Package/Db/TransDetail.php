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

    public function getLine($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id AND coin_id = :coin_id AND type = :type';
        return $this->query($sql, $param);
    }

    public function getById($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE id = :id AND :user_id AND coin_id = :coin_id AND type = :type';
        return $this->query($sql, $param);
    }

    public function getList($param, $field = '*')
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE ';
        return $this->queryAll($sql, $param);
    }

    public function getPaginationList($userId, $coinId, $maxId, $field = '*', $limit = 20)
    {
        $param['user_id'] = $userId;
        $param['coin_id'] = $coinId;
        $sqlAppend = '';
        if (!empty($maxId)) {
            $param['id'] = $maxId;
            $sqlAppend = 'AND id<:id';
        }
        $sql = 'SELECT ' . $field . ' FROM ' . $this->tableName . ' WHERE user_id = :user_id AND coin_id = :coin_id ' . $sqlAppend . ' ORDER BY id DESC';
        if (!empty($limit)) $sql .= ' LIMIT ' . $limit;
        return $this->queryAll($sql, $param);
    }

    public function insertTransDetail($data)
    {
        return $this->insert($this->tableName, $data);
    }

    public function updateTransDetail($data, $where, $whereParam)
    {
        return $this->update($this->tableName, $data, $where, $whereParam);
    }

    public function deleteTransDetail($id)
    {
        $where = 'id = :id';
        $whereParam = ['id' => $id];
        return $this->delete($this->tableName, $where, $whereParam);
    }

    private function incrAsset($userId, $coinId, $number, $profit = 0.00)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetName . ' SET number=number+' . $number . ',profit=profit+' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    private function decrAsset($userId, $coinId, $number, $profit = 0.00)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetName . ' SET number=number-' . $number . ',profit=profit-' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    private function incrAssetSell($userId, $coinId, $profit = 0.00)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetSellName . ' SET profit=profit+' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    private function decrAssetSell($userId, $coinId, $profit = 0.00)
    {
        $currDate = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . $this->tableAssetSellName . ' SET profit=profit-' . $profit . ',update_at="' . $currDate . '" WHERE user_id=' . $userId . ' AND coin_id="' . $coinId . '" LIMIT 1';
        return $this->exec('UPDATE', $sql);
    }

    public function buy($userId, $date, $coinId, $number, $price, $place)
    {
        $currDate = date('Y-m-d H:i:s');

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
                'create_at' => $currDate,
                'update_at' => $currDate
            ];
            $res = $this->insertTransDetail($data);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

//            $res = $this->incrAsset($userId, $coinId, $number, $number * $price);
//            if (empty($res)) {
//                $this->rollback();
//                return false;
//            }

            $this->commit();
            $this->endTransaction();
        } catch (\PDOException $e) {
            $this->rollback();
            $this->endTransaction();
            return false;
        }

        return true;
    }

    public function sell($userId, $date, $coinId, $number, $price)
    {
        $currDate = date('Y-m-d H:i:s');

        $profit = $number * $price;

        try {
            $this->beginTransaction();

            $data = [
                'user_id' => $userId,
                'date' => $date,
                'type' => self::TYPE_SELL,
                'coin_id' => $coinId,
                'number' => $number,
                'price' => $price,
                'create_at' => $currDate,
                'update_at' => $currDate
            ];
            $res = $this->insertTransDetail($data);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->incrAssetSell($userId, $coinId, $profit);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

//            $res = $this->decrAsset($userId, $coinId, $number);
//            if (empty($res)) {
//                $this->rollback();
//                return false;
//            }

            $this->commit();
            $this->endTransaction();
        } catch (\PDOException $e) {
            $this->rollback();
            $this->endTransaction();
            return false;
        }

        return true;
    }

    public function transBuyUpdate($userId, $coinId, $number, $price, $id, $dataTransDetail)
    {
        $currDate = date('Y-m-d H:i:s');

        $numberDiff = $number - $dataTransDetail['number'];
        $profitDiff = $number * $price - $dataTransDetail['number'] * $dataTransDetail['price'];

        try {
            $this->beginTransaction();

            $data = [
                'number' => $number,
                'price' => $price,
                'update_at' => $currDate
            ];
            $where = 'id = :id';
            $whereParam = [
                'id' => $id,
            ];
            $res = $this->updateTransDetail($data, $where, $whereParam);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->incrAsset($userId, $coinId, $numberDiff, $profitDiff);
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

    public function transSellUpdate($userId, $coinId, $number, $price, $id, $dataTransDetail)
    {
        $currDate = date('Y-m-d H:i:s');

        $numberDiff = $number - $dataTransDetail['number'];
        $profitDiff = $number * $price - $dataTransDetail['number'] * $dataTransDetail['price'];

        try {
            $this->beginTransaction();

            $data = [
                'number' => $number,
                'price' => $price,
                'update_at' => $currDate
            ];
            $where = 'id = :id';
            $whereParam = [
                'id' => $id,
            ];
            $res = $this->updateTransDetail($data, $where, $whereParam);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->incrAssetSell($userId, $coinId, $profitDiff);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->decrAsset($userId, $coinId, $numberDiff);
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

    /**
     * 买入删除
     * @param $userId
     * @param $coinId
     * @param $id
     * @param $dataTransDetail
     * @return bool
     */
    public function transBuyDelete($userId, $coinId, $id, $dataTransDetail)
    {
        $number = $dataTransDetail['number'];
        $profit = $dataTransDetail['number'] * $dataTransDetail['price'];

        try {
            $this->beginTransaction();

            $res = $this->deleteTransDetail($id);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->decrAsset($userId, $coinId, $number, $profit);
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

    /**
     * 卖出删除
     * @param $userId
     * @param $coinId
     * @param $id
     * @param $dataTransDetail
     * @return bool
     */
    public function transSellDelete($userId, $coinId, $id, $dataTransDetail)
    {
        $numberDiff = $dataTransDetail['number'];
        $profitDiff = $dataTransDetail['number'] * $dataTransDetail['price'];

        try {
            $this->beginTransaction();

            $res = $this->deleteTransDetail($id);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->decrAssetSell($userId, $coinId, $profitDiff);
            if (empty($res)) {
                $this->rollback();
                return false;
            }

            $res = $this->incrAsset($userId, $coinId, $numberDiff);
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
            'user_id' => $userId,
            'coin_id' => $coinId
        ];
        return $this->delete($this->tableName, $where, $whereParam);
    }
}