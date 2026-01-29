<?php
session_start();

// Si NO existe la variable de sesión 'user_id', no está logueado.
if (!isset($_SESSION['user_id'])) {
    // Lo mandamos al login
    header("Location: index.php");
    exit;
}

// Helper para saber si es admin (Para usar en el HTML)
$esAdmin = ($_SESSION['user_rol'] === 'Administrador');
?>