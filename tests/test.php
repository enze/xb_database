<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/xb/base/Xb.php';

use xb\db\Db;

$config = [
	'name' => 'item',
	'db_prefix' => 'db401_',
	'tbl_prefix' => 'tbl_',
	'split' => 'nosplit',
	'tblsplit' => 'nosplit',
	'master' => [
		'host' => ['10.0.0.20'],
		'user' => 'root',
		'pw' => '123456',
	],
];

$query = Xb::createObject('xb\\db\\Db', true, 'item', $config);

$query->select(1)->where(['id' => '1'])->fetch();