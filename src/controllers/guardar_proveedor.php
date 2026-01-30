<?php
require_once '../config/security.php';
require_once '../config/db.php';
require_once 'auditoria_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $nit = $_POST['nit'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $razon = $_POST['razon_social'];
    $banco = $_POST['banco']; // Ahora es texto
    $tipo_cuenta = $_POST['tipo_cuenta'];
    $num_cuenta = $_POST['numero_cuenta'];

    $uploadDir = '../uploads/proveedores/' . $nit . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    function subir($input, $dir, $pre) {
        if (isset($_FILES[$input]) && $_FILES[$input]['error'] == 0) {
            $n = $pre . '_' . uniqid() . '.pdf';
            move_uploaded_file($_FILES[$input]['tmp_name'], $dir . $n);
            return $dir . $n;
        }
        return null;
    }

    $ruta_rut = subir('pdf_rut', $uploadDir, 'RUT');
    $ruta_cert = subir('pdf_bancaria', $uploadDir, 'CERT');
    // YA NO SUBIMOS REQUISICIÓN AQUÍ
    
    // SQL: No incluimos ruta_requisicion_inicial
    $sql = "INSERT INTO proveedores (nombre, nit_cedula, email, telefono, razon_social, banco, tipo_cuenta, numero_cuenta, ruta_rut, ruta_cert_bancaria) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$nombre, $nit, $email, $telefono, $razon, $banco, $tipo_cuenta, $num_cuenta, $ruta_rut, $ruta_cert]);
        registrarAuditoria($pdo, "Crear Proveedor", "Registró: $nombre ($nit)");
        header("Location: ../dashboard.php?msg=creado");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>