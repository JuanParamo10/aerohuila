<?php
require_once 'config/db.php';
$pass = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "UPDATE usuarios SET password_hash = '$pass' WHERE email = 'admin@aerohuila.com'";
$pdo->query($sql);
echo "Contraseña restablecida a: admin123";
?>