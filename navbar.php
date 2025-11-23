<?php
require_once "Estructura.php";


// ---------- INSTANCIAS GLOBALES ----------
if (!isset($colaReportesPendientes)) $colaReportesPendientes = new ColaReportes();
if (!isset($historialNavegacion)) $historialNavegacion = new PilaHistorial();


// ============================================================
// PILA FINANTEC – Registrar la visita a esta página
// ============================================================
//$historialNavegacion->push(basename(__FILE__));


// Detecta la página actual para resaltar menú activo
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>


<style>
/* ===== NAVBAR FINANTEC (Estilo Profesional) ===== */

.navbar {
    width: 100%;
    background-color: #0D1B2A; /* Azul oscuro formal */
    color: white;
    padding: 15px 0;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    font-family: 'Montserrat', sans-serif;
}

.navbar-container {
    width: 90%;
    max-width: 1200px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar-logo {
    font-size: 1.6em;
    font-weight: 700;
    color: #FF9800; /* Naranja corporativo */
    text-decoration: none;
    letter-spacing: 1px;
}

.navbar-menu {
    display: flex;
    gap: 25px;
}

.navbar-menu a {
    color: #E0E0E0;
    text-decoration: none;
    font-size: 1.05em;
    padding: 6px 12px;
    border-radius: 6px;
    transition: 0.3s;
}

.navbar-menu a:hover {
    background-color: #FF9800;
    color: #0D1B2A;
}

.navbar-menu a.activo {
    background-color: #FF9800;
    color: #0D1B2A;
    font-weight: 600;
}


/* Etiqueta de información del sistema en el navbar */
.navbar-info {
    font-size: 0.85em;
    color: #FF9800;
    margin-left: 15px;
    opacity: 0.85;
}
</style>


<div class="navbar">
    <div class="navbar-container">

        <!-- LOGO -->
        <a href="index.php" class="navbar-logo">FINANTEC</a>

        <!-- OPCIONES DE MENÚ -->
        <div class="navbar-menu">
            <a href="index.php" 
               class="<?= ($pagina_actual == 'index.php') ? 'activo' : '' ?>">
               Inicio
            </a>

            <a href="creacion_reportes.php" 
               class="<?= ($pagina_actual == 'creacion_reportes.php') ? 'activo' : '' ?>">
               Creación de Reportes
            </a>

            <a href="descargas.php" 
               class="<?= ($pagina_actual == 'descargas.php') ? 'activo' : '' ?>">
               Archivos Descargados
            </a>
        </div>

    </div>
</div>

<!-- Separador visual para evitar que el contenido quede debajo del navbar -->
<div style="height:70px;"></div>
