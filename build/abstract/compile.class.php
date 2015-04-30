<?php
/**
 * abstract compile 类
 * 编译SQL语句的各个要素条件等信息
 *
 * 2015.4.8 9:34
 * @author enze.wei <[enzewei@gmail.com]>
 */

abstract class AbstractCompile extends AbstractBuildQuery {

	/**
	 * 编译SQL语句
	 */
	abstract protected function _compile();

	/**
	 * 给字段名增加标识，如mysql采用`xxx`的形式
	 * 一般采用callback的方式调用该方法
	 * 
	 * @param  string $field 字段名
	 * 
	 * @return string
	 */
	protected function _qField($field) {
		return Compiler::instance($this->_dbType)->qField($field);
	}

	/**
	 * 给表名名增加标识，如mysql采用`dbname` . `tablename`的形式
	 * 一般采用callback的方式调用该方法
	 * 
	 * @param  string $field 字段名
	 * 
	 * @return string
	 */
	protected function _qTable() {
		return Compiler::instance($this->_dbType)->qTable($this->_realTblName, $this->_realDbName);
	}

	/**
	 * 编译where条件
	 * 
	 * @return mix false|string
	 */
	protected function _compileWhere() {
		return $this->_compileCondition($this->_where);
	}

	/**
	 * 编译having条件
	 * 
	 * @return mix false|string
	 */
	protected function _complieHaving() {
		return $this->_compileCondition($this->_having);
	}

	/**
	 * 编译where|having条件，同时准备bindparam
	 *
	 * @param array $condions 需要编译的数组 where or having
	 * 
	 * @return mix string|false
	 */
	protected function _compileCondition($condions = array()) {
		$query = '';
		if (false === empty($condions) && true === is_array($condions)) {
			$end = '';
			foreach ($condions as $block) {
				//k 是 and 或者 or
				foreach ($block as $k => $v) {
					/*
					 * bindParam与push的特殊情况，为处理 is (not) null与 in时用到
					 */
					$bindParam = '?';
					$push = true;

					if ('(' == $v) {
						if (false === empty($query) && '(' != $end) {
							$query .= ' ' . strtoupper($k) . ' ';
						}
						$query .= '(';
					} else if (')' == $v) {
						$query .= ')';
					} else if (true === is_array($v)) {

						if (false === empty($query) && '(' != $end) {
							$query .= ' ' . strtoupper($k) . ' ';
						}

						list($field, $op, $value) = $v;

						if (null === $value) {
							$op = ('=' == $op ? 'is null' : 'is not null');
							$bindParam = '';
							$push = false;
						}

						/*
						 * 调用适配器编译字段名
						 */
						$field = $this->_qField($field);

						$op = strtoupper($op);
						if ('BETWEEN' == $op && true === is_array($value)) {
							$value = $value[0] . ' AND ' . $value[1];
						}

						if ('LIKE' == $op) {
							$value = '%' . $value . '%';
						}

						if ('IN' == $op) {
							$param = array();
							if (true === is_array($value)) {
								$param = array_fill(0, count($value), '?');
								$this->_param = array_merge($this->_param, $value);
							} else {
								$param = array('?');
								array_push($this->_param, $value);
							}
							$bindParam = '(' . join(',', $param) . ')';
							$push = false;
							unset($param);
						}

						$query .= strtolower($field) . ' ' . $op . ' ' . $bindParam;
						/*
						 * 准备bindParam的数组
						 */
						if (true === $push) {
							array_push($this->_param, $value);
						}
					}
					$end = $v;
				}
			}
			return $query;
		}
		return false;
	}

	/**
	 * 编译order by
	 * 
	 * @return mix string|false
	 */
	protected function _compileOrderBy() {
		$orderBy = array();
		if (false === empty($this->_orderBy) && true === is_array($this->_orderBy)) {
			foreach ($this->_orderBy as $block) {
				list($field, $sort) = $block;
				$orderBy[] = $this->_qField($field) . ' ' . strtoupper($sort);
			}
			return join(' , ', $orderBy);
		}
		return false;
	}

	/**
	 * 编译group by
	 * 
	 * @return mix string|false
	 */
	protected function _complieGroupBy() {
		$groupBy = array();
		if (false === empty($this->_groupBy) && true === is_array($this->_groupBy)) {

			$groupBy = array_map(array($this, '_qField'), $this->_groupBy);

			return join(' , ', $groupBy);
		}
		return false;
	}

	/**
	 * 编译表达式支持，目前仅支持sum
	 * 
	 * @return mix string|false
	 */
	protected function _compileExpr($expression = array()) {
		$expr = array();
		if (false === empty($expression) && true === is_array($expression)) {
			foreach ($expression as $block) {

				$size = count($block);
				switch ($size) {
					case 1:
						$expr[] = $this->_qField($block[0]);
						break;
					case 2:
						$expr[] = join($block[1], array_map(array($this, '_qField'), $block[0]));
						break;
					case 3:
						$expr[] = $this->_qField($block[0]) . $block[1] . $block[2];
						break;
					default:
						break;
					}
			}
			return join(' , ', $expr);
		}
		return false;
	}

	/**
	 * 编译Insert字段信息
	 * 
	 * @param  array $param field => value
	 * 
	 * @return array
	 * @throws DatabaseException
	 */
	protected function _compileInsert($param) {
		if (true === empty($param)) {
			throw new DatabaseException('-1000004');
		}

		if (false === is_array($param)) {
			throw new DatabaseException('-1000001');
		}

		$this->_field = array();
		$this->_param = array();

		$this->_field = array_map(array($this, '_qField'), array_keys(array_change_key_case($param, CASE_LOWER)));
		$this->_param = array_values($param);
		
		$data = array_map(function ($key) {
			return '?';
		}, $this->_field);

		return $data;
	}


	/**
	 * 编译Update字段信息
	 * 
	 * @param  array $param field => value
	 * 
	 * @return array
	 * @throws DatabaseException
	 */
	protected function _compileUpdate($param) {

		if (true === empty($param)) {
			throw new DatabaseException('-1000004');
		}

		if (false === is_array($param)) {
			throw new DatabaseException('-1000001');
		}
		$this->_field = array();
		$this->_param = array();

		$this->_field = array_map(array($this, '_qField'), array_keys(array_change_key_case($param, CASE_LOWER)));
		$this->_param = array_values($param);

		$data = array_map(function ($key) {
			return $key . ' = ?';
		}, $this->_field);

		return $data;
	}
}