<?php
/**
 * 按日期格式分区算法类
 *
 * 2015.3.27 16:40
 * @author enze.wei <[enzewei@gmail.com]>
 */

/**
 * 日期格式分库分表，日期格式都有前导0，即YYYY-mm-dd HH:ii形式。
 * 主要有以下几种模式：
 * 1、Y方式分库,M方式分表；
 * 2、Y方式分库 不分表；
 * 3、Y_M方式分库,不分表；
 * 4、Y_M方式分库，D方式分表；
 * 5、Y_M_D方式分库，不分表；
 * 6、Y_M_D方式分库，H方式分表
 * 7、不分库，Y方式分表
 * 8、不分库，M方式分表（无意义，该情况直接使用第一种模式）
 * 9、不分库，D方式分表（无意义，该情况直接使用第四种模式）
 * 10、不分库，H方式分表（无意义，该情况直接使用第六种模式）
 */
class DateSharding extends AbstractSplit implements InterFaceSharding {

	/*
	 * 模式一
	 */
	const DATE_SHARDING_ONE = 1;

	/*
	 * 模式二
	 */
	const DATE_SHARDING_TWO = 2;

	/*
	 * 模式三
	 */
	const DATE_SHARDING_THREE = 3;

	/*
	 * 模式四
	 */
	const DATE_SHARDING_FOUR = 4;

	/*
	 * 模式五
	 */
	const DATE_SHARDING_FIVE = 5;

	/*
	 * 模式六
	 */
	const DATE_SHARDING_SIX = 6;

	/*
	 * 模式七
	 */
	const DATE_SHARDING_SEVEN = 7;

	/*
	 * 模式八
	 */
	const DATE_SHARDING_EIGHT = 8;

	/*
	 * 模式九
	 */
	const DATE_SHARDING_NINE = 9;

	/*
	 * 模式十
	 */
	const DATE_SHARDING_TEN = 10;

	protected function _covert($split, $mode = self::DATE_SHARDING_ONE) {
		$split = explode('-', date('Y-m-d-H-i', (10 === strlen($split) ? (int) $split : time())));
		switch ($mode) {
			case self::DATE_SHARDING_TWO:
				$this->_id = array($split[0], '');
				break;
			case self::DATE_SHARDING_THREE:
				$this->_id = array($split[0] . '_' . $split[1], '');
				break;
			case self::DATE_SHARDING_FOUR:
			case self::DATE_SHARDING_NINE:
				$this->_id = array($split[0] . '_' . $split[1], $split[2]);
				break;
			case self::DATE_SHARDING_FIVE:
				$this->_id = array($split[0] . '_' . $split[1] . '_' . $split[2], '');
				break;
			case self::DATE_SHARDING_SIX:
			case self::DATE_SHARDING_TEN:
				$this->_id = array($split[0] . '_' . $split[1] . '_' . $split[2], $split[3]);
				break;
			case self::DATE_SHARDING_SEVEN:
				$this->_id = array('', $split[0]);
				break;
			case self::DATE_SHARDING_ONE:
			case self::DATE_SHARDING_EIGHT:
			default:
				$this->_id = array($split[0], $split[1]);
				break;
		}
		unset($split);
	}

	public function getId() {
		return $this->_id;
	}
}