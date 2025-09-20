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
            $_SESSION["flash"] = "⚠️ Ya existe un usuario con este correo.";
            $stmt->close();
            header("Location: login.php");
            exit();
        } else {
            // Hashear la contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar el nuevo usuario con rol "usuario"
            $stmt->close(); // cerramos antes de crear otro statement
            $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, password, rol) VALUES (?, ?, ?, 'usuario')");
            $stmt->bind_param("sss", $nombre, $email, $hash);

            if ($stmt->execute()) {
                $_SESSION["flash"] = "✅ Registro exitoso. Ahora puedes iniciar sesión.";
            } else {
                $_SESSION["flash"] = "⚠️ Error al registrar: " . htmlspecialchars($stmt->error);
            }

            $stmt->close();
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION["flash"] = "⚠️ Completa todos los campos correctamente.";
        header("Location: login.php");
        exit();
    }
}
?>