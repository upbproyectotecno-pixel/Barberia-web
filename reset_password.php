<?php
include "db.php";
session_start();

$token = isset($_GET["token"]) ? trim($_GET["token"]) : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = trim($_POST["token"]);
    $pass1 = trim($_POST["password"]);
    $pass2 = trim($_POST["password2"]);

    if (!$token || !$pass1 || !$pass2) {
        $_SESSION["flash"] = "⚠️ Completa todos los campos.";
        header("Location: reset_password.php?token=".urlencode($token));
        exit();
    }
    if ($pass1 !== $pass2) {
        $_SESSION["flash"] = "⚠️ Las contraseñas no coinciden.";
        header("Location: reset_password.php?token=".urlencode($token));
        exit();
    }

    // 1) Validar token vigente
    $stmt = $conn->prepare("SELECT user_id FROM usuario WHERE reset_token=? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $userId = $row["user_id"];
        $stmt->close();

        // 2) Guardar nueva contraseña (hash) y limpiar token
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuario SET password=?, reset_token=NULL, reset_expires=NULL WHERE user_id=?");
        $stmt->bind_param("si", $hash, $userId);

        if ($stmt->execute()) {
            $_SESSION["flash"] = "✅ Contraseña actualizada. Ahora puedes iniciar sesión.";
            $stmt->close();
            header("Location: login.php");
            exit();
        } else {
            $_SESSION["flash"] = "⚠️ Error al actualizar la contraseña.";
            $stmt->close();
            header("Location: reset_password.php?token=".urlencode($token));
            exit();
        }
    } else {
        $stmt && $stmt->close();
        $_SESSION["flash"] = "⚠️ Enlace inválido o vencido. Solicita uno nuevo.";
        header("Location: forgot_password.php");
        exit();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nueva contraseña</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1 class="titulo" style="text-align:center; margin:24px 0;">Crear nueva contraseña</h1>

  <?php if (isset($_SESSION["flash"])): ?>
    <div class="<?= strpos($_SESSION['flash'],'⚠️')!==false ? 'error' : 'success' ?> flash">
      <?= $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
    </div>
  <?php endif; ?>

  <?php if ($token): ?>
    <div class="container narrow">
      <form method="post" action="reset_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
        <input type="password" name="password" placeholder="Nueva contraseña" required>
        <input type="password" name="password2" placeholder="Repite la contraseña" required>
        <button class="btn" type="submit">Guardar</button>
        <a href="login.php" style="display:block;text-align:center;margin-top:8px;">← Volver al inicio de sesión</a>
      </form>
    </div>
  <?php else: ?>
    <p style="text-align:center;">⚠️ Falta el token. Solicita un nuevo enlace.</p>
  <?php endif; ?>
</body>
</html>