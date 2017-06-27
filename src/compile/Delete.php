<?php
/**
 * query select 类
 *
 * 2015.3.27 16:38
 * @modify 2015.4.8 9:33  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\compile;

use xb\db\compile\Where;

class Delete extends Where {
	
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
		$sql = 'DELETE FROM ';
		$sql .= $this->_qTable();

		if (false !== $where = $this->_compileWhere()) {
			$sql .= ' WHERE ' . $where;
		}

		if (false === empty($this->_limit)) {
			$sql .= ' LIMIT ' . $this->_limit;
		}
		$this->_sql = $sql;
	}

	public function __construct($dbType, $dbName, $tblName) {
		parent::__construct($dbType, $dbName, $tblName);
	}
}