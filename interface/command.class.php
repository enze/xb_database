<?php
/**
 * 数据库命令接口
 *
 * 2015.3.27 16:31
 * @author enze.wei <[enzewei@gmail.com]>
 */

interface InterFaceCommand {

	public function connectCommand();

	/**
	 * 字段处理接口
	 */
	public function qFieldCommand();

	/**
	 * 表名处理接口
	 */
	public function qTableCommand();

	/**
	 * rpcLog类型处理接口
	 */
	public function rpcLogTypeCommand();
}