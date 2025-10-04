<?php
include "db.php";
session_start();

// Verificar admin
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$stmt = $conn->prepare("SELECT rol FROM usuario WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$rol = $result->fetch_assoc()["rol"] ?? "usuario";
if ($rol !== "admin") {
    header("Location: dashboard.php");
    exit();
}

// Cambiar rol
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"], $_POST["nuevo_rol"])) {
    $user_id = intval($_POST["user_id"]);
    $nuevo_rol = $_POST["nuevo_rol"];

    if ($user_id != $_SESSION["user_id"]) {
        $stmt = $conn->prepare("UPDATE usuario SET rol = ? WHERE user_id = ?");
        $stmt->bind_param("si", $nuevo_rol, $user_id);
        $stmt->execute();
    }
    header("Location: usuarios_admin.php");
    exit();
}

// Eliminar usuario
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);

    if ($delete_id != $_SESSION["user_id"]) {
        $stmt = $conn->prepare("DELETE FROM usuario WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
    }

    header("Location: usuarios_admin.php");
    exit();
}

// Obtener lista de usuarios
$usuarios = $conn->query("SELECT * FROM usuario");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios - Barbería</title>
    <link rel="stylesheet" href="usuarios_admin.css">
</head>
<body>
    <h2>Gestionar Usuarios</h2>
    <a class="btn-volver" href="panel_admin.php">⬅️ Volver al Panel Admin</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
        <?php while ($u = $usuarios->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $u["user_id"]; ?></td>
                <td><?php echo htmlspecialchars($u["nombre"]); ?></td>
                <td><?php echo htmlspecialchars($u["email"]); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                        <select name="nuevo_rol">
                            <option value="usuario" <?php if ($u["rol"] == "usuario") echo "selected"; ?>>Usuario</option>
                            <option value="barbero" <?php if ($u["rol"] == "barbero") echo "selected"; ?>>Barbero</option>
                            <option value="admin" <?php if ($u["rol"] == "admin") echo "selected"; ?>>Admin</option>
                        </select>
                        <button type="submit">Actualizar</button>
                    </form>
                </td>
                <td>
                    <?php if ($u["user_id"] != $_SESSION["user_id"]) { ?>
                        <a href="usuarios_admin.php?delete_id=<?php echo $u['user_id']; ?>" 
                           onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">Eliminar</a>
                    <?php } else { ?>
                        <em>No puedes eliminarte</em>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
