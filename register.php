<?php
include "db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST["password"]);

    if ($nombre && $email && $password) {
        // Verificar si ya existe un usuario con ese correo
        $stmt = $conn->prepare("SELECT user_id FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<p style='color:red;'>⚠️ Ya existe un usuario con este correo.</p>";
        } else {
            // Hashear la contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar el nuevo usuario con rol "usuario"
            $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, password, rol) VALUES (?, ?, ?, 'usuario')");
            $stmt->bind_param("sss", $nombre, $email, $hash);

            if ($stmt->execute()) {
                // ✅ Solo registramos, no iniciamos sesión automáticamente
                header("Location: login.php?msg=registrado");
                exit();
            } else {
                echo "<p style='color:red;'>⚠️ Error al registrar: " . htmlspecialchars($stmt->error) . "</p>";
            }
        }
        $stmt->close();
    } else {
        echo "<p style='color:red;'>⚠️ Completa todos los campos correctamente.</p>";
    }
}
?>

<!-- 
Este formulario era el que usabas de prueba antes del diseño actual.
Lo dejo aquí comentado como referencia, para que lo tengas guardado:

<link rel="stylesheet" href="style.css">
<form method="POST">
    <h2>Registro</h2>
    <input type="text" name="nombre" placeholder="Nombre completo" required>
    <input type="email" name="email" placeholder="Correo" required>
    <input type="text" name="ciudad" placeholder="Ciudad" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Registrarse</button>
</form> 
-->
