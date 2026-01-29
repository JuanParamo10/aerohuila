<?php
require_once 'config/security.php';
require_once 'config/db.php';

// Seguridad Extra: Si no es Admin, expulsar
if (!$esAdmin) {
    header("Location: dashboard.php");
    exit;
}

// Consultar últimos 100 movimientos
try {
    $sql = "SELECT * FROM auditoria ORDER BY id DESC LIMIT 100";
    $logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $logs = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - Aerohuila</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4"><h4 class="fw-bold tracking-wider">AEROHUILA</h4><small class="text-light opacity-50">Administración</small></div>
        <hr class="border-secondary mx-3">
        <a href="dashboard.php"><i class="bi bi-truck me-2"></i> Proveedores</a>
             <a href="ordenes.php"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes</a>
        <a href="#" class="active"><i class="bi bi-shield-lock-fill me-2"></i> Auditoría</a>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark">Registro de Auditoría</h2>
                    <p class="text-muted">Monitoreo de actividad y seguridad del sistema (Últimos 100 eventos).</p>
                </div>
                <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir Log</button>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha / Hora</th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="white-space: nowrap;"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?></td>
                                    <td class="fw-bold text-primary">
                                        <i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($log['usuario_nombre']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge = 'secondary';
                                            if(strpos($log['accion'], 'Crear') !== false) $badge = 'success';
                                            if(strpos($log['accion'], 'Eliminar') !== false) $badge = 'danger';
                                            if(strpos($log['accion'], 'Editar') !== false) $badge = 'warning text-dark';
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $log['accion']; ?></span>
                                    </td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($log['descripcion']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>