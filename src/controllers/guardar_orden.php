<?php
require_once '../config/security.php';
require_once '../config/db.php';
require_once 'auditoria_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $provId = $_POST['proveedor_id'];
    $fecha = $_POST['fecha_orden'];
    $concepto = $_POST['concepto'];
    $valor = $_POST['valor'];
    $estado = $_POST['estado'];
    
    // Carpeta por ID de proveedor para mantener orden
    $uploadDir = '../uploads/ordenes/' . $provId . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Helper subida
    function subir($inputName, $dir, $prefix) {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
            $name = $prefix . '_' . uniqid() . '.pdf';
            move_uploaded_file($_FILES[$inputName]['tmp_name'], $dir . $name);
            return $dir . $name;
        }
        return null;
    }

    // Subir archivos nuevos (si existen)
    $rutas = [
        'req' => subir('pdf_requisicion', $uploadDir, 'REQ'),
        'fac' => subir('pdf_factura', $uploadDir, 'FAC'),
        'cta' => subir('pdf_cuenta', $uploadDir, 'CTA'),
        'pago' => subir('pdf_pago', $uploadDir, 'PAGO'),
        'compra' => subir('pdf_compra', $uploadDir, 'COMPRA')
    ];

    // --- LÓGICA DE GUARDADO ---
    if (!empty($_POST['id_orden_editar'])) {
        // EDITAR
        $idOrden = $_POST['id_orden_editar'];
        $sql = "UPDATE ordenes_pago SET fecha_orden=?, concepto_pago=?, valor=?, estado_orden=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha, $concepto, $valor, $estado, $idOrden]);
        
        // Actualizar archivos solo si se subieron nuevos
        if($rutas['req']) $pdo->prepare("UPDATE ordenes_pago SET ruta_requisicion=? WHERE id=?")->execute([$rutas['req'], $idOrden]);
        if($rutas['fac']) $pdo->prepare("UPDATE ordenes_pago SET ruta_factura=? WHERE id=?")->execute([$rutas['fac'], $idOrden]);
        if($rutas['cta']) $pdo->prepare("UPDATE ordenes_pago SET ruta_cuenta_cobro=? WHERE id=?")->execute([$rutas['cta'], $idOrden]);
        if($rutas['pago']) $pdo->prepare("UPDATE ordenes_pago SET ruta_orden_pago=? WHERE id=?")->execute([$rutas['pago'], $idOrden]);
        if($rutas['compra']) $pdo->prepare("UPDATE ordenes_pago SET ruta_orden_compra=? WHERE id=?")->execute([$rutas['compra'], $idOrden]);

        // AUDITORÍA EDITAR
        registrarAuditoria($pdo, "Editar Orden", "Actualizó orden ID $idOrden. Nuevo estado: $estado");

    } else {
        // CREAR
        $sql = "INSERT INTO ordenes_pago (proveedor_id, fecha_orden, concepto_pago, valor, estado_orden, ruta_requisicion, ruta_factura, ruta_cuenta_cobro, ruta_orden_pago, ruta_orden_compra) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$provId, $fecha, $concepto, $valor, $estado, $rutas['req'], $rutas['fac'], $rutas['cta'], $rutas['pago'], $rutas['compra']]);

        // AUDITORÍA CREAR
        registrarAuditoria($pdo, "Nueva Orden", "Creó orden por $" . number_format($valor) . " - Estado: $estado");
    }

    // --- ACTUALIZACIÓN AUTOMÁTICA DEL ESTADO GLOBAL ---
    // Buscar el estado de la orden más reciente
    $sqlUltimoEstado = "SELECT estado_orden FROM ordenes_pago 
                        WHERE proveedor_id = ? 
                        ORDER BY fecha_orden DESC, id DESC 
                        LIMIT 1";
    $stmtEstado = $pdo->prepare($sqlUltimoEstado);
    $stmtEstado->execute([$provId]);
    $ultimoEstado = $stmtEstado->fetchColumn();

    if ($ultimoEstado) {
        $stmtUpdate = $pdo->prepare("UPDATE proveedores SET estado_global = ? WHERE id = ?");
        $stmtUpdate->execute([$ultimoEstado, $provId]);
    }

    header("Location: ../dashboard.php?msg=orden_guardada");
}
?>