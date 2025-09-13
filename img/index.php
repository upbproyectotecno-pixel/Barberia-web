<?php
session_start();
$logueado = isset($_SESSION["user_id"]);
include "db.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Las Vegas Barber Shop</title>
  <link rel="stylesheet" href="landing.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>

<header>
  <h1>Las Vegas Barber Shop</h1>
  <nav>
    <a href="#inicio">Inicio</a>
    <a href="#servicios">Servicios</a>
    <a href="#barberos">Nuestros Barberos</a>
    <a href="#about">Sobre Nosotros</a>
    <a href="#sedes">Nuestras Sedes</a>
    <a href="#redes">Redes Sociales</a>
    <a href="#contacto">Contacto</a>
    <?php if ($logueado): ?>
      <a href="dashboard.php">Panel</a>
      <a href="logout.php">Cerrar sesión</a>
    <?php else: ?>
      <a href="login.php">Iniciar sesión</a>
      <a href="register.php">Registrarse</a>
    <?php endif; ?>
  </nav>
</header>

<!-- HERO -->
<section id="inicio" class="hero">
  <div class="hero__overlay">
    <h2>Tu mejor estilo comienza aquí</h2>
    <p>Reserva tu cita con nuestros expertos barberos</p>
    <a class="btn btn--primary" href="<?= $logueado ? 'dashboard.php' : 'login.php' ?>">Reservar ahora</a>
  </div>
</section>

<!-- SERVICIOS (como en la imagen) -->
<section id="servicios" class="contenedor">
  <h2>Servicios</h2>
  <div class="grid">
    <div class="card">
      <img src="img/servicios/corte.jpg" alt="Corte de Cabello" />
      <h3>Corte de Cabello</h3>
      <p>$15.000</p>
      <a class="btn" href="<?= $logueado ? 'dashboard.php' : 'login.php' ?>">Agendar</a>
    </div>
    <div class="card">
      <img src="img/servicios/barba.jpg" alt="Arreglo de Barba" />
      <h3>Arreglo de Barba</h3>
      <p>$10.000</p>
      <a class="btn" href="<?= $logueado ? 'dashboard.php' : 'login.php' ?>">Agendar</a>
    </div>
    <div class="card">
      <img src="img/servicios/afeitado.jpg" alt="Afeitado Clásico" />
      <h3>Afeitado Clásico</h3>
      <p>$12.000</p>
      <a class="btn" href="<?= $logueado ? 'dashboard.php' : 'login.php' ?>">Agendar</a>
    </div>
    <div class="card">
      <img src="img/servicios/tinte.jpg" alt="Tinte" />
      <h3>Tinte</h3>
      <p>$20.000</p>
      <a class="btn" href="<?= $logueado ? 'dashboard.php' : 'login.php' ?>">Agendar</a>
    </div>
  </div>
</section>

<!-- NUESTROS BARBEROS (cards con imagen + botón) -->
<section id="barberos" class="contenedor">
  <h2>Nuestros Barberos</h2>
  <div class="grid">
    <?php
    $barberos = $conn->query("SELECT user_id, nombre FROM usuario WHERE rol='barbero'");
    while ($b = $barberos->fetch_assoc()) {
      $id = (int)$b['user_id'];
      $nombre = htmlspecialchars($b['nombre']);
      echo "
        <div class='card'>
          <img src='img/barberos/{$id}.jpg' alt='{$nombre}' />
          <h3>{$nombre}</h3>
          <a class='btn' href='".($logueado ? "dashboard.php" : "login.php")."'>Agendar con él</a>
        </div>
      ";
    }
    ?>
  </div>
</section>

<!-- SOBRE NOSOTROS -->
<section id="about" class="contenedor seccion-oscura">
  <h2>Sobre Nosotros</h2>
  <p class="texto">
    En <strong>Barber JSK</strong> combinamos técnicas clásicas y tendencias modernas
    para que salgas con un look impecable. Calidad, puntualidad y servicio al cliente son
    nuestra prioridad.
  </p>
</section>

<!-- SEDES -->
<section id="sedes" class="contenedor">
  <h2>Nuestras Sedes</h2>
  <div class="grid">
    <div class="card">
      <img src="img/sedes/centro.jpg" alt="Sede Centro" />
      <h3>Sede Centro</h3>
      <p>Calle 10 #5-20</p>
      <p>Lun–Sáb: 9:00–19:00</p>
      <a class="btn btn--link" target="_blank" href="https://maps.google.com/?q=Calle 10 #5-20">Ver mapa</a>
    </div>
    <div class="card">
      <img src="img/sedes/norte.jpg" alt="Sede Norte" />
      <h3>Sede Norte</h3>
      <p>Av. 45 #120-15</p>
      <p>Lun–Sáb: 9:00–19:00</p>
      <a class="btn btn--link" target="_blank" href="https://maps.google.com/?q=Av. 45 #120-15">Ver mapa</a>
    </div>
    <div class="card">
      <img src="img/sedes/sur.jpg" alt="Sede Sur" />
      <h3>Sede Sur</h3>
      <p>Cra. 50 #30-55</p>
      <p>Lun–Sáb: 9:00–19:00</p>
      <a class="btn btn--link" target="_blank" href="https://maps.google.com/?q=Cra. 50 #30-55">Ver mapa</a>
    </div>
  </div>
</section>

<!-- REDES SOCIALES -->
<section id="redes" class="contenedor seccion-oscura">
  <h2>Redes Sociales</h2>
  <ul class="social-list">
    <li><a target="_blank" href="https://instagram.com/tu_barberia">📸 Instagram</a></li>
    <li><a target="_blank" href="https://facebook.com/tu_barberia">📘 Facebook</a></li>
    <li><a target="_blank" href="https://tiktok.com/@tu_barberia">🎵 TikTok</a></li>
    <li><a target="_blank" href="https://wa.me/573001112233">💬 WhatsApp</a></li>
  </ul>
</section>

<!-- CONTACTO -->
<section id="contacto" class="contenedor">
  <h2>Contacto</h2>
  <div class="contacto-grid">
    <div class="contacto-card">
      <h3>Escríbenos</h3>
      <p>📧 <a href="mailto:contacto@barberia.com">contacto@barberia.com</a></p>
      <p>📞 <a href="tel:+573001112233">+57 300 111 2233</a></p>
      <p>⏰ Lun–Sáb: 9:00–19:00</p>
      <a class="btn" href="<?= $logueado ? 'dashboard.php' : 'login.php' ?>">Reservar cita</a>
    </div>
    <div class="contacto-card">
      <h3>Ubicación Principal</h3>
      <p>Calle 10 #5-20</p>
      <a class="btn btn--link" target="_blank" href="https://maps.google.com/?q=Calle 10 #5-20">Cómo llegar</a>
    </div>
  </div>
</section>

<footer>
  <p>&copy; <?=date("Y")?> Las Vegas Barber Shop — Todos los derechos reservados.</p>
</footer>

<!-- Scroll suave -->
<script>
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener("click",function(e){
    const el = document.querySelector(this.getAttribute("href"));
    if(!el) return;
    e.preventDefault();
    el.scrollIntoView({behavior:"smooth"});
  });
});
</script>
</body>
</html>
