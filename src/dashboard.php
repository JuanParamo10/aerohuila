<?php
// 1. SEGURIDAD Y CONEXIÓN
require_once 'config/security.php'; 
require_once 'config/db.php';

// 2. ESTADÍSTICAS EN TIEMPO REAL
try {
    $totalProv = $pdo->query("SELECT COUNT(*) FROM proveedores")->fetchColumn();
    $cntPendiente = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado_global = 'Pendiente'")->fetchColumn();
    $cntRadicado  = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado_global = 'Radicado'")->fetchColumn();
    $cntPagado    = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado_global = 'Pagado'")->fetchColumn();
    $cntAnulado   = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado_global = 'Anulado'")->fetchColumn();
} catch (PDOException $e) {
    $totalProv = 0; $cntPendiente = 0; $cntRadicado = 0; $cntPagado = 0; $cntAnulado = 0;
}

// 3. CONSULTA INICIAL
try {
    $sql = "SELECT * FROM proveedores ORDER BY id DESC";
    $proveedores = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $proveedores = []; }

// 4. MENSAJES DE SISTEMA
$mensaje = '';
if (isset($_GET['msg'])) {
    $txt = ''; $tipo = 'success'; $icono = 'check-circle-fill';
    
    if ($_GET['msg'] == 'creado') $txt = 'Proveedor registrado exitosamente.';
    if ($_GET['msg'] == 'orden_guardada') $txt = 'Orden procesada y estado actualizado correctamente.';
    if ($_GET['msg'] == 'eliminado') { $txt = 'Elemento eliminado del sistema.'; $tipo = 'danger'; $icono = 'exclamation-triangle-fill'; }
    if ($_GET['msg'] == 'actualizado') { $txt = 'Datos actualizados correctamente.'; $tipo = 'warning'; }
    if ($_GET['msg'] == 'error_id') { $txt = 'Error crítico: No se detectó el ID del proveedor. Intente nuevamente.'; $tipo = 'danger'; $icono = 'x-octagon-fill'; }
    
    if($txt) {
        $mensaje = '<div class="alert alert-'.$tipo.' alert-dismissible fade show m-3 shadow-sm" role="alert">
                        <i class="bi bi-'.$icono.' me-2"></i> '.$txt.'
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aerohuila</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-white tracking-wider">AEROHUILA</h4>
            <small class="text-white-50">Administración</small>
        </div>
        
        <hr class="border-secondary mx-3">
        
        <a href="dashboard.php" class="active"><i class="bi bi-truck me-2"></i> Proveedores</a>
        <a href="ordenes.php"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
                <a href="requisiciones.php" class="active"><i class="bi bi-file-earmark-text me-2"></i> Requisiciones</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes</a>
        
        <?php if($esAdmin): ?>
            <a href="auditoria.php"><i class="bi bi-shield-lock me-2"></i> Auditoría</a>
            <a href="controllers/backup_db.php" class="text-warning" onclick="return confirm('¿Desea descargar una copia completa de la Base de Datos?')">
                <i class="bi bi-database-down me-2"></i> Backup BD
            </a>
        <?php endif; ?>
        
        <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
            <a href="controllers/logout.php" class="text-danger ps-0"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
            <div class="text-white-50 small mt-2">
                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            
            <?php echo $mensaje; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark">Gestión de Proveedores</h2>
                    <p class="text-muted">Control de pagos, facturación y auditoría.</p>
                </div>
                <?php if($esAdmin): ?>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#crearProveedorModal">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Proveedor
                    </button>
                <?php endif; ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col">
                    <div class="card card-stat shadow-sm p-3 text-center" style="background-color: #334155;">
                        <div class="stat-label">Total</div>
                        <div class="stat-num"><?php echo $totalProv; ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-stat shadow-sm p-3 text-center" style="background-color: #ef4444;">
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-num"><?php echo $cntPendiente; ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-stat shadow-sm p-3 text-center" style="background-color: #0ea5e9;">
                        <div class="stat-label">Radicados</div>
                        <div class="stat-num"><?php echo $cntRadicado; ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-stat shadow-sm p-3 text-center" style="background-color: #10b981;">
                        <div class="stat-label">Pagados</div>
                        <div class="stat-num"><?php echo $cntPagado; ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-stat shadow-sm p-3 text-center" style="background-color: #64748b;">
                        <div class="stat-label">Anulados</div>
                        <div class="stat-num"><?php echo $cntAnulado; ?></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="small text-muted">Nombre / Razón Social</label>
                            <input type="text" class="form-control" id="searchNombre" onkeyup="filtrarTabla()" placeholder="Buscar...">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">NIT / Cédula</label>
                            <input type="text" class="form-control" id="searchNit" onkeyup="filtrarTabla()" placeholder="Identificación">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Estado Actual</label>
                            <select class="form-select" id="searchEstado" onchange="filtrarTabla()">
                                <option value="">Todos</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Radicado">Radicado</option>
                                <option value="Pagado">Pagado</option>
                                <option value="Anulado">Anulado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Fecha Creación</label>
                            <input type="date" class="form-control" id="searchFecha" onchange="filtrarTabla()">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-secondary w-100" onclick="limpiarFiltros()">
                                <i class="bi bi-eraser"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaProveedores">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 50px;">ID</th>
                                    <th>Razón Social / Contacto</th>
                                    <th>NIT / Cédula</th>
                                    <th>Fecha Registro</th>
                                    <th>Estado Global</th>
                                    <th class="text-end pe-4" style="min-width: 220px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($proveedores) > 0): ?>
                                    <?php foreach ($proveedores as $row): ?>
                                    <tr class="fila-proveedor">
                                        <td class="ps-4 fw-bold text-secondary"><?php echo $row['id']; ?></td>
                                        
                                        <td>
                                            <div class="fw-bold text-dark nombre-p"><?php echo htmlspecialchars($row['razon_social']); ?></div>
                                            <small class="text-muted razon-p"><?php echo htmlspecialchars($row['nombre']); ?></small>
                                        </td>
                                        
                                        <td class="nit-p"><?php echo htmlspecialchars($row['nit_cedula']); ?></td>
                                        
                                        <td class="fecha-p" data-fecha="<?php echo date('Y-m-d', strtotime($row['fecha_creacion'])); ?>">
                                            <?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?>
                                        </td>
                                        
                                        <td>
                                            <span class="badge-estado st-<?php echo $row['estado_global']; ?>">
                                                <?php echo $row['estado_global']; ?>
                                            </span>
                                            <input type="hidden" class="estado-p" value="<?php echo $row['estado_global']; ?>">
                                        </td>
                                        
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-actions">
                                                
                                                <button class="btn btn-outline-primary" title="Gestión Financiera" onclick="abrirModalPagos(<?php echo htmlspecialchars(json_encode($row)); ?>)" data-bs-toggle="modal" data-bs-target="#gestionPagosModal">
                                                    <i class="bi bi-cash-stack"></i>
                                                </button>
                                                
                                                <form action="controllers/exportar_excel.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="tipo_reporte" value="individual_proveedor">
                                                    <input type="hidden" name="id_proveedor" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Descargar Historial Excel">
                                                        <i class="bi bi-file-excel"></i>
                                                    </button>
                                                </form>
                                                
                                                <a href="<?php echo str_replace('../', '', $row['ruta_rut']); ?>" target="_blank" class="btn btn-outline-secondary" title="Ver RUT">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                <?php if($esAdmin): ?>
                                                    <button class="btn btn-outline-warning text-dark" title="Editar Datos" onclick="cargarDatosEdicion(<?php echo htmlspecialchars(json_encode($row)); ?>)" data-bs-toggle="modal" data-bs-target="#editarProveedorModal">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    
                                                    <a href="controllers/acciones_generales.php?eliminar_proveedor=<?php echo $row['id']; ?>" class="btn btn-outline-danger" title="Eliminar Proveedor" onclick="return confirm('¿ADVERTENCIA CRÍTICA!\n\nSe eliminará todo el historial financiero y los documentos de este proveedor.\n\n¿Desea continuar?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>

                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="noResults"><td colspan="6" class="text-center py-4 text-muted">No hay proveedores registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div id="sinResultadosFiltro" class="text-center py-4 text-muted d-none">No se encontraron coincidencias.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crearProveedorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="controllers/guardar_proveedor.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-dark text-white"><h5 class="modal-title">Registrar Nuevo Proveedor</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Razón Social</label><textarea class="form-control" name="razon_social" required rows="2" placeholder="Nombre legal de la empresa..."></textarea></div>
                            
                            <div class="col-md-6"><label class="form-label">Nombre Contacto</label><input type="text" class="form-control" name="nombre" required></div>
                            <div class="col-md-6"><label class="form-label">NIT / Cédula</label><input type="text" class="form-control" name="nit" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input type="tel" class="form-control" name="telefono"></div>
                            
                            <div class="col-12"><hr></div>
                            <h6 class="text-primary">Información Bancaria</h6>
                            <div class="col-md-4"><label>Banco (Escribir)</label><input type="text" class="form-control" name="banco" required placeholder="Ej: Bancolombia"></div>
                            <div class="col-md-4"><label>Tipo</label><select class="form-select" name="tipo_cuenta"><option>Ahorros</option><option>Corriente</option></select></div>
                            <div class="col-md-4"><label>Número Cuenta</label><input type="text" class="form-control" name="numero_cuenta"></div>
                            
                            <div class="col-12"><hr></div>
                            <h6 class="text-primary">Documentación Inicial</h6>
                            <div class="col-md-6"><label class="small fw-bold">RUT (PDF)</label><input class="form-control" type="file" name="pdf_rut" accept="application/pdf" required></div>
                            <div class="col-md-6"><label class="small fw-bold">Cert. Bancaria (PDF)</label><input class="form-control" type="file" name="pdf_bancaria" accept="application/pdf" required></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Registro</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarProveedorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="controllers/acciones_generales.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="editar_proveedor">
                    <input type="hidden" name="id_proveedor" id="ed_id">
                    
                    <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Editar Datos Proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Razón Social</label><textarea class="form-control" name="razon_social" id="ed_razon" rows="2"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre" id="ed_nombre"></div>
                            <div class="col-md-6"><label class="form-label">NIT (No editable)</label><input type="text" class="form-control" name="nit" id="ed_nit" readonly></div>
                            <div class="col-md-6"><label class="form-label">Banco</label><input type="text" class="form-control" name="banco" id="ed_banco"></div>
                            <div class="col-md-6"><label class="form-label"># Cuenta</label><input type="text" class="form-control" name="numero_cuenta" id="ed_cuenta"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="text" class="form-control" name="email" id="ed_email"></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="telefono" id="ed_tel"></div>
                            
                            <div class="col-12 bg-light p-3 mt-3 rounded border">
                                <strong class="d-block mb-2 text-dark">Actualizar Documentos (Opcional)</strong>
                                <div class="row">
                                    <div class="col-md-6"><label class="small">Nuevo RUT (PDF)</label><input type="file" class="form-control form-control-sm" name="pdf_rut_edit" accept="application/pdf"></div>
                                    <div class="col-md-6"><label class="small">Nueva Cert. Bancaria (PDF)</label><input type="file" class="form-control form-control-sm" name="pdf_bancaria_edit" accept="application/pdf"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-warning">Actualizar Datos</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="gestionPagosModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Gestión Financiera</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="finTitle" class="mb-3 text-muted border-bottom pb-2">...</h6>
                    
                    <ul class="nav nav-tabs mb-3" id="pagoTabs">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabHist"><i class="bi bi-clock-history"></i> Historial</button></li>
                        <?php if($esAdmin): ?>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNueva" id="btnNuevaOrden"><i class="bi bi-plus-circle"></i> Nueva Orden / Editar</button></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabHist">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle table-hover">
                                    <thead class="table-light"><tr><th>N° OP</th><th>Fecha</th><th>Concepto</th><th>Valor</th><th>Estado</th><th style="min-width:220px">Documentos</th><th>Acción</th></tr></thead>
                                    <tbody id="bodyHistorial"></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php if($esAdmin): ?>
                        <div class="tab-pane fade" id="tabNueva">
                            <form action="controllers/guardar_orden.php" method="POST" enctype="multipart/form-data" id="formOrden">
                                <input type="hidden" name="proveedor_id" id="ordenProvId">
                                <input type="hidden" name="id_orden_editar" id="idOrdenEditar">
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="fw-bold text-primary">N° OP Manual</label>
                                        <input type="text" class="form-control" name="numero_op" id="opNum" required placeholder="Ej: OP-2026-001">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Fecha Orden</label>
                                        <input type="date" class="form-control" name="fecha_orden" id="opFecha" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Valor ($)</label>
                                        <input type="text" class="form-control" name="valor" id="opValor" required placeholder="0.000.000,00">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Estado</label>
                                        <select class="form-select" name="estado" id="opEstado" onchange="verificarPago()">
                                            <option>Pendiente</option>
                                            <option>Radicado</option>
                                            <option>Pagado</option>
                                            <option>Anulado</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label>Concepto (Descripción Detallada)</label>
                                        <textarea class="form-control" name="concepto" id="opConcepto" rows="5" placeholder="Escribe aquí el detalle completo..."></textarea>
                                    </div>

                                    <div class="col-12 bg-success-subtle p-3 rounded border border-success d-none" id="divPago">
                                        <h6 class="text-success fw-bold mb-3"><i class="bi bi-check-circle-fill"></i> Detalles del Pago Realizado</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4"><label>Fecha Real de Pago</label><input type="date" class="form-control" name="fecha_pago" id="opFechaPago"></div>
                                            <div class="col-md-4"><label>Soporte de Pago (PDF)</label><input type="file" class="form-control" name="pdf_soporte_pago" accept="application/pdf"></div>
                                            <div class="col-md-4"><label>OP Firmada (PDF)</label><input type="file" class="form-control" name="pdf_op_firmada" accept="application/pdf"></div>
                                        </div>
                                    </div>

                                    <div class="col-12"><hr><h6 class="text-primary">Soportes Documentales (Facturación)</h6></div>
                                    <div class="col-md-3"><label class="small">Factura o Cotización</label><input type="file" class="form-control form-control-sm" name="pdf_factura" accept="application/pdf"></div>
                                    <div class="col-md-3"><label class="small">Requisición</label><input type="file" class="form-control form-control-sm" name="pdf_requisicion" accept="application/pdf"></div>
                                    <div class="col-md-3"><label class="small">Cta. Cobro</label><input type="file" class="form-control form-control-sm" name="pdf_cuenta" accept="application/pdf"></div>
                                    <div class="col-md-3"><label class="small">Ord. Compra</label><input type="file" class="form-control form-control-sm" name="pdf_compra" accept="application/pdf"></div>

                                    <div class="col-12 text-end mt-4">
                                        <button type="button" class="btn btn-secondary me-2" onclick="limpiarFormularioOrden()">Limpiar Formulario</button>
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Orden</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ES_ADMIN = <?php echo $esAdmin ? 'true' : 'false'; ?>;
        
        // --- 1. FORMATO DE MONEDA ---
        const inputValor = document.getElementById('opValor');
        if(inputValor) {
            inputValor.addEventListener('input', function(e) {
                let val = this.value.replace(/[^0-9,]/g, '');
                let parts = val.split(',');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                if (parts.length > 1) { this.value = parts[0] + ',' + parts[1].substring(0, 2); } else { this.value = parts[0]; }
            });
        }

        // --- 2. FILTROS ---
        function filtrarTabla() {
            let filtroTexto = document.getElementById('searchNombre').value.toLowerCase();
            let filtroNit = document.getElementById('searchNit').value.toLowerCase();
            let filtroEstado = document.getElementById('searchEstado').value;
            let filtroFecha = document.getElementById('searchFecha').value;
            
            let filas = document.querySelectorAll('.fila-proveedor');
            let hayCoincidencias = false;

            filas.forEach(fila => {
                let nombre = fila.querySelector('.nombre-p').textContent.toLowerCase();
                let razon = fila.querySelector('.razon-p').textContent.toLowerCase();
                let nit = fila.querySelector('.nit-p').textContent.toLowerCase();
                let estado = fila.querySelector('.estado-p').value;
                let fecha = fila.querySelector('.fecha-p').getAttribute('data-fecha');

                let matchTexto = nombre.includes(filtroTexto) || razon.includes(filtroTexto);
                let matchNit = nit.includes(filtroNit);
                let matchEstado = filtroEstado === "" || estado === filtroEstado;
                let matchFecha = filtroFecha === "" || fecha === filtroFecha;

                if (matchTexto && matchNit && matchEstado && matchFecha) { 
                    fila.style.display = ''; 
                    hayCoincidencias = true; 
                } else { 
                    fila.style.display = 'none'; 
                }
            });
            
            let msg = document.getElementById('sinResultadosFiltro');
            if (!hayCoincidencias) msg.classList.remove('d-none'); else msg.classList.add('d-none');
        }

        function limpiarFiltros() { 
            document.getElementById('searchNombre').value = ''; 
            document.getElementById('searchNit').value = ''; 
            document.getElementById('searchEstado').value = ''; 
            document.getElementById('searchFecha').value = ''; 
            filtrarTabla(); 
        }
        
        // --- 3. LOGICA MODALES ---
        function verificarPago() { 
            let estado = document.getElementById('opEstado').value; 
            let div = document.getElementById('divPago'); 
            if(estado === 'Pagado') div.classList.remove('d-none'); else div.classList.add('d-none'); 
        }
        
        function abrirModalPagos(prov) {
            document.getElementById('finTitle').innerText = prov.razon_social + ' - NIT: ' + prov.nit_cedula;
            
            // Asignar ID
            if(document.getElementById('ordenProvId')) {
                document.getElementById('ordenProvId').value = prov.id;
            }
            
            limpiarFormularioOrden(); 

            fetch('api/obtener_ordenes.php?id_proveedor='+prov.id).then(r=>r.json()).then(data=>{
                let html = '';
                data.forEach(o => {
                    let docs = '';
                    if(o.ruta_requisicion) docs += `<a href="${o.ruta_requisicion.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark"></i> Req</a>`;
                    if(o.ruta_factura) docs += `<a href="${o.ruta_factura.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark-text"></i> Fac/Cot</a>`;
                    if(o.ruta_cuenta_cobro) docs += `<a href="${o.ruta_cuenta_cobro.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark"></i> Cta</a>`;
                    if(o.ruta_orden_compra) docs += `<a href="${o.ruta_orden_compra.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-cart"></i> OC</a>`;
                    
                    if(o.ruta_soporte_pago) docs += `<a href="${o.ruta_soporte_pago.replace('../','')}" target="_blank" class="btn-pdf-link text-success fw-bold"><i class="bi bi-cash"></i> SOPORTE PAGO</a>`;
                    if(o.ruta_op_firmada) docs += `<a href="${o.ruta_op_firmada.replace('../','')}" target="_blank" class="btn-pdf-link text-primary fw-bold"><i class="bi bi-pen"></i> OP FIRMADA</a>`;
                    
                    if(docs === '') docs = '<span class="text-muted small">-</span>';

                    let valorFmt = new Intl.NumberFormat('es-CO').format(o.valor);

                    html += `<tr>
                        <td class="fw-bold text-primary">${o.numero_op || '-'}</td>
                        <td>${o.fecha_orden}</td>
                        <td><small>${o.concepto_pago.substring(0,30)}...</small></td>
                        <td class="fw-bold">$${valorFmt}</td>
                        <td><span class="badge-estado st-${o.estado_orden}">${o.estado_orden}</span></td>
                        <td>${docs}</td>
                        <td>${ES_ADMIN ? `<button class="btn btn-sm btn-outline-primary" onclick='editarOrden(${JSON.stringify(o)})'><i class="bi bi-pencil"></i></button>` : ''}</td>
                    </tr>`;
                });
                document.getElementById('bodyHistorial').innerHTML = html || '<tr><td colspan="7" class="text-center text-muted py-3">No hay órdenes registradas.</td></tr>';
            });
        }

        // --- 4. LIMPIEZA INTELIGENTE (EL CORAZÓN DEL ARREGLO) ---
        function limpiarFormularioOrden() { 
            // Guardamos el ID del proveedor actual para que no se pierda
            let idProv = document.getElementById('ordenProvId').value;
            
            // Reseteamos el formulario
            document.getElementById('formOrden').reset(); 
            
            // Volvemos a poner el ID inmediatamente
            document.getElementById('ordenProvId').value = idProv;

            document.getElementById('idOrdenEditar').value = ""; 
            document.getElementById('opFecha').valueAsDate = new Date(); 
            verificarPago(); 
        }
        
        // --- 5. EDITAR ORDEN CORREGIDO ---
        function editarOrden(o) {
            if(!ES_ADMIN) return;
            
            // Cambiar pestaña
            let triggerEl = document.querySelector('#pagoTabs button[data-bs-target="#tabNueva"]') || document.querySelector('#finTabs button[data-bs-target="#tabNueva"]');
            if(triggerEl) {
                let tab = bootstrap.Tab.getInstance(triggerEl) || new bootstrap.Tab(triggerEl);
                tab.show();
            }
            
            // LLENAR DATOS
            document.getElementById('ordenProvId').value = o.proveedor_id; // <-- AQUÍ SE CORRIGE EL ERROR DEL ID
            document.getElementById('idOrdenEditar').value = o.id;
            document.getElementById('opNum').value = o.numero_op;
            document.getElementById('opFecha').value = o.fecha_orden;
            document.getElementById('opConcepto').value = o.concepto_pago;
            
            // Formatear valor
            let valorNum = parseFloat(o.valor).toFixed(2);
            let partes = valorNum.split('.');
            partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            document.getElementById('opValor').value = partes[0] + ',' + partes[1]; 

            document.getElementById('opEstado').value = o.estado_orden;
            
            // Manejar lógica Pagado
            if(o.estado_orden === 'Pagado') {
                if(o.fecha_pago) document.getElementById('opFechaPago').value = o.fecha_pago;
            }
            verificarPago();
        }

        function cargarDatosEdicion(p) { 
            document.getElementById('ed_id').value = p.id; 
            document.getElementById('ed_razon').value = p.razon_social; 
            document.getElementById('ed_nombre').value = p.nombre; 
            document.getElementById('ed_nit').value = p.nit_cedula; 
            document.getElementById('ed_banco').value = p.banco; 
            document.getElementById('ed_cuenta').value = p.numero_cuenta; 
            document.getElementById('ed_email').value = p.email; 
            document.getElementById('ed_tel').value = p.telefono; 
        }
    </script>
</body>
</html>