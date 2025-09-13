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

// Agregar servicio
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nombre"], $_POST["precio"])) {
    $nombre = trim($_POST["nombre"]);
    $precio = floatval($_POST["precio"]);

    if ($nombre !== "" && $precio > 0) {
        $stmt = $conn->prepare("INSERT INTO servicios (nombre, precio) VALUES (?, ?)");
        $stmt->bind_param("sd", $nombre, $precio);
        $stmt->execute();
    }
    header("Location: servicios_admin.php");
    exit();
}

// Eliminar servicio
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $stmt = $conn->prepare("DELETE FROM servicios WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: servicios_admin.php");
    exit();
}

// Editar servicio
if (isset($_POST["edit_id"])) {
    $edit_id = intval($_POST["edit_id"]);
    $nuevo_nombre = trim($_POST["nuevo_nombre"]);
    $nuevo_precio = floatval($_POST["nuevo_precio"]);

    if ($nuevo_nombre !== "" && $nuevo_precio > 0) {
        $stmt = $conn->prepare("UPDATE servicios SET nombre = ?, precio = ? WHERE id = ?");
        $stmt->bind_param("sdi", $nuevo_nombre, $nuevo_precio, $edit_id);
        $stmt->execute();
    }
    header("Location: servicios_admin.php");
    exit();
}

// Obtener lista de servicios
$servicios = $conn->query("SELECT * FROM servicios");
?>

<h2>Gestionar Servicios</h2>
<a href="dashboard.php">⬅ Volver al Dashboard</a>

<h3>Agregar servicio</h3>
<form method="POST">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="number" step="0.01" name="precio" placeholder="Precio" required>
    <button type="submit">Agregar</button>
</form>

<h3>Lista de servicios</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Precio</th>
        <th>Acciones</th>
    </tr>
    <?php while ($s = $servicios->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $s["id"]; ?></td>
            <td><?php echo htmlspecialchars($s["nombre"]); ?></td>
            <td>$<?php echo number_format($s["precio"], 2); ?></td>
            <td>
                <!-- Formulario para editar -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="edit_id" value="<?php echo $s['id']; ?>">
                    <input type="text" name="nuevo_nombre" value="<?php echo htmlspecialchars($s['nombre']); ?>" required>
                    <input type="number" step="0.01" name="nuevo_precio" value="<?php echo $s['precio']; ?>" required>
                    <button type="submit">Editar</button>
                </form>
                |
                <!-- Botón eliminar -->
                <a href="servicios_admin.php?delete_id=<?php echo $s['id']; ?>" 
                   onclick="return confirm('¿Seguro que deseas eliminar este servicio?');">Eliminar</a>
            </td>
        </tr>
    <?php } ?>
</table>
