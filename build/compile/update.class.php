<?php
/**
 * query select 类
 *
 * 2015.3.27 16:38
 * @modify 2015.4.8 9:33  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */

class UpdateQuery extends AbstractWhereQuery {
	
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
		$sql = 'UPDATE ';
		$sql .= $this->_qTable();
		$sql .= ' SET ';
		$sql .= join(' , ', $this->_data);

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
		$this->_data = $this->_compileUpdate($param);
		return $this;
	}
}