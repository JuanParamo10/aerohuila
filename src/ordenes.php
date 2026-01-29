<?php
require_once 'config/security.php';
require_once 'config/db.php';

$busqueda = isset($_GET['q']) ? $_GET['q'] : '';
$ordenes = [];

if ($busqueda) {
    $sql = "SELECT o.*, p.razon_social FROM ordenes_pago o JOIN proveedores p ON o.proveedor_id = p.id WHERE o.numero_op LIKE ? OR p.razon_social LIKE ? ORDER BY o.fecha_orden DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$busqueda%", "%$busqueda%"]);
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $ordenes = $pdo->query("SELECT o.*, p.razon_social FROM ordenes_pago o JOIN proveedores p ON o.proveedor_id = p.id ORDER BY o.fecha_orden DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
}
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
        <div class="text-center mb-4"><h4 class="fw-bold text-white">AEROHUILA</h4></div>
        <hr class="border-secondary mx-3">
        <a href="dashboard.php"><i class="bi bi-truck me-2"></i> Proveedores</a>
        <a href="ordenes.php" class="active"><i class="bi bi-receipt me-2"></i> Ordenes (OP)</a>
        <a href="usuarios.php"><i class="bi bi-people me-2"></i> Usuarios</a>
        <a href="reportes.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Reportes</a>
        <div class="mt-auto p-3"><a href="controllers/logout.php" class="text-danger">Salir</a></div>
    </div>

    <div class="main-content">
        <h2 class="fw-bold mb-4">Búsqueda de Órdenes (OP)</h2>
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="q" class="form-control" placeholder="Número de OP o Proveedor..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="ordenes.php" class="btn btn-secondary">Limpiar</a>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>OP</th><th>Fecha</th><th>Proveedor</th><th>Concepto</th><th>Valor</th><th>Estado</th><th>Docs</th></tr></thead>
                    <tbody>
                        <?php foreach($ordenes as $o): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?php echo $o['numero_op']; ?></td>
                            <td><?php echo $o['fecha_orden']; ?></td>
                            <td><?php echo $o['razon_social']; ?></td>
                            <td><?php echo substr($o['concepto_pago'],0,50); ?>...</td>
                            <td>$<?php echo number_format($o['valor'],0,',','.'); ?></td>
                            <td><span class="badge-estado st-<?php echo $o['estado_orden']; ?>"><?php echo $o['estado_orden']; ?></span></td>
                            <td><?php echo $o['ruta_op_firmada'] ? '<a href="'.str_replace('../','',$o['ruta_op_firmada']).'" target="_blank" class="btn btn-sm btn-outline-primary">OP</a>' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>