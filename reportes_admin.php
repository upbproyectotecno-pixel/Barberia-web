<?php
/*******************************************************
 * reportes_admin.php — Ganancias por barbero
 * Esquema real (según tus capturas):
 *  - usuario(user_id, nombre, email, rol)
 *  - citas(id, user_citas, barbero_id, fecha, hora)
 *  - cita_servicio(id, cita_id, servicio_id)
 *  - servicios(id, nombre, precio)
 * Conexión: root / 1234 / barberia (Laragon)
 *******************************************************/

// ===== BD =====
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '1234';
$DB_NAME = 'barberia';

// ===== filtros (opcionales) =====
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

// ===== conexión =====
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
  die("Error de conexión a la base de datos: " . $mysqli->connect_error);
}

/*
  Lógica:
  - Barberos: usuario.rol = 'barbero'
  - LEFT JOIN citas por barbero_id (filtros de fecha en el ON para no perder barberos sin citas)
  - LEFT JOIN cita_servicio por cita_id
  - LEFT JOIN servicios por id = servicio_id
  - SUM(s.precio) por cada fila de cita_servicio
*/

$onFecha = '';
$params = [];
$types  = '';

if ($desde) { $onFecha .= " AND c.fecha >= ?"; $params[] = $desde; $types .= 's'; }
if ($hasta) { $onFecha .= " AND c.fecha <= ?"; $params[] = $hasta; $types .= 's'; }

$sql = "
SELECT
  u.user_id                               AS barber_id,
  u.nombre                                 AS barber_name,
  COALESCE(SUM(COALESCE(s.precio,0)), 0)   AS ganancia
FROM usuario u
LEFT JOIN citas c
       ON c.barbero_id = u.user_id
      $onFecha
LEFT JOIN cita_servicio cs
       ON cs.cita_id = c.id
LEFT JOIN servicios s
       ON s.id = cs.servicio_id
WHERE u.rol = 'barbero'
GROUP BY u.user_id, u.nombre
ORDER BY barber_name ASC
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) { die("Error preparando consulta: " . $mysqli->error); }
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$total_general = 0.0;
$labels = [];
$data = [];
while ($r = $res->fetch_assoc()) {
  $rows[] = $r;
  $total_general += (float)$r['ganancia'];
  $labels[] = $r['barber_name'];
  $data[]   = (float)$r['ganancia'];
}
$stmt->close();
$mysqli->close();

// ===== base URL para enlazar el CSS del raíz (style.css) =====
function base_url_like() {
  $docRoot   = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']), '/');
  $scriptDir = rtrim(str_replace('\\','/', __DIR__), '/');
  $rel       = ltrim(str_replace($docRoot, '', $scriptDir), '/');
  if ($rel === '') return ''; // vhost tipo proyecto.test
  $parts = explode('/', $rel);
  $root = $parts[0] ?? '';
  return $root ? '/' . $root : '';
}
$BASE = base_url_like();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Reportes — Ganancias por Barbero</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- TU CSS del raíz (tal cual tu diseño oscuro con dorado) -->
  <link rel="stylesheet" href="<?php echo $BASE; ?>/style.css?v=1.0">

  <!-- Chart.js (solo para la gráfica) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <!-- Header según tu CSS -->
  <header>
    <h1>Reportes — Ganancias</h1>
    <nav>
      <a href="<?php echo $BASE; ?>/index.php">Inicio</a>
      <a href="<?php echo $BASE; ?>/panel_admin.php">Panel</a>
      <a href="<?php echo $BASE; ?>/logout.php">Salir</a>
    </nav>
  </header>

  <div class="container">
    <!-- Filtros (usa tus estilos de formulario/botón) -->
    <form method="get" action="" style="margin-bottom:18px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <label>
          Desde:
          <input type="date" name="desde" value="<?php echo htmlspecialchars($desde ?? ''); ?>">
        </label>
        <label>
          Hasta:
          <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta ?? ''); ?>">
        </label>
        <button class="btn" type="submit">Aplicar</button>
        <?php if (($desde ?? null) || ($hasta ?? null)): ?>
          <a class="btn" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="text-decoration:none; display:inline-block;">Limpiar</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Tabla -->
    <table>
      <thead>
        <tr>
          <th>Barbero</th>
          <th>Ganancia</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="2">No hay datos para el filtro seleccionado.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['barber_name']); ?></td>
              <td>$ <?php echo number_format((float)$r['ganancia'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <th>Total</th>
          <th>$ <?php echo number_format($total_general, 2); ?></th>
        </tr>
      </tfoot>
    </table>

    <!-- Gráfica -->
    <div style="margin-top:24px;">
      <canvas id="chartGanancias" height="240"></canvas>
    </div>
  </div>

  <footer>
    © <?php echo date('Y'); ?> Barbería — Reportes
  </footer>

  <script>
    // Datos para Chart.js
    const labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
    const data   = <?php echo json_encode($data, JSON_NUMERIC_CHECK); ?>;

    const ctx = document.getElementById('chartGanancias').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Ganancias',
          data,
          borderWidth: 1
          // Colores: los maneja tu tema/Chart.js; me dijiste que están bien
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (c) => ' $ ' + Number(c.parsed.y).toFixed(2) } }
        }
      }
    });
  </script>
</body>
</html>