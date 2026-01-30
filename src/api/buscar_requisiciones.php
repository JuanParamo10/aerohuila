<?php
require_once '../config/security.php';
require_once '../config/db.php';

$q = isset($_GET['q']) ? $_GET['q'] : '';

// Buscar por Número o Solicitante
$sql = "SELECT * FROM requisiciones 
        WHERE numero_requisicion LIKE ? OR solicitante LIKE ? 
        ORDER BY fecha_solicitud DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$q%", "%$q%"]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);
?>