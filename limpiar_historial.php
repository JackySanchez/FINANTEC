<?php
// limpiar_historial.php
// Elimina la cola (queue) y la pila (stack) FINANTEC

session_start();

// =================================================================
// 1. 🛡️ VERIFICACIÓN DE AUTENTICACIÓN (Seguridad)
// =================================================================
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

// =================================================================
// 2. ⚙️ CONFIGURACIÓN Y RUTAS ABSOLUTAS
// =================================================================
$dir_name = "pdfs/"; // Nombre de la carpeta
$dir_server = __DIR__ . '/' . $dir_name; // Ruta ABSOLUTA en el servidor
$queueFile = $dir_server . "report_queue.json";
$stackFile = $dir_server . "report_stack.json";

// Si no existe el directorio, lo creamos (usando la ruta absoluta)
if (!is_dir($dir_server)) {
    // Usamos permisos 0755
    mkdir($dir_server, 0755, true); 
}

// =================================================================
// 3. 🧹 LIMPIEZA DE ARCHIVOS JSON
// =================================================================

$mensaje = "Historial y cola limpiados correctamente.";
$limpiado = false;

// Vaciar Pila (Historial de reportes abiertos)
if (file_put_contents($stackFile, json_encode([]))) {
    $limpiado = true;
}

// Vaciar Cola (Reportes pendientes)
if (file_put_contents($queueFile, json_encode([]))) {
    $limpiado = true;
}

if (!$limpiado) {
    $mensaje = "Advertencia: Los archivos de historial JSON no existían o no se pudieron escribir.";
}

// =================================================================
// 4. ↩️ REDIRECCIÓN
// =================================================================
header("Location: descargas.php?msg=" . urlencode($mensaje));
exit;
?>