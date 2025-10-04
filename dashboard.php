<?php
include "db.php";
session_start();

/*
// 🔍 DEBUG opcional: para encontrar el archivo de la barra lateral
if (isset($_GET['trace'])) {
    header('Content-Type: text/plain');
    foreach (get_included_files() as $file) echo $file . "\n";
    exit;
}
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Obtener rol del usuario
$stmt = $conn->prepare("SELECT rol FROM usuario WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$rol = $result->fetch_assoc()["rol"] ?? "usuario";

$es_admin   = ($rol === "admin");
$es_barbero = ($rol === "barbero");

// Lista de servicios
$servicios_result = $conn->query("SELECT * FROM servicios");

// === Crear cita (solo usuario) ===
if ($rol === "usuario" && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["fecha"], $_POST["hora"], $_POST["servicios"], $_POST["barbero_id"])) {
    $fecha       = $_POST["fecha"];
    $hora        = $_POST["hora"];
    $servicios   = $_POST["servicios"];
    $barbero_id  = intval($_POST["barbero_id"]);
    $user_id     = $_SESSION["user_id"];

    if (count($servicios) === 0) {
        $error = "Debes seleccionar al menos un servicio.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE barbero_id = ? AND fecha = ? AND hora = ?");
        $stmt->bind_param("iss", $barbero_id, $fecha, $hora);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res["total"] > 0) {
            $error = "Ese barbero ya tiene una cita en esa fecha y hora.";
        } else {
            $stmt = $conn->prepare("INSERT INTO citas (user_citas, barbero_id, fecha, hora) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $barbero_id, $fecha, $hora);
            $stmt->execute();
            $cita_id = $stmt->insert_id;

            $stmt_detalle = $conn->prepare("INSERT INTO cita_servicio (cita_id, servicio_id) VALUES (?, ?)");
            foreach ($servicios as $servicio_id) {
                $stmt_detalle->bind_param("ii", $cita_id, $servicio_id);
                $stmt_detalle->execute();
            }

            header("Location: dashboard.php");
            exit();
        }
    }
}

// === Eliminar cita ===
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);

    if ($es_admin) {
        $conn->query("DELETE FROM cita_servicio WHERE cita_id = $delete_id");
        $conn->query("DELETE FROM citas WHERE id = $delete_id");
    } else {
        $stmt = $conn->prepare("DELETE FROM cita_servicio WHERE cita_id = ? AND cita_id IN (SELECT id FROM citas WHERE id = ? AND user_citas = ?)");
        $stmt->bind_param("iii", $delete_id, $delete_id, $_SESSION["user_id"]);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM citas WHERE id = ? AND user_citas = ?");
        $stmt->bind_param("ii", $delete_id, $_SESSION["user_id"]);
        $stmt->execute();
    }

    header("Location: dashboard.php");
    exit();
}

// === Consultar citas según rol ===
if ($es_admin) {
    $query = "
        SELECT c.id AS cita_id, c.fecha, c.hora, u.nombre AS usuario, b.nombre AS barbero,
            GROUP_CONCAT(s.nombre SEPARATOR ', ') AS servicios,
            SUM(s.precio) AS total
        FROM citas c
        JOIN cita_servicio cs ON c.id = cs.cita_id
        JOIN servicios s ON cs.servicio_id = s.id
        JOIN usuario u ON c.user_citas = u.user_id
        JOIN usuario b ON c.barbero_id = b.user_id
        GROUP BY c.id
        ORDER BY c.fecha DESC
    ";
} elseif ($es_barbero) {
    $query = "
        SELECT c.id AS cita_id, c.fecha, c.hora, u.nombre AS usuario,
            GROUP_CONCAT(s.nombre SEPARATOR ', ') AS servicios,
            SUM(s.precio) AS total
        FROM citas c
        JOIN cita_servicio cs ON c.id = cs.cita_id
        JOIN servicios s ON cs.servicio_id = s.id
        JOIN usuario u ON c.user_citas = u.user_id
        WHERE c.barbero_id = {$_SESSION["user_id"]}
        GROUP BY c.id
        ORDER BY c.fecha DESC
    ";
} else {
    $query = "
        SELECT c.id AS cita_id, c.fecha, c.hora, b.nombre AS barbero,
            GROUP_CONCAT(s.nombre SEPARATOR ', ') AS servicios,
            SUM(s.precio) AS total
        FROM citas c
        JOIN cita_servicio cs ON c.id = cs.cita_id
        JOIN servicios s ON cs.servicio_id = s.id
        JOIN usuario b ON c.barbero_id = b.user_id
        WHERE c.user_citas = {$_SESSION["user_id"]}
        GROUP BY c.id
        ORDER BY c.fecha DESC
    ";
}
$result = $conn->query($query);
?>
<link rel="stylesheet" href="style.css">

<header>
    <h1>Barbería</h1>
    <nav>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="container">
    <h2>Hola, <?php echo htmlspecialchars($_SESSION["nombre"]); ?> (<?php echo $rol; ?>)</h2>
</div>

<?php if ($rol === "usuario"): ?>
<div class="container">
    <h3>Agendar nueva cita</h3>
    <?php if (isset($error)): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
    <form method="POST">
        <input type="date" name="fecha" required>
        <input type="time" name="hora" required>

        <h4>Servicios:</h4>
        <div class="option-group">
            <?php while ($servicio = $servicios_result->fetch_assoc()): ?>
                <label>
                    <?php echo htmlspecialchars($servicio['nombre']); ?> ($<?php echo number_format($servicio['precio'], 0); ?>)
                    <input type="checkbox" name="servicios[]" value="<?php echo $servicio['id']; ?>">
                </label>
            <?php endwhile; ?>
        </div>

        <h4>Seleccionar barbero:</h4>
        <div class="option-group">
            <?php
            $barberos = $conn->query("SELECT user_id, nombre FROM usuario WHERE rol='barbero'");
            while ($b = $barberos->fetch_assoc()): ?>
                <label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php echo htmlspecialchars($b['nombre']); ?>
                    </div>
                    <input type="radio" name="barbero_id" value="<?php echo $b['user_id']; ?>" required>
                </label>
            <?php endwhile; ?>
        </div>

        <button type="submit" class="btn">Agendar</button>
    </form>
</div>
<?php endif; ?>

<div class="container">
    <h3><?php if ($es_admin) echo "Todas las citas"; ?></h3>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <?php if ($es_admin || $es_barbero): ?><th>Usuario</th><?php endif; ?>
            <?php if ($es_admin): ?><th>Barbero</th><?php endif; ?>
            <th>Servicios</th>
            <th>Total</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row["fecha"]; ?></td>
                <td><?php echo $row["hora"]; ?></td>
                <?php if ($es_admin || $es_barbero): ?><td><?php echo $row["usuario"]; ?></td><?php endif; ?>
                <?php if ($es_admin): ?><td><?php echo $row["barbero"]; ?></td><?php endif; ?>
                <td><?php echo $row["servicios"]; ?></td>
                <td>$<?php echo number_format($row["total"], 0); ?></td>
                <td>
                    <?php if ($es_admin): ?><a href="editar_cita.php?id=<?php echo $row["cita_id"]; ?>">Editar</a> |<?php endif; ?>
                    <a href="dashboard.php?delete_id=<?php echo $row["cita_id"]; ?>" onclick="return confirm('¿Seguro que quieres eliminar esta cita?');">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Barbería. Todos los derechos reservados.</p>
</footer>