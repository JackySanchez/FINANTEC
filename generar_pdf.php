<?php
// ======================================================================
// generar_pdf.php
// Procesa el formulario, realiza los cálculos y genera el reporte en PDF real usando FPDF.
// ======================================================================

// Iniciar la sesión
session_start();
date_default_timezone_set('America/Mexico_City');

// Incluir archivos necesarios.
require_once "Estructura.php";

// INCLUSIÓN DE FPDF
require('fpdf/fpdf.php');


// ----------------------------------------------------------------------
// DEFINICIÓN DE COLORES Y CONSTANTES GLOBALES
// ----------------------------------------------------------------------

// Colores definidos en el ámbito global
$ColorTitle = [13, 27, 42];  // Azul oscuro (FINANTEC-DARK)
$ColorOrange = [255, 152, 0]; // Naranja corporativo (FINANTEC)
$ColorGrey = [233, 236, 239]; // Gris claro para filas total-row

// Directorio ABSOLUTO de PDFs.
$pdfs_dir = __DIR__ . '/pdfs/'; 


// ----------------------------------------------------------------------
// FUNCIONES AUXILIARES ESPECÍFICAS
// ----------------------------------------------------------------------

/**
 * Convierte una cadena de texto con formato numérico (usando coma o punto
 * como decimal/miles) a un float estándar de PHP.
 * @param string $str La cadena de texto de entrada.
 * @return float El valor numérico.
 */
function parse_numeric_input($str) {
    if (empty($str)) return 0.0;
    
    $str = trim($str);
    $last_comma = strrpos($str, ',');
    $last_dot = strrpos($str, '.');

    if ($last_comma !== false && $last_dot !== false) {
        if ($last_comma > $last_dot) {
            $str = str_replace('.', '', $str); // Eliminar puntos (miles)
            $str = str_replace(',', '.', $str); // Convertir coma a punto a punto (decimal)
        } else {
            $str = str_replace(',', '', $str); // Eliminar comas (miles)
        }
    } else if ($last_comma !== false) {
        $str = str_replace(',', '.', $str);
    } 

    return (float)preg_replace('/[^\d\.]/', '', $str);
}


/**
 * Formatea un número float a una cadena de moneda con miles y decimales.
 * @param float $number El valor numérico.
 * @return string El valor formateado.
 */
function format_currency($number) {
    return number_format($number, 2, ',', '.');
}

/**
 * Verifica que la carpeta /pdfs exista; si no, la crea.
 * Usa la variable $pdfs_dir definida globalmente.
 * @return bool True si el directorio existe o se creó con éxito.
 */
function ensure_pdfs_folder() {
    global $pdfs_dir;
    if (!is_dir($pdfs_dir)) {
        if (!@mkdir($pdfs_dir, 0755, true)) {
             return false;
        }
    }
    return true; 
}


/**
 * Dibuja una fila con su etiqueta y valor en el PDF, aplicando estilos y colores.
 * ESTA FUNCIÓN FUE MOVIDA AQUÍ PARA EVITAR EL ERROR "Cannot redeclare function DrawRow()".
 * @param FPDF $pdf Instancia de FPDF.
 * @param string $label Etiqueta de la fila.
 * @param string $value Valor (ya formateado) de la fila.
 * @param string $style Estilo: 'normal', 'section', 'subtotal', 'total-row', 'final-total'.
 * @param array $data Datos del reporte (opcional, no usado aquí, pero mantenido por consistencia).
 */
function DrawRow(FPDF $pdf, $label, $value, $style = 'normal', $data = []) {
    // Acceder a las variables de color globales
    global $ColorTitle, $ColorOrange, $ColorGrey; 
    
    $pdf->SetX(20);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 10);
    
    $col1_width = 130;
    $col2_width = 40;
    $height = 6;
    $border = 0;
    $fill = false;
    
    if ($style === 'section') {
        // Estilo para encabezados de sección
        $pdf->SetFillColor(...$ColorTitle); 
        $pdf->SetTextColor(...$ColorOrange);
        $pdf->SetFont('Arial', 'B', 11);
        $height = 7;
        $col1_width = 170; // Ancho total
        $col2_width = 0;
        $border = 0;
        $fill = true;
        // Dibuja la celda de la sección y fuerza nueva línea (ln=1)
        $pdf->Cell($col1_width, $height, iconv('UTF-8', 'ISO-8859-1', $label), $border, 1, 'L', $fill);
        return; // Termina la función aquí
    } elseif ($style === 'subtotal') {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(50, 50, 50);
        $border = 'B'; // Borde inferior para separar
    } elseif ($style === 'total-row') {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(...$ColorGrey);
        $border = 'TB'; // Borde superior e inferior
        $fill = true;
    } elseif ($style === 'final-total') {
        $pdf->SetFont('Arial', 'BU', 11); // Subrayado para el total final
        $pdf->SetTextColor(...$ColorTitle);
        $height = 8;
        $border = 'TB'; // Borde superior e inferior
        $fill = false; 
    } else {
         // Estilo normal de fila de detalle
         $border = 'B'; 
    }

    // Columna 1 (Etiqueta). No hace salto de línea (ln=0).
    $pdf->Cell($col1_width, $height, iconv('UTF-8', 'ISO-8859-1', $label), $border, 0, 'L', $fill);
    
    // Columna 2 (Valor numérico). Fuerza salto de línea (ln=1).
    $pdf->Cell($col2_width, $height, $value, $border, 1, 'R', $fill); 
}


/**
 * Genera el documento PDF utilizando la librería FPDF.
 * @param array $data Datos de la empresa e inventarios.
 * @param float $cmpu Costo de Materia Prima Utilizada.
 * @param float $costo_primo Costo Primo.
 * @param float $costo_produccion_periodo Costo de Producción del Período.
 * @param float $costo_produccion_terminada Costo de Producción Terminada.
 * @param float $costo_ventas Costo de Ventas.
 * @param string $action 'I' (Inline/mostrar), 'D' (Descargar), o 'F' (Guardar en archivo).
 * @param string $filename Nombre del archivo.
 */
function generar_reporte_pdf($data, $cmpu, $costo_primo, $costo_produccion_periodo, $costo_produccion_terminada, $costo_ventas, $action = 'I', $filename = '') {
    
    // Función local para formatear, usando la función global
    $f = function($num) { return format_currency($num); };
    
    // Configuración del PDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(20, 15, 20);

    // --- TÍTULOS ---
    $pdf->SetY(20);
    $pdf->SetFont('Arial', 'B', 16);
    global $ColorTitle; 
    $pdf->SetTextColor(...$ColorTitle);
    
    // Usar iconv en lugar de utf8_decode (obsoleto)
    $pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1', 'Estado de Costo de Producción y Costo de Ventas'), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1', $data['nombre_empresa'] ?? 'Empresa No Definida'), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1', 'Fecha del Reporte: ' . ($data['fecha_reporte'] ?? date('d/m/Y'))), 0, 1, 'C');
    $pdf->Ln(5);
    
    
    // --- 1. MATERIA PRIMA ---
    DrawRow($pdf, '1. Materia Prima', '', 'section');
    DrawRow($pdf, 'Inventario Inicial de Materia Prima', $f($data['inv_ini_mp']), 'normal', $data);
    DrawRow($pdf, '(+) Compras Netas de Materia Prima', $f($data['compras_mp']), 'normal', $data);
    DrawRow($pdf, '(+) Gastos de Compra', $f($data['gastos_compra_mp']), 'normal', $data);
    
    $total_disponible = ($data['inv_ini_mp'] ?? 0) + ($data['compras_mp'] ?? 0) + ($data['gastos_compra_mp'] ?? 0);
    DrawRow($pdf, '(=) Total de Materia Prima Disponible', $f($total_disponible), 'subtotal', $data);
    
    DrawRow($pdf, '(-) Inventario Final de Materia Prima', $f($data['inv_final_mp']), 'normal', $data);
    DrawRow($pdf, '(=) COSTO DE MATERIA PRIMA UTILIZADA (CMPU)', $f($cmpu), 'total-row', $data);
    $pdf->Ln(2); // Espacio entre secciones

    // --- 2. COSTOS DE FABRICACIÓN ---
    DrawRow($pdf, '2. Costos de Fabricación', '', 'section');
    DrawRow($pdf, '(+) Mano de Obra Directa', $f($data['mano_obra_directa']), 'normal', $data);
    DrawRow($pdf, '(=) COSTO PRIMO', $f($costo_primo), 'subtotal', $data);
    DrawRow($pdf, '(+) Costos Indirectos de Fabricación (CIF)', $f($data['costos_indirectos_fab']), 'normal', $data);
    DrawRow($pdf, '(=) COSTO DE PRODUCCIÓN DEL PERÍODO', $f($costo_produccion_periodo), 'total-row', $data);
    $pdf->Ln(2);

    // --- 3. COSTO DE PRODUCCIÓN TERMINADA ---
    DrawRow($pdf, '3. Costo de Producción Terminada', '', 'section');
    DrawRow($pdf, '(+) Inventario Inicial de Productos en Proceso (IIPP)', $f($data['inv_ini_pp']), 'normal', $data);
    
    $costo_total_produccion = ($costo_produccion_periodo ?? 0) + ($data['inv_ini_pp'] ?? 0);
    DrawRow($pdf, '(=) Costo Total de Producción', $f($costo_total_produccion), 'subtotal', $data);
    
    DrawRow($pdf, '(-) Inventario Final de Productos en Proceso (IFPP)', $f($data['inv_final_pp']), 'normal', $data);
    DrawRow($pdf, '(=) COSTO DE PRODUCCIÓN TERMINADA (CPT)', $f($costo_produccion_terminada), 'total-row', $data);
    $pdf->Ln(2);

    // --- 4. COSTO DE VENTAS ---
    DrawRow($pdf, '4. Costo de Ventas', '', 'section');
    DrawRow($pdf, '(+) Inventario Inicial de Productos Terminados (IIPT)', $f($data['inv_ini_pt']), 'normal', $data);
    
    $total_productos_disponibles = ($costo_produccion_terminada ?? 0) + ($data['inv_ini_pt'] ?? 0);
    DrawRow($pdf, '(=) Total de Productos Terminados Disponibles para la Venta', $f($total_productos_disponibles), 'subtotal', $data);
    
    DrawRow($pdf, '(-) Inventario Final de Productos Terminados (IFPT)', $f($data['inv_final_pt']), 'normal', $data);
    DrawRow($pdf, '(=) COSTO DE VENTAS', $f($costo_ventas), 'final-total', $data);
    $pdf->Ln(5);

    // --- FOOTER NOTE ---
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Sistema');
$pdf->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1', 'Reporte generado por ' . $usuario . ' el ' . date('d/m/Y_H:i:s')), 0, 0, 'L');
$pdf->Ln(5);
    // Devolver el resultado según la acción
    if ($action === 'F') {
        $pdf->Output($filename, 'F');
    } else {
        $pdf->Output($filename, $action);
    }
}


// ----------------------------------------------------------------------
// 1. PROCESAMIENTO DE DATOS Y CÁLCULOS
// ----------------------------------------------------------------------

// ** APLICAMOS SEGURIDAD: SI NO ES POST, REDIRIGIMOS **
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si se accede directamente sin datos de formulario, redirigir
    header('Location: creacion_reportes.php');
    exit;
}

// Sanear y convertir todos los valores numéricos
$data = [];
$numeric_keys = [
    'inv_ini_mp', 'compras_mp', 'gastos_compra_mp', 'inv_final_mp',
    'mano_obra_directa', 'costos_indirectos_fab',
    'inv_ini_pp', 'inv_final_pp', 'inv_ini_pt', 'inv_final_pt'
];

foreach ($numeric_keys as $key) {
    $raw_value = trim($_POST[$key] ?? '0'); 
    $data[$key] = parse_numeric_input($raw_value);
}

// Sanear campos de texto
$data['nombre_empresa'] = htmlspecialchars(trim($_POST['nombre_empresa'] ?? 'Empresa No Definida'));
$data['fecha_reporte'] = htmlspecialchars(trim($_POST['fecha_reporte'] ?? date('d/m/Y')));


// ==========================================
// CÁLCULOS PRINCIPALES DE COSTOS
// ==========================================

// 1. Cálculo de Costo de Materia Prima Utilizada (CMPU)
$total_materia_prima_disponible = $data['inv_ini_mp'] + $data['compras_mp'] + $data['gastos_compra_mp'];
$cmpu = $total_materia_prima_disponible - $data['inv_final_mp'];

// 2. Cálculo de Costo Primo (CP)
$costo_primo = $cmpu + $data['mano_obra_directa'];

// 3. Cálculo de Costo de la Producción del Período (CPP)
$costo_produccion_periodo = $costo_primo + $data['costos_indirectos_fab'];

// 4. Cálculo de Costo de la Producción Terminada (CPT)
$costo_produccion_terminada = $costo_produccion_periodo + $data['inv_ini_pp'] - $data['inv_final_pp'];

// 5. Cálculo de Costo de Ventas (CV)
$costo_ventas = $costo_produccion_terminada + $data['inv_ini_pt'] - $data['inv_final_pt'];


// ----------------------------------------------------------------------
// 3. MANEJO DE ACCIONES
// ----------------------------------------------------------------------

$action = $_POST['action'] ?? '';
$filename_base = "reporte_" . date('Ymd_His');
$filename_pdf = $filename_base . ".pdf";
$usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Anonimo');


if ($action === 'generate_html') {
    // ACCIÓN 1: VISTA PREVIA (Muestra PDF en línea: 'I' de Inline)
    generar_reporte_pdf($data, $cmpu, $costo_primo, $costo_produccion_periodo, $costo_produccion_terminada, $costo_ventas, 'I', $filename_pdf);
    
    // Loguear la acción
    // log_reporte_to_csv($filename_pdf, 'Vista Previa (PDF)'); // Asumiendo que esta función está definida en Estructura.php

} elseif ($action === 'save_html') {
    // ACCIÓN 2: Generar, Guardar y FORZAR la Descarga
    
    // 1. Asegurar la existencia del directorio
    if (!ensure_pdfs_folder()) {
        $_SESSION['status_message'] = "❌ Error Crítico: No se pudo crear o escribir en el directorio 'pdfs/'. Revise los permisos del servidor.";
        header('Location: creacion_reportes.php');
        exit;
    }

    global $pdfs_dir;
    $final_filepath = $pdfs_dir . $filename_pdf; 

    // 2. Generar y Guardar el PDF real ('F' de File) para el historial
    generar_reporte_pdf($data, $cmpu, $costo_primo, $costo_produccion_periodo, $costo_produccion_terminada, $costo_ventas, 'F', $final_filepath);
    
    // 3. Loguear la acción de guardar
    // log_reporte_to_csv($filename_pdf, 'Generado y Guardado (PDF)'); // Asumiendo que esta función está definida en Estructura.php
    
    // 4. Agregar a la cola de reportes (si aplica)
    global $colaReportesPendientes;
    if (isset($colaReportesPendientes) && $colaReportesPendientes instanceof ColaReportes) {
        $colaReportesPendientes->enqueue(['file' => $filename_pdf, 'user' => $usuario]);
    }
    
    // 5. FORZAR LA DESCARGA ('D' de Download)
    generar_reporte_pdf($data, $cmpu, $costo_primo, $costo_produccion_periodo, $costo_produccion_terminada, $costo_ventas, 'D', $filename_pdf);
}
?>