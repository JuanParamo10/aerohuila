<?php
require_once '../config/security.php';       // 1. Seguridad
require_once '../config/db.php';             // 2. Base de Datos
require_once 'auditoria_helper.php';         // 3. Sistema de Auditoría

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Recoger datos
    $nombre = $_POST['nombre'];
    $nit = $_POST['nit'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $razon = $_POST['razon_social'];
    $banco = $_POST['banco'];
    $tipo_cuenta = $_POST['tipo_cuenta'];
    $num_cuenta = $_POST['numero_cuenta'];

    // Crear carpeta única por NIT
    $uploadDir = '../uploads/proveedores/' . $nit . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Helper interno para subir archivos con nombre único (uniqid)
    function subirArchivo($fileInputName, $targetDir, $prefix) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
            $fileName = $prefix . '_' . uniqid() . '.pdf';
            $targetPath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
                return $targetPath;
            }
        }
        return null;
    }

    // Subir archivos
    $ruta_rut = subirArchivo('pdf_rut', $uploadDir, 'RUT');
    $ruta_cert = subirArchivo('pdf_bancaria', $uploadDir, 'CERT');
    $ruta_req = subirArchivo('pdf_requisicion', $uploadDir, 'REQ_INI');

    // Insertar en BD
    $sql = "INSERT INTO proveedores (nombre, nit_cedula, email, telefono, razon_social, banco, tipo_cuenta, numero_cuenta, ruta_rut, ruta_cert_bancaria, ruta_requisicion_inicial) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$nombre, $nit, $email, $telefono, $razon, $banco, $tipo_cuenta, $num_cuenta, $ruta_rut, $ruta_cert, $ruta_req]);
        
        // AUDITORÍA
        registrarAuditoria($pdo, "Crear Proveedor", "Registró al proveedor: $nombre (NIT: $nit)");

        header("Location: ../dashboard.php?msg=creado");
    } catch (PDOException $e) {
        // Error de duplicado (NIT ya existe)
        if ($e->getCode() == 23000) {
            echo "<script>alert('Error: Este NIT ya está registrado.'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>