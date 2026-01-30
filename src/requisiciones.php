<?php
require_once 'config/security.php';
require_once 'config/db.php';

// --- 1. VERIFICACIÓN DE ROL FLEXIBLE ---
// Obtenemos el rol de la sesión (asegurando que no sea nulo)
$rolUsuario = $_SESSION['user_rol'] ?? '';

// Verificamos si la palabra "admin" aparece en el rol (sin importar mayúsculas)
// Esto aceptará: "admin", "Admin", "Administrador", "SUPERADMIN", etc.
$esAdmin = (stripos($rolUsuario, 'admin') !== false);

// Carga inicial de datos
$reqs = $pdo->query("SELECT * FROM requisiciones ORDER BY fecha_solicitud DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisiciones - Aerohuila</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4"><h4 class="fw-bold text-white tracking-wider">AEROHUILA</h4></div>
        <hr class="border-secondary mx-3">
        <a href="dashboard.php"><i class="bi bi-truck me-2"></i> Proveedores</a>
        <a href="ordenes.php"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
        <a href="requisiciones.php" class="active"><i class="bi bi-file-earmark-text me-2"></i> Requisiciones</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes</a>
        <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
            <a href="controllers/logout.php" class="text-danger ps-0"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
            <div class="text-white-50 small mt-2">
                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                <br><span style="font-size: 0.7em; color: #0ea5e9;">Rol: <?php echo htmlspecialchars($rolUsuario); ?></span>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> Operación exitosa.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark">Gestión de Requisiciones</h2>
                <button class="btn btn-primary shadow-sm" onclick="abrirModalCrear()">
                    <i class="bi bi-plus-circle"></i> Nueva Requisición
                </button>
            </div>

            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="buscadorVivo" 
                               placeholder="Buscar por Número de Requisición o Solicitante..." 
                               onkeyup="buscarEnVivo()">
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° Requisición</th>
                                <th>Fecha (Auto)</th>
                                <th>Solicitante</th>
                                <th>Archivo</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaResultados">
                            <?php foreach($reqs as $r): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo $r['numero_requisicion']; ?></td>
                                <td><?php echo $r['fecha_solicitud']; ?></td>
                                <td><?php echo $r['solicitante']; ?></td>
                                <td>
                                    <?php if($r['ruta_archivo']): ?>
                                        <a href="<?php echo str_replace('../','',$r['ruta_archivo']); ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Descargar">
                                            <i class="bi bi-file-earmark-arrow-down"></i> PDF/Excel
                                        </a>
                                    <?php else: echo '<span class="text-muted small">-</span>'; endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $bg = 'secondary';
                                        $txt = 'text-white';
                                        if($r['estado'] == 'Radicado') { $bg = 'primary'; }
                                        elseif($r['estado'] == 'Rechazado') { $bg = 'danger'; }
                                        elseif($r['estado'] == 'Pendiente') { $bg = 'warning'; $txt = 'text-dark'; }
                                    ?>
                                    <span class="badge bg-<?php echo $bg; ?> <?php echo $txt; ?>"><?php echo $r['estado']; ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-light border" onclick='editarRequisicion(<?php echo json_encode($r); ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReq" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalTitle">Gestión Requisición</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="controllers/guardar_requisicion.php" method="POST" enctype="multipart/form-data" id="formReq">
                        <input type="hidden" name="id_requisicion" id="reqId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Número Requisición</label>
                            <input type="text" class="form-control" name="numero_requisicion" id="reqNumero" required placeholder="Ej: REQ-001">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha (Automática)</label>
                            <input type="text" class="form-control bg-light" value="<?php echo date('Y-m-d'); ?>" readonly>
                            <small class="text-muted">La fecha se asigna automáticamente al crear.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Solicitante (Persona/Empresa)</label>
                            <input type="text" class="form-control" name="solicitante" id="reqSolicitante" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Archivo (PDF / Excel)</label>
                            <input type="file" class="form-control" name="archivo" accept=".pdf, .xls, .xlsx">
                            <small class="text-muted d-block mt-1">Si sube uno nuevo, reemplazará al anterior.</small>
                        </div>

                        <?php if($esAdmin): ?>
                        <div class="mb-3 p-3 bg-light border rounded">
                            <label class="form-label text-primary fw-bold">Estado (Opciones de Administrador)</label>
                            <select class="form-select" name="estado" id="reqEstado">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Radicado">Radicado</option>
                                <option value="Rechazado">Rechazado</option>
                            </select>
                        </div>
                        <?php else: ?>
                            <?php endif; ?>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pasar la variable PHP a JS
        const ES_ADMIN = <?php echo $esAdmin ? 'true' : 'false'; ?>;

        // --- BUSCADOR EN VIVO ---
        function buscarEnVivo() {
            let texto = document.getElementById('buscadorVivo').value;
            fetch('api/buscar_requisiciones.php?q=' + encodeURIComponent(texto))
                .then(r => r.json())
                .then(data => {
                    let html = '';
                    if(data.length === 0) {
                        html = '<tr><td colspan="6" class="text-center py-3 text-muted">No se encontraron resultados.</td></tr>';
                    } else {
                        data.forEach(r => {
                            let link = r.ruta_archivo ? `<a href="${r.ruta_archivo.replace('../','')}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-arrow-down"></i> Descargar</a>` : '-';
                            
                            let bg = 'secondary';
                            let txt = 'text-white';
                            if(r.estado == 'Radicado') { bg = 'primary'; }
                            else if(r.estado == 'Rechazado') { bg = 'danger'; }
                            else if(r.estado == 'Pendiente') { bg = 'warning'; txt = 'text-dark'; }

                            let jsonR = JSON.stringify(r).replace(/'/g, "&#39;");

                            html += `<tr>
                                <td class="fw-bold text-primary">${r.numero_requisicion}</td>
                                <td>${r.fecha_solicitud}</td>
                                <td>${r.solicitante}</td>
                                <td>${link}</td>
                                <td><span class="badge bg-${bg} ${txt}">${r.estado}</span></td>
                                <td><button class="btn btn-sm btn-light border" onclick='editarRequisicion(${jsonR})'><i class="bi bi-pencil"></i></button></td>
                            </tr>`;
                        });
                    }
                    document.getElementById('tablaResultados').innerHTML = html;
                });
        }

        // --- CREAR ---
        function abrirModalCrear() {
            document.getElementById('formReq').reset();
            document.getElementById('reqId').value = ""; 
            document.getElementById('modalTitle').innerText = "Nueva Requisición";
            
            // Si es admin, poner visualmente en Pendiente
            if(ES_ADMIN) {
                let el = document.getElementById('reqEstado');
                if(el) el.value = "Pendiente";
            }
            
            new bootstrap.Modal(document.getElementById('modalReq')).show();
        }

        // --- EDITAR ---
        function editarRequisicion(r) {
            document.getElementById('reqId').value = r.id;
            document.getElementById('reqNumero').value = r.numero_requisicion;
            document.getElementById('reqSolicitante').value = r.solicitante;
            
            // LÓGICA CRÍTICA PARA EL SELECT DE ESTADO
            // Si el usuario es admin y el select existe, le ponemos el valor real de la BD
            if(ES_ADMIN) {
                let el = document.getElementById('reqEstado');
                if(el) {
                    el.value = r.estado; // Asigna el estado actual (Pendiente/Radicado/Rechazado)
                }
            }

            document.getElementById('modalTitle').innerText = "Editar Requisición";
            new bootstrap.Modal(document.getElementById('modalReq')).show();
        }
    </script>
</body>
</html>