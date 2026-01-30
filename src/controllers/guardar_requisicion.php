<?php
require_once '../config/security.php';
require_once '../config/db.php';

// Verificamos si es admin (convertimos a minúsculas para evitar errores de 'Admin' vs 'admin')
$esAdmin = isset($_SESSION['user_rol']) && strtolower($_SESSION['user_rol']) === 'admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $id = $_POST['id_requisicion'] ?? '';
    $numero = $_POST['numero_requisicion'];
    $solicitante = $_POST['solicitante'];
    
    // Fecha automática (Hoy)
    $fecha = date('Y-m-d'); 

    // Manejo de Archivo
    $ruta_archivo = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
        $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
        $permitidos = ['pdf', 'xls', 'xlsx'];
        
        if (in_array(strtolower($ext), $permitidos)) {
            $dir = '../uploads/requisiciones/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $nombre_archivo = 'REQ_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['archivo']['tmp_name'], $dir . $nombre_archivo);
            $ruta_archivo = $dir . $nombre_archivo;
        }
    }

    if (!empty($id)) {
        // ========================
        // EDICIÓN
        // ========================
        $sql = "UPDATE requisiciones SET numero_requisicion=?, solicitante=?";
        $params = [$numero, $solicitante];

        // 1. Si subió archivo nuevo, actualizar ruta
        if ($ruta_archivo) {
            $sql .= ", ruta_archivo=?";
            $params[] = $ruta_archivo;
        }
        
        // 2. CORRECCIÓN CLAVE: Si llega el campo 'estado' en el POST, lo actualizamos.
        // (Confiamos en que si el HTML se lo mostró, es porque podía cambiarlo)
        if (!empty($_POST['estado'])) {
            $sql .= ", estado=?";
            $params[] = $_POST['estado'];
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

    } else {
        // ========================
        // CREACIÓN
        // ========================
        // Estado inicial por defecto
        $estadoInicial = 'Pendiente';
        
        // Si el admin lo está creando y eligió otro estado
        if (!empty($_POST['estado'])) {
            $estadoInicial = $_POST['estado'];
        }

        $sql = "INSERT INTO requisiciones (numero_requisicion, fecha_solicitud, solicitante, ruta_archivo, estado) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numero, $fecha, $solicitante, $ruta_archivo, $estadoInicial]);
    }

    header("Location: ../requisiciones.php?msg=guardado");
    exit;
}
?>