<?php
/**
 * 分区算法抽象类
 *
 * 2015.3.27 16:40
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\sharding;

abstract class Split {

	abstract protected function _covert($split, $mode);

	public function covert($split, $mode = null) {
		$this->_covert($split, $mode);
		return $this;
	}
}