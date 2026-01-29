<?php
require_once '../config/security.php';
require_once '../config/db.php';

// Solo el Admin puede descargar la base de datos
if (!$esAdmin) {
    header("Location: ../dashboard.php");
    exit;
}

// Configuración de la Base de Datos (Debes asegurarte que coincidan con db.php)
// En localhost suele ser root y sin pass, en Hostinger usa las variables reales.
$dbHost = 'localhost';
$dbUser = 'root';     // CAMBIAR EN HOSTINGER POR EL USUARIO REAL
$dbPass = 'admin123'; // CAMBIAR EN HOSTINGER POR LA CONTRASEÑA REAL
$dbName = 'aerohuila_db'; // CAMBIAR EN HOSTINGER

$fecha = date('Y-m-d_H-i-s');
$nombreArchivo = "Backup_Aerohuila_$fecha.sql";

// Encabezados para forzar la descarga
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"$nombreArchivo\"");

// COMANDO PARA GENERAR EL BACKUP
// 1. Obtenemos todas las tablas
$tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$sqlScript = "-- BACKUP AEROHUILA SYSTEM --\n";
$sqlScript .= "-- Fecha: $fecha --\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tablas as $tabla) {
    // Estructura de la tabla
    $createStmt = $pdo->query("SHOW CREATE TABLE $tabla")->fetch(PDO::FETCH_NUM);
    $sqlScript .= "DROP TABLE IF EXISTS `$tabla`;\n";
    $sqlScript .= $createStmt[1] . ";\n\n";

    // Datos de la tabla
    $rows = $pdo->query("SELECT * FROM $tabla")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $sqlScript .= "INSERT INTO `$tabla` VALUES(";
        $valores = [];
        foreach ($row as $value) {
            $value = addslashes($value);
            $value = str_replace("\n", "\\n", $value);
            $valores[] = '"' . $value . '"';
        }
        $sqlScript .= implode(', ', $valores);
        $sqlScript .= ");\n";
    }
    $sqlScript .= "\n\n";
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS=1;";

echo $sqlScript;
exit;
?>