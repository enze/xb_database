<?php
/**
 * Mysql的一些命令宏
 *
 * 2015.3.27 16:31
 * @author enze.wei <[enzewei@gmail.com]>
 */

class MysqlCommand implements InterFaceCommand {

	const USE_PDO = true;

	/**
	 *  PDO方式获取DSN
	 *  
	 * @return string
	 */
	private function _getDsnCommand() {
		return '{%type%}:host={%host%};port={%port%};dbname={%dbname%};charset=utf8';
	}

	/**
	 *  Mysql原生方式获取server
	 *  
	 * @return string
	 */
	private function _getConnectCommand() {
		//host:port
		return '{%host}:{%port}';
	}

	/**
	 * 连接命令
	 * 
	 * @return string
	 */
	public function connectCommand() {
		if (true === self::USE_PDO) {
			return $this->_getDsnCommand();
		} else {
			return $this->_getConnectCommand();
		}
	}

	public function qFieldCommand() {
		return '`%s`';
	}

	public function qTableCommand() {
		return '`%s` . `%s`';
	}

	public function rpcLogTypeCommand() {
		return RpcLogEnvConfig::RPC_LOG_TYPE_MYSQL;
	}

	public function useXATransaction() {
		return array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	}
}