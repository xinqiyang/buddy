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
 * DataComponent for MySQL Operation
 * ORM/ActiveRecord,easy to operate data from mysql.
 * @author xinqiyang(xinqiyang@gmail.com)
 * @version 1.0.0
 */

/**
 * Mysql Class
 * feature:
 * ORM/ActiveRecords
 * auto validate
 * easy to use
 */
class MMysql {
    // action status
    const MODEL_INSERT = 1;      //insert model
    const MODEL_UPDATE = 2;      // update model
    const MODEL_BOTH = 3;      // both uper
    const MUST_VALIDATE = 1; // must validate
    const EXISTS_VAILIDATE = 0; // if exists in form validate it.
    const VALUE_VAILIDATE = 2; // if value vailidate in for then check it.

    private   $_extModel = null;
    protected $db = null;
    protected $pk = 'id';
    protected $tablePrefix = '';
    protected $tableSuffix = '';
    protected $name = '';
    protected $dbName = '';
    protected $tableName = '';
    protected $trueTableName = '';
    protected $error = '';
    protected $fields = array();
    protected $data = array();
    protected $options = array();
    protected $_validate = array();
    protected $_auto = array();
    protected $_map = array();
    protected $config = array();
    protected $autoCheckFields = true;
    
    private static $mysql ;
    
    public static function instance($table,$connection='mysql')
    {
    	//set default value of params 
    	$table = empty($table) ? 'account' : $table;
    	$connection = empty($connection) ? 'mysql' : $connection;
    	if(!isset(self::$mysql[$table.$connection]))
    	{
    		self::$mysql[$table.$connection] = new self($table,$connection);
    	}
    	return self::$mysql[$table.$connection];
    }

    /**
     * construct 
     * @param string $name tablename
     * @param array $connection connection
     */
    private function __construct($name, $connection) {
        $this->config = C('mysql.'.$connection);
        
        $this->_initialize();
        if (!empty($name)) {
            $this->name = $name;
        } elseif (empty($this->name)) {
            $this->name = $this->getModelName();
        }
        //TODO:need repair
        $this->db = Db::getInstance($this->config);
       
        $this->tablePrefix = $this->tablePrefix ? $this->tablePrefix : $this->config['db_prefix'];
        //
        $this->tableSuffix = $this->tableSuffix ? $this->tableSuffix : '';
        //this  && $this->autoCheckFields
        if (!empty($this->name)){
            $this->_checkTableInfo();
        }
    }

    protected function _checkTableInfo() {
        if (empty($this->fields)) {
            $config = $this->config;
            if ($config['fields_cache']) {
                $this->fields = self::fileSave('_fields/' . $this->name);
                if (empty($this->fields))
                {
                    $this->flush();
                    
                }
            }else {
                $this->flush();
            }
        }
    }

    public function flush() {
        $fields = $this->db->getFields($this->getTableName());
        $this->fields = array_keys($fields);
        $this->fields['_autoinc'] = false;
        foreach ($fields as $key => $val) {
            $type[$key] = $val['type'];
            if ($val['primary']) {
                $this->fields['_pk'] = $key;
                if ($val['autoinc'])
                    $this->fields['_autoinc'] = true;
            }
        }
        $config = $this->config;
        if ($config['fields_cache']) {
            self::fileSave('_fields/' . $this->name, $this->fields);
        }
    }

    private function fileSave($name, $value='', $path='') {
        static $_cache = array();
        $config = $this->config;
        $path = empty($path) ? $config['fields_cache_path'] : $path;
        //logDebug($path);
        if(!is_dir($path)){
        	@mkdir($path);
        }
        $filename = $path . $name . '.php';
        if ('' !== $value) {
            if (is_null($value)) {
                return unlink($filename);
            } else {
                $dir = dirname($filename);
                if (!is_dir($dir))
                    mkdir($dir);
                return file_put_contents($filename, "<?php\nreturn " . var_export($value, true) . ";\n?>");
            }
        }
        if (isset($_cache[$name]))
            return $_cache[$name];
        if (is_file($filename)) {
            $value = include $filename;
            $_cache[$name] = $value;
        } else {
            $value = false;
        }
        return $value;
    }

    public function __set($name, $value) {
        $this->data[$name] = $value;
    }

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __isset($name) {
        return isset($this->data[$name]);
    }

    public function __unset($name) {
        unset($this->data[$name]);
    }

    public function __call($method, $args) {
        if (in_array(strtolower($method), array('field', 'table', 'where', 'order', 'limit', 'page', 'alias', 'having', 'group', 'lock', 'distinct'), true)) {
            $this->options[strtolower($method)] = $args[0];
            return $this;
        } elseif (in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true)) {
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->getField(strtoupper($method) . '(' . $field . ') AS tp_' . $method);
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            $field = model_parse_name(substr($method, 5));
            $where[$field] = $args[0];
            return $this->where($where)->find();
        } else {
            model_throw_exception(__CLASS__ . ':' . $method . '_METHOD_NOT_EXIST_');
            return;
        }
    }

    protected function _initialize() {
        
    }

    /**
     * facade of data action
     * Enter description here ...
     * @param unknown_type $data
     */
    protected function _facade($data) {
        if (!empty($this->fields)) {
        	
            foreach ($data as $key => $val) {
                if (!in_array($key, $this->fields, true)) {
                    unset($data[$key]);
                } 
                /*
                elseif (is_scalar($val)) {
                    $fieldType = strtolower($this->fields['_type'][$key]);
                    if (false !== strpos($fieldType, 'int')) {
                        $data[$key] = intval($val);
                    } elseif (false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double')) {
                        $data[$key] = floatval($val);
                    }
                }
                */
            }
        }
        $this->_before_write($data);
        return $data;
    }

    protected function _before_write(&$data) {

    }

    public function add($data='', $options=array(), $replace=false) {
        if (empty($data)) {
            if (!empty($this->data)) {
                $data = $this->data;
            } else {
                $this->error = '_DATA_TYPE_INVALID_';
                return false;
            }
        }
        
        $options = $this->_parseOptions($options);
        $data = $this->_facade($data);
        if (false === $this->_before_insert($data, $options)) {
            return false;
        }
        
        $result = $this->db->insert($data, $options, $replace);
        
        if (false !== $result) {
            $insertId = $this->getLastInsID();
            if ($insertId) {
                $data[$this->getPk()] = $insertId;
                $this->_after_insert($data, $options);
                return $insertId;
            }
        }
        return $result;
    }

    protected function _before_insert(&$data, $options) {

    }

    protected function _after_insert($data, $options) {

    }

    public function addAll($dataList, $options=array(), $replace=false) {
        if (empty($dataList)) {
            $this->error = '_DATA_TYPE_INVALID_';
            return false;
        }
        $options = $this->_parseOptions($options);
        foreach ($dataList as $key => $data) {
            $dataList[$key] = $this->_facade($data);
        }
        $result = $this->db->insertAll($dataList, $options, $replace);
        if (false !== $result) {
            $insertId = $this->getLastInsID();
            if ($insertId) {
                return $insertId;
            }
        }
        return $result;
    }

    public function selectAdd($fields='', $table='', $options=array()) {
        $options = $this->_parseOptions($options);
        if (false === $result = $this->db->selectInsert($fields ? $fields : $options['field'], $table ? $table : $this->getTableName(), $options)) {
            $this->error = '_OPERATION_WRONG_';
            return false;
        } else {
            return $result;
        }
    }

    public function save($data='', $options=array()) {
        if (empty($data)) {
            if (!empty($this->data)) {
                $data = $this->data;
            } else {
                $this->error = '_DATA_TYPE_INVALID_';
                return false;
            }
        }
        $data = $this->_facade($data);
        $options = $this->_parseOptions($options);
        if (false === $this->_before_update($data, $options)) {
            return false;
        }
        if (!isset($options['where'])) {
            if (isset($data[$this->getPk()])) {
                $pk = $this->getPk();
                $where[$pk] = $data[$pk];
                $options['where'] = $where;
                $pkValue = $data[$pk];
                unset($data[$pk]);
            } else {
                $this->error = '_OPERATION_WRONG_';
                return false;
            }
        }
        $result = $this->db->update($data, $options);
        if (false !== $result) {
            if (isset($pkValue))
                $data[$pk] = $pkValue;
            $this->_after_update($data, $options);
        }
        return $result;
    }

    protected function _before_update(&$data, $options) {

    }

    protected function _after_update($data, $options) {
        
    }

    public function delete($options=array()) {
        if (empty($options) && empty($this->options)) {
            if (!empty($this->data) && isset($this->data[$this->getPk()]))
                return $this->delete($this->data[$this->getPk()]);
            else
                return false;
        }
        if (is_numeric($options) || is_string($options)) {
            $pk = $this->getPk();
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
                $pkValue = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        $options = $this->_parseOptions($options);
        $result = $this->db->delete($options);
        if (false !== $result) {
            $data = array();
            if (isset($pkValue))
                $data[$pk] = $pkValue;
            $this->_after_delete($data, $options);
        }
        return $result;
    }

    protected function _after_delete($data, $options) {
        
    }

    public function select($options=array()) {
        if (is_string($options) || is_numeric($options)) {
            $pk = $this->getPk();
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (empty($resultSet)) {
            return null;
        }
        $this->_after_select($resultSet, $options);
        return $resultSet;
    }

    protected function _after_select(&$resultSet, $options) {

    }

    public function findAll($options=array()) {
        return $this->select($options);
    }

    private function _parseOptions($options) {
        if (is_array($options))
            $options = array_merge($this->options, $options);

        $this->options = array();
        if (!isset($options['table']))
            $options['table'] = $this->getTableName();
        if (!empty($options['alias'])) {
            $options['table'] .= ' ' . $options['alias'];
        }
        $this->_options_filter($options);
        return $options;
    }

    protected function _options_filter(&$options) {
        
    }

    public function find($options=array()) {
        if (!empty($options) && ( is_numeric($options) || is_string($options))) {
            $where[$this->getPk()] = $options;
            $options = array();
            $options['where'] = $where;
        }
        $options['limit'] = 1;
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (empty($resultSet)) {
            return null;
        }
        $this->data = $resultSet[0];
        $this->_after_find($this->data, $options);
        return $this->data;
    }

    protected function _after_find(&$result, $options) {

    }

    public function setField($field, $value, $condition='') {
        if (empty($condition) && isset($this->options['where']))
            $condition = $this->options['where'];
        $options['where'] = $condition;
        if (is_array($field)) {
            foreach ($field as $key => $val)
                $data[$val] = $value[$key];
        } else {
            $data[$field] = $value;
        }
        return $this->save($data, $options);
    }

    public function setInc($field, $condition='', $step=1) {
        return $this->setField($field, array('exp', $field . '+' . $step), $condition);
    }

    public function setDec($field, $condition='', $step=1) {
        return $this->setField($field, array('exp', $field . '-' . $step), $condition);
    }

    public function getField($field, $condition='', $sepa=' ') {
        if (empty($condition) && isset($this->options['where']))
            $condition = $this->options['where'];
        $options['where'] = $condition;
        $options['field'] = $field;
        $options = $this->_parseOptions($options);
        if (strpos($field, ',')) {
            $resultSet = $this->db->select($options);
            if (!empty($resultSet)) {
                $field = explode(',', $field);
                $key = array_shift($field);
                $cols = array();
                foreach ($resultSet as $result) {
                    $name = $result[$key];
                    $cols[$name] = '';
                    foreach ($field as $val)
                        $cols[$name] .= $result[$val] . $sepa;
                    $cols[$name] = substr($cols[$name], 0, -strlen($sepa));
                }
                return $cols;
            }
        } else {
            $options['limit'] = 1;
            $result = $this->db->select($options);
            if (!empty($result)) {
                return reset($result[0]);
            }
        }
        return null;
    }

    public function create($data='', $type='') {
        if (empty($data)) {
            $data = $_POST;
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        } elseif (!is_array($data)) {
            $this->error = '_DATA_TYPE_INVALID_';
            return false;
        }
        $type = $type ? $type : (!empty($data[$this->getPk()]) ? self::MODEL_UPDATE : self::MODEL_INSERT);
        
        var_dump('check');
        // NO TOKEN_ON CHECK 
        if (C('TOKEN_ON') && !$this->autoCheckToken($data)) {
            $this->error = '_TOKEN_ERROR_';
            return false;
        }
        var_dump('check');
        if (!empty($this->_map)) {
            foreach ($this->_map as $key => $val) {
                if (isset($data[$key])) {
                    $data[$val] = $data[$key];
                    unset($data[$key]);
                }
            }
        }

        if (!$this->autoValidation($data, $type))
            return false;

        $vo = array();
        foreach ($this->fields as $key => $name) {
            if (substr($key, 0, 1) == '_')
                continue;
            $val = isset($data[$name]) ? $data[$name] : null;
            if (!is_null($val)) {
                $vo[$name] = is_string($val) ? stripslashes($val) : $val;
            }
        }
        $this->autoOperation($vo, $type);
        $this->data = $vo;
        return $vo;
    }

    public function autoCheckToken($data) {
        $name = C('TOKEN_NAME');
        //var_dump($name,$data[$name]);
        if (isset($_SESSION[$name])) {
            if (empty($data[$name]) || $_SESSION[$name] != $data[$name]) {
                return false;
            }
            //var_dump('session',$_SESSION[$name],'session');
            unset($_SESSION[$name]);
        }
        return true;
    }

    public function regex($value, $rule) {
        //TODO:xinqiyang  will move the the app config file
        $validate = array(
            'require' => '/.+/',
            'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url' => '/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
            'currency' => '/^\d+(\.\d+)?$/',
            'number' => '/^\d+$/',
            'zip' => '/^[1-9]\d{5}$/',
            'integer' => '/^[-\+]?\d+$/',
            'double' => '/^[-\+]?\d+(\.\d+)?$/',
            'english' => '/^[A-Za-z]+$/',
        );
        if (isset($validate[strtolower($rule)]))
            $rule = $validate[strtolower($rule)];
        return preg_match($rule, $value) === 1;
    }

    private function autoOperation(&$data, $type) {
        if (!empty($this->_auto)) {
            foreach ($this->_auto as $auto) {
                if (empty($auto[2]))
                    $auto[2] = self::MODEL_INSERT;
                if ($type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    switch ($auto[3]) {
                        case 'function':
                        case 'callback':
                            $args = isset($auto[4]) ? $auto[4] : array();
                            if (isset($data[$auto[0]])) {
                                array_unshift($args, $data[$auto[0]]);
                            }
                            if ('function' == $auto[3]) {
                                $data[$auto[0]] = call_user_func_array($auto[1], $args);
                            } else {
                                $data[$auto[0]] = call_user_func_array(array(&$this, $auto[1]), $args);
                            }
                            break;
                        case 'field':
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'string':
                        default:
                            $data[$auto[0]] = $auto[1];
                    }
                    if (false === $data[$auto[0]])
                        unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

    protected function autoValidation($data, $type) {
        if (!empty($this->_validate)) {
            foreach ($this->_validate as $key => $val) {
            	
                if (empty($val[5]) || $val[5] == self::MODEL_BOTH || $val[5] == $type) {
                    if (0 == strpos($val[2], '{%') && strpos($val[2], '}'))
                        $val[2] = substr($val[2], 2, -1);
                    $val[3] = isset($val[3]) ? $val[3] : self::EXISTS_VAILIDATE;
                    $val[4] = isset($val[4]) ? $val[4] : 'regex';
                    switch ($val[3]) {
                        case self::MUST_VALIDATE:
                        	if (false === $this->_validationField($data, $val)) {
                                $this->error = $val[2];
                                return false;
                            }
                            break;
                        case self::VALUE_VAILIDATE:
                            if ('' != trim($data[$val[0]])) {
                                if (false === $this->_validationField($data, $val)) {
                                    $this->error = $val[2];
                                    return false;
                                }
                            }
                            break;
                        default:
                            if (isset($data[$val[0]])) {
                                if (false === $this->_validationField($data, $val)) {
                                    $this->error = $val[2];
                                    return false;
                                }
                            }
                    }
                }
            }
            
        }
        return true;
    }

    protected function _validationField($data, $val) {
        switch ($val[4]) {
            case 'function':
            case 'callback':
                $args = isset($val[6]) ? $val[6] : array();
                array_unshift($args, $data[$val[0]]);
                if ('function' == $val[4]) {
                    return call_user_func_array($val[1], $args);
                } else {
                    return call_user_func_array(array(&$this, $val[1]), $args);
                }
            case 'confirm':
                return $data[$val[0]] == $data[$val[1]];
            case 'in':
                return in_array($data[$val[0]], $val[1]);
            case 'equal':
                return $data[$val[0]] == $val[1];
            case 'unique':
                if (is_string($val[0]) && strpos($val[0], ','))
                    $val[0] = explode(',', $val[0]);
                $map = array();
                if (is_array($val[0])) {
                    foreach ($val[0] as $field)
                        $map[$field] = $data[$field];
                } else {
                	if(isset($data[$val[0]]))
                	{
                    	$map[$val[0]] = $data[$val[0]];
                	}
                }
                if (!empty($data[$this->getPk()])) {
                    $map[$this->getPk()] = array('neq', $data[$this->getPk()]);
                }
                if ($this->where($map)->find())
                    return false;
                break;
            case 'regex':
            default:
            	if(isset($data[$val[0]]))
            	{
                	return $this->regex($data[$val[0]], $val[1]);
            	}
            	return false;
        }
        return true;
    }

    public function query($sql) {
        if (!empty($sql)) {
            if (strpos($sql, '__TABLE__'))
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            return $this->db->query($sql);
        }else {
            return false;
        }
    }
    
	public function bindQuery($sql,$array) {
        if (!empty($sql) && !empty($array)) {
            if (strpos($sql, '__TABLE__'))
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            return $this->db->bindQuery($sql,$array);
        }else {
            return false;
        }
    }

    public function execute($sql) {
        if (!empty($sql)) {
            if (strpos($sql, '__TABLE__'))
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            return $this->db->execute($sql);
        }else {
            return false;
        }
    }

    public function db($linkNum, $config='') {
        static $_db = array();
        if (!isset($_db[$linkNum])) {
            $_db[$linkNum] = Db::getInstance($config);
        } elseif (NULL === $config) {
            $_db[$linkNum]->close();
            unset($_db[$linkNum]);
            return;
        }
        $this->db = $_db[$linkNum];
        return $this;
    }

    public function getModelName() {
        if (empty($this->name))
            $this->name = substr(get_class($this), 0, -5);
        return $this->name;
    }

    /**
     * get table name error
     * Enter description here ...
     */
    public function getTableName() {
        if (empty($this->trueTableName)) {
            $tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            $tableName .= $this->name;
         
            $tableName .= ! empty($this->tableSuffix) ? $this->tableSuffix : '';
            if (!empty($this->dbName))
                $tableName = $this->dbName . '.' . $tableName;
            $this->trueTableName = strtolower($tableName);
        }
        return $this->trueTableName;
    }

    public function startTrans() {
        $this->commit();
        $this->db->startTrans();
        return;
    }

    public function commit() {
        return $this->db->commit();
    }

    public function rollback() {
        return $this->db->rollback();
    }

    public function getError() {
        return $this->error;
    }

    public function getDbError() {
        return $this->db->getError();
    }

    public function getLastInsID() {
        return $this->db->lastInsID;
    }

    public function getLastSql() {
        return $this->db->getLastSql();
    }

    public function getPk() {
        return isset($this->fields['_pk']) ? $this->fields['_pk'] : $this->pk;
    }

    public function getDbFields() {
        return $this->fields;
    }

    public function data($data) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        } elseif (is_string($data)) {
            parse_str($data, $data);
        } elseif (!is_array($data)) {
            model_throw_exception('_DATA_TYPE_INVALID_');
        }
        $this->data = $data;
        return $this;
    }

    public function join($join) {
        if (is_array($join))
            $this->options['join'] = $join;
        else
            $this->options['join'][] = $join;
        return $this;
    }

    public function setProperty($name, $value) {
        if (property_exists($this, $name))
            $this->$name = $value;
        return $this;
    }

}

/**
 * Db Class
 * use factory mode,now use mysql Only
 */
class Db {

    protected $dbType = null;
    protected $autoFree = false;
    public    $debug = false;
    protected $pconnect = false;
    protected $queryStr = '';
    public    $lastInsID = null;
    protected $numRows = 0;
    protected $numCols = 0;
    protected $transTimes = 0;
    protected $error = '';
    protected $linkID = array();
    protected $_linkID = null;
    protected $queryID = null;
    protected $connected = false;
    protected $config = '';
    protected $beginTime;
    protected $comparison = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE');
    protected $selectSql = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';

    function __construct($config) {
        return $this->factory($config);
    }

    public static function getInstance() {
        $args = func_get_args();
        $o = new Db($args);
        return call_user_func_array(array(&$o, 'factory'), $args);
    }

    public function factory($db_config) {
        if (isset($db_config[0])) {
            $db_config = $db_config[0];
        }
        if (empty($db_config['dbms']))
            model_throw_exception('_NO_DB_CONFIG_');

        $this->dbType = ucwords(strtolower($db_config['dbms']));
        $dbClass = 'Db' . $this->dbType;
        if (class_exists($dbClass)) {
            $db = new $dbClass($db_config);
            if ('pdo' != strtolower($db_config['dbms'])) {
                $db->dbType = strtoupper($this->dbType);
            }
        } else {
            model_throw_exception('_NOT_SUPPORT_DB_' . ': ' . $db_config['dbms']);
        }
        return $db;
    }

    private function parseConfig($db_config='') {
        if (isset($db_config[0])) {
            $db_config = $db_config[0];
        }
        return $db_config;
    }

    public function addConnect($config, $linkNum=null) {
        $db_config = $this->parseConfig($config);
        if (empty($linkNum))
            $linkNum = count($this->linkID);
        if (isset($this->linkID[$linkNum]))
            return false;
        return $this->connect($db_config, $linkNum);
    }

    public function switchConnect($linkNum) {
        if (isset($this->linkID[$linkNum])) {
            $this->_linkID = $this->linkID[$linkNum];
            return true;
        } else {
            return false;
        }
    }

    protected function initConnect($master=true) {
        if (!$this->connected)
            $this->_linkID = $this->connect();
    }

    protected function multiConnect($master=false) {
        static $_config = array();
        if (empty($_config)) {
            foreach ($this->config as $key => $val) {
                $_config[$key] = explode(',', $val);
            }
        }
        $r = floor(mt_rand(0, count($_config['hostname']) - 1));
        $db_config = array(
            'username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0],
            'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0],
            'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0],
            'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0],
            'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0],
            'params' => isset($_config['params'][$r]) ? $_config['params'][$r] : $_config['params'][0],
        );
        return $this->connect($db_config, $r);
    }

    protected function parseLock($lock=false) {
        if (!$lock)
            return '';
        if ('ORACLE' == $this->dbType) {
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }

    protected function parseSet($data) {
        $set = array();
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value))
                $set[] = $this->addSpecialChar($key) . '=' . $value;
        }
        return ' SET ' . implode(',', $set);
    }

    protected function parseValue($value) {
        if (is_string($value)) {
            $value = '\'' . $this->escape_string($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escape_string($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    protected function parseField($fields) {
        if (is_array($fields)) {
            $array = array();
            foreach ($fields as $key => $field) {
                if (!is_numeric($key))
                    $array[] = $this->addSpecialChar($key) . ' AS ' . $this->addSpecialChar($field);
                else
                    $array[] = $this->addSpecialChar($field);
            }
            $fieldsStr = implode(',', $array);
        }elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->addSpecialChar($fields);
        } else {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    protected function parseTable($tables) {
        if (is_string($tables))
            $tables = explode(',', $tables);
        array_walk($tables, array(&$this, 'addSpecialChar'));
        return implode(',', $tables);
    }

    protected function parseWhere($where) {
        $whereStr = '';
        if (is_string($where)) {
            $whereStr = $where;
        } else {
            if (array_key_exists('_logic', $where)) {
                $operate = ' ' . strtoupper($where['_logic']) . ' ';
                unset($where['_logic']);
            } else {
                $operate = ' AND ';
            }
            foreach ($where as $key => $val) {
                $whereStr .= "( ";
                if (0 === strpos($key, '_')) {
                    $whereStr .= $this->parseSpecialWhere($key, $val);
                } else {
                    $key = $this->addSpecialChar($key);
                    if (is_array($val)) {
                        if (is_string($val[0])) {
                            if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i', $val[0])) {
                                $whereStr .= $key . ' ' . $this->comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
                            } elseif ('exp' == strtolower($val[0])) {
                                $whereStr .= ' (' . $key . ' ' . $val[1] . ') ';
                            } elseif (preg_match('/IN/i', $val[0])) {
                                if (is_string($val[1])) {
                                    $val[1] = explode(',', $val[1]);
                                }
                                $zone = implode(',', $this->parseValue($val[1]));
                                $whereStr .= $key . ' ' . strtoupper($val[0]) . ' (' . $zone . ')';
                            } elseif (preg_match('/BETWEEN/i', $val[0])) {
                                $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                                $whereStr .= ' (' . $key . ' BETWEEN ' . $data[0] . ' AND ' . $data[1] . ' )';
                            } else {
                                model_throw_exception('_EXPRESS_ERROR_' . ':' . $val[0]);
                            }
                        } else {
                            $count = count($val);
                            if (in_array(strtoupper(trim($val[$count - 1])), array('AND', 'OR', 'XOR'))) {
                                $rule = strtoupper(trim($val[$count - 1]));
                                $count = $count - 1;
                            } else {
                                $rule = 'AND';
                            }
                            for ($i = 0; $i < $count; $i++) {
                                $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                                if ('exp' == strtolower($val[$i][0])) {
                                    $whereStr .= '(' . $key . ' ' . $data . ') ' . $rule . ' ';
                                } else {
                                    $op = is_array($val[$i]) ? $this->comparison[strtolower($val[$i][0])] : '=';
                                    $whereStr .= '(' . $key . ' ' . $op . ' ' . $this->parseValue($data) . ') ' . $rule . ' ';
                                }
                            }
                            $whereStr = substr($whereStr, 0, -4);
                        }
                    } else {
                            //TODO：TEST
                            $whereStr .= $key . " = " . $this->parseValue($val);
                    }
                }
                $whereStr .= ' )' . $operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    protected function parseSpecialWhere($key, $val) {
        $whereStr = '';
        switch ($key) {
            case '_string':
                $whereStr = $val;
                break;
            case '_complex':
                $whereStr = substr($this->parseWhere($val), 6);
                break;
            case '_query':
                parse_str($val, $where);
                if (array_key_exists('_logic', $where)) {
                    $op = ' ' . strtoupper($where['_logic']) . ' ';
                    unset($where['_logic']);
                } else {
                    $op = ' AND ';
                }
                $array = array();
                foreach ($where as $field => $data)
                    $array[] = $this->addSpecialChar($field) . ' = ' . $this->parseValue($data);
                $whereStr = implode($op, $array);
                break;
        }
        return $whereStr;
    }

    protected function parseLimit($limit) {
        return!empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    protected function parseJoin($join) {
        $joinStr = '';
        if (!empty($join)) {
            if (is_array($join)) {
                foreach ($join as $key => $_join) {
                    if (false !== stripos($_join, 'JOIN'))
                        $joinStr .= ' ' . $_join;
                    else
                        $joinStr .= ' LEFT JOIN ' . $_join;
                }
            }else {
                $joinStr .= ' LEFT JOIN ' . $join;
            }
        }
        return $joinStr;
    }

    protected function parseOrder($order) {
        if (is_array($order)) {
            $array = array();
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $this->addSpecialChar($val);
                } else {
                    $array[] = $this->addSpecialChar($key) . ' ' . $val;
                }
            }
            $order = implode(',', $array);
        }
        return!empty($order) ? ' ORDER BY ' . $order : '';
    }

    protected function parseGroup($group) {
        return!empty($group) ? ' GROUP BY ' . $group : '';
    }

    protected function parseHaving($having) {
        return!empty($having) ? ' HAVING ' . $having : '';
    }

    protected function parseDistinct($distinct) {
        return!empty($distinct) ? ' DISTINCT ' : '';
    }

    public function insert($data, $options=array(), $replace=false) {
    	
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            //var_dump($value);die;
            if (is_scalar($value)) {
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    public function selectInsert($fields, $table, $options=array()) {
        if (is_string($fields))
            $fields = explode(',', $fields);
        array_walk($fields, array($this, 'addSpecialChar'));
        $sql = 'INSERT INTO ' . $this->parseTable($table) . ' (' . implode(',', $fields) . ') ';
        $sql .= str_replace(
                        array('%TABLE%', '%DISTINCT%', '%FIELDS%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'),
                        array(
                            $this->parseTable($options['table']),
                            $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                            $this->parseField(isset($options['field']) ? $options['field'] : '*'),
                            $this->parseJoin(isset($options['join']) ? $options['join'] : ''),
                            $this->parseWhere(isset($options['where']) ? $options['where'] : ''),
                            $this->parseGroup(isset($options['group']) ? $options['group'] : ''),
                            $this->parseHaving(isset($options['having']) ? $options['having'] : ''),
                            $this->parseOrder(isset($options['order']) ? $options['order'] : ''),
                            $this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
                        ), $this->selectSql);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    public function update($data, $options) {
        $sql = 'UPDATE '
                . $this->parseTable($options['table'])
                . $this->parseSet($data)
                . $this->parseWhere(isset($options['where']) ? $options['where'] : '')
                . $this->parseOrder(isset($options['order']) ? $options['order'] : '')
                . $this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
                . $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    public function delete($options=array()) {
        $sql = 'DELETE FROM '
                . $this->parseTable($options['table'])
                . $this->parseWhere(isset($options['where']) ? $options['where'] : '')
                . $this->parseOrder(isset($options['order']) ? $options['order'] : '')
                . $this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
                . $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    public function select($options=array()) {
        if (isset($options['page'])) {
            list($page, $listRows) = explode(',', $options['page']);
            $page = $page ? $page : 1;
            $listRows = $listRows ? $listRows : ($options['limit'] ? $options['limit'] : 20);
            $offset = $listRows * ((int) $page - 1);
            $options['limit'] = $offset . ',' . $listRows;
        }
        $sql = str_replace(
                        array('%TABLE%', '%DISTINCT%', '%FIELDS%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'),
                        array(
                            $this->parseTable($options['table']),
                            $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                            $this->parseField(isset($options['field']) ? $options['field'] : '*'),
                            $this->parseJoin(isset($options['join']) ? $options['join'] : ''),
                            $this->parseWhere(isset($options['where']) ? $options['where'] : ''),
                            $this->parseGroup(isset($options['group']) ? $options['group'] : ''),
                            $this->parseHaving(isset($options['having']) ? $options['having'] : ''),
                            $this->parseOrder(isset($options['order']) ? $options['order'] : ''),
                            $this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
                        ), $this->selectSql);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        if(isset($options['keyid'])){
        	return $this->queryKeyID($sql);
        }
        return $this->query($sql);
    }

    protected function addSpecialChar(&$value) {
        if (0 === strpos($this->dbType, 'MYSQL')) {
            $value = trim($value);
            if (false !== strpos($value, ' ') || false !== strpos($value, ',') || false !== strpos($value, '*') || false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos($value, '`')) {
                
            } else {
                $value = '`' . $value . '`';
            }
        }
        return $value;
    }

    public function getLastSql() {
        return $this->queryStr;
    }

    public function getError() {
        return $this->error;
    }

}

class DbMysql extends Db {

    public function __construct($config) {
        if (!extension_loaded('mysql')) {
            model_throw_exception('_NOT_SUPPERT_' . ':mysql');
        }
        if (!empty($config)) {
            $this->config = $config;
        }
    }

    public function connect($config='', $linkNum=0) {

        if (!isset($this->linkID[$linkNum])) {
            if (empty($config))
                $config = $this->config;
            $host = $config['hostname'] . ($config['hostport'] ? ":{$config['hostport']}" : '');
            if ($this->pconnect) {
                $this->linkID[$linkNum] = mysql_pconnect($host, $config['username'], $config['password'], 131072);
            } else {
                $this->linkID[$linkNum] = mysql_connect($host, $config['username'], $config['password'], true, 131072);
            }

            if (!$this->linkID[$linkNum] || (!empty($config['database']) && !mysql_select_db($config['database'], $this->linkID[$linkNum]))) {
                model_throw_exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID[$linkNum]);
            if ($dbVersion >= "4.1") {
                mysql_query("SET NAMES 'utf8'", $this->linkID[$linkNum]);
            }
            if ($dbVersion > '5.0.1') {
                mysql_query("SET sql_mode=''", $this->linkID[$linkNum]);
            }
            $this->connected = true;
            unset($this->config);
        }
        return $this->linkID[$linkNum];
    }
    
    /**
     * Prepare for SQL
     * @param string $strSql
     * @param array $arrData
     */
	private  function _prepare($strSql='', $arrData = array()) {
        if (!empty($arrData) && $strSql) {
            $arrSql = preg_split('/(:[a-zA-Z0-9_]+)/',$strSql, -1, PREG_SPLIT_DELIM_CAPTURE);
            $strResSql = '';
            foreach ($arrSql as $key) {
                $k = substr($key,1);
                if (isset($arrData[$k])) {
                    if(is_string($arrData[$k])) {
                        $strResSql .= "'".$this->_objLinkID->real_escape_string($arrData[$k])."'";
                    } elseif (is_array($arrData[$k])){
                        $strResSql .= "'".$this->_prepareIn($arrData[$k])."'";
                    }else{ 
                    	$strResSql .= $arrData[$k];
                    }
                } else {
                    $strResSql .= $key;
                }
            }
        } else {
            $strResSql = $strSql;
        }
        return $strResSql;
    }
    
 	/**
     * 
     * Prepare for in
     * @param array $arrData
     */
    private function _prepareIn($arrData){
        foreach ($arrData as $key => $value){
            if (is_string($value)) {
                $arrData[$key] =  $this->_objLinkID->real_escape_string($value);
            }
        }
        return implode("','",array_values($arrData)); 
    }
    
    
    /**
     * bind query
     * use a sql and arry ,auto bind then query
     * $db->bindQuery("SELECT * FROM sz_table WHRER id=:id",array('id'=>'123'));
     * @param string $str
     * @param array $arrBind
     */
	public function bindQuery($str, $arrBind=array()) {
        $this->initConnect(false);
        if (!$this->_linkID)
            return false;
        $this->queryStr = $this->_prepare($str, $arrBind);
        if ($this->queryID) {
            $this->free();
        }
        $this->queryID = mysql_query($str, $this->_linkID);
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getAll();
        }
    }
    
    

    public function free() {
        @mysql_free_result($this->queryID);
        $this->queryID = 0;
    }

    public function query($str) {
        $this->initConnect(false);
        if (!$this->_linkID)
            return false;
        $this->queryStr = $str;
        if ($this->queryID) {
            $this->free();
        }
        $this->queryID = mysql_query($str, $this->_linkID);
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getAll();
        }
    }
    
	public function queryKeyID($str) {
        $this->initConnect(false);
        if (!$this->_linkID)
            return false;
        $this->queryStr = $str;
        if ($this->queryID) {
            $this->free();
        }
        $this->queryID = mysql_query($str, $this->_linkID);
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getKeyIDAll();
        }
    }

    public function execute($str) {
        $this->initConnect(true);
        if (!$this->_linkID)
            return false;
        $this->queryStr = $str;
        if ($this->queryID) {
            $this->free();
        }
        $result = mysql_query($str, $this->_linkID);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = mysql_affected_rows($this->_linkID);
            $this->lastInsID = mysql_insert_id($this->_linkID);
            return $this->numRows;
        }
    }

    public function startTrans() {
        $this->initConnect(true);
        if (!$this->_linkID)
            return false;
        if ($this->transTimes == 0) {
            mysql_query('START TRANSACTION', $this->_linkID);
        }
        $this->transTimes++;
        return;
    }

    public function commit() {
        if ($this->transTimes > 0) {
            $result = mysql_query('COMMIT', $this->_linkID);
            $this->transTimes = 0;
            if (!$result) {
                model_throw_exception($this->error());
            }
        }
        return true;
    }

    public function rollback() {
        if ($this->transTimes > 0) {
            $result = mysql_query('ROLLBACK', $this->_linkID);
            $this->transTimes = 0;
            if (!$result) {
                model_throw_exception($this->error());
            }
        }
        return true;
    }

    private function getAll() {
        $result = array();
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($this->queryID)) {
                $result[] = $row;
            }
            mysql_data_seek($this->queryID, 0);
        }
        return $result;
    }
    
    /**
     * get the id as key of the dataset
     */
	private function getKeyIDAll() {
        $result = array();
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($this->queryID)) {
            	if(isset($row['id'])){
                	$result[$row['id']] = $row;
            	}else{
            		$result[] = $row;
            	}
            }
            mysql_data_seek($this->queryID, 0);
        }
        return $result;
    }

    public function getFields($tableName) {
        $result = $this->query('SHOW COLUMNS FROM ' . $tableName);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name' => $val['Field'],
                    'type' => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    public function getTables($dbName='') {
        if (!empty($dbName)) {
            $sql = 'SHOW TABLES FROM ' . $dbName;
        } else {
            $sql = 'SHOW TABLES ';
        }
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    public function replace($data, $options=array()) {
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value)) {
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql = 'REPLACE INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        return $this->execute($sql);
    }

    public function insertAll($datas, $options=array()) {
        if (!is_array($datas[0]))
            return false;
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'addSpecialChar'));
        $values = array();
        foreach ($datas as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                $val = $this->parseValue($val);
                if (is_scalar($val)) {
                    $value[] = $val;
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        $sql = 'INSERT INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
        return $this->execute($sql);
    }

    public function close() {
        if (!empty($this->queryID))
            mysql_free_result($this->queryID);
        if ($this->_linkID && !mysql_close($this->_linkID)) {
            model_throw_exception($this->error());
        }
        $this->_linkID = 0;
    }

    public function error() {
        $this->error = mysql_error($this->_linkID);
        if ($this->debug && '' != $this->queryStr) {
            $this->error .= "\n [ SQL ] : " . $this->queryStr;
        }
        return $this->error;
    }

    public function escape_string($str) {
        return mysql_escape_string($str);
    }

    public function __destruct() {
        $this->close();
    }
}