<?php
/**
 * DSN抽象类
 *
 * 2015.3.27 16:31
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\base;

abstract class Dsn {

	const DATABASE_SEPARATOR = '_';

	protected $_dbType = '';

	/*
	 * 数据库配置数组
	 */
	protected $_dbInfo = array();

	/*
	 * 当前数据库名
	 */
	protected $_dbName = '';

	/*
	 * 当前实际数据库名
	 */
	protected $_dbRealName = '';

	/*
	 * 当前数据库编号，水平拆分的情况下会具备
	 */
	protected $_dbNumber = '';

	/*
	 * DSN适配器对象
	 */
	protected $_xbDsnAdapter = null;

	protected function __construct($dbType) {
		switch ($dbType) {
			case 'mysql':
			default:
				$this->_xbDsnAdapter = new MysqlCommand;
				$this->_dbType = 'mysql';
				break;
		}
	}
}