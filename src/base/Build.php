<?php
/**
 * Build Sql 抽象类
 *
 * 2015.4.8 11:21
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\base;

abstract class Build {

	/*
	 * 数据库类型，默认mysql
	 */
	protected $_dbType = 'mysql';

	/*
	 * SQL语句
	 */
	protected $_sql = '';

	/*
	 * PDO bindParam参数
	 */
	protected $_param = array();

	/**
	 * 重置相关属性，可至selectQuery,updateQuery等出查看具体实现
	 * 
	 * @return object $this
	 */
	abstract protected function _reset();
	
	public function __construct($dbType) {
		$this->_dbType = $dbType;
	}

	/**
	 * 获取当前Query信息
	 * 
	 * @return array
	 */
	abstract public function getQuery();

	/**
	 * 重置属性
	 * 
	 * @return boolean
	 */
	public function reset() {
		return $this->_reset();
	}
}