<?php
include "db.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Verificar rol
$stmt = $conn->prepare("SELECT rol FROM usuario WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$rol = $result->fetch_assoc()["rol"] ?? "usuario";

if ($rol !== "admin") {
    header("Location: dashboard.php");
    exit(); // Solo admin puede editar
}

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit();
}

$cita_id = intval($_GET["id"]);

// Obtener datos de la cita
$stmt = $conn->prepare("SELECT fecha, hora FROM citas WHERE id = ?");
$stmt->bind_param("i", $cita_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$cita = $result->fetch_assoc();

// Servicios disponibles
$servicios_disponibles = $conn->query("SELECT * FROM servicios");

// Servicios actuales
$stmt2 = $conn->prepare("SELECT servicio_id FROM cita_servicio WHERE cita_id = ?");
$stmt2->bind_param("i", $cita_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$servicios_seleccionados = [];
while ($row = $result2->fetch_assoc()) {
    $servicios_seleccionados[] = $row["servicio_id"];
}

// Guardar cambios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fecha = $_POST["fecha"];
    $hora = $_POST["hora"];
    $servicios = $_POST["servicios"] ?? [];

    if (count($servicios) == 0) {
        $error = "Debes seleccionar al menos un servicio.";
    } else {
        $stmt = $conn->prepare("UPDATE citas SET fecha = ?, hora = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fecha, $hora, $cita_id);
        $stmt->execute();

        $stmt_del = $conn->prepare("DELETE FROM cita_servicio WHERE cita_id = ?");
        $stmt_del->bind_param("i", $cita_id);
        $stmt_del->execute();

        $stmt_ins = $conn->prepare("INSERT INTO cita_servicio (cita_id, servicio_id) VALUES (?, ?)");
        foreach ($servicios as $servicio_id) {
            $sid = intval($servicio_id);
            $stmt_ins->bind_param("ii", $cita_id, $sid);
            $stmt_ins->execute();
        }

        header("Location: dashboard.php");
        exit();
    }
}
?>

<h2>Editar cita (solo admin)</h2>
<a href="dashboard.php">Volver</a>

<?php if (isset($error)) : ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST">
    <label>Fecha:</label><br>
    <input type="date" name="fecha" value="<?= htmlspecialchars($cita['fecha']) ?>" required><br><br>

    <label>Hora:</label><br>
    <input type="time" name="hora" value="<?= htmlspecialchars($cita['hora']) ?>" required><br><br>

    <label>Servicios:</label><br>
    <?php while ($serv = $servicios_disponibles->fetch_assoc()) { ?>
        <label>
            <?= htmlspecialchars($serv['nombre']) ?> - $<?= number_format($serv['precio'], 2) ?>
            <input type="checkbox" name="servicios[]" value="<?= $serv['id'] ?>"
                <?= in_array($serv['id'], $servicios_seleccionados) ? 'checked' : '' ?>>
        </label><br>
    <?php } ?>
    <br>
    <button type="submit">Guardar cambios</button>
</form>
