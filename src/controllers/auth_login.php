<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Buscar usuario por email
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verificar contraseña
    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        // ¡LOGIN EXITOSO!
        // Guardamos datos en la sesión del navegador
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nombre'] = $usuario['nombre'];
        $_SESSION['user_rol'] = $usuario['rol']; // 'Administrador' o 'Visitante'
        
        header("Location: ../dashboard.php");
        exit;
    } else {
        // ERROR
        header("Location: ../index.php?error=invalid");
        exit;
    }
}
?>