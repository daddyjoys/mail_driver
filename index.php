<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL | E_STRICT);

mb_internal_encoding('utf-8');
set_time_limit(60);
// Подключимся к БД
$DBH = new PDO("mysql:host=localhost;dbname=examples", 'root', '');
$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$DBH->exec('SET NAMES utf8');

include_once "imap_driver.php";

$login =  '';
$password = '';

$app = new IMAP_DRIVER($DBH);
// $app->open('ssl://imap.gmail.com', 993);
$app->open('imap.gmail.com', 993, $login, $password, 'INBOX');
$app->getLetters(3);
$app->close();

// Вспомогьная функция, используем для отладки
function v3($arr) {
	echo('<pre style="background:wheat;font-size:13px;border:1px dotted rgb(13, 125, 212);background: rgb(217, 241, 255);padding: 3px 10px;margin:15px;">');
	var_export($arr);
	echo('</pre>');
}
?>