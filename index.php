<?php
ini_set('display_errors', 1);
error_reporting(1);

mb_internal_encoding('utf-8');
$DBH = new PDO("mysql:host=localhost;dbname=examples", 'root', 'GaVrIlOiD007');
// $DBH = new PDO("mysql:host=82.151.200.101;dbname=rosstour", 'online', 'GaVrIlOiD007');
$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$DBH->exec('SET NAMES utf8');
?>