<?php
include "db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST["password"]);

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT user_id, nombre, password, rol FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verificar contraseña (hash o texto plano por compatibilidad)
            if ($password === $row["password"] || password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["nombre"] = $row["nombre"];
                $_SESSION["rol"] = strtolower(trim($row["rol"])); // normalizamos

                // Redirección según rol
                switch ($_SESSION["rol"]) {
                    case "admin":
                        header("Location: panel_admin.php");
                        break;
                    case "barbero":
                        header("Location: barberos.php");
                        break;
                    case "usuario":
                    default:
                        header("Location: dashboard.php");
                        break;
                }
                exit();
            } else {
                $_SESSION["flash"] = "⚠️ Contraseña incorrecta.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION["flash"] = "⚠️ Usuario no encontrado.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION["flash"] = "⚠️ Correo o contraseña inválidos.";
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión</title>

    <!-- Estilos -->
    <link rel="stylesheet" href="estilo/css/login.css">
    <link rel="stylesheet" href="estilo/css/titulo.css">

    <!-- Fuente urbana -->
    <link href="https://fonts.googleapis.com/css2?family=Staatliches&display=swap" rel="stylesheet">
</head>
<body>

    <!-- 🔥 Título Animado -->
    <h1 class="titulo">Barber JSK</h1>

    <!-- Mensajes Flash -->
    <?php if (isset($_SESSION["flash"])): ?>
        <p style="text-align:center; color: <?= strpos($_SESSION["flash"], '✅') !== false ? 'green' : 'red' ?>; font-weight:bold;">
            <?= $_SESSION["flash"]; ?>
        </p>
        <?php unset($_SESSION["flash"]); ?>
    <?php endif; ?>

    <div class="container" id="container">

        <!-- Registro -->
        <div class="form-container sign-up-container">
            <form action="register.php" method="post">
                <h1>Crea tu cuenta</h1>
                <span>Usa tu cuenta para registrarte :]</span>
                <input type="text" name="nombre" placeholder="Nombre completo" required>
                <input type="email" name="email" placeholder="Correo" required>
                <input type="text" name="ciudad" placeholder="Ciudad" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Registrarme</button>
            </form>
        </div>

        <!-- Login -->
        <div class="form-container sign-in-container">
            <form action="login.php" method="post">
                <h1>Iniciar sesión</h1>
                <span>Usa tu cuenta</span>
                <input type="email" name="email" placeholder="Correo" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
                <button type="submit">Iniciar sesión</button>
            </form>
        </div>

        <!-- Overlay -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Bienvenido de nuevo</h1>
                    <p>Para mantenerte conectado con nosotros, inicia sesión con tu información personal.</p>
                    <button class="ghost" id="signIn">Iniciar sesión</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hola, amigo!</h1>
                    <p>Ingresa tus datos personales y comienza a conocernos</p>
                    <button class="ghost" id="signUp">Registrarme</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>Created with <i class="fa fa-heart"></i> by Barber JSK</p>
        <p>© 2025 Barber JSK — Todos los derechos reservados.</p>
    </footer>

    <script src="estilo/js/login.js"></script>
</body>
</html>
