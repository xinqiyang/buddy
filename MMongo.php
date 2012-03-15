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
 * $mongo = MMongo::instance('mongo');
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
class MMongo 
{
	
	static $mongodb_arr;
	private  $mongo;
	private  $curr_db_name;
	private  $curr_table_name;
	private  $error;
	/**
	 * construct
	 * @param string $mongo_server mongo server
	 * @param bool $connect connect
	 * @param bool $auto_balance auto balance
	 */
	private function __construct($mongo_server)
	{
		if (isset($mongo_server['instances']) && isset($mongo_server['autobalance']) && isset($mongo_server['connect']) && isset($mongo_server['database']))
		{
			$auto_balance = $mongo_server['autobalance'];
			$connect = $mongo_server['connect'];
			
			if(is_array($mongo_server['instances'])){
				$mongo_server_num = count($mongo_server['instances']);
				if ($mongo_server_num > 1 && $auto_balance)
				{
					$prior_server_num = mt_rand(1, $mongo_server_num);
					$rand_keys = array_rand($mongo_server['instances'],$mongo_server_num);
					$mongo_server_str = $mongo_server['instances'][$prior_server_num-1];
					foreach ($rand_keys as $key)
					{
						if ($key != $prior_server_num - 1)
						{
							$mongo_server_str .= ',' . $mongo_server['instances'][$key];
						}
					}
				}else{
					$mongo_server_str = $mongo_server['instances'][0];
				}
			}
			else
			{
				$mongo_server_str = $mongo_server['instances'];
			}
		}

		try {
			$this->mongo = new Mongo($mongo_server_str, array('connect'=>$connect));
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			throw_exception(__CLASS__.''.__FUNCTION__.":new error ".$e->getMessage());
			return false;
		}
	}

	/**
	 * return mongodb instance
	 * 
	 * @param string $mongo_server
	 * @param bool $flag
	 */
	public static function instance($node='mongo')
	{
		$node = empty($node) ? 'mongo' : $node;
		$arrConfig = C("mongo.$node");
		if(empty($arrConfig)){
			throw_exception(__CLASS__.'/'.__FUNCTION__.":get mongo.$node config error pleae check resource config");
		}
		if(!isset(self::$mongodb_arr[$node]))
		{
			$mongo = new self($arrConfig);
			$mongo->selectDb($arrConfig['database']);
			self::$mongodb_arr[$node] = $mongo;
		}
		return self::$mongodb_arr[$node];
	}

	private  function connect()
	{
		try {
			$this->mongo->connect();
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			throw_exception(__CLASS__.'/'.__FUNCTION__.":".$e->getMessage());
			return false;
		}
	}

	/**
	 * select db
	 */
	public function selectDb($dbname)
	{
		$this->curr_db_name = $dbname;
	}


	public function ensureIndex($table_name, $index, $index_param=array())
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


	public function insert($table_name, $record)
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

	public function count($table_name)
	{
		$dbname = $this->curr_db_name;
		return $this->mongo->$dbname->$table_name->count();
	}

	public function update($table_name, $condition, $newdata, $options=array())
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


	public function remove($table_name, $condition, $options=array())
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


	public function find($table_name, $query_condition, $result_condition=array(), $fields=array())
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


	public function findOne($table_name, $condition, $fields=array())
	{
		$dbname = $this->curr_db_name;
		return $this->mongo->$dbname->$table_name->findOne($condition, $fields);
	}


	public function getError()
	{
		return $this->error;
	}
}
