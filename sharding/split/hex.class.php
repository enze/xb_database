<?php
/**
 * 按十六进制格式分区算法类
 *
 * 2015.3.27 16:40
 * @author enze.wei <[enzewei@gmail.com]>
 */

/**
 * 十六进制格式分库分表，只有三种模式：1、0x0-0xf库,0x00-0xff表；2、不分库，0x0-0xf表；3、不分库,0x00-0xff表。其他方式无意义
 */
class HexSharding extends AbstractSplit implements InterFaceSharding {

	/*
	 * 默认分库分表模式即模式一
	 */
	const HEX_SHARDING_ALL = 1;

	/*
	 * 模式二
	 */
	const HEX_SHARDING_UNIT = 2;

	/*
	 * 模式三
	 */
	const HEX_SHARDING_DOUBLE = 3;

	protected function _covert($split, $mode = self::HEX_SHARDING_ALL) {
		$split = md5($split);
		switch ($mode) {
			case self::HEX_SHARDING_UNIT:
				$this->_id = array('', $split[0]);
				break;
			case self::HEX_SHARDING_DOUBLE:
				$this->_id = array('', $split[0] . $split[1]);
				break;
			case self::HEX_SHARDING_ALL:
			default:
				$this->_id = array($split[0], $split[0] . $split[1]);
				break;
		}
		unset($split);
	}

	public function getId() {
		return $this->_id;
	}
}