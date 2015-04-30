<?php
/**
 * query where 抽象类
 *
 * 2015.3.27 16:38
 * @modify 2015.4.8 10:32  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */

abstract class AbstractWhereQuery extends AbstractWhere {

	/**
	 * 默认where为and限定
	 * 
	 * where条件为in的时候，有两种方式传递
	 * where('id', 'in', array('1', '2', '3'))
	 * or
	 * where('id', 'in', '22')
	 * 其他情况下的写法有两种
	 * where('id', '=', 'xxx')
	 * or
	 * where(array('id', 'status'), array('=', '='), array(1,9))
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 */
	public function where($field = array(), $op = array(), $value = array()) {
		return $this->andWhere($field, $op, $value);
	}

	/**
	 * and限定where
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function andWhere($field = array(), $op = array(), $value = array()) {
		if (true === is_string($field)) {
			if (false === is_string($op) || false === is_string($value)) {
				$op = strtoupper($op);
				if ('BETWEEN' != $op && 'NOT BETWEEN' != $op && 'IN' != $op && null !== $value) {
					throw new DatabaseException('-1000002');
				}
			}
			$this->_where[] = array('and' => array($field, $op, $value));
		} else if (true === is_array($field)) {
			foreach ($field as $k => $v) {
				$this->_where[] = array('and' => array($v, $op[$k], $value[$k]));
			}
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * or限定where 写法与where/andWhere一致
	 * 
	 * @param  array  $field 字段名
	 * @param  array  $op    操作符 > < = 等等
	 * @param  array  $value 字段的值或需要满足的条件
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function orWhere($field = array(), $op = array(), $value = array()) {
		if (true === is_string($field)) {
			if (false === is_string($op) || false === is_string($value)) {
				$op = strtoupper($op);
				if ('BETWEEN' != $op && 'NOT BETWEEN' != $op && 'IN' != $op && null !== $value) {
					throw new DatabaseException('-1000002');
				}
			}
			$this->_where[] = array('or' => array($field, $op, $value));
		} else if (true === is_array($field)) {
			foreach ($field as $k => $v) {
				$this->_where[] = array('or' => array($v, $op[$k], $value[$k]));
			}
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/******************* 以下open与close方法需要成对使用，有open就应该有close **********/

	/**
	 * 使用括号来改变优先级，默认对and使用括号
	 * 打开左括号
	 * 适用于一些where条件需要结合的场景，同时希望运算什么，再运算什么的
	 * and 运算符的优先级要高于 or，因此当一定场景下是需要使用括号来改变
	 * 
	 * @return object $this
	 */
	public function whereOpen() {
		return $this->andWhereOpen();
	}

	/**
	 * 对and使用括号
	 * 
	 * @return object $this
	 */
	public function andWhereOpen() {
		$this->_where[] = array('and' => '(');
		return $this;
	}

	/**
	 * 对or使用括号
	 * 举个栗子：限定条件为二年级的男生中并且为班长的同学或学习委员的同学
	 * 那么这个条件应该类似于：grade = 2 and sex = boy and (duty = monitor or 'member of the glorious title of the study')
	 * 
	 * @return object $this
	 */
	public function orWhereOpen() {
		$this->_where[] = array('or' => '(');
		return $this;
	}

	/**
	 * 与whereOpen对应使用
	 * 虽然最终是执行的andWhereClose，但是在使用了whereOpen后，还是建议使用whereClose
	 * 主要是为了增加可读性
	 * 使用右括号
	 * 
	 * @return object $this
	 */
	public function whereClose() {
		return $this->andWhereClose();
	}

	/**
	 * 与andWhereOpen对应使用
	 * 使用右括号
	 * 
	 * @return object $this
	 */
	public function andWhereClose() {
		$this->_where[] = array('and' => ')');
		return $this;
	}

	/**
	 * 与orWhereOpen对应使用
	 * 使用右括号
	 * 
	 * @return object $this
	 */
	public function orWhereClose() {
		$this->_where[] = array('or' => ')');
		return $this;
	}

	/******************* 以上open与close方法需要成对使用，有open就应该有close **********/

	/**
	 * 排序
	 * 特别说明：虽然可以在update，delete操作中使用order by，但是经常会有意外的情况发生，
	 * 因此该方法仅推荐在select中使用。
	 *
	 * @example
	 * orderBy('id', 'desc')
	 * or
	 * orderBy(array('id', 'orderId'), array('asc', 'desc'))
	 * 
	 * @param  array  $field [description]
	 * @param  array  $sort  [description]
	 * 
	 * @return object $this
	 * @throws DataBaseException 显式抛出DataBaseException异常
	 */
	public function orderBy($field = array(), $sort = array()) {
		if (true === is_string($field)) {
			if (false === is_string($sort)) {
				throw new DatabaseException('-1000002');
			}
			$this->_orderBy[] = array($field, $sort);
		} else if (true === is_array($field)) {
			foreach ($field as $k => $v) {
				$this->_orderBy[] = array($v, $sort[$k]);
			}
		} else {
			throw new DatabaseException('-1000003');
		}
		return $this;
	}

	/**
	 * Limit限定
	 * 支持两种形式
	 * limit 1
	 * or
	 * limit 1, 10
	 * 
	 * @param  array  $limit 限定条数
	 * 
	 * @return object $this
	 */
	public function limit($limit = array()) {
		if (false === is_array($limit)) {
			$this->_limit = intval($limit);
		} else {
			list($index, $total) = $limit;
			$this->_limit = $index . ', ' . $total;
		}
		return $this;
	}
}