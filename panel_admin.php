<?php
include "db.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Verificar que sea admin
$stmt = $conn->prepare("SELECT rol FROM usuario WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$rol = $result->fetch_assoc()["rol"] ?? "usuario";

if ($rol !== "admin") {
    header("Location: dashboard.php");
    exit();
}
?>

<h2>Panel de Administración</h2>
<p>Bienvenido <?php echo htmlspecialchars($_SESSION["nombre"]); ?> (<?php echo $rol; ?>)</p>
<a href="dashboard.php">Volver al Dashboard</a> | 
<a href="logout.php">Cerrar sesión</a>

<hr>
<ul>
    <li><a href="usuarios_admin.php">Gestionar Usuarios</a></li>
    <li><a href="servicios_admin.php">Gestionar Servicios</a></li>
    <li><a href="dashboard.php">Gestionar Citas</a></li>
    <li><a href="reportes_admin.php">Reportes</a></li>
</ul>
