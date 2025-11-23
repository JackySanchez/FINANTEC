<?php 
// Es CRUCIAL que ob_start() y session_start() sean las primeras líneas
ob_start();
session_start();

// 1. INCLUSIONES Y SEGURIDAD CENTRALIZADA
require_once 'Estructura.php'; // Contiene funciones y clases (y el posible SVG suelto)
date_default_timezone_set('America/Mexico_City');

// SEGURIDAD CRÍTICA: Usa la función centralizada para verificar la sesión.

// 2. RUTAS DE ARCHIVOS IMPORTANTES
$pdf_dir = __DIR__ . '/pdfs/'; // Directorio ABSOLUTO de PDFs
$pdf_dir_relative = 'pdfs/'; // Directorio RELATIVO para enlaces HTML
$csv_file = __DIR__ . '/registros_reportes.csv'; // Archivo ABSOLUTO de historial CSV
$usuario_actual = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario'); // Obtenemos el usuario

// Asegurarse de que el directorio de PDFs existe y tiene permisos (CORRECCIÓN CRÍTICA)
$archivos_pdf = [];
$error_pdf_dir = false;

if (!is_dir($pdf_dir)) {
    // Intentar crear el directorio si no existe
    if (!mkdir($pdf_dir, 0755, true)) {
        $error_pdf_dir = "Error: No se pudo crear el directorio de PDFs ({$pdf_dir}). Verifique permisos.";
    }
}

if (!$error_pdf_dir) {
    $scandir_result = scandir($pdf_dir);
    
    if ($scandir_result === false) {
        $error_pdf_dir = "Error: No se pudo leer el directorio de PDFs ({$pdf_dir}). Verifique permisos de lectura.";
    } else {
        // Lógica de filtrado original (solo si scandir tuvo éxito)
        $archivos_pdf = array_diff($scandir_result, array('.', '..'));
        $archivos_pdf = array_filter($archivos_pdf, function($file) use ($pdf_dir) {
            return is_file($pdf_dir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
        });
    }
}


// 3. LOGICA DE ELIMINACION DE PDF
if (isset($_GET['eliminar_pdf']) && is_string($_GET['eliminar_pdf'])) {
    $archivo_a_eliminar = $pdf_dir . basename($_GET['eliminar_pdf']);

    if (file_exists($archivo_a_eliminar) && is_file($archivo_a_eliminar)) {
        if (unlink($archivo_a_eliminar)) {
            // Éxito: Redirigir para eliminar el parámetro GET
            header('Location: descargas.php?status=pdf_eliminado');
            exit;
        } else {
            // Error al eliminar
            header('Location: descargas.php?status=error_permisos');
            exit;
        }
    } else {
        // Archivo no encontrado
        header('Location: descargas.php?status=error_no_existe');
        exit;
    }
}

// 4. LOGICA DE LIMPIEZA TOTAL
if (isset($_GET['limpiar_todo']) && $_GET['limpiar_todo'] === 'true') {
    // 4.1. Eliminar todos los PDFs
    $archivos_eliminados = 0;
    foreach (glob($pdf_dir . '*.pdf') as $archivo) {
        if (is_file($archivo)) {
            unlink($archivo);
            $archivos_eliminados++;
        }
    }
    
    // 4.2. Crear archivo CSV vacío (limpiar historial)
    file_put_contents($csv_file, "Fecha;Hora;Usuario;Archivo_Generado;Estado\n");

    header('Location: descargas.php?status=limpieza_completa&count=' . $archivos_eliminados);
    exit;
}

// 5. LOGICA DE LIMPIEZA SOLO CSV
if (isset($_GET['limpiar_csv']) && $_GET['limpiar_csv'] === 'true') {
    // Crear archivo CSV vacío (limpiar historial)
    file_put_contents($csv_file, "Fecha;Hora;Usuario;Archivo_Generado;Estado\n");

    header('Location: descargas.php?status=csv_limpiado');
    exit;
}

// Incluir la barra de navegación y el head
require_once 'navbar.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos y Descargas - FINANTEC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Incluir Font Awesome para los íconos (Descarga, Basura, Reporte) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Estilos Finantec (incluyendo correcciones forzadas) */
        .table-finantec th { background-color: #1f2937; color: #FF9800; border-bottom: 2px solid #374151; }
        .table-finantec td { border-bottom: 1px solid #374151; }
        .bg-finantec-dark { background-color: #1f2937; }
        .text-finantec-blue { color: #0057A5; }
        .text-finantec-orange { color: #FF9800; }
        .btn-finantec {
            display: inline-flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 0.3rem 0.6rem !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            border-radius: 0.3rem !important;
            line-height: 1.1 !important;
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-finantec svg {
            width: 1rem !important; /* 16px */
            height: 1rem !important; /* 16px */
        }

        /* REGLA DE EMERGENCIA PARA ICONOS GIGANTES FUERA DE CONTEXTO */
        /* Esta regla es vital. Si la flecha persiste, significa que la flecha es el body */
        body > svg, body > img {
            max-width: 50px !important; 
            max-height: 50px !important;
            display: none !important; /* Forzar a esconderlo */
        }
        
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        body { font-family: 'Inter', sans-serif; background-color: #f7f7f7; color: #333; }
        
        /* Estilos específicos para los botones grandes de acción */
        .action-button {
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 150px;
            height: 150px;
            border-radius: 1rem;
            text-align: center;
            color: #0D1B2A;
            font-weight: 600;
        }
        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .action-button i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        /* Estilo para el botón de "Nuevo Reporte" que tiene un color diferente */
        .btn-new-report {
            background-color: transparent;
            color: #0057A5; /* Azul Finantec */
            border: 2px solid #0057A5;
            text-align: center;
            line-height: 1.2;
            padding: 10px;
        }
        .btn-new-report:hover {
            background-color: #0057A5;
            color: white;
        }
        .btn-new-report i {
            font-size: 2rem;
        }

        /* Estilos del ícono de descarga CSV */
        .csv-download-icon {
            font-size: 3rem; 
            color: #0057A5; /* Azul oscuro */
            transition: transform 0.2s;
        }
        .csv-download-icon:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- TÍTULO DE LA SECCIÓN -->
        <h1 class="text-3xl font-bold mb-4 flex items-center text-gray-800">
            <i class="fas fa-folder-open text-finantec-orange mr-3"></i>
            Gestión de Archivos y Descargas
        </h1>
        <p class="mb-8 text-gray-600">Hola, **<?= $usuario_actual ?>**! Aquí puedes visualizar, descargar y gestionar los reportes generados.</p>

        <!-- Mensajes de Estado (alerta de éxito o error) -->
        <?php if (isset($_GET['status'])): ?>
            <div class="mb-4 p-3 rounded-lg text-sm 
                <?php 
                    if ($_GET['status'] == 'pdf_eliminado' || $_GET['status'] == 'limpieza_completa' || $_GET['status'] == 'csv_limpiado') {
                        echo 'bg-green-100 text-green-800';
                    } else {
                        echo 'bg-red-100 text-red-800';
                    }
                ?>">
                <?php
                    switch ($_GET['status']) {
                        case 'pdf_eliminado': echo "✔️ Reporte PDF eliminado correctamente."; break;
                        case 'limpieza_completa': echo "🗑️ Limpieza completa. Se eliminaron " . (isset($_GET['count']) ? htmlspecialchars($_GET['count']) : 'X') . " PDF y se limpió el historial CSV."; break;
                        case 'csv_limpiado': echo "📋 Historial CSV limpiado correctamente."; break;
                        case 'error_permisos': echo "❌ Error: No se pudo eliminar el archivo. Problema de permisos."; break;
                        case 'error_no_existe': echo "❌ Error: El archivo especificado no existe."; break;
                        default: echo "Mensaje de estado desconocido.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Mensaje de Error de Directorio (CRÍTICO) -->
        <?php if ($error_pdf_dir): ?>
            <div class="mb-4 p-3 rounded-lg text-sm bg-red-100 text-red-800 font-bold border border-red-300">
                ⚠️ <?= htmlspecialchars($error_pdf_dir) ?> La tabla de PDFs no se mostrará hasta que se solucione este problema de acceso al directorio.
            </div>
        <?php endif; ?>


        <!-- ============================================== -->
        <!-- SECCIÓN 1: ACCIONES PRINCIPALES Y PDFS -->
        <!-- ============================================== -->
        
        <h2 class="text-2xl font-semibold mb-6 text-finantec-orange">Archivos PDF de Reportes Generados</h2>

        <div class="flex flex-wrap gap-6 mb-12 items-start">
            
            <!-- Botón Generar Nuevo Reporte (Enlace) -->
            <a href="creacion_reportes.php" class="action-button btn-new-report flex-shrink-0">
                <i class="fas fa-plus-circle"></i>
                <span>Generar<br>Nuevo Reporte</span>
            </a>

            <!-- Botón Limpiar Historial CSV -->
            <a href="descargas.php?limpiar_csv=true" class="action-button bg-finantec-orange flex-shrink-0" onclick="return confirm('¿Estás seguro de que deseas limpiar SOLO el historial CSV? Esto no eliminará los archivos PDF.')">
                <i class="fas fa-file-csv"></i>
                <span>LIMPIAR HISTORIAL CSV</span>
            </a>

            <!-- Botón Limpiar TODO (Archivos y CSV) -->
            <a href="descargas.php?limpiar_todo=true" class="action-button bg-red-600 hover:bg-red-700 text-white flex-shrink-0" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de que deseas eliminar TODOS los archivos PDF generados y el historial CSV? Esta acción es irreversible.')">
                <i class="fas fa-trash-alt"></i>
                <span>LIMPIAR TODO<br>(ARCHIVOS Y CSV)</span>
            </a>

        </div>

        <!-- Tabla de Archivos PDF -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <?php if (!$error_pdf_dir && !empty($archivos_pdf)): ?>
                <table class="min-w-full divide-y divide-gray-200 table-finantec">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre del Archivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider w-32">Tamaño</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider w-40">Fecha de Creación</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider w-40">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                        <?php foreach ($archivos_pdf as $file): 
                            $full_path = $pdf_dir . $file;
                            $size = round(filesize($full_path) / 1024, 2); // Tamaño en KB
                            $date = date("d/m/Y H:i:s", filemtime($full_path)); // Fecha de modificación
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-finantec-blue"><?= htmlspecialchars($file) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= $size ?> KB</td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= $date ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center space-x-2">
                                    <!-- Botón de Descargar -->
                                    <a href="<?= $pdf_dir_relative . htmlspecialchars($file) ?>" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-semibold transition duration-150 ease-in-out">
                                        Descargar
                                    </a>
                                    <!-- Botón de Eliminar -->
                                    <a href="descargas.php?eliminar_pdf=<?= urlencode($file) ?>" 
                                       onclick="return confirm('¿Estás seguro de que deseas eliminar el archivo: <?= htmlspecialchars($file) ?>?')"
                                       class="btn-finantec bg-orange-500 hover:bg-orange-600 text-white shadow-md">
                                        ELIMINAR
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (!$error_pdf_dir): ?>
                <p class="p-6 text-gray-500 italic">No hay archivos PDF de reportes generados actualmente. Ve a "Creación de Reportes" para generar uno.</p>
            <?php endif; ?>
        </div>

    </div>
    
</body>
</html>
<?php 
ob_end_flush();
?>