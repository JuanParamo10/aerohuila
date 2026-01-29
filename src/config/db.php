<?php
// Configuración para Docker
$host = 'aerohuila_db'; // Nombre del servicio en docker-compose
$db   = 'aerohuila_db';
$user = 'admin';
$pass = 'admin';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=3306";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción no mostrar el error exacto
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>