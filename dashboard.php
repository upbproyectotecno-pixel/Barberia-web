<?php
include "db.php";
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Obtener rol
$stmt = $conn->prepare("SELECT rol FROM usuario WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$rol = $result->fetch_assoc()["rol"] ?? "usuario";

$es_admin = ($rol === "admin");
$es_barbero = ($rol === "barbero");

// Obtener lista de servicios (solo usuarios pueden agendar)
$servicios_result = $conn->query("SELECT * FROM servicios");

// Agregar cita (solo usuarios)
if ($rol === "usuario" && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["fecha"], $_POST["hora"], $_POST["servicios"], $_POST["barbero_id"])) {
    $fecha = $_POST["fecha"];
    $hora = $_POST["hora"];
    $servicios = $_POST["servicios"];
    $barbero_id = intval($_POST["barbero_id"]);
    $user_id = $_SESSION["user_id"];

    if (count($servicios) == 0) {
        $error = "Debes seleccionar al menos un servicio.";
    } else {
        // Verificar disponibilidad del barbero en fecha y hora
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

// Eliminar cita (usuarios solo las suyas, admin cualquiera)
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);

    if ($es_admin) {
        $stmt_del_detalle = $conn->prepare("DELETE FROM cita_servicio WHERE cita_id = ?");
        $stmt_del_detalle->bind_param("i", $delete_id);
        $stmt_del_detalle->execute();

        $stmt_del = $conn->prepare("DELETE FROM citas WHERE id = ?");
        $stmt_del->bind_param("i", $delete_id);
        $stmt_del->execute();
    } else {
        $stmt_del_detalle = $conn->prepare("DELETE FROM cita_servicio WHERE cita_id = ? AND cita_id IN (SELECT id FROM citas WHERE id = ? AND user_citas = ?)");
        $stmt_del_detalle->bind_param("iii", $delete_id, $delete_id, $_SESSION["user_id"]);
        $stmt_del_detalle->execute();

        $stmt_del = $conn->prepare("DELETE FROM citas WHERE id = ? AND user_citas = ?");
        $stmt_del->bind_param("ii", $delete_id, $_SESSION["user_id"]);
        $stmt_del->execute();
    }

    header("Location: dashboard.php");
    exit();
}

// Consultar citas
if ($es_admin) {
    $stmt = $conn->prepare("
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
    ");
} elseif ($es_barbero) {
    $stmt = $conn->prepare("
        SELECT c.id AS cita_id, c.fecha, c.hora, u.nombre AS usuario,
        GROUP_CONCAT(s.nombre SEPARATOR ', ') AS servicios,
        SUM(s.precio) AS total
        FROM citas c
        JOIN cita_servicio cs ON c.id = cs.cita_id
        JOIN servicios s ON cs.servicio_id = s.id
        JOIN usuario u ON c.user_citas = u.user_id
        WHERE c.barbero_id = ?
        GROUP BY c.id
        ORDER BY c.fecha DESC
    ");
    $stmt->bind_param("i", $_SESSION["user_id"]);
} else {
    $stmt = $conn->prepare("
        SELECT c.id AS cita_id, c.fecha, c.hora, b.nombre AS barbero,
        GROUP_CONCAT(s.nombre SEPARATOR ', ') AS servicios,
        SUM(s.precio) AS total
        FROM citas c
        JOIN cita_servicio cs ON c.id = cs.cita_id
        JOIN servicios s ON cs.servicio_id = s.id
        JOIN usuario b ON c.barbero_id = b.user_id
        WHERE c.user_citas = ?
        GROUP BY c.id
        ORDER BY c.fecha DESC
    ");
    $stmt->bind_param("i", $_SESSION["user_id"]);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<h2>Hola, <?php echo htmlspecialchars($_SESSION["nombre"]); ?> (<?php echo $rol; ?>) | <a href="logout.php">Cerrar sesión</a></h2>
<link rel="stylesheet" href="style.css">

<style>
.opcion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 350px; /* ajusta según diseño */
    margin: 5px 0;
}
</style>

<?php if ($rol === "usuario"): ?>
    <h3>Agendar nueva cita</h3>
    <?php if (isset($error)) : ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="date" name="fecha" required>
        <input type="time" name="hora" required>
        <label>Servicios:</label><br>
        <?php while ($servicio = $servicios_result->fetch_assoc()) { ?>
            <label class="opcion">
                <?php echo htmlspecialchars($servicio['nombre']); ?> ($<?php echo number_format($servicio['precio'], 2); ?>)
                <input type="checkbox" name="servicios[]" value="<?php echo $servicio['id']; ?>">
            </label><br>
        <?php } ?>
        <label>Seleccionar barbero:</label><br>
        <?php
        $barberos = $conn->query("SELECT user_id, nombre FROM usuario WHERE rol = 'barbero'");
        while ($barbero = $barberos->fetch_assoc()) {
            echo "<label class='opcion'>" . htmlspecialchars($barbero['nombre']) . " 
                  <input type='radio' name='barbero_id' value='" . $barbero['user_id'] . "' required></label><br>";
        }
        ?>
        <button type="submit">Agendar</button>
    </form>
<?php endif; ?>

<h3>
    <?php
    if ($es_admin) echo "Todas las citas";
    elseif ($es_barbero) echo "Mis citas (como barbero)";
    else echo "Mis citas";
    ?>
</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>Fecha</th>
        <th>Hora</th>
        <?php if ($es_admin || $es_barbero): ?><th>Usuario</th><?php endif; ?>
        <?php if ($es_admin): ?><th>Barbero</th><?php endif; ?>
        <th>Servicios</th>
        <th>Total ($)</th>
        <th>Acciones</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row["fecha"]); ?></td>
            <td><?php echo htmlspecialchars($row["hora"]); ?></td>
            <?php if ($es_admin || $es_barbero): ?><td><?php echo htmlspecialchars($row["usuario"]); ?></td><?php endif; ?>
            <?php if ($es_admin): ?><td><?php echo htmlspecialchars($row["barbero"]); ?></td><?php endif; ?>
            <td><?php echo htmlspecialchars($row["servicios"]); ?></td>
            <td><?php echo number_format($row["total"], 2); ?></td>
            <td>
                <?php if ($es_admin): ?>
                    <a href="editar_cita.php?id=<?php echo $row["cita_id"]; ?>">Editar</a> |
                <?php endif; ?>
                <a href="dashboard.php?delete_id=<?php echo $row["cita_id"]; ?>" onclick="return confirm('¿Seguro que quieres eliminar esta cita?');">Eliminar</a>
            </td>
        </tr>
    <?php } ?>
</table>

<?php if ($es_barbero): ?>
    <h3>Mis ganancias</h3>
    <form method="GET">
        <label>Filtrar por mes:</label>
        <input type="month" name="mes">
        <button type="submit">Ver</button>
    </form>
    <table border="1" cellpadding="5">
        <tr><th>Total Ganado ($)</th></tr>
        <?php
        if (isset($_GET["mes"])) {
            $mes = $_GET["mes"];
            $stmt = $conn->prepare("
                SELECT SUM(s.precio) AS total_ganancias
                FROM citas c
                JOIN cita_servicio cs ON c.id = cs.cita_id
                JOIN servicios s ON cs.servicio_id = s.id
                WHERE c.barbero_id = ? AND DATE_FORMAT(c.fecha, '%Y-%m') = ?
            ");
            $stmt->bind_param("is", $_SESSION["user_id"], $mes);
        } else {
            $stmt = $conn->prepare("
                SELECT SUM(s.precio) AS total_ganancias
                FROM citas c
                JOIN cita_servicio cs ON c.id = cs.cita_id
                JOIN servicios s ON cs.servicio_id = s.id
                WHERE c.barbero_id = ?
            ");
            $stmt->bind_param("i", $_SESSION["user_id"]);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $g = $res->fetch_assoc();
        $total_barbero = $g["total_ganancias"] ?? 0;
        ?>
        <tr><td><?php echo number_format($total_barbero, 2); ?></td></tr>
    </table>
<?php endif; ?>

<?php if ($es_admin): ?>
    <h3>Ganancias por barbero</h3>
    <form method="GET">
        <label>Filtrar por mes:</label>
        <input type="month" name="mes_admin">
        <button type="submit">Ver</button>
    </form>
    <table border="1" cellpadding="5">
        <tr>
            <th>Barbero</th>
            <th>Total Ganado ($)</th>
        </tr>
        <?php
        if (isset($_GET["mes_admin"])) {
            $mes_admin = $_GET["mes_admin"];
            $stmt = $conn->prepare("
                SELECT b.nombre AS barbero, SUM(s.precio) AS total_ganancias
                FROM citas c
                JOIN cita_servicio cs ON c.id = cs.cita_id
                JOIN servicios s ON cs.servicio_id = s.id
                JOIN usuario b ON c.barbero_id = b.user_id
                WHERE DATE_FORMAT(c.fecha, '%Y-%m') = ?
                GROUP BY b.user_id
            ");
            $stmt->bind_param("s", $mes_admin);
        } else {
            $stmt = $conn->prepare("
                SELECT b.nombre AS barbero, SUM(s.precio) AS total_ganancias
                FROM citas c
                JOIN cita_servicio cs ON c.id = cs.cita_id
                JOIN servicios s ON cs.servicio_id = s.id
                JOIN usuario b ON c.barbero_id = b.user_id
                GROUP BY b.user_id
            ");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $total_general = 0;
        while ($row = $res->fetch_assoc()) {
            $total_general += $row["total_ganancias"];
            echo "<tr><td>" . htmlspecialchars($row["barbero"]) . "</td><td>" . number_format($row["total_ganancias"], 2) . "</td></tr>";
        }
        ?>
        <tr><th>Total General</th><th><?php echo number_format($total_general, 2); ?></th></tr>
    </table>
<?php endif; ?>
