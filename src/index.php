<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aerohuila</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
        }

        .login-page {
            height: 100%;
            width: 100%;
            overflow: hidden;
        }

        /* --- SECCIÓN IZQUIERDA (IMAGEN) --- */
        .bg-image {
            /* Imagen de aeropuerto/arquitectura moderna */
            background-image: url('https://images.unsplash.com/photo-1530521954074-e64f6810b32d?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
            min-height: 100vh;
        }
        
        .bg-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.7) 100%); /* Azul oscuro corporativo */
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: white;
        }

        /* --- SECCIÓN DERECHA (FORMULARIO) --- */
        .login-section {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #ffffff;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }

        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }

        /* Inputs Modernos (Floating) */
        .form-floating > .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }
        .form-floating > .form-control:focus {
            border-color: #0ea5e9; /* Azul cyan moderno */
            box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.1);
        }
        .form-floating > label {
            color: #94a3b8;
        }

        /* Botón Moderno */
        .btn-login {
            background: #0f172a;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(15, 23, 42, 0.5);
        }

        /* Animación suave de entrada */
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="container-fluid login-page">
        <div class="row h-100">
            
            <div class="col-lg-7 col-md-6 d-none d-md-block bg-image p-0">
                <div class="bg-overlay">
                    <div class="fade-in-up">
                        <h1 class="display-4 fw-bold mb-3">Consorcio<br>Aerohuila</h1>
                        <p class="lead mb-4 opacity-75">Gestión integral de proveedores, control financiero y auditoría aeroportuaria.</p>
                        <div class="d-flex align-items-center gap-3">
                            <div class="badge bg-white text-dark p-2 px-3 rounded-pill"><i class="bi bi-shield-check me-1"></i> Seguro</div>
                            <div class="badge bg-white text-dark p-2 px-3 rounded-pill"><i class="bi bi-lightning-charge me-1"></i> Rápido</div>
                            <div class="badge bg-white text-dark p-2 px-3 rounded-pill"><i class="bi bi-cloud-arrow-up me-1"></i> Cloud</div>
                        </div>
                    </div>
                    
                    <div class="mt-auto opacity-50 small">
                        &copy; 2026 Sistema de Gestión Interna.
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-6 login-section">
                <div class="login-wrapper fade-in-up">
                    
                    <div class="mb-5">
                        <div class="brand-logo"><i class="bi bi-airplane-engines me-2"></i>AEROHUILA</div>
                        <h4 class="fw-semibold text-secondary">Bienvenido de nuevo</h4>
                        <p class="text-muted small">Ingresa tus credenciales para acceder al panel.</p>
                    </div>

                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm rounded-3 mb-4" role="alert">
                            <i class="bi bi-exclamation-octagon-fill fs-4 me-2"></i>
                            <div>Credenciales incorrectas. Intenta nuevamente.</div>
                        </div>
                    <?php endif; ?>

                    <form action="controllers/auth_login.php" method="POST">
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="email" id="floatingInput" placeholder="name@example.com" required>
                            <label for="floatingInput">Correo Electrónico</label>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" name="password" id="floatingPassword" placeholder="Password" required>
                            <label for="floatingPassword">Contraseña</label>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-login text-white">
                                Ingresar al Sistema <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>

                        <div class="text-center">
                            <small class="text-muted">¿Olvidaste tu contraseña? <a href="#" class="text-decoration-none fw-bold text-dark">Contactar Soporte</a></small>
                        </div>
                    </form>

                    <div class="mt-5 text-center text-muted small d-md-none">
                        &copy; 2026 Aerohuila S.A.S
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>