<?php
// 1. LIMPIEZA DE BÚFER
ob_start();
require_once '../config/security.php';
require_once '../config/db.php';
ob_clean(); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tipo = $_POST['tipo_reporte'];
    $fecha = date('Y-m-d_H-i');
    $filename = "Reporte_Aerohuila_$fecha.xls";
    
    // 2. HEADERS CORRECTOS
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // 3. ESTRUCTURA HTML COMPLETA (ESTO ARREGLA EL ERROR VISUAL)
    echo "<html xmlns:x='urn:schemas-microsoft-com:office:excel'>";
    echo "<head>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
    echo "<style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { background-color: #0f172a; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center; vertical-align: middle; }
        .subheader { background-color: #334155; color: #ffffff; font-weight: bold; border: 1px solid #000000; }
        td { border: 1px solid #cccccc; vertical-align: middle; padding: 5px; }
        .money { text-align: right; mso-number-format:'\#\,\#\#0'; }
        .center { text-align: center; }
        .text { mso-number-format:'\@'; } /* Fuerza texto para evitar notación científica */
        .st-Pagado { background-color: #dcfce7; color: #166534; }
        .st-Pendiente { background-color: #fee2e2; color: #991b1b; }
        .st-Radicado { background-color: #e0f2fe; color: #075985; }
        .st-Anulado { background-color: #f3f4f6; color: #4b5563; }
    </style>";
    echo "</head>";
    echo "<body>";

    // ==========================================
    // CASO 1: REPORTE INDIVIDUAL DE UNA SOLA ORDEN (NUEVO)
    // ==========================================
    if ($tipo == 'individual_orden') {
        $idOrden = $_POST['id_orden'];
        
        $sql = "SELECT o.*, 
                       p.nombre as p_nombre, p.nit_cedula, p.razon_social, p.banco, p.tipo_cuenta, p.numero_cuenta, p.email, p.telefono 
                FROM ordenes_pago o 
                JOIN proveedores p ON o.proveedor_id = p.id 
                WHERE o.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idOrden]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            echo "<h3>DETALLE DE ORDEN DE PAGO N° {$row['numero_op']}</h3>";
            echo "<table>
                <tr><th class='header' colspan='4'>INFORMACIÓN DEL PROVEEDOR</th></tr>
                <tr>
                    <td class='subheader'>Razón Social</td><td>{$row['razon_social']}</td>
                    <td class='subheader'>NIT/Cédula</td><td class='text'>{$row['nit_cedula']}</td>
                </tr>
                <tr>
                    <td class='subheader'>Contacto</td><td>{$row['p_nombre']}</td>
                    <td class='subheader'>Teléfono</td><td class='text'>{$row['telefono']}</td>
                </tr>
                <tr>
                    <td class='subheader'>Banco</td><td>{$row['banco']}</td>
                    <td class='subheader'>Cuenta</td><td class='text'>{$row['tipo_cuenta']} - {$row['numero_cuenta']}</td>
                </tr>
                
                <tr><td colspan='4'></td></tr>

                <tr><th class='header' colspan='4'>DETALLE DE LA ORDEN</th></tr>
                <tr>
                    <td class='subheader'>N° OP Manual</td><td class='text'><b>{$row['numero_op']}</b></td>
                    <td class='subheader'>Fecha Orden</td><td>{$row['fecha_orden']}</td>
                </tr>
                <tr>
                    <td class='subheader'>Estado</td><td class='st-{$row['estado_orden']}'><b>{$row['estado_orden']}</b></td>
                    <td class='subheader'>Valor Total</td><td class='money'>$ " . number_format($row['valor'], 0, ',', '.') . "</td>
                </tr>
                <tr>
                    <td class='subheader'>Concepto</td><td colspan='3'>{$row['concepto_pago']}</td>
                </tr>
                
                <tr><td colspan='4'></td></tr>

                <tr><th class='header' colspan='4'>INFORMACIÓN DE PAGO</th></tr>
                <tr>
                    <td class='subheader'>Fecha Pago</td><td>".($row['fecha_pago']?:'-')."</td>
                    <td class='subheader'>Soporte Cargado</td><td>".($row['ruta_soporte_pago']?'SI':'NO')."</td>
                </tr>
            </table>";
        }
    }

    // ==========================================
    // CASO 2: REPORTE GENERAL DE ÓRDENES (DESDE LA LUPA)
    // ==========================================
    elseif ($tipo == 'general_ordenes') {
        $busqueda = isset($_POST['busqueda']) ? $_POST['busqueda'] : '';
        
        $sql = "SELECT o.*, 
                       p.nombre as prov_contacto, p.nit_cedula, p.razon_social, p.banco, p.tipo_cuenta, p.numero_cuenta
                FROM ordenes_pago o 
                JOIN proveedores p ON o.proveedor_id = p.id 
                WHERE o.numero_op LIKE ? OR p.razon_social LIKE ? OR p.nit_cedula LIKE ?
                ORDER BY o.fecha_orden DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$busqueda%", "%$busqueda%", "%$busqueda%"]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>REPORTE DE BÚSQUEDA DE ÓRDENES</h3>";
        if($busqueda) echo "<p>Filtro: '$busqueda'</p>";
        
        // Renderizar tabla maestra
        renderTablaMaestra($filas);
    }

    // ==========================================
    // CASO 3: REPORTE FINANCIERO (POR FECHAS/ESTADO) - REPARADO
    // ==========================================
    elseif ($tipo == 'financiero_fechas') {
        $inicio = $_POST['fecha_inicio'];
        $fin = $_POST['fecha_fin'];
        $estado = $_POST['estado_filtro'];

        $sql = "SELECT o.*, 
                       p.nombre as prov_contacto, p.nit_cedula, p.razon_social, p.banco, p.tipo_cuenta, p.numero_cuenta
                FROM ordenes_pago o 
                JOIN proveedores p ON o.proveedor_id = p.id 
                WHERE (o.fecha_orden BETWEEN ? AND ?)";
        
        $params = [$inicio, $fin];
        if ($estado != 'Todos') {
            $sql .= " AND o.estado_orden = ?";
            $params[] = $estado;
        }
        $sql .= " ORDER BY o.fecha_orden DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>REPORTE FINANCIERO ($inicio a $fin)</h3>";
        renderTablaMaestra($filas);
    }

    // ==========================================
    // CASO 4: REPORTE INDIVIDUAL PROVEEDOR
    // ==========================================
    elseif ($tipo == 'individual_proveedor') {
        $id = $_POST['id_proveedor'];
        $prov = $pdo->query("SELECT * FROM proveedores WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>HISTORIAL: {$prov['razon_social']} (NIT: {$prov['nit_cedula']})</h3>";
        echo "<table>
            <tr><td class='subheader'>Banco</td><td>{$prov['banco']}</td><td class='subheader'>Cuenta</td><td class='text'>{$prov['numero_cuenta']}</td></tr>
            <tr><td class='subheader'>Contacto</td><td>{$prov['nombre']}</td><td class='subheader'>Tel</td><td class='text'>{$prov['telefono']}</td></tr>
        </table><br>";

        $filas = $pdo->query("SELECT o.*, '{$prov['razon_social']}' as razon_social, '{$prov['nit_cedula']}' as nit_cedula, '{$prov['banco']}' as banco, '{$prov['numero_cuenta']}' as numero_cuenta FROM ordenes_pago o WHERE proveedor_id=$id ORDER BY fecha_orden DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        renderTablaMaestra($filas);
    }

    // ==========================================
    // CASO 5: GLOBAL DE PROVEEDORES - REPARADO
    // ==========================================
    elseif ($tipo == 'global_proveedores') {
        $filas = $pdo->query("SELECT * FROM proveedores ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>DIRECTORIO DE PROVEEDORES</h3>";
        echo "<table>
            <thead>
                <tr class='subheader'>
                    <th>ID</th>
                    <th>Razón Social</th>
                    <th>NIT/Cédula</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Banco</th>
                    <th>Tipo Cta</th>
                    <th># Cuenta</th>
                    <th>Estado Global</th>
                    <th>Fecha Registro</th>
                </tr>
            </thead>
            <tbody>";
        foreach($filas as $r) {
            echo "<tr>
                <td>{$r['id']}</td>
                <td>{$r['razon_social']}</td>
                <td class='text'>{$r['nit_cedula']}</td>
                <td>{$r['nombre']}</td>
                <td>{$r['email']}</td>
                <td class='text'>{$r['telefono']}</td>
                <td>{$r['banco']}</td>
                <td>{$r['tipo_cuenta']}</td>
                <td class='text'>{$r['numero_cuenta']}</td>
                <td>{$r['estado_global']}</td>
                <td>{$r['fecha_creacion']}</td>
            </tr>";
        }
        echo "</tbody></table>";
    }

    echo "</body></html>";
    
    ob_end_flush();
    exit;
}

// FUNCIÓN HELPER PARA TABLAS DE ÓRDENES (EVITA REPETIR CÓDIGO)
function renderTablaMaestra($filas) {
    echo "<table>
        <thead>
            <tr class='subheader'>
                <th>Razón Social</th>
                <th>NIT / Cédula</th>
                <th>Banco</th>
                <th># Cuenta</th>
                
                <th>N° OP</th>
                <th>Fecha Orden</th>
                <th>Concepto</th>
                <th>Valor ($)</th>
                <th>Estado</th>
                <th>Fecha Pago</th>
                
                <th>Fac/Cot</th>
                <th>Soporte Pago</th>
                <th>OP Firmada</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach($filas as $row) {
        $f_pago = $row['fecha_pago'] ? $row['fecha_pago'] : '-';
        $fac = $row['ruta_factura'] ? 'SI' : '-';
        $sop = $row['ruta_soporte_pago'] ? 'SI' : '-';
        $opf = $row['ruta_op_firmada'] ? 'SI' : '-';
        
        // Manejar datos de proveedor que pueden venir con diferentes alias
        $razon = $row['razon_social'] ?? '';
        $nit = $row['nit_cedula'] ?? '';
        $banco = $row['banco'] ?? '';
        $cta = $row['numero_cuenta'] ?? '';

        echo "<tr>
            <td>$razon</td>
            <td class='text'>$nit</td>
            <td>$banco</td>
            <td class='text'>$cta</td>
            
            <td class='text center'><b>{$row['numero_op']}</b></td>
            <td class='center'>{$row['fecha_orden']}</td>
            <td>{$row['concepto_pago']}</td>
            <td class='money'>$ " . number_format($row['valor'], 0, ',', '.') . "</td>
            <td class='st-{$row['estado_orden']} center'>{$row['estado_orden']}</td>
            <td class='center'>$f_pago</td>
            
            <td class='center'>$fac</td>
            <td class='center'>$sop</td>
            <td class='center'>$opf</td>
        </tr>";
    }
    echo "</tbody></table>";
}
?>