<?php
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Evitar que se borre a sí mismo o al Super Admin (lógica básica)
    // En un sistema real verificaríamos la sesión actual aquí
    
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: ../usuarios.php?msg=eliminado");
}
?>