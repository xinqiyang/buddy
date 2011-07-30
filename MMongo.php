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
* Mongodb class
* 
* examples:
* $mongo = new MMongo("127.0.0.1:11223");
* $mongo->selectDb("test_db");
* $mongo->ensureIndex("test_table", array("id"=>1), array('unique'=>true));
* $mongo->count("test_table");
* $mongo->insert("test_table", array("id"=>2, "title"=>"asdqw"));
* $mongo->update("test_table", array("id"=>1),array("id"=>1,"title"=>"bbb"));
* $mongo->update("test_table", array("id"=>1),array("id"=>1,"title"=>"bbb"),array("upsert"=>1));
* $mongo->find("c", array("title"=>"asdqw"), array("start"=>2,"limit"=>2,"sort"=>array("id"=>1)))
* $mongo->findOne("$mongo->findOne("ttt", array("id"=>1))", array("id"=>1));
* $mongo->remove("ttt", array("title"=>"bbb"));
* $mongo->remove("ttt", array("title"=>"bbb"), array("justOne"=>1));
* $mongo->getError();
* 
* @author xinqiyang
 *
 */
class MMongo {

	var $mongo;

	var $curr_db_name;
	var $curr_table_name;
	var $error;

	/**
	 * counstruct
	 * 
	 * 支持传入多个mongo_server(1.一个出问题时连接其它的server 2.自动将查询均匀分发到不同server)
	 *
	 * 参数：
	 * $mongo_server:数组或字符串-array("127.0.0.1:1111", "127.0.0.1:2222")-"127.0.0.1:1111"
	 * $connect:初始化mongo对象时是否连接，默认连接
	 * $auto_balance:是否自动做负载均衡，默认是
	 *
	 * 返回值：
	 * 成功：mongo object
	 * 失败：false
	 */
	function __construct($mongo_server, $connect=true, $auto_balance=true)
	{
		if (is_array($mongo_server))
		{
			$mongo_server_num = count($mongo_server);
			if ($mongo_server_num > 1 && $auto_balance)
			{
				$prior_server_num = rand(1, $mongo_server_num);
				$rand_keys = array_rand($mongo_server,$mongo_server_num);
				$mongo_server_str = $mongo_server[$prior_server_num-1];
				foreach ($rand_keys as $key)
				{
					if ($key != $prior_server_num - 1)
					{
						$mongo_server_str .= ',' . $mongo_server[$key];
					}
				}
			}
			else
			{
				$mongo_server_str = implode(',', $mongo_server);
			}
		}
		else
		{
			$mongo_server_str = $mongo_server;
		}
		
		try {
			$this->mongo = new Mongo($mongo_server, array('connect'=>$connect));
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * get mongodb instance
	 * Enter description here ...
	 * @param unknown_type $mongo_server
	 * @param unknown_type $flag
	 */
	function getInstance($mongo_server, $flag=array())
	{
		static $mongodb_arr;
		if (empty($flag['tag']))
		{
			$flag['tag'] = 'default';
		}
		if (isset($flag['force']) && $flag['force'] == true)
		{
			$mongo = new MMongo($mongo_server);
			if (empty($mongodb_arr[$flag['tag']]))
			{
				$mongodb_arr[$flag['tag']] = $mongo;
			}
			return $mongo;
		}
		else if (isset($mongodb_arr[$flag['tag']]) && is_resource($mongodb_arr[$flag['tag']]))
		{
			return $mongodb_arr[$flag['tag']];
		}
		else
		{
			$mongo = new MMongo($mongo_server);
			$mongodb_arr[$flag['tag']] = $mongo;
			return $mongo;
		}
	}

	/**
	 * 连接mongodb server
	 *
	 * 参数：无
	 *
	 * 返回值：
	 * 成功：true
	 * 失败：false
	 */
	function connect()
	{
		try {
			$this->mongo->connect();
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * select db
	 *
	 * 参数：$dbname
	 *
	 * 返回值：无
	 */
	function selectDb($dbname)
	{
		$this->curr_db_name = $dbname;
	}

	/**
	 * 创建索引：如索引已存在，则返回。
	 *
	 * 参数：
	 * $table_name:表名
	 * $index:索引-array("id"=>1)-在id字段建立升序索引
	 * $index_param:其它条件-是否唯一索引等
	 *
	 * 返回值：
	 * 成功：true
	 * 失败：false
	 */
	function ensureIndex($table_name, $index, $index_param=array())
	{
		$dbname = $this->curr_db_name;
		$index_param['safe'] = 1;
		try {
			$this->mongo->$dbname->$table_name->ensureIndex($index, $index_param);
			return true;
		}
		catch (MongoCursorException $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * 插入记录
	 *
	 * 参数：
	 * $table_name:表名
	 * $record:记录
	 *
	 * 返回值：
	 * 成功：true
	 * 失败：false
	 */
	function insert($table_name, $record)
	{
		$dbname = $this->curr_db_name;
		try {
			$this->mongo->$dbname->$table_name->insert($record, array('safe'=>true));
			return true;
		}
		catch (MongoCursorException $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * 查询表的记录数
	 *
	 * 参数：
	 * $table_name:表名
	 *
	 * 返回值：表的记录数
	 */
	function count($table_name)
	{
		$dbname = $this->curr_db_name;
		return $this->mongo->$dbname->$table_name->count();
	}

	/**
	 * 更新记录
	 *
	 * 参数：
	 * $table_name:表名
	 * $condition:更新条件
	 * $newdata:新的数据记录
	 * $options:更新选择-upsert/multiple
	 *
	 * 返回值：
	 * 成功：true
	 * 失败：false
	 */
	function update($table_name, $condition, $newdata, $options=array())
	{
		$dbname = $this->curr_db_name;
		$options['safe'] = 1;
		if (!isset($options['multiple']))
		{
			$options['multiple'] = 0;
		}
		try {
			$this->mongo->$dbname->$table_name->update($condition, $newdata, $options);
			return true;
		}
		catch (MongoCursorException $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * 删除记录
	 *
	 * 参数：
	 * $table_name:表名
	 * $condition:删除条件
	 * $options:删除选择-justOne
	 *
	 * 返回值：
	 * 成功：true
	 * 失败：false
	 */
	function remove($table_name, $condition, $options=array())
	{
		$dbname = $this->curr_db_name;
		$options['safe'] = 1;
		try {
			$this->mongo->$dbname->$table_name->remove($condition, $options);
			return true;
		}
		catch (MongoCursorException $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * 查找记录
	 *
	 * 参数：
	 * $table_name:表名
	 * $query_condition:字段查找条件
	 * $result_condition:查询结果限制条件-limit/sort等
	 * $fields:获取字段
	 *
	 * 返回值：
	 * 成功：记录集
	 * 失败：false
	 */
	function find($table_name, $query_condition, $result_condition=array(), $fields=array())
	{
		$dbname = $this->curr_db_name;
		$cursor = $this->mongo->$dbname->$table_name->find($query_condition, $fields);
		if (!empty($result_condition['start']))
		{
			$cursor->skip($result_condition['start']);
		}
		if (!empty($result_condition['limit']))
		{
			$cursor->limit($result_condition['limit']);
		}
		if (!empty($result_condition['sort']))
		{
			$cursor->sort($result_condition['sort']);
		}
		$result = array();
		try {
			while ($cursor->hasNext())
			{
				$result[] = $cursor->getNext();
			}
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
		catch (MongoCursorTimeoutException $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
		return $result;
	}

	/**
	 * 查找一条记录
	 *
	 * 参数：
	 * $table_name:表名
	 * $condition:查找条件
	 * $fields:获取字段
	 *
	 * 返回值：
	 * 成功：一条记录
	 * 失败：false
	 */
	function findOne($table_name, $condition, $fields=array())
	{
		$dbname = $this->curr_db_name;
		return $this->mongo->$dbname->$table_name->findOne($condition, $fields);
	}

	/**
	 * 获取当前错误信息
	 *
	 * 参数：无
	 *
	 * 返回值：当前错误信息
	 */
	function getError()
	{
		return $this->error;
	}
}
