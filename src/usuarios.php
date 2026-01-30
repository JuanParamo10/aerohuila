<?php
// 1. SEGURIDAD
require_once 'config/security.php'; 
require_once 'config/db.php';

// Consultar Usuarios
try {
    $sql = "SELECT * FROM usuarios ORDER BY id DESC";
    $usuarios = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $usuarios = []; }

// Mensajes
$mensaje = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'creado') {
        $mensaje = '<div class="alert alert-success alert-dismissible fade show m-3" role="alert">Usuario creado exitosamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    if ($_GET['msg'] == 'eliminado') {
        $mensaje = '<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">Usuario eliminado.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Aerohuila</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="sidebar d-flex flex-column">
        <div class="text-center mb-4"><h4 class="fw-bold text-white tracking-wider">AEROHUILA</h4><small class="text-light opacity-50">Administración</small></div>
        <hr class="border-secondary mx-3">
        <a href="dashboard.php"><i class="bi bi-truck me-2"></i> Proveedores</a>
             <a href="ordenes.php"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
        <a href="usuarios.php" class="active"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes / Exportar</a>
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
            <?php echo $mensaje; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h2 class="fw-bold text-dark">Gestión de Usuarios</h2><p class="text-muted">Administra los accesos al sistema.</p></div>
                
                <?php if($esAdmin): ?>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Crear Nuevo Usuario
                </button>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nombre</th>
                                    <th>Email (Usuario)</th>
                                    <th>Rol</th>
                                    <th>Fecha Creación</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($user['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if($user['rol'] == 'Administrador'): ?>
                                            <span class="badge bg-primary">Administrador</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Visitante</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['fecha_creacion'])); ?></td>
                                    <td class="text-end pe-4">
                                        <?php if($esAdmin && $user['email'] !== 'admin@aerohuila.com'): ?>
                                            <a href="controllers/eliminar_usuario.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-outline-danger btn-sm"
                                               onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                        <?php elseif(!$esAdmin): ?>
                                            <small class="text-muted"><i class="bi bi-lock"></i> Solo lectura</small>
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
    </div>

    <div class="modal fade" id="crearUsuarioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="controllers/guardar_usuario.php" method="POST">
                    <div class="modal-header bg-dark text-white"><h5 class="modal-title">Registrar Nuevo Usuario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nombre Completo</label><input type="text" class="form-control" name="nombre" required></div>
                        <div class="mb-3"><label class="form-label">Correo (Usuario)</label><input type="email" class="form-control" name="email" required></div>
                        <div class="mb-3"><label class="form-label">Contraseña</label><input type="password" class="form-control" name="password" required></div>
                        <div class="mb-3"><label class="form-label">Rol</label>
                            <select class="form-select" name="rol">
                                <option value="Visitante">Visitante</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-dark">Crear Usuario</button></div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>