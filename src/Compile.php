<?php
/**
 * 编译SQL工具类
 * 主要用于处理SQL语句的各个条件约束，以符合不同的数据库
 * 通过调用command的adapter来实现各种数据库约束的处理
 * 
 * 根据不同的db编译SQL格式
 *
 * 2015.3.27 16:31
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db;

use xb\db\base\Dsn as AbstractDsn

class Compiler extends AbstractDsn {

	static private $_compiler = null;
	
	protected function __construct($dbType) {
		parent::__construct($dbType);
	}

	static public function instance($dbType = 'mysql') {
		if (null === self::$_compiler[$dbType]) {
			self::$_compiler[$dbType] = new self($dbType);
		}
		return self::$_compiler[$dbType];
	}

	public function qField($field) {
		if ('*' == $field || true === empty($field)) {
			return '*';
		}
		return sprintf($this->_xbDsnAdapter->qFieldCommand(), $field);
	}

	public function qTable($tblName, $dbName) {
		return sprintf($this->_xbDsnAdapter->qTableCommand(), $dbName, $tblName);
	}

	public function xbRpcLogType() {
		return $this->_xbDsnAdapter->rpcLogTypeCommand();
	}

	public function useXATransaction() {
		return $this->_xbDsnAdapter->useXATransaction();
	}
}