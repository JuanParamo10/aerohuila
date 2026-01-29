<?php
require_once '../config/security.php';
require_once '../config/db.php';
require_once 'auditoria_helper.php';

// 1. ELIMINAR ORDEN DE PAGO
if (isset($_GET['eliminar_orden'])) {
    $id = $_GET['eliminar_orden'];
    
    // Obtener info para auditoría y saber a qué proveedor pertenece antes de borrar
    $stmtInfo = $pdo->prepare("SELECT valor, proveedor_id FROM ordenes_pago WHERE id = ?");
    $stmtInfo->execute([$id]);
    $info = $stmtInfo->fetch();
    $provId = $info['proveedor_id'];
    
    // Borrar la orden
    $stmt = $pdo->prepare("DELETE FROM ordenes_pago WHERE id = ?");
    $stmt->execute([$id]);
    
    // AUDITORÍA
    registrarAuditoria($pdo, "Eliminar Orden", "Eliminó la orden ID: $id (Valor: $" . number_format($info['valor']) . ")");

    // --- RECALCULAR ESTADO DEL PROVEEDOR ---
    // Buscamos cuál es ahora la última orden de este proveedor
    $sqlUltimo = "SELECT estado_orden FROM ordenes_pago WHERE proveedor_id = ? ORDER BY fecha_orden DESC, id DESC LIMIT 1";
    $stmtUltimo = $pdo->prepare($sqlUltimo);
    $stmtUltimo->execute([$provId]);
    $nuevoEstado = $stmtUltimo->fetchColumn();

    // Si no quedan órdenes, vuelve a 'Pendiente'. Si quedan, toma el estado de la última.
    $estadoFinal = $nuevoEstado ? $nuevoEstado : 'Pendiente';

    // Actualizar proveedor
    $sqlUpdate = $pdo->prepare("UPDATE proveedores SET estado_global = ? WHERE id = ?");
    $sqlUpdate->execute([$estadoFinal, $provId]);
    
    header("Location: ../dashboard.php?msg=eliminado");
    exit;
}

// 2. ELIMINAR PROVEEDOR
if (isset($_GET['eliminar_proveedor'])) {
    $id = $_GET['eliminar_proveedor'];
    
    $stmtInfo = $pdo->prepare("SELECT nombre FROM proveedores WHERE id = ?");
    $stmtInfo->execute([$id]);
    $nombre = $stmtInfo->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
    $stmt->execute([$id]);
    
    registrarAuditoria($pdo, "Eliminar Proveedor", "Eliminó al proveedor: $nombre y todo su historial.");
    
    header("Location: ../dashboard.php?msg=eliminado");
    exit;
}

// 3. EDITAR DATOS BÁSICOS
if (isset($_POST['accion']) && $_POST['accion'] == 'editar_proveedor') {
    $id = $_POST['id_proveedor'];
    $nombre = $_POST['nombre'];
    $nit = $_POST['nit'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $razon = $_POST['razon_social'];

    $sql = "UPDATE proveedores SET nombre=?, nit_cedula=?, email=?, telefono=?, razon_social=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $nit, $email, $telefono, $razon, $id]);
    
    registrarAuditoria($pdo, "Editar Proveedor", "Actualizó datos de contacto de: $nombre");

    header("Location: ../dashboard.php?msg=actualizado");
    exit;
}
?>