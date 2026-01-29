<?php
require_once '../config/db.php';

if (isset($_GET['id_proveedor'])) {
    $id = $_GET['id_proveedor'];
    
    try {
        $sql = "SELECT * FROM ordenes_pago WHERE proveedor_id = ? ORDER BY fecha_orden DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolvemos los datos en formato JSON para que Javascript los entienda
        echo json_encode($ordenes);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>