<?php
/**
 * query select 类
 *
 * 2015.3.27 16:38
 * @modify 2015.4.9 10:33  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\compile;

use xb\db\compile\Where;

class Select extends Where {

	protected $_distinct = false;
	protected $_offset = 0;
	protected $_groupBy = array();
	protected $_having = array();
	protected $_count = false;
	protected $_sum = array();
	protected $_max = '';
	protected $_min = '';
	protected $_field = array();

	protected function _reset() {
		$this->_distinct = false;
		$this->_offset = 0;
		$this->_groupBy = array();
		$this->_having = array();
		$this->_count = false;
		$this->_sum = array();
		$this->_max = '';
		$this->_min = '';
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
		$sql = 'SELECT ';

		if (true === $this->_distinct) {
			/*
			 * 强制要求当需要使用distinct的时候，仅查询一个字段，如果有多个字段，请使用group by
			 * 因为distinct关键字是作用在其后的所有字段的。
			 * 而使用group_concat或者count distinct的时候，是需要使用group by的，因此暂时没有必要这样用
			 */
			/*
			if (false === empty($this->_field)) {
				$sql .= ' GROUP_CONCAT(DISTINCT ' . $this->_field[0] . ') ';
			} else {
				$sql .= ' DISTINCT ';
			}
			*/
			$sql .= ' DISTINCT ';
		}
		
		$sql .= (true === empty($this->_field) ? ' * ' : join(', ', $this->_field));

		if (true === $this->_count) {
			$sql .= ' , COUNT(*) as total';
		}

		if (false === empty($this->_max)) {
			$sql .= ' , MAX(' . $this->_qField($this->_max) . ') as max';
		}

		if (false === empty($this->_min)) {
			$sql .= ' , MIN(' . $this->_qField($this->_min) . ') as min';
		}

		if (false !== $sum = $this->_compileExpr($this->_sum)) {
			$sql .= ' , SUM(' . $sum . ') as sum';
		}
		$sql .= ' FROM ';
		$sql .= $this->_qTable();

		if (false !== $where = $this->_compileWhere()) {
			$sql .= ' WHERE ' . $where;
		}

		if (false !== $groupBy = $this->_complieGroupBy()) {
			$sql .= ' GROUP BY ' . $groupBy;
		}

		if (false !== $having = $this->_complieHaving()) {
			$sql .= ' HAVING ' . $having;
		}

		if (false !== $orderBy = $this->_compileOrderBy()) {
			$sql .= ' ORDER BY ' . $orderBy;
		}

		if (false === empty($this->_limit)) {
			$sql .= ' LIMIT ' . $this->_limit;
		}

		if (false === empty($this->_offset)) {
			$sql .= ' OFFSET ' . $this->_offset;
		}
		$this->_sql = $sql;
	}

	public function __construct($dbType, $dbName, $tblName) {
		parent::__construct($dbType, $dbName, $tblName);
	}


	/**
	 * distinct
	 * 
	 * @param  boolean $distinct 默认false
	 * 
	 * @return object $this
	 */
	public function distinct($distinct = false) {
		$this->_distinct = (boolean) $distinct;
		return $this;
	}

	/**
	 * groupby
	 * groupBy('field');
	 * groupBy(array('field', 'field'))
	 * 
	 * @param  array  $field 需要groupby的字段
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function groupBy($field = array()) {
		if (true === is_string($field)) {
			$this->_groupBy[] = $field;
		} else if (true === is_array($field)) {
			$this->_groupBy = array_merge($this->_groupBy, $field);
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * having 默认having为andHaving方式
	 * having('id', '=', 'xxx')
	 * or
	 * having(array('id', 'status'), array('=', '='), array(1,9))
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function having($field = array(), $op = array(), $value = array()) {
		return $this->andHaving($field, $op, $value);
	}

	/**
	 * andHaving
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function andHaving($field = array(), $op = array(), $value = array()) {
		if (true === is_string($field)) {
			if (false === is_string($op) || false === is_string($value)) {
				throw new DatabaseException('-1000002');
			}
			$this->_having[] = array('and' => array($field, $op, $value));
		} else if (true === is_array($field)) {
			foreach ($field as $k => $v) {
				$this->_having[] = array('and' => array($v, $op[$k], $value[$k]));
			}
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * orHaving 写法与having/andHaving一致
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function orHaving($field = array(), $op = array(), $value = array()) {
		if (true === is_string($field)) {
			if (false === is_string($op) || false === is_string($value)) {
				throw new DatabaseException('-1000002');
			}
			$this->_having[] = array('or' => array($field, $op, $value));
		} else if (true === is_array($field)) {
			foreach ($field as $k => $v) {
				$this->_having[] = array('or' => array($v, $op[$k], $value[$k]));
			}
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * count汇总
	 * 以count(*) as total的形式，不支持count(id)这样的写法
	 * 
	 * @param  boolean $count 是否需要汇总
	 * @return object $this
	 */
	public function count($count = false) {
		$this->_count = (boolean) $count;
		return $this;
	}

	/**
	 * select sum
	 *
	 *  sum(field),sum(field,[op(+|-|*|/...)], [value])
	 *  sum(array(field1, field2), [op])	 * 
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function sum($field = array(), $op = array(), $value = array()) {
		if (true === is_string($field)) {
			if (true === empty($op) && true === empty($value)) {
				$this->_sum[] = array($field);
			} else if (false === empty($op) && true === is_string($op) && false === empty($value) && true === is_string($value)){
				$this->_sum[] = array($field, $op, $value);
			}
		} else if (true === is_array($field)) {
			if (false === empty($op) && true === is_string($op)) {
				$this->_sum[] = array($field, $op);
			}
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * max
	 * 仅支持单个字段max，不支持各种子查询max
	 * max(field)
	 * 
	 * @param  string  $field 字段名
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function max($field) {
		if (true === is_string($field)) {
			$this->_max = $field;
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * min
	 * 与max写法一致
	 * min(field)
	 * 
	 * @param  string  $field 字段名
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function min($field) {
		if (true === is_string($field)) {
			$this->_min = $field;
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/******************* 以下open与close方法需要成对使用，有open就应该有close **********/

	/**
	 * 使用括号来改变优先级，默认对and使用括号
	 * 打开左括号
	 * 适用于一些having条件需要结合的场景，同时希望运算什么，再运算什么的
	 * and 运算符的优先级要高于 or，因此当一定场景下是需要使用括号来改变
	 * 
	 * @return object $this
	 */
	public function havingOpen() {
		return $this->andHavingOpen();
	}

	/**
	 * 对and使用括号
	 * 
	 * @return object $this
	 */
	public function andHavingOpen() {
		$this->_having[] = array('and' => '(');
		return $this;
	}

	/**
	 * 对or使用括号 同andHavingOpen
	 * 
	 * @return object $this
	 */
	public function orHavingOpen() {
		$this->_having[] = array('or' => '(');
		return $this;
	}

	/**
	 * 与havingOpen对应使用
	 * 虽然最终是执行的andHavingClose，但是在使用了havingOpen后，还是建议使用havingClose
	 * 主要是为了增加可读性
	 * 使用右括号
	 * 
	 * @return object $this
	 */
	public function havingClose() {
		return $this->andHavingClose();
	}

	/**
	 * 与andHavingOpen对应使用
	 * 使用右括号
	 * 
	 * @return object $this
	 */
	public function andHavingClose() {
		$this->_having[] = array('and' => ')');
		return $this;
	}

	/**
	 * 与orHavingOpen对应使用
	 * 使用右括号
	 * 
	 * @return object $this
	 */
	public function orHavingClose() {
		$this->_having[] = array('or' => ')');
		return $this;
	}

	/**
	 * 左关联查询
	 * 
	 * @return void
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function leftJoin() {
		return $this->join();
	}

	/**
	 * 右关联查询
	 * 
	 * @return void
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function rightJoin() {
		return $this->join();
	}

	/**
	 * 关联查询 禁用方法
	 * 
	 * @return void
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function join() {
		throw new DatabaseException('-4000001');
	}

	/**
	 * 联合关联查询 禁用方法
	 * 
	 * @return void
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function union() {
		throw new DatabaseException('-4000002');
	}

	/**
	 * in子查询 禁用方法
	 * 
	 * @return void
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function in() {
		throw new DatabaseException('-4000003');
	}

	/**
	 * 一般与查询联合使用 禁用方法
	 * 
	 * @return void
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function on() {
		throw new DatabaseException('-4000004');
	}

	/**
	 * 分页偏移
	 * 
	 * @param  integer $offset 偏移量
	 * 
	 * @return object $this
	 */
	public function offset($offset = 0) {
		$this->_offset = (int) $offset;
		return $this;
	}

	/**
	 * 查询attibute的结果集
	 * find(array('field', 'field'))
	 * 那么结果集里就只有attibute的值
	 * 内部使用select a, b 这种形式
	 * 
	 * @param  array  $attibute array
	 * 
	 * @return object $this
	 */
	public function find($attibute = array()) {
		if (true === empty($attibute)) {
			$attibute = array('*');
		}
		$this->_field = array_map(array($this, '_qField'), $attibute);
		return $this;
	}
}