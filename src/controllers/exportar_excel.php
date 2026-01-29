<?php
// 1. INICIAR EL BÚFER INMEDIATAMENTE (Captura cualquier error o espacio en blanco accidental)
ob_start();

require_once '../config/security.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 2. LIMPIEZA TOTAL: Borramos cualquier alerta o error previo del búfer
    // Esto asegura que el archivo Excel empiece limpio, sin textos de error.
    ob_clean(); 

    $tipo = isset($_POST['tipo_reporte']) ? $_POST['tipo_reporte'] : 'reporte';
    $fecha_actual = date('Y-m-d_H-i');
    $nombre_archivo = "Reporte_" . $tipo . "_" . $fecha_actual . ".xls";

    // 3. CABECERAS PARA DESCARGA (Forzar UTF-8)
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$nombre_archivo");
    header("Pragma: no-cache");
    header("Expires: 0");

    // 4. BOM (Byte Order Mark): Esto hace que Excel reconozca tildes y Ñ automáticamente
    echo "\xEF\xBB\xBF"; 

    // Estilos CSS para la tabla (Simple y compatible con Excel)
    echo "
    <style>
        body { font-family: Arial, sans-serif; }
        th { background-color: #0f172a; color: white; border: 1px solid #000; padding: 10px; }
        td { border: 1px solid #ccc; padding: 8px; vertical-align: middle; }
        .st-Pendiente { background-color: #fee2e2; color: #991b1b; }
        .st-Radicado { background-color: #e0f2fe; color: #075985; }
        .st-Pagado { background-color: #dcfce7; color: #166534; }
        .st-Anulado { background-color: #f3f4f6; color: #4b5563; }
        .text-center { text-align: center; }
    </style>";

    // --- GENERACIÓN DE REPORTES ---

    // CASO 1: REPORTE GLOBAL
    if ($tipo == 'global_proveedores') {
        $sql = "SELECT * FROM proveedores ORDER BY nombre ASC";
        $stmt = $pdo->query($sql);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>REPORTE GLOBAL DE PROVEEDORES - AEROHUILA</h3>";
        echo "<table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>NIT / Cédula</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Razón Social / Servicio</th>
                        <th>Banco</th>
                        <th>Tipo Cuenta</th>
                        <th>No. Cuenta</th>
                        <th>Estado Actual</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($datos as $row) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nombre']}</td>
                    <td>{$row['nit_cedula']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['telefono']}</td>
                    <td>{$row['razon_social']}</td>
                    <td>{$row['banco']}</td>
                    <td>{$row['tipo_cuenta']}</td>
                    <td>'{$row['numero_cuenta']}</td>
                    <td class='st-{$row['estado_global']}'>{$row['estado_global']}</td>
                    <td>{$row['fecha_creacion']}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    }

    // CASO 2: REPORTE FINANCIERO
    elseif ($tipo == 'financiero_fechas') {
        $inicio = $_POST['fecha_inicio'];
        $fin = $_POST['fecha_fin'];
        $estado = $_POST['estado_filtro'];

        $sql = "SELECT o.*, p.nombre as proveedor_nombre, p.nit_cedula 
                FROM ordenes_pago o 
                JOIN proveedores p ON o.proveedor_id = p.id 
                WHERE o.fecha_orden BETWEEN ? AND ?";
        
        $params = [$inicio, $fin];

        if ($estado !== 'Todos') {
            $sql .= " AND o.estado_orden = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY o.fecha_orden DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>REPORTE FINANCIERO DE ÓRDENES DE PAGO</h3>";
        echo "<p><strong>Desde:</strong> $inicio <strong>Hasta:</strong> $fin | <strong>Filtro:</strong> $estado</p>";
        echo "<table>
                <thead>
                    <tr>
                        <th>Fecha Orden</th>
                        <th>Proveedor</th>
                        <th>NIT</th>
                        <th>Concepto del Pago</th>
                        <th>Valor</th>
                        <th>Estado Orden</th>
                        <th>Req.</th>
                        <th>Fac.</th>
                        <th>Pago.</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($datos as $row) {
            $req = $row['ruta_requisicion'] ? 'SI' : 'NO';
            $fac = $row['ruta_factura'] ? 'SI' : 'NO';
            $pag = $row['ruta_orden_pago'] ? 'SI' : 'NO';

            echo "<tr>
                    <td>{$row['fecha_orden']}</td>
                    <td>{$row['proveedor_nombre']}</td>
                    <td>{$row['nit_cedula']}</td>
                    <td>{$row['concepto_pago']}</td>
                    <td>$ " . number_format($row['valor'], 0, ',', '.') . "</td>
                    <td class='st-{$row['estado_orden']}'>{$row['estado_orden']}</td>
                    <td class='text-center'>$req</td>
                    <td class='text-center'>$fac</td>
                    <td class='text-center'>$pag</td>
                  </tr>";
        }
        echo "</tbody></table>";
    }

    // CASO 3: HISTORIAL INDIVIDUAL
    elseif ($tipo == 'individual_proveedor') {
        $idProv = $_POST['id_proveedor'];

        $stmtP = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmtP->execute([$idProv]);
        $prov = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$prov) { echo "Proveedor no encontrado"; exit; }

        $stmtO = $pdo->prepare("SELECT * FROM ordenes_pago WHERE proveedor_id = ? ORDER BY fecha_orden DESC");
        $stmtO->execute([$idProv]);
        $ordenes = $stmtO->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>HISTORIAL INDIVIDUAL: {$prov['nombre']}</h3>";
        echo "<p><strong>NIT:</strong> {$prov['nit_cedula']} <br> <strong>Estado Actual:</strong> {$prov['estado_global']}</p>";
        
        echo "<table>
                <thead>
                    <tr>
                        <th>Fecha Orden</th>
                        <th>Concepto</th>
                        <th>Valor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($ordenes as $row) {
            echo "<tr>
                    <td>{$row['fecha_orden']}</td>
                    <td>{$row['concepto_pago']}</td>
                    <td>$ " . number_format($row['valor'], 0, ',', '.') . "</td>
                    <td class='st-{$row['estado_orden']}'>{$row['estado_orden']}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    }
    
    // 5. ENVIAR AL NAVEGADOR Y FINALIZAR
    ob_end_flush();
    exit;
}
?>