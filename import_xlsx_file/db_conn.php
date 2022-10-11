<?php

/* @var $logger \Monolog\Logger instance */

$host = getenv( 'MYSQL_HOST');
$port = getenv( 'MYSQL_PORT');
$dbname = getenv( 'MYSQL_DATABASE');
$user = getenv( 'MYSQL_USER');
$password = getenv( 'MYSQL_PASSWORD');

$options = [
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_EMULATE_PREPARES => false,
    \PDO::ATTR_STRINGIFY_FETCHES => false
];

//$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

try {
    $db = new \PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    $logger->error($e->getMessage());
}
