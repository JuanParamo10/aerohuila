<?php
require_once '../config/security.php';
require_once '../config/db.php';
require_once 'auditoria_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. VALIDACIÓN DE SEGURIDAD (ID PROVEEDOR)
    if (empty($_POST['proveedor_id'])) {
        header("Location: ../dashboard.php?msg=error_id");
        exit;
    }

    $provId = $_POST['proveedor_id'];
    $numOP = trim($_POST['numero_op']); // Limpiamos espacios
    $fecha = $_POST['fecha_orden'];
    $concepto = $_POST['concepto'];
    $estado = $_POST['estado'];
    
    // --- 2. VALIDACIÓN DE OP DUPLICADA (NUEVO) ---
    // Preparamos la consulta base
    $sqlCheck = "SELECT COUNT(*) FROM ordenes_pago WHERE numero_op = ?";
    $paramsCheck = [$numOP];

    // Si es edición, excluimos la orden actual de la búsqueda
    if (!empty($_POST['id_orden_editar'])) {
        $sqlCheck .= " AND id != ?";
        $paramsCheck[] = $_POST['id_orden_editar'];
    }

    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute($paramsCheck);
    
    if ($stmtCheck->fetchColumn() > 0) {
        // SI YA EXISTE, DEVUELVE ERROR
        echo "<script>
            alert('Error: El número de OP ($numOP) ya existe en el sistema. Por favor verifique.');
            window.location.href = '../dashboard.php';
        </script>";
        exit;
    }
    // ----------------------------------------------

    // Limpieza Moneda
    $valorRaw = $_POST['valor'];
    $valorClean = str_replace('.', '', $valorRaw);
    $valorClean = str_replace(',', '.', $valorClean);
    $valor = floatval($valorClean);
    
    // Fecha Pago
    $fechaPago = ($estado == 'Pagado' && !empty($_POST['fecha_pago'])) ? $_POST['fecha_pago'] : null;

    $uploadDir = '../uploads/ordenes/' . $provId . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    function subir($input, $dir, $pre) {
        if (isset($_FILES[$input]) && $_FILES[$input]['error'] == 0) {
            $n = $pre . '_' . uniqid() . '.pdf';
            move_uploaded_file($_FILES[$input]['tmp_name'], $dir . $n);
            return $dir . $n;
        }
        return null;
    }

    $rutas = [
        'req' => subir('pdf_requisicion', $uploadDir, 'REQ'),
        'fac' => subir('pdf_factura', $uploadDir, 'FAC'),
        'cta' => subir('pdf_cuenta', $uploadDir, 'CTA'),
        'compra' => subir('pdf_compra', $uploadDir, 'COMPRA'),
        'soporte_pago' => subir('pdf_soporte_pago', $uploadDir, 'SOPORTE_PAGO'),
        'op_firmada' => subir('pdf_op_firmada', $uploadDir, 'OP_FIRMADA')
    ];

    if (!empty($_POST['id_orden_editar'])) {
        // EDITAR
        $idOrden = $_POST['id_orden_editar'];
        $sql = "UPDATE ordenes_pago SET numero_op=?, fecha_orden=?, concepto_pago=?, valor=?, estado_orden=?, fecha_pago=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numOP, $fecha, $concepto, $valor, $estado, $fechaPago, $idOrden]);
        
        // Actualizar archivos
        if($rutas['req']) $pdo->prepare("UPDATE ordenes_pago SET ruta_requisicion=? WHERE id=?")->execute([$rutas['req'], $idOrden]);
        if($rutas['fac']) $pdo->prepare("UPDATE ordenes_pago SET ruta_factura=? WHERE id=?")->execute([$rutas['fac'], $idOrden]);
        if($rutas['cta']) $pdo->prepare("UPDATE ordenes_pago SET ruta_cuenta_cobro=? WHERE id=?")->execute([$rutas['cta'], $idOrden]);
        if($rutas['compra']) $pdo->prepare("UPDATE ordenes_pago SET ruta_orden_compra=? WHERE id=?")->execute([$rutas['compra'], $idOrden]);
        if($rutas['soporte_pago']) $pdo->prepare("UPDATE ordenes_pago SET ruta_soporte_pago=? WHERE id=?")->execute([$rutas['soporte_pago'], $idOrden]);
        if($rutas['op_firmada']) $pdo->prepare("UPDATE ordenes_pago SET ruta_op_firmada=? WHERE id=?")->execute([$rutas['op_firmada'], $idOrden]);

        registrarAuditoria($pdo, "Editar Orden", "OP: $numOP");

    } else {
        // CREAR
        $sql = "INSERT INTO ordenes_pago (proveedor_id, numero_op, fecha_orden, concepto_pago, valor, estado_orden, fecha_pago, ruta_requisicion, ruta_factura, ruta_cuenta_cobro, ruta_orden_compra, ruta_soporte_pago, ruta_op_firmada) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$provId, $numOP, $fecha, $concepto, $valor, $estado, $fechaPago, $rutas['req'], $rutas['fac'], $rutas['cta'], $rutas['compra'], $rutas['soporte_pago'], $rutas['op_firmada']]);

        registrarAuditoria($pdo, "Nueva Orden", "OP: $numOP");
    }

    $ultimo = $pdo->query("SELECT estado_orden FROM ordenes_pago WHERE proveedor_id=$provId ORDER BY fecha_orden DESC, id DESC LIMIT 1")->fetchColumn();
    if($ultimo) $pdo->query("UPDATE proveedores SET estado_global='$ultimo' WHERE id=$provId");

    header("Location: ../dashboard.php?msg=orden_guardada");
}
?>