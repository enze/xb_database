<?php
/**
 * 数据库分区接口
 *
 * 2015.3.27 16:37
 * @author enze.wei <[enzewei@gmail.com]>
 */

interface InterFaceSharding {

	/**
	 * 获取sharding编号
	 * @return string sharding id
	 */
	public function getId();
}