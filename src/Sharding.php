<?php
/**
 * DB分区方式
 * 适配器模式
 *
 * 2015.3.27 16:31
 * @modify 2015.4.7 14:03  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db;

class Sharding {

	/*
	 * DB分割符
	 */
	const DATABASE_SEPARATOR = '_';

	/*
	 * 当前加载的数据库配置信息
	 */
	private $_dbInfo = array();

	/*
	 * 当前数据库编号，允许为空
	 */
	private $_dbNumber = '';

	/*
	private $_sharding = '';

	*/

	public function xbShardingAdapter($split) {
		if (true === empty($split)) {
			return true;
		}
		$split = mb_convert_case($split, MB_CASE_TITLE, 'UTF-8');
		$className = $split . 'Sharding';
		return new $className;
	}

	public function __construct(array $dbInfo) {
		$this->_dbInfo = $dbInfo;
	}

	/**
	 * 获取真实数据库名
	 * 
	 * @param  string $dbName
	 * @param  string $split
	 * 
	 * @return string 数据库名
	 */
	protected function _getRealDbName($dbName, $split = null) {
		if (true === empty($dbName)) {
			throw new DatabaseException('-6000001');
		}
		
		/*
		 * 没有指定分库，直接返回数据库名
		 */
		if (null === $split) {
			$realDbName = $this->_dbInfo['db_prefix'] . $dbName;
		} else {
			/*
			 * 配置文件的分库方式为字符串
			 */
			if (true === is_string($this->_dbInfo['split'])) {
				/*
				 * 不拆分
				 */
				if ('nosplit' == $this->_dbInfo['split']) {
					$realDbName = $this->_dbInfo['db_prefix'] .  $dbName;
				} else if ('special' == $this->_dbInfo['split']) {
					/*
					 * 特殊拆分方式，直接以外部传递进来的split确定  array('dbSplit', 'tblSplit')
					 */
					$realDbName = $this->_dbInfo['db_prefix']  . $dbName . self::DATABASE_SEPARATOR . $split[0];
					$this->_dbNumber = $split[0];
				}
			} else {
				/*
				 * 配置文件为数组，则按照split查询数据应该在哪儿
				 */
				foreach ($this->_dbInfo['split'] as $key => $value) {
					if ($split >= $value['min'] && $split <= $value['max']) {
						$this->_dbNumber = $key;
						break;
					}
				}

				if ('' === $this->_dbNumber) {
					throw new DatabaseException('-6000002');
				}
				$realDbName = $this->_dbInfo['db_prefix'] . $dbName . self::DATABASE_SEPARATOR . $this->_dbNumber;
			}
		}

		return $realDbName;
	}

	protected function _changeDb($dbName, $split = null) {
		$this->_dbName = $dbName;

		if (false === isset($this->_dbInfo)) {
			/*
			 * 没有水平拆分的数据库或直接指定了具体的数据库
			 * 该处数据库名需要严格按照一定的格式，【格式要求为(^[a-z]+?[a-z\d]*+)(?:[\_]?)([\w]*)】 => 字母开头，其他仅允许字母、数字与下划线
			 * 假设有水平拆分：
			 * 形如：db_0，那么传递进来的$dbName就应该是db_0
			 * 而经过处理后的$this->_dbName则为db,另外还有数据库编号$this->_dbNumber则为0。
			 * 另外一种形式：db，除了$this->_number应该为空，其他的都一致
			 */
			if (null === $split) {
				preg_match('/(^[a-z]+?[a-z\d]*+)(?:[\_]?)([\w]*)/is', $dbName, $match);
				$this->_dbName = $match[1];
				$this->_dbNumber = $match[2];
			}
			$this->_dbInfo = Loader::loadConfig('database.' . $this->_dbType . '.' . $this->_dbName);
		}

		$this->_realDbName = $this->_getRealDbName($dbName, $split);
	}


	protected function _getFullTableName($dbName, $tblName) {
		if (true === empty ($dbName)) {
			throw new DatabaseException('-5000001');
		}
		if (true === empty ($tblName)) {
			throw new DatabaseException('-5000002');
		}

		$this->_changeDb($dbName);

		return $this->_dbInfo['tbl_prefix'] . $tblName;
	}

	protected function _getTableName($dbName, $tblName, $split) {
		if (true === empty ($dbName)) {
			throw new DatabaseException('-5000001');
		}
		if (true === empty ($tblName)) {
			throw new DatabaseException('-5000002');
		}
		if (true === empty ($split)) {
			throw new DatabaseException('-5000005');
		}

		$this->_changeDb($dbName, $split);

		//当前表落在$this->_dbNumber的数据库中

		$tableNumber = '';

		/*
		 * 分库不分表的情况
		 */
		if (true === is_string($this->_dbInfo['tblsplit'])) {
			if ('special' === $this->_dbInfo['tblsplit']) {
				return $this->_dbInfo['tbl_prefix'] . $tblName . self::DATABASE_SEPARATOR . $split[1];
			} else {
				return $this->_dbInfo['tbl_prefix'] . $tblName;
			}
		} else {
			/*
			 * 分表的情况
			 */
			foreach ($this->_dbInfo[$this->_dbName]['tblsplit'][$this->_dbNumber] as $key => $value) {
				if ($split >= $value['min'] && $split <= $value['max']) {
					$tableNumber = $key;
					break;
				}
			}

			return $this->_dbInfo[$this->_dbName]['tbl_prefix'] . $tblName . self::DATABASE_SEPARATOR . $tableNumber;
		}
	}

	/**
	 * 获取真实表名
	 * 
	 * @param  [type] $split [description]
	 * @return [type]        [description]
	 */
	protected function _getRealTblName($dbName, $tblName, $split = null) {
		if (null === $split) {
			$realTblName = $this->_getFullTableName($dbName, $tblName);
		} else {
			$realTblName = $this->_getTableName($dbName, $tblName, $split);
		}
		return $realTblName;
	}

	/**
	 * 获取真实数据库信息
	 * 
	 * @param  string $dbName  数据库名
	 * @param  string $tblName 数据表名
	 * @param  mix $split
	 * 
	 * @return array
	 */
	public function getRealInfo($dbName, $tblName, $split = null) {
		$this->_realDbName = $this->_getRealDbName($dbName, $split);
		$this->_realTblName = $this->_getRealTblName($dbName, $tblName, $split);
		return array('realDbName' => $this->_realDbName, 'realTblName' => $this->_realTblName, 'dbNumber' => $this->_dbNumber);
	}
}