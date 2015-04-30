<?php
/**
 * 连接数据库类
 * 采用PDO DSN的方式连接
 *
 * 2015.3.27 16:31
 * @author enze.wei <[enzewei@gmail.com]>
 */

class Connect extends AbstractDsn {

	/*
	 * PDO对象
	 */
	private $_pdo = array();

	/*
	 * 连接数据库信息数组
	 */
	private $_dbConn = array();



	/**
	 * 加载中间件
	 * 
	 * @param  integer $connType 0短连接 1长连接
	 * @return void
	 */
	private function _initMiddleware($connType = 0) {
		/*
		 * 主数据库设置连接参数
		 */
		$this->_dbConn['master'] = array (
			strtr($this->_xbDsnAdapter->connectCommand(), 
				  array(
				  	'{%type%}' => $this->_dbType, 
				  	'{%host%}' => $this->_dbInfo['middleware']['host'],
				  	'{%port%}' => $this->_dbInfo['middleware']['port'],
				  	'{%dbname%}' => $this->_dbInfo['realDbName'],
				  )), 
			$this->_dbInfo['middleware']['user'],
			$this->_dbInfo['middleware']['pswd'],
			$connType,
		);

		/*
		 * 从数据库设置连接参数
		 */
		$this->_dbConn['slave'] = array (				
			strtr($this->_xbDsnAdapter->connectCommand(), 
				  array(
				  	'{%type%}' => $this->_dbType, 
				  	'{%host%}' => $this->_dbInfo['middleware']['host'],
				  	'{%port%}' => $this->_dbInfo['middleware']['port'],
				  	'{%dbname%}' => $this->_dbInfo['realDbName'],
				  )), 
			$this->_dbInfo['middleware']['user'],
			$this->_dbInfo['middleware']['pswd'],
			$connType,
		);
	}

	/**
	 * 加载数据库master信息
	 * @param  string  $dbNumber 数据库编号，允许为空
	 * @param  integer $connType 0短连接 1长连接
	 * @return void
	 */
	private function _initMasterConnection($dbNumber = '', $connType = 0) {

		$masterKey = 0;
		$offset = false;
		if (1 === count($this->_dbInfo['master']['host'])) {
			/*
			 * 只有默认master且不分库，使用masterkey为default
			 */
			$masterKey = 'default';
		} else {
			$masterKey = $dbNumber;
			$offset = array_rand($this->_dbInfo['master']['host'][$masterKey]);
		}

		$this->_dbConn['master'] = array (
			strtr($this->_xbDsnAdapter->connectCommand(), 
				  array(
				  	'{%type%}' => $this->_dbType, 
				  	'{%host%}' => (false === $offset ? $this->_dbInfo['master']['host'][$masterKey] : $this->_dbInfo['master']['host'][$masterKey][$offset]),
				  	'{%port%}' => (false === $offset ? $this->_dbInfo['master']['port'][$masterKey] : $this->_dbInfo['master']['port'][$masterKey][$offset]),
				  	'{%dbname%}' => $this->_dbInfo['realDbName'],
				  )), 
			false === $offset ? $this->_dbInfo['master']['user'][$masterKey] : $this->_dbInfo['master']['user'][$masterKey][$offset],
			false === $offset ? $this->_dbInfo['master']['pswd'][$masterKey] : $this->_dbInfo['master']['pswd'][$masterKey][$offset],
			$connType,
		);
	}

	/**
	 * 加载数据库slave信息
	 * @param  string  $dbNumber 数据库编号，允许为空
	 * @param  integer $connType 0短连接 1长连接
	 * @return void
	 */
	private function _initSlaveConnection($dbNumber = '', $connType = 0) {

		$slaveKey = 0;
		$offset = 0;
		if (1 === count($this->_dbInfo['slave']['host'])) {
			/*
			 * 只有默认slave且不分库，使用slavekey为default，且存在多个slave库
			 */
			$slaveKey = 'default';
		} else {
			$slaveKey = $this->_dbNumber;
		}
		$offset = array_rand($this->_dbInfo['slave']['host'][$slaveKey]);

		$this->_dbConn['slave'] = array (
			strtr($this->_xbDsnAdapter->connectCommand(), 
				  array(
				  	'{%type%}' => $this->_dbType, 
				  	'{%host%}' => $this->_dbInfo['slave']['host'][$slaveKey][$offset],
				  	'{%port%}' => $this->_dbInfo['slave']['port'][$slaveKey][$offset],
				  	'{%dbname%}' => $this->_dbInfo['realDbName'],
				  )), 
			$this->_dbInfo['slave']['user'][$slaveKey][$offset],
			$this->_dbInfo['slave']['pswd'][$slaveKey][$offset],
			$connType,
		);
	}

	/**
	 * 加载master与slave连接信息
	 * @param  string  $dbNumber 数据库编号，允许为空
	 * @param  integer $connType 0短连接 1长连接
	 * @return void
	 */
	private function _initMasterAndSlave($dbNumber = '', $connType = 0) {
		$this->_initMasterConnection($dbNumber, $connType);
		$this->_initSlaveConnection($dbNumber, $connType);
	}

	/**
	 * 初始化数据库连接信息
	 * 
	 * @param  string $dbName 真实数据库名
	 * @param  string  $dbNumber 数据库编号，允许为空
	 * @param  integer $connType 0短连接 1长连接
	 * @return void
	 */
	protected function _initConnection($dbName, $dbNumber = '', $connType = 0) {

		if (false === is_numeric($connType)) {
			throw new DatabaseException('-7000002');
		}

		$connType = (1 === intval($connType)) ? array(PDO::ATTR_PERSISTENT => true) : array(PDO::ATTR_PERSISTENT => false);

		$this->_dbInfo['realDbName'] = $dbName;

		if (true === $this->_dbInfo['usemiddleware']) {
			/*
			 * 使用中间件
			 */
			$this->_initMiddleware($connType);
		} else {
			$this->_initMasterAndSlave($dbNumber, $connType);
		}
	}
	
	public function __construct($dbType, array $dbInfo) {
		parent::__construct($dbType);
		$this->_dbInfo = $dbInfo;
	}

	/**
	 * 连接数据库
	 * 
	 * @param  string $dbName 真实数据库名
	 * @param  string  $dbNumber 数据库编号，允许为空
	 * @param  integer $xbType 1 从 2 主
	 * @param  integer $connType 0短连接 1长连接
	 * 
	 * @return object PDO object
	 */
	public function connect($dbName, $dbNumber = '', $xbType = 1, $connType = 0) {
		/*
		 * 加载连接数据库信息
		 */
		$this->_initConnection($dbName, $dbNumber, $connType);
		try {
			$startTime = RpcLog::getMicroTime();
			$realConnect = false;
			if (2 == $xbType) {
				if (false === isset($this->_pdo['master'])) {
					$this->_pdo['master'] = new PDO(
												$this->_dbConn['master'][0], 
												$this->_dbConn['master'][1], 
												$this->_dbConn['master'][2], 
												$this->_dbConn['master'][3]
											);
					/*
					 * 不使用PHP本地prepare
					 */
					$this->_pdo['master']->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
					$this->_pdo['master']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$this->_pdo['master']->exec('set names utf8');
					$realConnect = true;
				}
				$dsn = $this->_dbConn['master'][0];
				$user = $this->_dbConn['master'][1];
				$conn = $this->_pdo['master'];
			} else {
				if (false === isset($this->_pdo['slave'])) {
					$this->_pdo['slave'] = new PDO(
												$this->_dbConn['slave'][0], 
												$this->_dbConn['slave'][1], 
												$this->_dbConn['slave'][2], 
												$this->_dbConn['slave'][3]
											);
					/*
					 * 不使用PHP本地prepare
					 */
					$this->_pdo['slave']->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
					$this->_pdo['slave']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					
					$this->_pdo['slave']->exec('set names utf8');
					$realConnect = true;
				}
				$dsn = $this->_dbConn['slave'][0];
				$user = $this->_dbConn['slave'][1];
				$conn = $this->_pdo['slave'];
			}
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log(($realConnect ? 'real ' : '') . 'connect database dsn:{' . $dsn . '} user:{' . $user . '}', $startTime, $endTime, $this->_xbDsnAdapter->rpcLogTypeCommand());
			return $conn;
		} catch (PDOException $e) {
			$dbException = new DatabaseException('-8000003', $e->getMessage());
			$dbException->deploy();
			throw $dbException;
		}
	}
}

