<?php
/**
 * DB类
 * 采用PDO的方式
 *
 * 2015.3.27 16:31
 * @modify 2015.4.7 13:46  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db;

use xb\db\base\Db as InterfaceDb;

use xb\db\Query as QueryDb;

class Db implements InterfaceDb {

	/*
	 * 单例对象
	 */
	static private $_dbObj = [];

	private $_selectQuery = null;
	private $_updateQuery = null;
	private $_insertQuery = null;
	private $_deleteQuery = null;

	/*
	 * 数据库拆分实例化对象
	 */
	private $_sharding = [];

	/*
	 * 连接数据库实例化对象
	 */
	private $_connection = [];

	/*
	 * 连接数据库对象 PDO对象
	 */
	private $_conn = [];

	/*
	 * PDOStatement对象
	 */
	private $_xbPdo = [];

	/*
	 * 数据库Query实例化对象
	 */
	private $_queryDb = [];

	/*
	 * 数据库配置数组
	 */
	private $_dbInfo = [];

	/*
	 * 拆分前dbname
	 */
	protected $_dbName = '';

	/*
	 * 拆分前table name
	 */
	protected $_tblName = '';

	/*
	 * 真实dbname
	 */
	protected $_realDbName = '';

	/*
	 * 真实table name
	 */
	protected $_realTblName = '';

	/*
	 * 数据库编号，允许为空
	 */
	protected $_dbNumber = '';


	/*
	 * 数据库类型 默认mysql
	 */
	protected $_dbType = 'mysql';

	/**
	 * 构造方法，主要设置数据库类型
	 * 
	 * @param string $dbType 默认mysql
	 */
	public function __construct($dbName, $config = [], $dbType = 'mysql') {
		$this->_dbType = $dbType;
		$this->changeDb($dbName);
		$this->_dbInfo[$this->_dbName] = $config;
	}

	/**
	 * 单例方法
	 * 
	 * @param  string $dbName 数据库名，这里主要作用是区分多个数据库之间的实例化对象
	 * @param  string $dbType 默认mysql
	 * @return object
	 */
	static public function instance($dbName, $dbType = 'mysql', $config = []) {
		if (false === isset(self::$_dbObj[$dbName])) {
			self::$_dbObj[$dbName] = new self($dbType);
		}
		/*
		 * 默认设置dbname为current db
		 */
		self::$_dbObj[$dbName]->changeDb($dbName);
		return self::$_dbObj[$dbName];
	}

	/**
	 * 初始化信息，目前需要初始化拆分数据库方式，以及读取当前数据库配置
	 *
	 * @param int $xbType 1 从 2 主
	 * @return void
	 */
	private function _init($xbType = 1) {
		/*
		 * 实例化拆分数据库类
		 */
		if (false === isset($this->_sharding[$this->_dbName])) {
			//$this->_dbInfo[$this->_dbName] = Loader::loadConfig('database.' . $this->_dbType . '.' . $this->_dbName);
			$this->_sharding[$this->_dbName] = new Sharding($this->_dbInfo[$this->_dbName]);
		}
		/*
		 * 实例化连接数据库类
		 */
		if (2 == $xbType) {
			if (false === isset($this->_connection[$this->_dbName]['master'])) {
				$this->_connection[$this->_dbName]['master'] = new Connect($this->_dbType, $this->_dbInfo[$this->_dbName]);
			}
		} else {
			if (false === isset($this->_connection[$this->_dbName]['slave'])) {
				$this->_connection[$this->_dbName]['slave'] = new Connect($this->_dbType, $this->_dbInfo[$this->_dbName]);
			}
		}

		/*
		 * 实例化数据库Query类
		 */
		
		if (false === isset($this->_queryDb[$this->_dbName])) {
			$this->_queryDb[$this->_dbName] = new QueryDb($this->_dbType);
		}
	}

	/**
	 * 加载query必要信息
	 * 
	 * @return void
	 */
	private function _initQuery($xbType = 1, $split = null) {
		$this->_sharding($split);
		$this->_connect($xbType);
	}

	/**
	 * 构造拆分算法
	 * 
	 * 分库分表有几种方式：
	 * 1、配置文件配置split, tblsplit为nosplit则该处split为null
	 * 2、配置为数组时，采用配置文件的拆分规则   //todo 需测试
	 * 		a.数据库的split配置
	 * 		第一维数组key为数据库编号
	 * 	  分割规则按照参照的ID区间进行区分，如userId：1-1000000在一个数据库1000001-2000000在另一个数据库
	 * 		array(
	 *				'0' => array('min' => 0, 'max' => 5),
	 *				'1' => array('min' => 6, 'max' => 10),
	 *			),
	 *		b.在数据库的split配置为以上模式时，表的tblsplit有几种写法：
	 *			I.不分表  nosplit
	 *			II.分表 特殊方式  special
	 *				采用sharding算法，split为数组,key为sharding的参照信息，如：userId, 手机号等, type为hex,date等,
	 *				method为split的方式，具体的值参考对应type类常量
	 *				array(key, type, method)
	 *			III. 分表 配置数组
	 *			array(
	 *				'0' => array('0' => array('min' => 0, 'max' => 3), '1' => array('min' => '4', 'max' => '5')),
	 *				'1' => array('0' => array('min' => 6, 'max' => 7), '1' => array('min' => '8', 'max' => '10')),
	 *			),
	 * 3、配置文件配置split为special，则该处split需要为数组，参考2.b.II的split写法
	 *
	 * @param mix $split null|array
	 * 
	 * @return object
	 */
	private function _sharding($split = null) {
		/*
		 * 处理分库分表的情况
		 */
		if (true === is_array($split) && false === empty($split)) {
			list($key, $type, $method) = $split;
			$split = $this->_sharding[$this->_dbName]->xbShardingAdapter($type)->covert($key, $method)->getId();
		}

		/*
		 * 获取真实数据库名与表名
		 */
		$realInfo = $this->_sharding[$this->_dbName]->getRealInfo($this->_dbName, $this->_tblName, $split);
		$this->_realDbName = $realInfo['realDbName'];
		$this->_realTblName = $realInfo['realTblName'];
		$this->_dbNumber = $realInfo['dbNumber'];
	}

	/**
	 * 连接数据库
	 * 
	 * @param  integer $xbType   1 从 2 主
	 * @param  integer $connType 0短连接 1长连接
	 * 
	 * @return void
	 */
	private function _connect($xbType = 1, $connType = 0) {
		if (2 == $xbType) {
			$this->_conn[$this->_dbName] = $this->_connection[$this->_dbName]['master']->connect($this->_realDbName, $this->_dbNumber, $xbType, $connType);
		} else {
			$this->_conn[$this->_dbName] = $this->_connection[$this->_dbName]['slave']->connect($this->_realDbName, $this->_dbNumber, $xbType, $connType);
		}
	}

	/**
	 * Query查询
	 * @param  string $sql   待查询SQL语句，不做prepare
	 * 
	 * @return object  PDOStatement
	 */
	private function _query($sql) {
		return $this->_queryDb[$this->_dbName]->query($this->_conn[$this->_dbName], $sql);
	}

	/**
	 * insert update delete
	 * @param  string $sql   待执行SQL语句，不做prepare
	 * 
	 * @return mix  int|false
	 */
	private function _exec($sql) {
		return $this->_queryDb[$this->_dbName]->exec($this->_conn[$this->_dbName], $sql);
	}

	/**
	 * 获取刚刚插入的主键ID
	 * 
	 * @return string
	 */
	private function _getLastInsertId() {
		return $this->_queryDb[$this->_dbName]->getInsertId($this->_conn[$this->_dbName]);
	}

	/**
	 * 获取一条记录结果集
	 *
	 * array('field' => xxxxx, 'field' => xxxxx);
	 * @return mix array|false
	 */
	private function _fetch() {
		/*
		 * 获取QueryInfo
		 * array(sql, bindParam)
		 */
		$queryInfo = $this->_selectQuery->getQuery();
		list($sql, $param) = $queryInfo;
		/*
		 * 预处理执行SQL
		 */
		$this->_xbPdo = $this->_queryDb[$this->_dbName]->prepare($this->_conn[$this->_dbName], $sql);
		/*
		 * 获取记录结果集
		 */
		$result = $this->_queryDb[$this->_dbName]->fetch($this->_xbPdo, $param);
		/**
		 * 重置查询
		 */
		$this->_selectQuery->reset();
		return $result;
	}

	/**
	 * 获取多条记录结果集
	 * 
	 * array(0 => array('field' => xxxxx), 1 => array('field' => xxxxx));
	 * @return mix array|false
	 */
	private function _fetchAll() {
		/*
		 * 获取QueryInfo
		 * array(sql, bindParam)
		 */
		$queryInfo = $this->_selectQuery->getQuery();
		list($sql, $param) = $queryInfo;
		/*
		 * 预处理执行SQL
		 */
		$this->_xbPdo = $this->_queryDb[$this->_dbName]->prepare($this->_conn[$this->_dbName], $sql);
		/*
		 * 获取记录结果集
		 */
		$result = $this->_queryDb[$this->_dbName]->fetchAll($this->_xbPdo, $param);
		/**
		 * 重置查询
		 */
		$this->_selectQuery->reset();
		return $result;
	}

	/**
	 * 新增记录集
	 * 如果有自增主键，则返回该主键值，否则返回string类型0
	 * 如果insert失败则返回false
	 * 
	 * @return mix string|false
	 */
	private function _insert() {
		/*
		 * 获取QueryInfo
		 * array(sql, bindParam)
		 */
		$queryInfo = $this->_insertQuery->getQuery();
		list($sql, $param) = $queryInfo;
		/*
		 * 预处理执行SQL
		 */
		$this->_xbPdo = $this->_queryDb[$this->_dbName]->prepare($this->_conn[$this->_dbName], $sql);
		/*
		 * 执行Query
		 */
		$result = $this->_queryDb[$this->_dbName]->execute($this->_xbPdo, $param);
		/**
		 * 重置查询
		 */
		$this->_insertQuery->reset();

		if (true === $result) {
			return $this->_getLastInsertId();
		}
		return false;
	}

	/**
	 * 更新记录集
	 * 
	 * @return boolean
	 */
	private function _update() {
		/*
		 * 获取QueryInfo
		 * array(sql, bindParam)
		 */
		$queryInfo = $this->_updateQuery->getQuery();
		list($sql, $param) = $queryInfo;
		/*
		 * 预处理执行SQL
		 */
		$this->_xbPdo = $this->_queryDb[$this->_dbName]->prepare($this->_conn[$this->_dbName], $sql);
		/*
		 * 执行Query
		 */
		$result = $this->_queryDb[$this->_dbName]->execute($this->_xbPdo, $param);
		/**
		 * 重置查询
		 */
		$this->_updateQuery->reset();
		return $result;
	}

	/**
	 * 删除记录集
	 * 
	 * @return boolean
	 */
	private function _delete() {
		/*
		 * 获取QueryInfo
		 * array(sql, bindParam)
		 */
		$queryInfo = $this->_deleteQuery->getQuery();
		list($sql, $param) = $queryInfo;

		/*
		 * 预处理执行SQL
		 */
		$this->_xbPdo = $this->_queryDb[$this->_dbName]->prepare($this->_conn[$this->_dbName], $sql);
		/*
		 * 执行Query
		 */
		$result = $this->_queryDb[$this->_dbName]->execute($this->_xbPdo, $param);
		/**
		 * 重置查询
		 */
		$this->_deleteQuery->reset();
		return $result;
	}

	/****************** 事务处理相关 *****************/

	/**
	 * 开启本地事务
	 * 
	 * @param  mix $split null|string
	 * 
	 * @return void
	 */
	private function _open($split = null) {
		$this->_init(2);
		$this->_initQuery(2, $split);
		return $this->_queryDb[$this->_dbName]->open($this->_conn[$this->_dbName]);
	}

	/**
	 * 提交本地事务
	 * 
	 * @return void
	 */
	private function _commit() {
		return $this->_queryDb[$this->_dbName]->commit($this->_conn[$this->_dbName]);
	}

	/**
	 * 回滚本地事务
	 * 
	 * @return void
	 */
	private function _rollback() {
		return $this->_queryDb[$this->_dbName]->rollBack($this->_conn[$this->_dbName]);
	}

	/**
	 * 开启分布式事务
	 * 
	 * @param  string $uniqid XID
	 * @param  mix $split null|string
	 * 
	 * @return void
	 */
	private function _openXA($uniqid, $split = null) {
		$this->_init(2);
		$this->_initQuery(2, $split);
		return $this->_queryDb[$this->_dbName]->openXA($this->_conn[$this->_dbName], $uniqid);
	}

	/**
	 * 结束SQL处理状态
	 * 
	 * @param  string $uniqid XID
	 * 
	 * @return void
	 */
	private function _endXA($uniqid) {
		return $this->_queryDb[$this->_dbName]->endXA($this->_conn[$this->_dbName], $uniqid);
	}

	/**
	 * 实现事务提交的准备工作
	 * 
	 * @param  string $uniqid XID
	 * 
	 * @return void
	 */
	private function _prepareXA($uniqid) {
		return $this->_queryDb[$this->_dbName]->prepareXA($this->_conn[$this->_dbName], $uniqid);
	}

	/**
	 * 事务最终提交，事务完成。
	 * 
	 * @param  string $uniqid XID
	 * 
	 * @return void
	 */
	private function _commitXA($uniqid) {
		return $this->_queryDb[$this->_dbName]->commitXA($this->_conn[$this->_dbName], $uniqid);
	}

	/**
	 * 事务回滚并终止。
	 * 
	 * @param  string $uniqid XID
	 * 
	 * @return void
	 */
	private function _rollbackXA($uniqid) {
		return $this->_queryDb[$this->_dbName]->rollbackXA($this->_conn[$this->_dbName], $uniqid);
	}

	/***************  以下为初始化数据库与表 **********/

	/**
	 * 改变当前数据库设置
	 * 
	 * @param  string $dbName 数据库名
	 * 
	 * @return object
	 */
	public function changeDb($dbName) {
		$this->_dbName = $dbName;
		return $this;
	}

	/**
	 * 改变当前数据库表
	 * 
	 * @param  string $tblName 数据库表名
	 * 
	 * @return object
	 */
	public function changeTbl($tblName) {
		$this->_tblName = $tblName;
		return $this;
	}

	/**************  以下为需要执行Query的类型 ************/

	/**
	 * 构造查询
	 * 
	 * @return object
	 */
	public function select($split = null) {
		$this->_init(1);
		$this->_initQuery(1, $split);
		$this->_selectQuery = new SelectQuery($this->_dbType, $this->_realDbName, $this->_realTblName);
		return $this->_selectQuery;
	}

	/**
	 * 构造新增
	 * 
	 * @return object
	 */
	public function insert($split = null) {
		$this->_init(2);
		$this->_initQuery(2, $split);
		$this->_insertQuery = new InsertQuery($this->_dbType, $this->_realDbName, $this->_realTblName);
		return $this->_insertQuery;
	}

	/**
	 * 构造删除
	 * 
	 * @return object
	 */
	public function delete($split = null) {
		$this->_init(2);
		$this->_initQuery(2, $split);
		$this->_deleteQuery = new DeleteQuery($this->_dbType, $this->_realDbName, $this->_realTblName);
		return $this->_deleteQuery;
	}

	/**
	 * 构造更新
	 * 
	 * @return object
	 */
	public function update($split = null) {
		$this->_init(2);
		$this->_initQuery(2, $split);
		$this->_updateQuery = new UpdateQuery($this->_dbType, $this->_realDbName, $this->_realTblName);
		return $this->_updateQuery;
	}

	/************************** 以下为获取结果集或执行query ********************/

	/**
	 * 获取一条记录结果集
	 * 
	 * @return mix false|array
	 */
	public function fetch() {
		return $this->_fetch();
	}

	/**
	 * 获取多条记录结果集
	 * 
	 * 当使用groupBy,having等多条汇总的时间需要使用fetchAll
	 * 
	 * @return mix false|array
	 */
	public function fetchAll() {
		return $this->_fetchAll();
	}

	/**
	 * 获取最后插入的主键ID
	 * 
	 * @return string
	 */
	public function getLastInsertId() {
		return $this->_getLastInsertId();
	}

	/**
	 * execute用于update/delete/insert
	 * 
	 * @param  integer $queryType query的类型，默认是insert操作
	 * 
	 * @return mix boolean|string
	 */
	public function save($queryType = 1) {
		switch ($queryType) {
			case 2:
				/*
				 * 更新操作
				 */
				return $this->_update();
				break;
			case 3:
				/*
				 * 删除操作
				 */
				return $this->_delete();
				break;
			case 1:
			default:
				/*
				 * 默认新增操作
				 */
				return $this->_insert();
				break;
		}
	}

	/******************* 以下为本地事务处理 **********************/

	/**
	 * 开启本地事务
	 * 
	 * @param  mix $split null|string
	 * 
	 * @return void
	 */
	public function open($split = null) {
		return $this->_open($split);
	}

	/**
	 * 提交本地事务
	 * 
	 * @return void
	 */
	public function commit() {
		return $this->_commit();
	}

	/**
	 * 回滚本地事务
	 * 
	 * @return void
	 */
	public function rollback() {
		return $this->_rollback();
	}

	/****************** 以下为分布式事务处理 *********************/

	/**
	 * 开启分布式事务
	 * 
	 * @param  string $uniqid XID
	 * @param  mix $split null|string
	 * 
	 * @return void
	 */
	public function openXA($uniqid, $split = null) {
		return $this->_openXA($uniqid, $split);
	}
	

	/**
	 * 提交分布式事务
	 * 这里直接整合了end,prepare,commit的动作
	 * 
	 * @return void
	 */
	public function commitXA($uniqid) {
		$this->_endXA($uniqid);
		$this->_prepareXA($uniqid);
		return $this->_commitXA($uniqid);
	}

	/**
	 * 回滚分布式事务
	 * 需要调用end，然后执行回滚动作
	 * 
	 * @return void
	 */
	public function rollbackXA($uniqid) {
		$this->_endXA($uniqid);
		return $this->_rollbackXA($uniqid);
	}
	
	/******************* 设置配置文件 **********************/
	
	public function __get($name) {
		return $this->_dbInfo[$name];
	}
	
	public function __set($name, $value) {
		if (true === array_key_exists($name, $this->_dbInfo)) {
			$this->_dbInfo[$name] = $value;
		}
	}
}