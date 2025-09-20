<?php
include "db.php";
session_start();

/* ===== Config ===== */
$DEBUG_SHOW_LINK = true; // siempre mostramos el enlace en pantalla

/* ===== Helpers ===== */
function now_plus_minutes($minutes){
    return date('Y-m-d H:i:s', time() + ($minutes * 60));
}
function base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return $scheme . '://' . $host . $dir;
}

/* ===== POST ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $_SESSION["flash"] = "⚠️ Ingresa un correo válido.";
        header("Location: forgot_password.php");
        exit();
    }

    // Buscar usuario
    $stmt = $conn->prepare("SELECT user_id FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row    = $res->fetch_assoc();
        $userId = $row["user_id"];
        $stmt->close();

        // Generar token + 1h
        $token   = bin2hex(random_bytes(32));
        $expires = now_plus_minutes(60);

        // Guardar token
        $stmt = $conn->prepare("UPDATE usuario SET reset_token=?, reset_expires=? WHERE user_id=?");
        $stmt->bind_param("ssi", $token, $expires, $userId);
        $stmt->execute();
        $stmt->close();

        // Link de reseteo
        $resetLink = base_url() . "/reset_password.php?token=" . urlencode($token);

        // Mostrar siempre el link (no se envía correo)
        $_SESSION["flash"] = "🔗 Enlace de restablecimiento (válido 1 hora):<br><small>$resetLink</small>";
    } else {
        // Correo NO existe
        $_SESSION["flash"] = "⚠️ El correo no está registrado en el sistema.";
        $stmt && $stmt->close();
    }

    header("Location: forgot_password.php");
    exit();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Restablecer contraseña</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1 class="titulo" style="text-align:center; margin:24px 0;">Restablecer contraseña</h1>

  <?php if (isset($_SESSION["flash"])): ?>
    <div class="<?= strpos($_SESSION['flash'],'⚠️')!==false ? 'error' : 'success' ?> flash">
      <?= $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
    </div>
  <?php endif; ?>

  <div class="container narrow">
    <form method="post" action="forgot_password.php">
      <label>Escribe tu correo y te mostraremos un enlace:</label>
      <input type="email" name="email" placeholder="tu@correo.com" required>
      <button class="btn" type="submit">Generar enlace</button>
      <a href="login.php" style="display:block;text-align:center;margin-top:8px;">← Volver al inicio de sesión</a>
    </form>
  </div>
</body>
</html>