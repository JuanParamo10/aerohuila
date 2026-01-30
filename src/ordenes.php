<?php
require_once 'config/security.php';
require_once 'config/db.php';

// Carga inicial (últimas 20)
$ordenes = $pdo->query("SELECT o.*, p.razon_social FROM ordenes_pago o JOIN proveedores p ON o.proveedor_id = p.id ORDER BY o.fecha_orden DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Órdenes - Aerohuila</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4"><h4 class="fw-bold text-white tracking-wider">AEROHUILA</h4></div>
                 <center><small class="text-white-50">Administración</small></center> 
        <hr class="border-secondary mx-3">
        <a href="dashboard.php"><i class="bi bi-truck me-2"></i> Proveedores</a>
        <a href="ordenes.php" class="active"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes</a>
        <a href="auditoria.php"><i class="bi bi-shield-lock me-2"></i> Auditoría</a>
                    <a href="controllers/backup_db.php" class="text-warning" onclick="return confirm('¿Desea descargar una copia completa de la Base de Datos?')">
                <i class="bi bi-database-down me-2"></i> Backup BD
            </a>
        <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
            <a href="controllers/logout.php" class="text-danger ps-0"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark">Búsqueda de Órdenes (OP)</h2>
                
                <form action="controllers/exportar_excel.php" method="POST" id="formExportar">
                    <input type="hidden" name="tipo_reporte" value="general_ordenes">
                    <input type="hidden" name="busqueda" id="inputExportarBusqueda">
                    <button type="button" onclick="exportarExcel()" class="btn btn-success shadow-sm">
                        <i class="bi bi-file-earmark-excel"></i> Exportar Lista
                    </button>
                </form>
            </div>

            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="buscadorVivo" 
                               placeholder="Escribe el Número de OP, Proveedor o NIT..." 
                               onkeyup="buscarEnVivo()">
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° OP</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Concepto</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaResultados">
                            <?php foreach($ordenes as $o): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo $o['numero_op'] ?: 'S/N'; ?></td>
                                <td><?php echo $o['fecha_orden']; ?></td>
                                <td><?php echo $o['razon_social']; ?></td>
                                <td><?php echo substr($o['concepto_pago'],0,40); ?>...</td>
                                <td class="fw-bold">$ <?php echo number_format($o['valor'], 0, ',', '.'); ?></td>
                                <td><span class="badge-estado st-<?php echo $o['estado_orden']; ?>"><?php echo $o['estado_orden']; ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-light border me-1" onclick='verDetalle(<?php echo json_encode($o); ?>)' title="Ver Detalles">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    
                                    <form action="controllers/exportar_excel.php" method="POST" class="d-inline">
                                        <input type="hidden" name="tipo_reporte" value="individual_orden">
                                        <input type="hidden" name="id_orden" value="<?php echo $o['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Excel Individual">
                                            <i class="bi bi-file-excel"></i>
                                        </button>
                                    </form>
                                    
                                    <?php if($o['ruta_op_firmada']): ?>
                                        <a href="<?php echo str_replace('../','',$o['ruta_op_firmada']); ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-1" title="Ver PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Detalle de Orden de Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>N° OP:</strong> <span id="detOp" class="text-primary fw-bold"></span></div>
                        <div class="col-md-6"><strong>Estado:</strong> <span id="detEstado"></span></div>
                        <div class="col-md-6"><strong>Fecha:</strong> <span id="detFecha"></span></div>
                        <div class="col-md-6"><strong>Valor:</strong> <span id="detValor" class="fw-bold"></span></div>
                        <div class="col-12"><hr></div>
                        <div class="col-12"><strong>Proveedor:</strong> <p id="detProv" class="mb-1"></p></div>
                        <div class="col-12"><strong>Concepto Completo:</strong> <div id="detConcepto" class="p-3 bg-light rounded border mt-1"></div></div>
                        
                        <div class="col-12 mt-3" id="seccionPago">
                            <div class="alert alert-success mb-0">
                                <strong><i class="bi bi-check-circle"></i> Pagado el:</strong> <span id="detFechaPago"></span>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <h6>Documentos Adjuntos:</h6>
                            <div id="detDocs" class="d-flex gap-2 flex-wrap"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- BUSCADOR EN VIVO ---
        function buscarEnVivo() {
            let texto = document.getElementById('buscadorVivo').value;
            fetch('api/buscar_ordenes_global.php?q=' + encodeURIComponent(texto))
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if(data.length === 0) {
                        html = '<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron órdenes.</td></tr>';
                    } else {
                        data.forEach(o => {
                            let valorFmt = new Intl.NumberFormat('es-CO').format(o.valor);
                            let linkOp = o.ruta_op_firmada ? `<a href="${o.ruta_op_firmada.replace('../','')}" target="_blank" class="btn btn-sm btn-outline-primary ms-1" title="Ver PDF"><i class="bi bi-file-earmark-pdf"></i></a>` : '';
                            let jsonOrden = JSON.stringify(o).replace(/'/g, "&#39;");

                            html += `
                            <tr>
                                <td class="fw-bold text-primary">${o.numero_op || 'S/N'}</td>
                                <td>${o.fecha_orden}</td>
                                <td>${o.razon_social}</td>
                                <td>${o.concepto_pago.substring(0,40)}...</td>
                                <td class="fw-bold">$ ${valorFmt}</td>
                                <td><span class="badge-estado st-${o.estado_orden}">${o.estado_orden}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-light border me-1" onclick='verDetalle(${jsonOrden})' title="Ver Detalles"><i class="bi bi-eye"></i></button>
                                    
                                    <form action="controllers/exportar_excel.php" method="POST" class="d-inline">
                                        <input type="hidden" name="tipo_reporte" value="individual_orden">
                                        <input type="hidden" name="id_orden" value="${o.id}">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Excel Individual"><i class="bi bi-file-excel"></i></button>
                                    </form>
                                    
                                    ${linkOp}
                                </td>
                            </tr>`;
                        });
                    }
                    document.getElementById('tablaResultados').innerHTML = html;
                });
        }

        // --- EXPORTAR LISTA COMPLETA ---
        function exportarExcel() {
            let texto = document.getElementById('buscadorVivo').value;
            document.getElementById('inputExportarBusqueda').value = texto;
            document.getElementById('formExportar').submit();
        }

        // --- MODAL DETALLE ---
        function verDetalle(o) {
            document.getElementById('detOp').innerText = o.numero_op || 'Sin Número';
            document.getElementById('detEstado').innerHTML = `<span class="badge-estado st-${o.estado_orden}">${o.estado_orden}</span>`;
            document.getElementById('detFecha').innerText = o.fecha_orden;
            document.getElementById('detValor').innerText = '$ ' + new Intl.NumberFormat('es-CO').format(o.valor);
            document.getElementById('detProv').innerText = o.razon_social;
            document.getElementById('detConcepto').innerText = o.concepto_pago;

            if(o.estado_orden === 'Pagado') {
                document.getElementById('seccionPago').classList.remove('d-none');
                document.getElementById('detFechaPago').innerText = o.fecha_pago || 'No registrada';
            } else {
                document.getElementById('seccionPago').classList.add('d-none');
            }

            let docsHtml = '';
            const links = [ {k: 'ruta_requisicion', t: 'Requisición'}, {k: 'ruta_factura', t: 'Factura'}, {k: 'ruta_cuenta_cobro', t: 'Cta. Cobro'}, {k: 'ruta_orden_compra', t: 'Ord. Compra'}, {k: 'ruta_soporte_pago', t: 'Soporte Pago'}, {k: 'ruta_op_firmada', t: 'OP Firmada'} ];
            links.forEach(l => {
                if(o[l.k]) docsHtml += `<a href="${o[l.k].replace('../','')}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark"></i> ${l.t}</a> `;
            });
            document.getElementById('detDocs').innerHTML = docsHtml || '<span class="text-muted">No hay documentos.</span>';
            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        }
    </script>
</body>
</html>