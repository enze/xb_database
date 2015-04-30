<?php
/**
 * query 抽象类
 *
 * 2015.4.8 16:38
 * @author enze.wei <[enzewei@gmail.com]>
 */

abstract class AbstractQuery {

	protected $_dbType = 'mysql';

	public function __construct($dbType) {
		$this->_dbType = $dbType;
	}

	/**
	 * 调用限定
	 * 这里限定只能由DB类中调用，其他方式调用全部抛异常
	 * 
	 * @return void
	 * @throws  DatabaseException
	 */
	protected function _limitTrace() {		
		$backTrace = debug_backtrace(false);
		if (2 > count($backTrace)) {
			throw new DatabaseException('-4100001');
		}
		array_shift($backTrace);
		array_shift($backTrace);
		$called = array_shift($backTrace);

		if ('DB' != $called['class']) {
			throw new DatabaseException('-4100001');
		}
	}

	/**
	 * 对象instanceof检测
	 * 
	 * @param  object $object 待检测的对象
	 * @param  string $class  类名称
	 * 
	 * @return void
	 * @throws  DatabaseException
	 */
	protected function _checkObject($object, $class) {
		if (false === ($object instanceof $class)) {
			throw new DatabaseException('-1000005', $class);
		}
	}
}