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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="panel_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2><i class="fas fa-cog"></i> Admin Panel</h2>
        <p>👋 Bienvenido <?php echo htmlspecialchars($_SESSION["nombre"]); ?> (<?php echo $rol; ?>)</p>
        <a href="dashboard.php"><i class="fas fa-home"></i> Volver al Dashboard</a>
        <a href="usuarios_admin.php"><i class="fas fa-users"></i> Gestionar Usuarios</a>
        <a href="servicios_admin.php"><i class="fas fa-scissors"></i> Gestionar Servicios</a>
        <a href="dashboard.php"><i class="fas fa-calendar-check"></i> Gestionar Citas</a>
        <a href="reportes_admin.php"><i class="fas fa-chart-line"></i> Reportes</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>

    <!-- Contenido principal con imagen -->
    <div class="main">
         <img src="img/Logo.png" alt="BARBERIA JSK">
    </div>
</body>
</html>
