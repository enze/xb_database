<?php
/**
 * query select 类
 *
 * 2015.3.27 16:38
 * @modify 2015.4.9 14:36  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\compile;

use xb\db\compile\Where;

class Insert extends Where {
	
	protected $_field = array();

	protected function _reset() {
		$this->_field = array();
		$this->_where = array();
		$this->_orderBy = array();
		$this->_limit = 0;
		$this->_realDbName = '';
		$this->_realTblName = '';
		$this->_sql = '';
		$this->_param = array();
		return $this;
	}

	/**
	 * 编译SQL语句
	 * 
	 * @return void
	 */
	protected function _compile() {

		$this->_sql = '';
		$sql = 'INSERT INTO ';
		$sql .= $this->_qTable();
		$sql .= ' (';
		$sql .= join(' , ', $this->_field);
		$sql .= ') VALUES (';
		$sql .= join(' , ', $this->_data);
		$sql .= ') ';
		$this->_sql = $sql;
	}

	public function __construct($dbType, $dbName, $tblName) {
		parent::__construct($dbType, $dbName, $tblName);
	}

	/**
	 * 设置需要添加的记录的必要属性  alias of setAttribute
	 * 
	 * @param array $param field=>value
	 */
	public function setAttibute($param) {
		return $this->setAttribute($param);
	}

	/**
	 * 设置需要添加的记录的必要属性
	 * 
	 * @param array $param field=>value
	 */
	public function setAttribute($param) {
		$this->_data = $this->_compileInsert($param);
		return $this;
	}
}