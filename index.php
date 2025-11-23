<?php
// =================================================================
// index.php - Plataforma de Reportes y Gestión Financiera FINANTEC
// =================================================================

// 1. INICIAMOS LA SESIÓN PRIMERO (¡CRÍTICO!)
session_start();

// 2. Incluimos Estructura.php para funciones, clases y la verificación de seguridad.
require_once "Estructura.php";


// Lógica de Logout (Cerrar Sesión)
if (isset($_GET['logout'])) {
    // ⚠️ Buena práctica: Agregar log_action aquí si existe en Estructura.php
    if (function_exists('log_action')) {
        log_action("CIERRE DE SESIÓN de usuario: " . ($_SESSION['usuario'] ?? 'desconocido'));
    }
    
    $_SESSION = array(); // Limpia todas las variables de sesión
    session_destroy();
    header('Location: login.php');
    exit();
}
// =================================================================

// Manejo de error para evitar un Fatal Error si Estructura.php no es perfecto
try {
    // ⚠️ Se asume que Estructura.php tiene estas clases
    $colaReportesPendientes = new ColaReportes();
    $historialNavegacion = new PilaHistorial();
} catch (Throwable $e) {
    // ----------------------------------------------------------------------------------
    // CORRECCIÓN/ROBUSTEZ: Si falla, usamos objetos dummy.
    // Usamos una clase anónima para el objeto dummy para PHP 7.4 y anteriores
    // (ya que la sintaxis fn() => [] en un objeto genérico puede no ser compatible).
    // ----------------------------------------------------------------------------------
    $colaReportesPendientes = new class {
        public function obtenerTodos() {
            return []; // Siempre devuelve un array vacío en caso de error
        }
    };
    $historialNavegacion = new class {
        public function push($item) {}
    };
    error_log("FINANTEC ERROR: Fallo al inicializar clases principales (ColaReportes o PilaHistorial). Usando objetos dummy.");
}


// ============================================================
// FINANTEC – Registro de navegación (PILA)
// ============================================================
$historialNavegacion->push(basename(__FILE__)); 

$usuario_actual = htmlspecialchars($_SESSION['usuario'] ?? 'FINANTEC');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="FINANTEC es una empresa dedicada al análisis financiero, creación de reportes contables y soluciones tecnológicas para gestión administrativa y de costos.">
    <meta name="keywords" content="finanzas, reportes, contabilidad, gestión de costos, software financiero, FINANTEC">
    <meta name="author" content="FINANTEC Soluciones Tecnológicas S.A. de C.V.">

    <title>FINANTEC | Plataforma de Reportes y Gestión Financiera</title>

    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos en línea para Footer, mantenidos de tu versión */
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        
        /* Ajuste de botón para ser consistente con button-action del navbar/style.css */
        .button-action {
            display: inline-block;
            /* Estos estilos serán sobrescritos por style.css si se carga correctamente,
            pero los mantenemos aquí por si acaso, usando la paleta FINANTEC */
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            background-color: #FF9800; /* Naranja FINANTEC */ 
            color: #0D1B2A; /* Azul oscuro para texto */
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .footer-finantec {
            background-color: #111827;
            color: #d1d5db; 
            padding: 40px 20px;
            margin-top: 40px;
            font-size: 14px;
            border-top: 3px solid #0057A5; /* Azul corporativo */
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-column h4 {
            color: #0057A5; /* Azul corporativo */ 
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .footer-column a {
            display: block;
            color: #9ca3af; 
            margin-bottom: 8px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-column a:hover {
            color: #FF9800; /* Naranja corporativo al pasar el mouse */
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 20px auto 0;
            padding-top: 20px;
            border-top: 1px solid #374151;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .valores-list { list-style: disc inside; padding-left: 20px; }
    </style>
</head>
<body>

    <?php 
    // Incluir el navbar.php para la navegación
    include 'navbar.php'; 
    ?>
    
    <div class="container">

<?php
          // -------------------------------------------------------------------
          // SOLUCIÓN DEFINITIVA: Verificación de objeto y método antes de llamar
          // -------------------------------------------------------------------
          $reportes = []; // Inicializamos como array vacío para seguridad
          
          // CRÍTICO: Solo llamamos al método si $colaReportesPendientes es un objeto 
          // Y tiene el método obtenerTodos(). Si falla, $reportes se queda como []
          if (is_object($colaReportesPendientes) && method_exists($colaReportesPendientes, 'obtenerTodos')) {
              $reportes = $colaReportesPendientes-> obtenerTodos();
          }
          
          // Contamos de forma segura: Si $reportes no es un array (por algún error imprevisto),
          // usamos un array vacío para que count() funcione sin problemas.
          $reportesPendientes = count(is_array($reportes) ? $reportes : []);
          // -------------------------------------------------------------------
?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
             <h1 style="color:#0057A5; margin:0;">Bienvenido, <?= $usuario_actual ?></h1>
             
             <a href="?logout=true" class="button-action" style="background-color:#D32F2F; color:white;">
                 Cerrar Sesión
             </a>
        </div>


        <h3 style="text-align:center; color:#FF9800; margin-top:0; border-bottom: 2px solid #FF9800; padding-bottom: 10px;">
            Soluciones Tecnológicas y Financieras
        </h3>

        <p style="font-size:17px; line-height:1.7; text-align:justify; margin-top:20px;">
             <strong>FINANTEC Soluciones Tecnológicas S.A. de C.V.</strong> es una empresa especializada en el desarrollo de herramientas digitales orientadas 
             al análisis financiero, la administración de recursos y la automatización de reportes estratégicos. Nuestro 
             objetivo es proporcionar soluciones eficientes que permitan a empresas, estudiantes y profesionales optimizar 
             sus procesos contables y de gestión.
        </p>

        <hr style="margin:30px 0; border:1px solid #DDD;">

        <h2 style="color:#0057A5;">Nuestra Visión y Valores 🌟</h2>
        <div style="display:flex; gap: 40px; margin-bottom: 30px;">
            <div style="flex: 1;">
                <h3 style="color:#0057A5; border-bottom: 1px solid #DDD; padding-bottom: 5px; font-size: 20px;">Visión</h3>
                <p style="line-height:1.6;">Liderar el mercado de software financiero en Latinoamérica, siendo reconocidos por la **calidad** de nuestros reportes, la **innovación** constante y el **impacto positivo** en la eficiencia operativa de nuestros clientes.</p>
            </div>

            <div style="flex: 1;">
                <h3 style="color:#0057A5; border-bottom: 1px solid #DDD; padding-bottom: 5px; font-size: 20px;">Valores Fundamentales</h3>
                <ul class="valores-list" style="line-height:1.6;">
                    <li><strong>Integridad:</strong> Máxima transparencia y ética en el manejo de datos.</li>
                    <li><strong>Innovación:</strong> Búsqueda constante de soluciones tecnológicas avanzadas.</li>
                    <li><strong>Excelencia:</strong> Compromiso con la calidad y precisión en cada reporte.</li>
                    <li><strong>Compromiso:</strong> Orientación total a la satisfacción y éxito del cliente.</li>
                </ul>
            </div>
        </div>

        <hr style="margin:30px 0; border:1px solid #DDD;">

        <div style="text-align:center; margin-top:30px;">
            <a href="creacion_reportes.php" class="button-action" style="margin-right:10px;">
                Crear Reporte Financiero
            </a>

            <a href="descargas.php" class="button-action" style="background-color:#0277BD; color:white;">
                Archivos Descargados 
            </a>
            
        </div>

    </div>

    <footer class="footer-finantec">
        <div class="footer-grid">
            <div class="footer-column">
                <h4>FINANTEC (Matriz)</h4>
                <p>FINANTEC Soluciones Tecnológicas S.A. de C.V.</p>
                <p><strong>RFC:</strong> FST190101XYZ (Ejemplo)</p>
                <p><strong>Dirección:</strong> Av. Tecnología #450, Col. Centro, Gómez Palacio, Dgo. C.P. 35000</p>
            </div>
            
            <div class="footer-column">
                <h4>Contacto</h4>
                <p><strong>Email:</strong> <a href="mailto:contacto@finantec.com.mx" style="display:inline; color: #3b82f6;">contacto@finantec.com.mx</a></p>
                <p><strong>Teléfono:</strong> +52 871 555 1234</p>
                <p><strong>Soporte:</strong> 9:00 a.m. - 6:00 p.m. (Lunes a Viernes)</p>
            </div>
            
            <div class="footer-column">
                <h4>Enlaces Legales</h4>
                <a href="politicas_privacidad.php">Política de Privacidad</a>
                <a href="terminos_servicio.php">Términos de Servicio</a>
                <a href="licencia.php">Acuerdo de Licencia</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> FINANTEC Soluciones Tecnológicas S.A. de C.V. Todos los derechos reservados.
        </div>
    </footer>

</body>
</html>