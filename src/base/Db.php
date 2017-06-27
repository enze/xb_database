<?php
/**
 * 数据库CRUD接口
 *
 * 2015.3.27 16:37
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\db\base;

interface Db {

	public function select();

	public function insert();

	public function delete();

	public function update();

}