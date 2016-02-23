<?php
/**
 *
 */
require_once('ProxyList.php');
$type = 'RO';
$balance = new ProxyList();
$mysql = $balance->getConnection($type);

var_dump($mysql);
