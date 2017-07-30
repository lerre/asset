<?php

class Db
{
    CONST TYPE_MASTER = 'master';
    CONST TYPE_SLAVE = 'slave';
    CONST CHARSET = 'utf8mb4';
    CONST ACTION_SELECT = 'SELECT';
    CONST ACTION_INSERT = 'INSERT';
    CONST ACTION_UPDATE = 'UPDATE';
    CONST ACTION_DELETE = 'DELETE';

    protected $dbConfig = [];
    protected $dsnConfig = [];
    protected $pdo = [];
    protected $sql = [];
    protected $transaction = null;
    protected $databaseName = '';
    protected $tableName = '';

    public function __construct()
    {
        $this->config = [
            'master' => [
                'db' => 'asset',
                'host' => '127.0.0.1',
                'port' => '3306',
                'user' => 'asset',
                'pass' => 'asset##@@++%%',
                'charset' => 'utf8mb4',
                'timeout' => 3
            ],
            'slave' => [
                'db' => 'asset',
                'host' => '127.0.0.1',
                'port' => '3306',
                'user' => 'asset',
                'pass' => 'asset##@@++%%',
                'charset' => 'utf8mb4',
                'timeout' => 3
            ]
        ];
        $this->setDbConfig($this->config);
    }

    /**
     * 数据库配置
     * @param $dbConfig
     * @return array
     */
    private function setDbConfig($dbConfig)
    {
        if (!isset($dbConfig['slave'])) {
            if (isset($dbConfig['slave_list']) && is_array($dbConfig['slave_list'])) {
                shuffle($dbConfig['slave_list']);
                $slave = array_pop($dbConfig['slave_list']);
                if (!empty($slave['host'])) {
                    $dbConfig['slave'] = $slave;
                }
                unset($dbConfig['slave_list']);
            }
        }
        if (!isset($dbConfig['slave']) && isset($dbConfig['master'])) {
            $dbConfig['slave'] = $dbConfig['master'];
        }
        $this->dbConfig = $dbConfig;
    }

    /**
     * 数据库连接配置
     * @param string $type
     * @return array
     */
    private function setDsnConfig($type = 'master')
    {
        $dbConfig = $this->dbConfig;
        $config[$type]['dsn'] = "mysql:host={$dbConfig[$type]['host']};port={$dbConfig[$type]['port']};dbname={$dbConfig[$type]['db']}";
        $config[$type]['username'] = $dbConfig[$type]['user'];
        $config[$type]['password'] = $dbConfig[$type]['pass'];
        if (isset($dbConfig[$type]['charset'])) {
            $config[$type]['charset'] = $dbConfig[$type]['charset'];
        }
        $this->dsnConfig = $config;
    }

    /**
     * 获取PDO对象
     * @param string $type
     * @return array
     */
    private function getPdo($type = 'master')
    {
        $this->setDsnConfig($type);
        $key = $type;
        if (!isset($this->pdo[$key])) {
            try {
                $pdo = new \PDO(
                    $this->dsnConfig[$type]['dsn'],
                    $this->dsnConfig[$type]['username'],
                    $this->dsnConfig[$type]['password'],
                    [
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                    ]
                );
                if (isset($this->dsnConfig[$type]['charset'])) {
                    $charset = $this->dsnConfig[$type]['charset'];
                } else {
                    $charset = self::CHARSET;
                }
                $pdo->exec("SET NAMES {$charset}");
                $this->pdo[$key] = $pdo;
            } catch (\PDOException $e) {
                echo $this->dsnConfig[$type]['dsn'] . '||' . $e->__toString();
                exit;
            }
        }
        return $this->pdo[$key];
    }

    /**
     * 对sql语句进行预处理，同时对参数进行同步处理 ,以实现在调用时sql和参数多种占位符格式支持
     * 如 $sql="id=?", $param=1 处理成sql="id=:id",$param['id']=1
     * @param $sql
     * @param $param
     * @return array
     */
    private function parseParam(&$sql, &$param)
    {
        if (empty($param)) return [];

        if (!is_array($param)) {
            $param = [$param];
        }

        $tmp = [];

        $_first = each($param);
        if (!is_int($_first['key'])) {
            foreach ($param as $_key => $_value) {
                $tmp[":" . ltrim($_key, ":")] = $_value;
            }
        } else {
            preg_match_all("/`?([\w_]+)`?\s*[\=<>!]+\s*\?\s+/i", $sql . " ", $matches, PREG_SET_ORDER);
            if ($matches) {
                foreach ($matches as $_key => $_match) {
                    $fieldName = ":" . $_match[1]; //字段名称
                    $i = 0;
                    while (array_key_exists($fieldName, $param)) {
                        $fieldName = ":" . $_match[1] . "_" . ($i++);
                    }
                    $sql = str_replace(trim($_match[0]), str_replace("?", $fieldName, $_match[0]), $sql);
                    if (array_key_exists($_key, $param)) {
                        $tmp[$fieldName] = $param[$_key];
                    }
                }
            }
        }

        $param = $tmp;

        //fix sql like: select * from table where id in(:ids)
        preg_match_all("/\s+in\s*\(\s*(\:\w+)\s*\)/i", $sql . " ", $matches, PREG_SET_ORDER);

        if ($matches) {
            foreach ($matches as $_key => $_match) {
                $value = $param[$_match[1]];
                if (!is_array($value)) {
                    $value = explode(",", addslashes($value));
                }
                $tmp = [];
                foreach ($value as $_value) {
                    $tmp[] = is_numeric($_value) ? $_value : "'" . $_value . "'";
                }
                $value = implode(",", $tmp);
                $sql = str_replace($_match[0], " In (" . $value . ") ", $sql);

                unset($param[$_match[1]]);
            }
        }

        $this->sql = [
            'sql' => $sql,
            'param' => $param
        ];
    }

    /**
     * 自动生成条件语句
     *
     * @param array $where
     * @return string
     */
    public function buildWhere($where)
    {
        $sql_where = '';
        if (is_array($where)) {
            foreach ($where as $f => $v) {
                $f_type = gettype($v);
                if ($f_type == 'array') {
                    $sql_where .= ($sql_where ? " AND " : "") . "(`{$f}` " . $v ['operator'] . " '" . $v ['value'] . "')";
                } elseif ($f_type == 'string')
                    $sql_where .= ($sql_where ? " OR " : "") . "(`{$f}` LIKE '%{$v}%')";
                else {
                    $sql_where .= ($sql_where ? " AND " : "") . "(`{$f}` = '{$v}')";
                }
            }
        } elseif (strlen($where)) {
            $sql_where = $where;
        } else
            return '';
        $sql_where = $sql_where ? " WHERE " . $sql_where : '';
        return $sql_where;
    }

    /**
     * @param $pdo
     * @param $sql
     * @param array $param
     * @return array|object
     */
    private function preExec($pdo, $sql, $param = [])
    {
        try {
            $sth = $pdo->prepare($sql);
            $sth->execute($param);
        } catch (\PDOException $e) {
            $errorInfo = $e->errorInfo;
            $data['error_code'] = $errorInfo[0];
            $data['error'] = $errorInfo[1];
            if ($data['error_code'] == 2006) { //Mysql has gone away
                //关闭连接
                $this->close();
                //重连
                $type = key($this->dsnConfig);
                $pdo = $this->getPdo($type);
                return $this->preExec($pdo, $sql, $param);
            }
            throw $e;
        }
        return $sth;
    }

    /**
     * 执行主库CRUD操作
     * @param $action
     * @param $sql
     * @param $param
     * @return int
     */
    public function exec($action, $sql, $param = [])
    {
        $pdo = $this->getPdo(self::TYPE_MASTER);
        switch ($action) {
            case self::ACTION_SELECT:
                $this->parseParam($sql, $param);
                $sth = $this->preExec($pdo, $sql, $param);
                $result = $sth;
                break;
            case self::ACTION_INSERT:
                $this->preExec($pdo, $sql, $param);
                $result = $pdo->lastInsertId();
                break;
            case self::ACTION_UPDATE:
            case self::ACTION_DELETE:
                $this->parseParam($sql, $param);
                $sth = $this->preExec($pdo, $sql, $param);
                $result = $sth->rowCount();
                break;
            default:
                $result = null;
                break;
        }
        return $result;
    }

    /**
     * 执行从库查询操作
     * @param $sql
     * @param $param
     * @return int
     */
    private function execQuery($sql, $param = null)
    {
        $pdo = $this->getPdo(self::TYPE_SLAVE);
        $this->parseParam($sql, $param);
        return $this->preExec($pdo, $sql, $param);
    }

    /**
     * 从结果集中获取所有行
     * @param $sql
     * @param $param
     * @return array
     */
    public function queryAll($sql, $param = null)
    {
        $sth = $this->execQuery($sql, $param);
        return $sth->fetchAll();
    }

    /**
     * 从结果集中获取下一行
     * @param $sql
     * @param $param
     * @return array
     */
    public function query($sql, $param = null)
    {
        $sth = $this->execQuery($sql, $param);
        return $sth->fetch();
    }

    /**
     * 从结果集中的下一行返回单独的一列
     * @param $sql
     * @param $param
     * @return string
     */
    public function queryColumn($sql, $param = null)
    {
        $sth = $this->execQuery($sql, $param);
        return $sth->fetchColumn();
    }

    /**
     * 将数据插入到指定表中
     * @param $tableName
     * @param array $data 要insert到表中的数据
     * @param string $ignore 插入的时候是否ignore
     * @param string $appendSql
     * @return int 返回最后插入行的ID
     */
    public function insert($tableName, array $data, $ignore = '', $appendSql = '')
    {
        $ignore = (is_string($ignore) && strtolower($ignore) === 'ignore') ? $ignore : '';
        $sql = "INSERT {$ignore} INTO `{$tableName}` (" . join(",", array_keys($data)) . ") VALUES (" . rtrim(str_repeat("?,", count($data)), ",") . ")";
        $sql .= $appendSql;
        $param = array_values($data);
        return $this->exec(self::ACTION_INSERT, $sql, $param);
    }

    /**
     * 将数据覆盖到指定表中
     * @param $tableName
     * @param array $data
     * @return int 返回最后插入行的ID
     */
    public function replace($tableName, array $data)
    {
        $sql = "REPLACE INTO `{$tableName}` (" . join(",", array_keys($data)) . ") VALUES (" . rtrim(str_repeat("?,", count($data)), ",") . ")";
        $param = array_values($data);
        return $this->exec(self::ACTION_INSERT, $sql, $param);
    }

    /**
     * 对指定表进行更新操作
     * DB::update('tableName',array('title'=>'this is title','content'=>'this is content'),'id=?',array(12));
     *
     * @param $tableName
     * @param $data 要进行更新的数据  array('title'=>'this is title','hits=hits+1')
     * @param $where
     * @param $whereParam
     * @return array|bool|int
     */
    public function update($tableName, $data, $where, $whereParam = [])
    {
        if (!is_array($data)) $data = (array)$data;

        $sql = "UPDATE `{$tableName}` SET ";
        $tmp = $param = [];
        foreach ($data as $k => $v) {
            if (is_int($k)) { //如：'hits=hits+1'，可以是直接的函数
                $tmp[] = $v;
            } else { //其他情况全部使用占位符，如：'title'=>'this is title'
                $tmp[] = "`{$k}`=:k_{$k}";
                $param[":k_" . $k] = $v;
            }
        }
        $where = $this->buildWhere($where);
        $this->parseParam($where, $whereParam);
        $param = array_merge($param, $whereParam);
        $sql .= join(",", $tmp) . " {$where}";
        return $this->exec(self::ACTION_UPDATE, $sql, $param);
    }

    /**
     * 对指定表进行删除操作
     *
     * 示例：
     *     Db::delete('tableName',"id=?",array(1));
     *
     * @param $tableName
     * @param $where
     * @param $whereParam
     * @return int
     */
    public function delete($tableName, $where, $whereParam = null)
    {
        $sql = "DELETE FROM `{$tableName}` WHERE {$where}";
        return $this->exec(self::ACTION_DELETE, $sql, $whereParam);
    }

    /**
     * 开启事务
     * @return object
     */
    public function beginTransaction()
    {
        $this->transaction = $this->getPdo(self::TYPE_MASTER);
        return $this->transaction->beginTransaction();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack()
    {
        return $this->transaction->rollBack();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        return $this->transaction->commit();
    }

    /**
     * 关闭事务
     * @return null
     */
    public function endTransaction()
    {
        $this->transaction = null;
        return;
    }

    /**
     * 调试SQL
     * @return array
     */
    public function debug()
    {
        return $this->sql;
    }

    /**
     * 关闭连接
     * @return bool
     */
    private function close()
    {
        if (!$this->pdo) {
            return false;
        }
        foreach ($this->pdo as $key => $pdo) {
            unset($this->pdo[$key]);
        }
        return true;
    }

    public function __destruct()
    {
        $this->close();
    }
}