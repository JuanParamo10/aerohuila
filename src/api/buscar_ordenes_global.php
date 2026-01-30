<?php
require_once '../config/security.php';
require_once '../config/db.php';

$q = isset($_GET['q']) ? $_GET['q'] : '';

$sql = "SELECT o.*, p.razon_social, p.nit_cedula 
        FROM ordenes_pago o 
        JOIN proveedores p ON o.proveedor_id = p.id 
        WHERE o.numero_op LIKE ? OR p.razon_social LIKE ? OR p.nit_cedula LIKE ?
        ORDER BY o.fecha_orden DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$q%", "%$q%", "%$q%"]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolvemos JSON puro para que Javascript lo lea
header('Content-Type: application/json');
echo json_encode($resultados);
?>