<?php
class mail_driver {
	private $DBH; // pdo
	private $pointer; // Указатель на соединение с imap сервером
	private $imap_host; // url на imap сервер
	private $imap_port; // используемый port

	function __construct($DBH) {
		$this->DBH = $DBH;
		
	}

	/**
	 *	Инициализация модуля
	 *
	 *	@access public
	 *	@return string
	 */
	public function init($url, $login, $password) {
		
	}

	/**
	 *	Подключение к imap серверу
	 *
	 *	@access public
	 *	@return string
	 */
	private function open() {
		$errno = $errstr = '';
		$fp = fsockopen($host, $port, $errno, $errstr, 15)
	}

}
?>