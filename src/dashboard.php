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

// 3. LÓGICA DE BÚSQUEDA
$where = "WHERE 1=1";
$params = [];

if (!empty($_GET['q'])) {
    $where .= " AND (nombre LIKE ? OR razon_social LIKE ?)";
    $params[] = "%" . $_GET['q'] . "%";
    $params[] = "%" . $_GET['q'] . "%";
}
if (!empty($_GET['nit'])) {
    $where .= " AND nit_cedula LIKE ?";
    $params[] = "%" . $_GET['nit'] . "%";
}
if (!empty($_GET['estado'])) {
    $where .= " AND estado_global = ?";
    $params[] = $_GET['estado'];
}
if (!empty($_GET['fecha'])) {
    $where .= " AND DATE(fecha_creacion) = ?";
    $params[] = $_GET['fecha'];
}

try {
    $sql = "SELECT * FROM proveedores $where ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $proveedores = []; }

// 4. MENSAJES
$mensaje = '';
if (isset($_GET['msg'])) {
    $txt = ''; $tipo = 'success'; $icono = 'check-circle-fill';
    if ($_GET['msg'] == 'creado') $txt = 'Proveedor registrado exitosamente.';
    if ($_GET['msg'] == 'orden_guardada') $txt = 'Orden procesada y estado global actualizado.';
    if ($_GET['msg'] == 'eliminado') { $txt = 'Elemento eliminado.'; $tipo = 'danger'; $icono = 'exclamation-triangle-fill'; }
    if ($_GET['msg'] == 'actualizado') { $txt = 'Datos actualizados.'; $tipo = 'warning'; }
    if($txt) $mensaje = '<div class="alert alert-'.$tipo.' alert-dismissible fade show m-3 shadow-sm" role="alert"><i class="bi bi-'.$icono.' me-2"></i> '.$txt.'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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
    <style>
        /* Estilos de Estado (Badge Fijo) */
        .badge-estado { font-size: 0.85rem; padding: 0.4rem 1rem; border-radius: 50px; font-weight: 600; border: 1px solid transparent; display: inline-block; width: 100px; text-align: center;}
        .st-Pendiente { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .st-Radicado { background: #e0f2fe; color: #075985; border-color: #7dd3fc; }
        .st-Pagado { background: #dcfce7; color: #166534; border-color: #86efac; }
        .st-Anulado { background: #f3f4f6; color: #4b5563; border-color: #d1d5db; }
        
        .btn-group-actions .btn { padding: 0.25rem 0.5rem; }
        .btn-pdf-link { text-decoration: none; font-size: 0.8rem; display: block; margin-bottom: 2px; color: #dc3545; }
        .btn-pdf-link:hover { text-decoration: underline; color: #a71d2a; }
        
        .card-stat { transition: transform 0.2s; color: white; border:none; }
        .card-stat:hover { transform: translateY(-3px); }
        .stat-num { font-size: 2rem; font-weight: bold; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
    </style>
</head>
<body>

    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4"><h4 class="fw-bold tracking-wider">AEROHUILA</h4><small class="text-light opacity-50">Administración</small></div>
        <hr class="border-secondary mx-3">
        <a href="dashboard.php" class="active"><i class="bi bi-truck me-2"></i> Proveedores</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes / Exportar</a>
        
        <?php if($esAdmin): ?>
            <a href="auditoria.php"><i class="bi bi-shield-lock me-2"></i> Auditoría</a>
        <?php endif; ?>

        <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
            <a href="controllers/logout.php" class="text-danger ps-0"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
            <div class="text-light opacity-50 small mt-2">
                <i class="bi bi-person-circle me-1"></i> Hola, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <?php echo $mensaje; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h2 class="fw-bold text-dark">Gestión de Proveedores</h2><p class="text-muted">Control de pagos, facturación y auditoría.</p></div>
                <?php if($esAdmin): ?>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#crearProveedorModal"><i class="bi bi-plus-circle me-1"></i> Nuevo Proveedor</button>
                <?php endif; ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col"><div class="card card-stat shadow-sm" style="background-color: #334155;"><div class="card-body p-3"><div class="stat-label"><i class="bi bi-people-fill me-1"></i> Total</div><div class="stat-num"><?php echo $totalProv; ?></div></div></div></div>
                <div class="col"><div class="card card-stat shadow-sm" style="background-color: #ef4444;"><div class="card-body p-3"><div class="stat-label"><i class="bi bi-exclamation-circle-fill me-1"></i> Pendientes</div><div class="stat-num"><?php echo $cntPendiente; ?></div></div></div></div>
                <div class="col"><div class="card card-stat shadow-sm" style="background-color: #0ea5e9;"><div class="card-body p-3"><div class="stat-label"><i class="bi bi-folder-fill me-1"></i> Radicados</div><div class="stat-num"><?php echo $cntRadicado; ?></div></div></div></div>
                <div class="col"><div class="card card-stat shadow-sm" style="background-color: #10b981;"><div class="card-body p-3"><div class="stat-label"><i class="bi bi-check-circle-fill me-1"></i> Pagados</div><div class="stat-num"><?php echo $cntPagado; ?></div></div></div></div>
                <div class="col"><div class="card card-stat shadow-sm" style="background-color: #64748b;"><div class="card-body p-3"><div class="stat-label"><i class="bi bi-x-circle-fill me-1"></i> Anulados</div><div class="stat-num"><?php echo $cntAnulado; ?></div></div></div></div>
            </div>

            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="small text-muted">Nombre / Razón Social</label><input type="text" class="form-control" id="searchNombre" onkeyup="filtrarTabla()" placeholder="Escribe para buscar..."></div>
                        <div class="col-md-2"><label class="small text-muted">NIT / Cédula</label><input type="text" class="form-control" id="searchNit" onkeyup="filtrarTabla()" placeholder="Identificación"></div>
                        <div class="col-md-3"><label class="small text-muted">Estado Actual</label>
                            <select class="form-select" id="searchEstado" onchange="filtrarTabla()">
                                <option value="">Todos</option><option value="Pendiente">Pendiente</option><option value="Radicado">Radicado</option><option value="Pagado">Pagado</option><option value="Anulado">Anulado</option>
                            </select>
                        </div>
                        <div class="col-md-2"><label class="small text-muted">Fecha Creación</label><input type="date" class="form-control" id="searchFecha" onchange="filtrarTabla()"></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()"><i class="bi bi-eraser"></i> Limpiar</button></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaProveedores">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Proveedor / Razón Social</th>
                                    <th>NIT / Cédula</th>
                                    <th>Fecha Registro</th>
                                    <th>Estado Global</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($proveedores) > 0): ?>
                                    <?php foreach ($proveedores as $row): ?>
                                    <tr class="fila-proveedor">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark nombre-p"><?php echo htmlspecialchars($row['nombre']); ?></div>
                                            <small class="text-muted razon-p"><?php echo htmlspecialchars(substr($row['razon_social'], 0, 45)); ?></small>
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
                                                <button class="btn btn-primary" onclick="abrirModalPagos(<?php echo htmlspecialchars(json_encode($row)); ?>)" data-bs-toggle="modal" data-bs-target="#gestionPagosModal" title="Pagos"><i class="bi bi-cash-stack"></i></button>
                                                
                                                <form action="controllers/exportar_excel.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="tipo_reporte" value="individual_proveedor">
                                                    <input type="hidden" name="id_proveedor" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Descargar Historial"><i class="bi bi-file-excel"></i></button>
                                                </form>
                                                
                                                <a href="<?php echo str_replace('../', '', $row['ruta_rut']); ?>" target="_blank" class="btn btn-outline-secondary" title="Ver RUT"><i class="bi bi-eye"></i></a>
                                                
                                                <?php if($esAdmin): ?>
                                                    <button class="btn btn-outline-warning text-dark" onclick="cargarDatosEdicion(<?php echo htmlspecialchars(json_encode($row)); ?>)" data-bs-toggle="modal" data-bs-target="#editarProveedorModal"><i class="bi bi-pencil"></i></button>
                                                    <a href="controllers/acciones_generales.php?eliminar_proveedor=<?php echo $row['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('¿Eliminar proveedor?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="noResults"><td colspan="5" class="text-center py-4 text-muted">No hay proveedores registrados.</td></tr>
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
                            <div class="col-md-6"><label class="form-label">Nombre <span class="text-danger">*</span></label><input type="text" class="form-control" name="nombre" required></div>
                            <div class="col-md-6"><label class="form-label">NIT / Cédula <span class="text-danger">*</span></label><input type="text" class="form-control" name="nit" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input type="tel" class="form-control" name="telefono"></div>
                            <div class="col-12"><label class="form-label">Razón Social</label><textarea class="form-control" rows="2" name="razon_social" required></textarea></div>
                            <div class="col-12"><hr></div>
                            <h6 class="text-primary">Información Bancaria</h6>
                            <div class="col-md-4"><label>Banco</label><select class="form-select" name="banco" required><option>Bancolombia</option><option>Davivienda</option><option>BBVA</option><option>Otro</option></select></div>
                            <div class="col-md-4"><label>Tipo</label><select class="form-select" name="tipo_cuenta" required><option>Ahorros</option><option>Corriente</option></select></div>
                            <div class="col-md-4"><label>Número Cuenta</label><input type="text" class="form-control" name="numero_cuenta"></div>
                            <div class="col-12"><hr></div>
                            <h6 class="text-primary">Documentación Inicial (PDF)</h6>
                            <div class="col-md-4"><label class="small fw-bold">1. RUT</label><input class="form-control" type="file" accept="application/pdf" name="pdf_rut" required></div>
                            <div class="col-md-4"><label class="small fw-bold">2. Cert. Bancaria</label><input class="form-control" type="file" accept="application/pdf" name="pdf_bancaria" required></div>
                            <div class="col-md-4"><label class="small fw-bold">3. Req. Inicial</label><input class="form-control" type="file" accept="application/pdf" name="pdf_requisicion" required></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarProveedorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="controllers/acciones_generales.php" method="POST">
                    <input type="hidden" name="accion" value="editar_proveedor">
                    <input type="hidden" name="id_proveedor" id="edit_id">
                    <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Editar Datos</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre" id="edit_nombre" required></div>
                            <div class="col-md-6"><label class="form-label">NIT / Cédula</label><input type="text" class="form-control" name="nit" id="edit_nit" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="edit_email"></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="telefono" id="edit_telefono"></div>
                            <div class="col-12"><label class="form-label">Razón Social</label><textarea class="form-control" rows="2" name="razon_social" id="edit_razon"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-warning">Actualizar</button></div>
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
                    <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                        <div><strong>Proveedor:</strong> <span id="finProvName">...</span> &nbsp;|&nbsp; <strong>NIT:</strong> <span id="finProvNit">...</span></div>
                    </div>

                    <ul class="nav nav-tabs mb-3" id="pagoTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" id="tab-historial" data-bs-toggle="tab" data-bs-target="#historial" type="button"><i class="bi bi-clock-history"></i> Historial</button></li>
                        <?php if($esAdmin): ?>
                            <li class="nav-item"><button class="nav-link" id="tab-nueva" data-bs-toggle="tab" data-bs-target="#nueva" type="button"><i class="bi bi-plus-circle"></i> Nueva / Editar Orden</button></li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="historial">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light"><tr><th>Fecha</th><th>Concepto</th><th>Valor</th><th>Estado</th><th style="min-width: 200px;">Documentos (Descargar)</th><?php if($esAdmin): ?><th>Acciones</th><?php endif; ?></tr></thead>
                                    <tbody id="tablaHistorialBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <?php if($esAdmin): ?>
                        <div class="tab-pane fade" id="nueva">
                            <form id="formOrden" action="controllers/guardar_orden.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="proveedor_id" id="ordenProvId">
                                <input type="hidden" name="id_orden_editar" id="idOrdenEditar">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" class="form-control" name="fecha_orden" id="ordFecha" required></div>
                                    <div class="col-md-6"><label class="form-label">Concepto</label><input type="text" class="form-control" name="concepto" id="ordConcepto" required></div>
                                    <div class="col-md-3"><label class="form-label">Valor</label><input type="number" class="form-control" name="valor" id="ordValor" required></div>
                                    <div class="col-md-4"><label class="form-label">Estado</label>
                                        <select class="form-select" name="estado" id="ordEstado">
                                            <option value="Pendiente">Pendiente</option><option value="Radicado">Radicado</option><option value="Pagado">Pagado</option><option value="Anulado">Anulado</option>
                                        </select>
                                    </div>
                                    <div class="col-12"><hr><h6 class="text-primary">Carga de Documentos (Solo PDF)</h6></div>
                                    <div class="col-md-4"><label class="small fw-bold">1. Requisición</label><input type="file" class="form-control form-control-sm" name="pdf_requisicion" accept="application/pdf"></div>
                                    <div class="col-md-4"><label class="small fw-bold">2. Factura</label><input type="file" class="form-control form-control-sm" name="pdf_factura" accept="application/pdf"></div>
                                    <div class="col-md-4"><label class="small fw-bold">3. Cta. Cobro</label><input type="file" class="form-control form-control-sm" name="pdf_cuenta" accept="application/pdf"></div>
                                    <div class="col-md-4"><label class="small fw-bold">4. Ord. Pago</label><input type="file" class="form-control form-control-sm" name="pdf_pago" accept="application/pdf"></div>
                                    <div class="col-md-4"><label class="small fw-bold">5. Ord. Compra</label><input type="file" class="form-control form-control-sm" name="pdf_compra" accept="application/pdf"></div>
                                    <div class="col-12 mt-4 text-end">
                                        <button type="button" class="btn btn-secondary me-2" onclick="limpiarFormularioOrden()">Limpiar</button>
                                        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Orden</button>
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
                // Ahora leemos el valor del input oculto, no del select
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

            let msgSinResultados = document.getElementById('sinResultadosFiltro');
            if (!hayCoincidencias) {
                msgSinResultados.classList.remove('d-none');
            } else {
                msgSinResultados.classList.add('d-none');
            }
        }

        function limpiarFiltros() {
            document.getElementById('searchNombre').value = '';
            document.getElementById('searchNit').value = '';
            document.getElementById('searchEstado').value = '';
            document.getElementById('searchFecha').value = '';
            filtrarTabla();
        }

        function cargarDatosEdicion(prov) {
            document.getElementById('edit_id').value = prov.id;
            document.getElementById('edit_nombre').value = prov.nombre;
            document.getElementById('edit_nit').value = prov.nit_cedula;
            document.getElementById('edit_email').value = prov.email;
            document.getElementById('edit_telefono').value = prov.telefono;
            document.getElementById('edit_razon').value = prov.razon_social;
        }
        
        function abrirModalPagos(prov) {
            document.getElementById('finProvName').innerText = prov.nombre;
            document.getElementById('finProvNit').innerText = prov.nit_cedula;
            if(ES_ADMIN) { document.getElementById('ordenProvId').value = prov.id; limpiarFormularioOrden(); }
            cargarHistorial(prov.id);
        }
        
        function limpiarFormularioOrden() {
            if(!ES_ADMIN) return;
            document.getElementById('formOrden').reset();
            document.getElementById('idOrdenEditar').value = "";
            document.getElementById('ordFecha').valueAsDate = new Date();
        }
        
        function cargarHistorial(idProv) {
            const tbody = document.getElementById('tablaHistorialBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Cargando datos...</td></tr>';
            fetch('api/obtener_ordenes.php?id_proveedor=' + idProv)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    if (data.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay historial.</td></tr>'; return; }
                    data.forEach(orden => {
                        let acciones = '';
                        if(ES_ADMIN) {
                            acciones = `<td><button class="btn btn-sm btn-outline-primary" onclick='editarOrden(${JSON.stringify(orden)})'><i class="bi bi-pencil"></i></button> <a href="controllers/acciones_generales.php?eliminar_orden=${orden.id}" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')"><i class="bi bi-trash"></i></a></td>`;
                        }

                        let docs = '';
                        if(orden.ruta_requisicion) docs += `<a href="${orden.ruta_requisicion.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark-pdf-fill"></i> Requisición</a>`;
                        if(orden.ruta_factura) docs += `<a href="${orden.ruta_factura.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark-pdf-fill"></i> Factura</a>`;
                        if(orden.ruta_cuenta_cobro) docs += `<a href="${orden.ruta_cuenta_cobro.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark-pdf-fill"></i> Cuenta Cobro</a>`;
                        if(orden.ruta_orden_pago) docs += `<a href="${orden.ruta_orden_pago.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark-pdf-fill"></i> Orden Pago</a>`;
                        if(orden.ruta_orden_compra) docs += `<a href="${orden.ruta_orden_compra.replace('../','')}" target="_blank" class="btn-pdf-link"><i class="bi bi-file-earmark-pdf-fill"></i> Orden Compra</a>`;
                        if(docs === '') docs = '<span class="text-muted small">Sin archivos</span>';

                        let html = `<tr><td>${orden.fecha_orden}</td><td>${orden.concepto_pago}</td><td>$ ${orden.valor}</td><td><span class="badge bg-secondary">${orden.estado_orden}</span></td>
                                    <td>${docs}</td>${acciones}</tr>`;
                        tbody.innerHTML += html;
                    });
                })
                .catch(err => tbody.innerHTML = '<tr><td colspan="6" class="text-danger">Error conexión.</td></tr>');
        }
        
        function editarOrden(orden) {
            if(!ES_ADMIN) return;
            new bootstrap.Tab(document.querySelector('#pagoTabs button[data-bs-target="#nueva"]')).show();
            document.getElementById('idOrdenEditar').value = orden.id;
            document.getElementById('ordFecha').value = orden.fecha_orden;
            document.getElementById('ordConcepto').value = orden.concepto_pago;
            document.getElementById('ordValor').value = orden.valor;
            document.getElementById('ordEstado').value = orden.estado_orden;
        }
    </script>
</body>
</html>