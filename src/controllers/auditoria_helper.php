<?php
// Este archivo se incluirá dentro de otros controladores que ya tienen db.php y session_start()

function registrarAuditoria($pdo, $accion, $descripcion) {
    // Verificar si hay sesión activa para saber quién fue
    $usuario = isset($_SESSION['user_nombre']) ? $_SESSION['user_nombre'] : 'Sistema/Desconocido';

    try {
        $sql = "INSERT INTO auditoria (usuario_nombre, accion, descripcion) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario, $accion, $descripcion]);
    } catch (Exception $e) {
        // Si falla la auditoría, no detenemos el sistema, solo lo ignoramos silenciosamente
        // error_log("Error auditoria: " . $e->getMessage());
    }
}
?>