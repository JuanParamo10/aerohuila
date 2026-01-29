<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $email, $password_hash, $rol]);
        
        header("Location: ../usuarios.php?msg=creado");
    } catch (PDOException $e) {
        // Error común: El email ya existe (definido como UNIQUE en SQL)
        if ($e->getCode() == 23000) {
            echo "<script>alert('Error: Este correo ya está registrado.'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>