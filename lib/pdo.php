<?php

/**
 * MYSQL-PDO连接类
 * User: xiebin
 * Date: 16/4/20
 * Time: 下午4:55
 */
class MY_PDO
{
    //数据库对象
    static private $_pdo = null;
    //数据库名称
    protected $dbName = '';
    //PDO链接数据库信息
    protected $dsn;
    //PDO对象
    protected $dbh;

    /**
     * 构造方法
     * @param $dbHost
     * @param $dbUser
     * @param $dbPasswd
     * @param $dbName
     * @param $dbCharset
     * @throws Exception
     */
    public function __construct($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset)
    {
        try {
            $this->dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName;
            $this->dbh = new PDO($this->dsn, $dbUser, $dbPasswd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $this->dbh->exec('SET NAMES ' . $dbCharset);
        } catch (PDOException $e) {
            $this->outputError($e->getMessage());
        }
    }

    /**
     * 防止克隆
     *
     */
    private function __clone()
    {
    }

    /**
     * 获取单例对象
     * @param $dbHost
     * @param $dbUser
     * @param $dbPasswd
     * @param $dbName
     * @param $dbCharset
     * @return MyPDO|null 返回单例PDO对象
     */
    static public function getPdo($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset)
    {
        if (!(self::$_pdo instanceof self)) {
            self::$_pdo = new self($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset);
        }
        return self::$_pdo;
    }

    /**
     * 获取所有数据
     * @param $strSql SQL语句
     * @param null $params 预处理语句参数数组
     * @param bool|false $debug 调试模式
     * @return mixed
     */
    public function getAll($strSql, $params = null, $debug = false)
    {
        $result = $this->query($strSql, $params, $debug);
        return $result->fetchAll();
    }

    /**
     * 获取一条数据
     * @param $strSql SQL语句
     * @param null $params 预处理语句参数数组
     * @param bool|false $debug 调试模式
     * @return mixed
     */
    public function getOne($strSql, $params = null, $debug = false)
    {
        $result = $this->query($strSql, $params, $debug);
        return $result->fetch();
    }

    /**
     * 获取一条数据
     * @param $strSql SQL语句
     * @param null $params 预处理语句参数数组
     * @param bool|false $debug 调试模式
     * @return mixed
     */
    public function getColumn($strSql, $params = null, $debug = false)
    {
        $result = $this->query($strSql, $params, $debug);
        return $result->fetchColumn();
    }

    /**
     * Query 查询
     * @param String $strSql SQL语句
     * @param null $params 预处理语句参数数组
     * @param Boolean $debug 调试模式
     * @return Array 结果级数组
     */
    public function query($strSql, $params = null, $debug = false)
    {
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->prepare($strSql);
        if (count($params) > 0) {
            foreach ($params as $key => &$value) {
                $result->bindParam($key, $value);
            }
        }
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        if ($result->execute()) {
            return $result;
        }
        return $result;
    }

    /**
     * 预编译后插入
     * @param $table 将要插入的表名
     * @param array $valuesToInsert 将要插入的数组 array( key1 => value1, key2 => value2)
     * @return int 成功返回最后插入的ID,失败返回false
     */
    public function prepareInsert($table, $valuesToInsert)
    {
        $keys = array_keys($valuesToInsert);
        $fields = '`' . implode('`, `', $keys) . '`';

        $placeholder = str_repeat('?,', count($keys));
        $placeholder = rtrim($placeholder, ',');

        $result = $this->dbh->prepare("INSERT INTO `{$table}`($fields) VALUES($placeholder)");
        if ($result->execute(array_values($valuesToInsert))) {
            return $this->insertId();
        }
        return false;
    }

    /**
     * 执行SQL语句
     * @param $sql
     * @return int|bool 成功返回影响的记录行数,失败返回false
     */
    public function exec($sql)
    {
        return $this->dbh->exec($sql);
    }

    /**
     * 返回最后一次插入的ID
     * @param string $name
     * @return integer
     */
    public function insertId($name = null)
    {
        return $this->dbh->lastInsertId($name);
    }

    /**
     * 事务开始
     */
    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        $this->dbh->commit();
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        $this->dbh->rollback();
    }

    /**
     * 销毁数据库链接
     */
    public function destruct()
    {
        $this->dbh = null;
    }

    /**
     * 输出错误信息
     * @param $strErrMsg 错误信息
     * @throws Exception
     */
    private function outputError($strErrMsg)
    {
        throw new Exception('MySQL Error: ' . $strErrMsg);
    }

    /**
     * getPDOError 捕获PDO错误信息
     */
    private function getPDOError()
    {
        if ($this->dbh->errorCode() != '00000') {
            $arrayError = $this->dbh->errorInfo();
            $this->outputError($arrayError[2]);
        }
    }

    /**
     * debug
     *
     * @param mixed $debuginfo
     */
    private function debug($debuginfo)
    {
        var_dump($debuginfo);
        exit();
    }


}