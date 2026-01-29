<?php
require_once 'config/security.php';
require_once 'config/db.php';

// Consultar lista de proveedores para el select
try {
    $provs = $pdo->query("SELECT id, nombre, nit_cedula FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $provs = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Aerohuila</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4"><h4 class="fw-bold tracking-wider">AEROHUILA</h4><small class="text-light opacity-50">Administración</small></div>
        <hr class="border-secondary mx-3">
        <a href="dashboard.php"><i class="bi bi-truck me-2"></i> Proveedores</a>
             <a href="ordenes.php"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php" class="active"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes / Exportar</a>
        <?php if($esAdmin): ?>
    <a href="auditoria.php"><i class="bi bi-shield-lock me-2"></i> Auditoría</a>
<?php endif; ?>
<?php if($esAdmin): ?>

    
    <a href="controllers/backup_db.php" class="text-warning" onclick="return confirm('¿Descargar copia completa de la Base de Datos?');">
        <i class="bi bi-database-down me-2"></i> Backup BD
    </a>
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
            <h2 class="fw-bold text-dark mb-4">Centro de Reportes</h2>
            
            <div class="row g-4">
                
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-globe me-2"></i> Reporte Global</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Listado completo de proveedores y estado actual.</p>
                            <form action="controllers/exportar_excel.php" method="POST">
                                <input type="hidden" name="tipo_reporte" value="global_proveedores">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-excel me-2"></i> Descargar Excel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-calendar-range me-2"></i> Reporte Financiero</h5>
                        </div>
                        <div class="card-body">
                            <form action="controllers/exportar_excel.php" method="POST">
                                <input type="hidden" name="tipo_reporte" value="financiero_fechas">
                                <div class="row g-3">
                                    <div class="col-6"><label class="small">Desde</label><input type="date" class="form-control" name="fecha_inicio" required></div>
                                    <div class="col-6"><label class="small">Hasta</label><input type="date" class="form-control" name="fecha_fin" required></div>
                                    <div class="col-12"><label class="small">Estado</label>
                                        <select class="form-select" name="estado_filtro">
                                            <option value="Todos">Todos</option><option>Pendiente</option><option>Radicado</option><option>Pagado</option><option>Anulado</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-grid mt-3">
                                        <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-excel me-2"></i> Generar Reporte</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i> Historial Individual</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Exporta el historial de un proveedor.</p>
                            <form action="controllers/exportar_excel.php" method="POST">
                                <input type="hidden" name="tipo_reporte" value="individual_proveedor">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Buscar Proveedor (Nombre o NIT)</label>
                                    <select class="form-select" id="selectProveedorLupa" name="id_proveedor" required>
                                        <option value="">Escriba para buscar...</option>
                                        <?php foreach($provs as $p): ?>
                                            <option value="<?php echo $p['id']; ?>">
                                                <?php echo htmlspecialchars($p['nombre'] . ' - NIT: ' . $p['nit_cedula']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-excel me-2"></i> Descargar Historial</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Activar la lupa (Select2) en el selector específico
            $('#selectProveedorLupa').select2({
                theme: 'bootstrap-5',
                placeholder: "Escriba el nombre o NIT...",
                allowClear: true
            });
        });
    </script>
</body>
</html>