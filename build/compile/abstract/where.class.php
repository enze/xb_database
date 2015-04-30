<?php
/**
 * query where 抽象类
 *
 * 2015.3.27 16:38
 * @modify 2015.4.8 10:32  refactor
 * @author enze.wei <[enzewei@gmail.com]>
 */

abstract class AbstractWhere extends AbstractCompile {

	/*
	 * where条件数组，array(array('and' => array('field', 'op', 'value')))
	 */
	protected $_where = array();

	/*
	 * order 数组, array(array('field', 'sort'))
	 */
	protected $_orderBy = array();

	/*
	 * limit
	 */
	protected $_limit = 0;
	
}