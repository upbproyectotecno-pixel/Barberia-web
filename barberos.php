<?php
session_start();
require_once "db.php"; // Debe definir $conn (mysqli)

// 1) Autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$rol = strtolower(trim($_SESSION['rol'] ?? ''));
if ($rol !== 'barbero') {
    header("Location: dashboard.php");
    exit();
}

$barbero_id = (int)$_SESSION['user_id'];
$nombre_barbero = $_SESSION['nombre'] ?? 'Barbero';

// 2) Citas del barbero + servicios + TOTAL $
$sql = "
SELECT 
    c.id AS cita_id,
    u.nombre AS cliente,
    DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha_fmt,
    DATE_FORMAT(c.hora, '%H:%i') AS hora_fmt,
    COALESCE(GROUP_CONCAT(s.nombre ORDER BY s.nombre SEPARATOR ', '), '—') AS servicios,
    COALESCE(SUM(s.precio), 0) AS total_precio
FROM citas c
JOIN usuario u ON u.user_id = c.user_citas
LEFT JOIN cita_servicio cs ON cs.cita_id = c.id
LEFT JOIN servicios s ON s.id = cs.servicio_id
WHERE c.barbero_id = ?
GROUP BY c.id, u.nombre, c.fecha, c.hora
ORDER BY c.fecha ASC, c.hora ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) { die("Error preparando consulta: " . $conn->error); }
$stmt->bind_param("i", $barbero_id);
$stmt->execute();
$result = $stmt->get_result();

// helper formato moneda
function money($n) {
    return '$' . number_format((float)$n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Barbero - Barber JSK</title>
    <link rel="stylesheet" href="estilo/css/barberos.css">

    <style>
      /* Extras para badge de precio si tu CSS no lo tiene */
      .price-badge{
        display:inline-block; padding:.35rem .6rem; border-radius:10px;
        background:#222; border:1px solid #d4af37; color:#d4af37; font-weight:600;
      }
    </style>
</head>
<body>

<!-- ===== Sidebar ===== -->
<div class="sidebar">
  <h2>Barber JSK</h2>
  <p><?= htmlspecialchars($nombre_barbero) ?> (Barbero)</p>
  <a href="barberos.php">Inicio</a>
  <a href="logout.php">Cerrar sesión</a>
</div>

<!-- ===== Main ===== -->
<div class="main">
  <h1>Panel de Barbero</h1>

  <div class="card">
    <h2>Mis Citas</h2>
    <p>Aquí puedes revisar tus citas programadas.</p>
  </div>

  <div class="card">
    <h2>Próximas Citas</h2>
    <table>
      <thead>
        <tr>
          <th>Cliente</th>
          <th>Servicio(s)</th>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="5">No tienes citas asignadas.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['cliente']) ?></td>
              <td><?= htmlspecialchars($row['servicios']) ?></td>
              <td><?= htmlspecialchars($row['fecha_fmt']) ?></td>
              <td><?= htmlspecialchars($row['hora_fmt']) ?></td>
              <td><span class="price-badge"><?= money($row['total_precio']) ?></span></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2>Noticias y Actualizaciones</h2>
    <p>Recuerda actualizar tu disponibilidad y revisar las promociones del mes.</p>
  </div>

  <div class="card">
    <h2>Atención al Cliente</h2>
    <p>Contacta con la administración si tienes dudas o necesitas soporte.</p>
  </div>
</div>

</body>
</html>