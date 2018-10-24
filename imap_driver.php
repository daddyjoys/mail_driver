<?php
class IMAP_DRIVER {
	private $DBH; 				// pdo pointer
	private $pointer; 			// Указатель на соединение с imap сервером
	private $imap_host; 		// url на imap сервер
	private $imap_port; 		// используемый port
	private $error_descriptions = array( // Описания ошибок
											101 => 'Ошибка соединения с сервером',
											102 => 'Нет информации о хосте',
											103 => 'Не указан порт для соединения',
											104 => 'Не указан логин',
											105 => 'Не указан пароль',
										);
	public $errors; 			// Возникшие ошибки

	function __construct($DBH) {
		$this->DBH = $DBH;
		
	}

	/**
	 *	Подключение к imap серверу
	 *
	 *	@access public
	 *	@return 
	 */
	public function open($host, $port, $login, $password, $folder = '') {
		if ( empty($host) ) {
			$this->setErrors(102); // Нет информации о хосте
		}
		elseif ( empty($port) ) {
			$this->setErrors(103); // Не указан порт для соединения
		}
		elseif ( empty($login) ) {
			$this->setErrors(104); // Не указан логин
		}
		elseif ( empty($password) ) {
			$this->setErrors(104); // Не указан пароль
		}
		else {
			try {
				$this->pointer = imap_open ('{' . $host . ':' . $port . '/imap/ssl}' . $folder, $login, $password);
			} 
			catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}
			if ( empty($this->pointer) ) {
				$this->setErrors(101); // Ошибка соединения с сервером
			}
		}
		// $folders = imap_list($this->pointer, "{imap.gmail.com:993/imap/ssl}INBOX", "*");
		// v3($folders);
	}

	/**
	 *	Получение писем
	 *	$mode All - получение всех писем
	 *
	 *	@access public
	 *	@params string, string
	 *	@return
	 */
	public function getLetters($limit = 0) {
		$result = array();
		// Получить количество сообщений в текущем почтовом ящике
		$count = imap_num_msg($this->pointer);
		// $i = 1046;
		$j = $limit;
		if ( empty($limit) ) {
			$j = $count;
		}
		for ($i = $count; $i > ($count - $j); $i--) {
			$header = imap_header($this->pointer, $i);
			$uid = imap_uid($this->pointer, $i);
			$result[$i] = $this->getHeader($header);
			$result[$i]['uid'] = $uid;
			
			$result[$i]['body'] = $this->getBody($i, $uid);
		}
			// v3($count);
			// v3($result);
			// v3($uid);
			// v3($header);
	}

	/**
	 *	Получение информации о отправителе, получателе и теме письма
	 *
	 *	@access private
	 *	@return array
	 */
	private function getHeader($header) {
		$result = array();
		if ( !empty($header) ) {
			if ( !empty($header->from[0]) and !empty($header->to[0]) ) {
				 $from = $header->from[0];
				 $to = $header->to[0];
				 $fromName = '';
				 if ( isset($from->personal) ) {
					$fromName = base64_decode(str_ireplace('=?UTF-8?B?', '', $from->personal));
				 }
				 $subject = $subjectRAW = '';
				 if ( isset($header->subject) ) {
					$subject = base64_decode(str_ireplace('=?UTF-8?B?', '', $header->subject));
				 }
				 $result['header'] = array(
										'date' 			=> ( isset($header->date) ) ? $header->date : '',
										'subject' 		=> $subject,
										'from' 			=> ( isset($from->mailbox) and isset($from->host) ) ? $from->mailbox . "@" . $from->host : '',
										'fromName' 		=> $fromName,
										'to' 			=> ( isset($to->mailbox) and isset($to->host) ) ? $to->mailbox . "@" . $to->host : '',
										'RAW' 			=> $header,
									);
			}
		}

		return $result;
	}

	/**
	 *	Получение тела письма
	 *
	 *	@access private
	 *	@params int
	 *	@return string
	 */
	private function getBody($i, $uid) {
		$result = '';
		$structure = imap_fetchstructure($this->pointer, $uid, FT_UID);
		$mimetype = $this->get_mime_type($structure);
		$part = 0;
		if ( $structure->type == 1) {
			$text = imap_fetchbody($this->pointer, $uid, $part, FT_UID);
		}
		else {
			$text = imap_body($this->pointer, $uid, FT_UID);
		}
		// $bodystruct = imap_bodystruct($this->pointer, $i, $part);
		// $text = imap_body($this->pointer, $uid, FT_UID);
		// v3($text);
		v3($structure->parts);

		$coding = $structure->parts[$part]->encoding;
		if ($coding == 0) {
			$body = imap_7bit($text);
			// $body = imap_qprint($text);
		} elseif ($coding == 1) {
			$body= imap_8bit($text);
		} elseif ($coding == 2) {
			$body = imap_binary($text);
		} elseif ($coding == 3) {
			$body = imap_base64($text);
		} elseif ($coding == 4) {
			$body = imap_qprint($text);
		} elseif ($coding == 5) {
			$body = $text;
		}

		return $result;
	}

	/** Получение mime type
	 *
	 *	@access private
	 *	@params obj
	 *	@return string 
	 */
	private function get_mime_type($structure) {
		$result = 'TEXT/PLAIN';
		$mimeTypes = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
		if ( isset($structure->subtype) and isset($mimeTypes[intval($structure->type)]) ) {
			$result = $mimeTypes[intval($structure->type)] . "/" . $structure->subtype;
		}

		return $result;
	}

	/**
	 *	Закрытие соединения
	 *
	 *	@access public
	 *	@return
	 */
	public function close() {
		imap_close($this->pointer);
	}

	/**
	 *	Установка ошибки
	 *
	 *	@access private
	 *	@return
	 */
	private function setErrors($code) {
		if ( !empty($this->error_descriptions[$code]) ) {
			$this->errors[$code] = $this->error_descriptions[$code];
		}
	}

}
?>