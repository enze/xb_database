<?php
/**
 * query 类
 *
 * 2015.4.8 14:52
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db;

use xb\db\base\Query as AbstractQuery;

class Query extends AbstractQuery {


	/**
	 * 获取需要bind的param类型
	 * 
	 * @param  mix $param
	 * 
	 * @return int
	 */
	protected function _getParamType($param) {
		if (is_string($param)) {
			return PDO::PARAM_STR;
		}
		
		if (is_bool($param)) {
			return PDO::PARAM_BOOL;
		}
		
		if (is_null($param)) {
			return PDO::PARAM_NULL;
		}
		
		if (is_integer($param)) {
			return PDO::PARAM_INT;
		}
		
		if (is_object($param)) {
			return PDO::PARAM_LOB;
		}

	}

	/**
	 * 绑定参数
	 * 使用PDOStatement的bindValue方法
	 * 
	 * @param  mix $param 需要绑定的参数
	 * @param  int $key
	 * @param  object $xbPdo PDOStatement
	 * 
	 * @return void
	 */
	protected function _bindParam($param, $key, PDOStatement $xbPdo) {
		try {
			$this->_checkObject($xbPdo, 'PDOStatement');
			$type = $this->_getParamType($param);

			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			
			/*
			 * key从1开始
			 */
			$xbPdo->bindValue($key + 1, $param, $type);

			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('bindParam use PDOStatement param:{' . json_encode($param) . '}', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
		} catch (DatabaseException $e) {
			throw $e;
		}
	}


	/**
	 * 查询
	 * 不做prepare处理，因此该方法慎用
	 * $res = query($conn, $sql)
	 * foreach ($res as $row) { ... }
	 * 
	 * @param  object  PDO
	 * @param  string  $sql
	 * 
	 * @return object  PDOStatement
	 */
	public function query(PDO $conn, $sql) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			return $conn->query($sql);
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * insert、update、delete
	 * 
	 * @param  object  PDO
	 * @param  string  $sql
	 * 
	 * @return mix  int|false
	 */
	public function exec(PDO $conn, $sql) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			return $conn->exec($sql);
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 获取刚刚插入的主键ID
	 * 
	 * @return string
	 */
	public function getInsertId(PDO $conn) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			return $conn->lastInsertId();
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 预处理执行SQL语句
	 * 
	 * @param  string  $sql
	 * @param  integer $xbType 1 从 2 主
	 * 
	 * @return object  PDOStatement
	 * @throws DatabaseException
	 */
	public function prepare(PDO $conn, $sql) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');

			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();

			$res = $conn->prepare($sql);

			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('prepare query sql:{' . $sql . '}', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
			return $res;
		} catch (PDOException $e) {
			$dbException = new DatabaseException('-8000002', $e->getMessage());
			$dbException->deploy();
			throw $dbException;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 获取一条记录结果集
	 * 
	 * @param  PDOStatement $xbPdo PDOStatement对象
	 * @param  array $param
	 * 
	 * @return mix array|false
	 */
	public function fetch(PDOStatement $xbPdo, $param) {
		try {
			$this->_limitTrace();
			$this->_checkObject($xbPdo, 'PDOStatement');
			/*
			 * bindParam
			 */
			array_walk($param, array($this, '_bindParam'), $xbPdo);

			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();

			if (true === $xbPdo->execute()) {
				$res = $xbPdo->fetch(PDO::FETCH_ASSOC);
			} else {
				$res = false;
			}

			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('execute query use PDOStatement and fetch result with PDO::FETCH_ASSOC', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());

			return $res;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 获取多条记录结果集
	 * 
	 * @param  PDOStatement $xbPdo PDOStatement对象
	 * @param  array $param
	 * 
	 * @return mix array|false
	 */
	public function fetchAll(PDOStatement $xbPdo, $param) {
		try {
			$this->_limitTrace();
			$this->_checkObject($xbPdo, 'PDOStatement');
			/*
			 * bindParam
			 */
			array_walk($param, array($this, '_bindParam'), $xbPdo);

			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();

			if (true === $xbPdo->execute()) {
				$res = $xbPdo->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$res = false;
			}

			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('execute query use PDOStatement and fetchAll result with PDO::FETCH_ASSOC', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());

			return $res;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 执行一条Query
	 * 
	 * @param  PDOStatement $xbPdo PDOStatement对象
	 * @param  array $param
	 * 
	 * @return mix array|false
	 */
	public function execute(PDOStatement $xbPdo, $param) {
		try {
			$this->_limitTrace();
			$this->_checkObject($xbPdo, 'PDOStatement');
			/*
			 * bindParam
			 */
			array_walk($param, array($this, '_bindParam'), $xbPdo);

			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();

			$res = $xbPdo->execute();

			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();
			RpcLog::log('execute query use PDOStatement', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());

			return $res;
		} catch (PDOException $e) {
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			//RpcLog::log('execute query use PDOStatement, errorInfo:{' . $e->getMessage() . '}', $startTime, $endTime, RpcLogEnvConfig::RPC_LOG_TYPE_EXCEPTION);
			RpcLog::log('execute query use PDOStatement, errorInfo:{' . $e->getMessage() . '}', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
			throw $e;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/******************  以下为事务处理相关 ***************/

	/**
	 * 开启本地事务
	 * 
	 * @param  PDO    $conn   PDO对象
	 * 
	 * @return boolean
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function open(PDO $conn) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 开启本地事务
			 */
			$res = $conn->beginTransaction();
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('open local transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());

			return $res;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 提交本地事务
	 * 
	 * @param  PDO    $conn   PDO对象
	 * 
	 * @return boolean
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function commit(PDO $conn) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 提交本地事务
			 */
			$res = $conn->commit();
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('commit local transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
			return $res;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 回滚本地事务
	 * 
	 * @param  PDO    $conn   PDO对象
	 * 
	 * @return boolean
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function rollback(PDO $conn) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 回滚本地事务
			 */
			$res = $conn->rollback();
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('rollback local transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
			return $res;
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/***********  以下为处理分布式事务  ************/

	/**
	 * 开启分布式事务，并将事务置于ACTIVE状态，此后执行的SQL语句都将置于该事务中。
	 * 
	 * @param  PDO    $conn   PDO对象
	 * @param  string $uniqid 唯一的XID
	 * 
	 * @return void
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function openXA(PDO $conn, $uniqid) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			$attr = Compiler::instance($this->_dbType)->useXATransaction();
			$conn->setAttribute($attr[0], $attr[1]);
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 开启分布式事务
			 */
			$conn->exec('XA START "' . $uniqid . '"');
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('open xa transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 将事务置于IDLE状态，表示事务内SQL操作完成。类似于PDO中的prepare，
	 * 而xa prepare则与bindParam类似，当然不能完全等同，可以这样去理解
	 * 
	 * 后续事务操作可以使XA PREPARE xid 或 XA COMMIT xid ONE PHASE. 
	 * 
	 * @param  PDO    $conn   PDO对象
	 * @param  string $uniqid 唯一的XID
	 * 
	 * @return void
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function endXA(PDO $conn, $uniqid) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 结束SQL处理状态
			 */
			$conn->exec('XA END "' . $uniqid . '"');
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('end xa transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 实现事务提交的准备工作，事务状态置于PREPARED状态。
	 * 事务如果无法提交，该语句将会失败。
	 * 
	 * xa prepare与bindParam类似，当然不能完全等同，可以这样去理解
	 * 
	 * 此后可执行XA COMMIT和XA ROLLBACK
	 * 
	 * @param  PDO    $conn   PDO对象
	 * @param  string $uniqid 唯一的XID
	 * 
	 * @return void
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function prepareXA(PDO $conn, $uniqid) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 准备提交事务
			 */
			$conn->exec('XA PREPARE "' . $uniqid . '"');
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('prepare xa transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
		} catch (DatabaseException $e) {
			throw $e;
		}
	}

	/**
	 * 事务最终提交，事务完成。
	 * 
	 * @param  PDO    $conn   PDO对象
	 * @param  string $uniqid 唯一的XID
	 * 
	 * @return void
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function commitXA(PDO $conn, $uniqid) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 提交事务
			 */
			$conn->exec('XA COMMIT "' . $uniqid . '"');
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('commit xa transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
		} catch (DatabaseException $e) {
			throw $e;
		}
	}


	/**
	 * 事务回滚并终止。
	 * 注意：需要先调用xa end，然后执行rollback动作
	 * 
	 * @param  PDO    $conn   PDO对象
	 * @param  string $uniqid 唯一的XID
	 * 
	 * @return void
	 * @throws DatabaseException
	 * @throws PDOException
	 */
	public function rollbackXA(PDO $conn, $uniqid) {
		try {
			$this->_limitTrace();
			$this->_checkObject($conn, 'PDO');
			/*
			 * RpcLog
			 */
			$startTime = RpcLog::getMicroTime();
			/*
			 * 回滚事务
			 */
			$conn->exec('XA ROLLBACK "' . $uniqid . '"');
			/*
			 * RpcLog
			 */
			$endTime = RpcLog::getMicroTime();			
			RpcLog::log('rollback xa transaction', $startTime, $endTime, Compiler::instance($this->_dbType)->xbRpcLogType());
		} catch (DatabaseException $e) {
			throw $e;
		}
	}
}