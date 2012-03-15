<?php

// +----------------------------------------------------------------------
// | Buddy Framework 
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://buddy.woshimaijia.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: xinqiyang <xinqiyang@gmail.com>
// +----------------------------------------------------------------------

/**
 * Mysqli简单高性能高稳定性的数据访问类
 * 开发中和线上必须配置mysqli的扩展
 * 获取大量数据的测试代码：
 * 
 * $sql = 'select * from sz_account limit 1';
 * $sqllarge = 'select * from sz_stream';
 * $db = Base::instance('Mysqlidb');
 * $result = $db->fetchRow($sql);
 * //查询超大对象，获取连接对象，进行查询
 * $db->connect()->real_query($sqllarge);
 * //获取查询结果
 * $result2 = $db->connect()->use_result();
 * //循环输出结果
 * while($row = $result2->fetch_row()){
 *     var_dump($row);
 * }
 * echo "-------<br />";
 * var_dump($result);
 * 
 * 查询小量但是不想从db返回后在做二次循环时候用这个
 * if ($db->query($sqlproduct)) {
 *    while ($arrRow = mysqli_fetch_assoc($db->getQuery())) {
 *        var_dump($arrRow);
 *        echo "--------------<br />";
 *    }
 * }
 */
class Mysqlidb {

    /**
     * DB配置节点
     * @var array
     */
    protected $_arrConfig = null;

    /**
     * 是否自动释放
     * @var bool
     */
    protected $_bolAutoFree = false;

    /**
     * 是否使用持续链接
     * @var bool
     */
    protected $_bolPconnect = false;

    /**
     * 事务次数
     * @var integer
     */
    protected $_intTransTimes = 0;

    /**
     * 查询语句
     * @var string
     */
    protected $_strQueryStr = '';

    /**
     * 最后自增ID
     * @var integer
     */
    protected $_intLastInsID = null;

    /**
     * 影响行数
     * @var integer
     */
    protected $_intNumRows = 0;

    /**
     * 当前的链接对象
     * @var mysqli
     */
    protected $_objLinkID = null;

    /**
     * 当前查询的对象
     * @var object
     */
    protected $_objQueryID = null;

    /**
     * 是否使用主库
     * @var bool
     */
    protected $_bolIsMaster = false;

    /**
     * 当前是否需要连接到主库
     * @var bool
     */
    protected $_bolMaster = false;

    /**
     * 标记是否成功
     * @var bool
     */
    protected $_bolConnected = false;


    /**
     * 构造函数
     * 获取配置信息传入，构造DB对象
     */
    public function __construct($dbNode='mysqli') {
        if (!extension_loaded('mysqli')) {
            throw new Exception('MYSQLI EXTENSTION NOT LOADED!');
        }
        //加载配置
        $arrConfig = C('mysqli.'.$dbNode);
        if (!empty($arrConfig)) {
            $this->_arrConfig = $arrConfig;
        } else {
            throw new Exception('DB CONFIG ITEMS IS EMPTY!');
        }
    }

    /**
     * 获取配置
     * @param bool $bolMaster 是否使用主库
     * @param int $intRetry  重试的配置获取 
     * @return array  
     */
    private function _get_db_config($bolMaster, $intRetry = 0) {
        if ($bolMaster) {
            return $this->_arrConfig['master'];
        } else {
            //如果不是连接在主库上的话，就通过从库来读
            switch ($intRetry) {
                case 0:
                    return $this->_arrConfig['near'][array_rand($this->_arrConfig['near'])];
                case 1:
                    return $this->_arrConfig['backup'][array_rand($this->_arrConfig['backup'])];
                case 2:
                    return $this->_arrConfig['master'];
                default:
                    return $this->_arrConfig['master'];
            }
        }
    }
    

    /**
     * 连接数据库获取实例
     * 根据传入的linkNum来实现db的切换
     * 连接到DB SERVER的配置
     */
    public function connect() {
        if ($this->_objLinkID && $this->_bolIsMaster == false && $this->_bolMaster == true) {
            //已连接到slave但需要连接到master，则断开已有连接
            $this->close();
        }

        if (!isset($this->_objLinkID)) {
            //创建连接
            $connect_success = false;
            for ($i = 0; $i < 3; $i++) {
                $arrConfig = $this->_get_db_config($this->_bolMaster, $i);
                $this->_objLinkID = new mysqli($arrConfig['hostname'], $arrConfig['username'],
                                $arrConfig['password'], $arrConfig['database'], $arrConfig['hostport']);
                if (mysqli_connect_errno()) {
                    continue;
                } else {
                    $connect_success = true;
                    break;
                }
            }
            if (!$connect_success) {
                throw new Exception(mysqli_connect_error());
            }
            if (!$this->_objLinkID->set_charset('utf8')) {
                throw new Exception("Failed to set character set utf-8!");
            }
            $this->_bolConnected = true;
            $this->_bolIsMaster = $this->_bolMaster;
        }
        return $this->_objLinkID;
    }
    

    /**
     * 查询SQL语句返回结果
     * @param string $str sql语句
     * @return array  查询结果
     */
    public function query($strSql) {

        //连接数据库
        $this->connect();
        $this->_strQueryStr = $strSql;
        //释放上次的查询
        $this->free();
        $this->_objQueryID = mysqli_query($this->_objLinkID, $this->_strQueryStr);
        if (!$this->_objQueryID) {
            //查询失败则抛出异常
            throw new Exception(
                    "QUERY ERROR:" . $this->_objLinkID->error . " SQL:" . $strSql);
            return false;
        } else {
            $this->_intNumRows = mysqli_num_rows($this->_objQueryID);
            return true;
        }
    }

    /**
     * 获取query后的查询结果
     *
     */
    public function fetch() {
        return mysqli_fetch_assoc($this->_objQueryID);
    }

    /**
     * 查询返回单条记录
     *
     * $db->fetchRow($strSql);
     * @param string $strSql SQL语句
     * @return array/empty array 返回当条数据或者是空数组
     */
    public function fetchRow($strSql) {
        if ($this->query($strSql)) {
            return $this->fetch();
        }
        return array();
    }

    /**
     * 查询返回一列的全部记录，例如SELECT field FROM table WHERE id = 3;
     *
     * $db->fetchColumn($strSql);
     * @param string $strSql SQL语句
     * @return array/empty array 返回当条数据或者是空数组
     */
    public function fetchColumn($strSql) {
        $arrColumn = array();
        if ($this->query($strSql)) {
            while ($arrRow = $this->fetch()) {
                $arrRow = array_values($arrRow);
                $arrColumn[] = $arrRow[0];
            }
        }
        return $arrColumn;
    }

    /**
     *
     * 查询返回第一个记录的第一个字段的值，例如SELECT field FROM table WHERE id = 12;
     *
     * $db->fetchOne($strSql);
     * @param string $strSql
     */
    public function fetchOne($strSql) {
        $arrRow = $this->fetchRow($strSql);
        if (!is_array($arrRow)) {
            return false;
        }
        $arrRow = array_values($arrRow);
        return $arrRow[0];
    }

    /**
     *
     * 查询全部行，例如SELECT * FROM table WHERE id > 3;
     *
     * $db->fetchAll($strSql);
     * @param string $strSql
     */
    public function fetchAll($strSql) {
        $arrAll = array();
        if ($this->query($strSql)) {
            return mysqli_fetch_all($this->_objQueryID, MYSQLI_ASSOC);
        }
        return $arrAll;
    }

    /**
     * 执行SQL语句
     * @param string $str sql语句
     * @return int  返回execute影响的行数
     */
    public function execute($strSql) {
        //连接到主库
        $this->_bolMaster = true;
        $this->connect();
        $this->_strQueryStr = $strSql;
        $this->free();
        //$startInterval = microtime(true);
        $result = mysqli_query($this->_objLinkID, $this->_strQueryStr);
        if (false === $result) {
            throw new Topaz_Exception("QUERY ERROR:" . $this->_objLinkID->error . " SQL:" . $strSql);
            return false;
        } else {
            $this->_intNumRows = mysqli_affected_rows($this->_objLinkID);
            $this->_intLastInsID = mysqli_insert_id($this->_objLinkID);
            //$endInterval = microtime(true);
            //error_log(($endInterval - $startInterval) . " " . $strSql . "\n", 3 ,"/tmp/mccsql.log");    		
            return $this->_intNumRows;
        }
    }

    /**
     * 插入数据库
     * @TODO: 需要处理下table的前缀问题
     * @param type $strTable
     * @param type $arrBind
     * @return type 
     */
    public function insert($strTable, $arrBind) {
        $fields = "(";
        $values = "(";
        foreach ($arrBind as $key => $value) {
            $fields .= "`" . $key . "`";
            $fields .= ",";
            $values .= ("'" . $this->escape($value) . "'");
            $values .= ",";
        }
        $fields = rtrim($fields, ",");
        $values = rtrim($values, ",");
        $fields .= ")";
        $values .= ")";
        $sql = "INSERT INTO {$strTable} {$fields} VALUES {$values}";
        return $this->execute($sql);
    }

    /**
     * 更新操作
     * @param type $strTable 表名
     * @param type $arrBind 待绑定的数组
     * @param type $strWhere 查询条件
     * @return type 
     */
    public function update($strTable, $arrBind, $strWhere) {
        $sql = "UPDATE {$strTable} SET ";
        $setString = "";
        foreach ($arrBind as $key => $value) {
            $strSeg = "`" . $key . "`";
            $valSeg = ("'" . $this->escape($value) . "'");
            $strSeg .= (" = " . $valSeg);
            $strSeg .= ",";
            $setString .= $strSeg;
        }

        $setString = rtrim($setString, ',');
        $sql .= $setString;
        $sql .= (" WHERE " . $strWhere);
        return $this->execute($sql);
    }

    /**
     * 开始事务
     * @return null
     */
    public function beginTransaction() {
        $this->_bolMaster = true;
        $this->_objLinkID = $this->connect();
        if (!$this->_objLinkID) {
            return false;
        }
        $result = mysqli_query($this->_objLinkID, 'START TRANSACTION');
        return $result;
    }

    /**
     * 提交
     * @return bool true/false
     */
    public function commit() {
        $result = mysqli_query($this->_objLinkID, 'COMMIT');
        return $result;
    }

    /**
     * 回滚
     * @return bool 结果
     */
    public function rollBack() {
        $result = mysqli_query($this->_objLinkID, 'ROLLBACK');
        return $result;
    }

    /**
     * 释放最近一次查询结果
     */
    public function free() {
        if (!empty($this->_objQueryID)) {
            mysqli_free_result($this->_objQueryID);
            $this->_objQueryID = null;
        }
    }

    /**
     * 回收资源
     */
    public function close() {
        if (!empty($this->_objQueryID)) {
            mysqli_free_result($this->_objQueryID);
        }
        if (!empty($this->_objLinkID)) {
            mysqli_close($this->_objLinkID);
        }
        unset($this->_objLinkID);
        unset($this->_objQueryID);
        $this->_bolConnected = false;
    }

    /**
     * destruct
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * 获取最后一条查询的SQL
     * @return string
     */
    public function getLastSql() {
        return $this->_strQueryStr;
    }

    public function getLastInsertId() {
        return $this->_intLastInsID;
    }

    public function escape($string) {
        $this->connect();
        return $this->_objLinkID->real_escape_string($string);
    }

    public function getNumRows() {
        return $this->_intNumRows;
    }

    public function getConnection($bolMaster) {
        if (!isset($bolMaster) || $bolMaster === null) {
            $bolMaster = $this->_bolMaster;
        }
        $this->_bolMaster = $bolMaster;
        $this->connect();
        return $this->_objLinkID;
    }

    public function getQuery() {
        return $this->_objQueryID;
    }
    
    
}

