<?php
/**
 * Build Sql
 *
 * 2015.4.8 11:32
 * @author enze.wei <[enzewei@gmail.com]>
 */

abstract class AbstractBuildQuery extends AbstractBuild {

	/*
	 * 真实数据库名
	 */
	protected $_realDbName = '';

	/*
	 * 真实表名
	 */
	protected $_realTblName = '';
	
	/**
	 * 构造方法
	 * 
	 * @param string $dbType  数据库类型 mysql|mssql|etc.
	 * @param string $dbName  真实数据库名
	 * @param string $tblName 真实表名
	 */
	public function __construct($dbType, $dbName, $tblName) {
		/*
		 * 设置数据库类型
		 */
		parent::__construct($dbType);

		/*
		 *设置真实数据库名与表名
		 */
		$this->_realDbName = $dbName;
		$this->_realTblName = $tblName;
	}

	/**
	 * 构造SQL语句，返回SQL语句以及bindParam
	 * 
	 * @return void
	 */
	public function build() {
		$this->_compile();
	}

	/**
	 * 获取query info，主要为SQL与bindParam
	 * 你不应该直接去调用该方法，该方法作为DB类内部调用的方法来使用
	 * 
	 * @return array array($sql, $param)
	 */
	public function getQuery() {
		$backTrace = debug_backtrace(false);
		if (2 > count($backTrace)) {
			throw new DatabaseException('-4100001');
		}
		array_shift($backTrace);
		$called = array_shift($backTrace);

		if ('DB' != $called['class']) {
			throw new DatabaseException('-4100001');
		}
		return array($this->_sql, $this->_param);
	}
}