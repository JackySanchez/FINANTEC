<?php
// ======================================================================
//  creacion_reportes.php
//  Página encargada de capturar la información para generar reportes PDF
// ======================================================================


// ======================================================================
// 1. VERIFICACIÓN DE AUTENTICACIÓN
// ======================================================================
session_start();

// Si el usuario NO está autenticado, se redirige obligatoriamente al login.
// Esto evita que personas entren directamente a esta vista sin permiso.
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header('Location: login.php');
    exit();
}


// ======================================================================
// 2. INCLUSIÓN DE ARCHIVOS EXTERNOS
// ======================================================================
require_once "Estructura.php";
// Aquí se asume que Estructura.php contiene clases o funciones
// compartidas por el sistema.


// ======================================================================
// 3. FUNCIONES AUXILIARES
// ======================================================================

/**
 * Verifica que la carpeta /pdfs exista; si no, la crea.
 * Esta carpeta se usa para almacenar PDFs generados.
 */
function ensure_pdfs_folder() {
    $dir = __DIR__ . '/pdfs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Realiza un pequeño resumen estadístico de un arreglo numérico.
 * - count: cantidad
 * - sum: suma de elementos
 * - avg: promedio
 */
function resumen_arreglo_numerico(array $arr) {
    $count = count($arr);
    $sum   = array_sum($arr);
    $avg   = $count ? ($sum / $count) : 0;

    return ['count' => $count, 'sum' => $sum, 'avg' => $avg];
}

/**
 * Inserta un elemento en un arreglo asociativo de forma segura.
 */
function array_insert_assoc(array &$arr, $key, $value) {
    $arr[$key] = $value;
    return $arr;
}

/**
 * Valida un conjunto de campos requeridos.
 * Devuelve una lista de los que falten.
 */
function validar_campos_requeridos(array $arr, array $required) {
    $missing = [];

    foreach ($required as $k) {
        if (!isset($arr[$k]) || trim((string)$arr[$k]) === '') {
            $missing[] = $k;
        }
    }
    return $missing;
}


// ======================================================================
// 4. INICIO DEL DOCUMENTO HTML
// ======================================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Finantec - Creación de Reporte</title>

    <!-- Hoja de estilos principal -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Incluye el navbar del sistema
include 'navbar.php';
?>


<!-- ================================================================== -->
<!-- FORMULARIO PRINCIPAL DE CAPTURA DE DATOS -->
<!-- ================================================================== -->

<div class="container">
    <div class="card">

        <h2>Creación de Reporte</h2>
        <p class="small">
            Ingrese los valores. Puede usar coma o punto como separador decimal;
            el sistema aplicará una máscara automática al escribir.
        </p>

        <!--
            El formulario envía los datos a generar_pdf.php.
            target="_blank" hace que la vista previa se abra en otra pestaña.
        -->
        <form method="post" action="generar_pdf.php" id="reportForm" target="_blank">

            <div class="form-grid">

                <!-- =================== DATOS GENERALES =================== -->

                <div>
                    <label for="nombre_empresa">Nombre de la Empresa</label>
                    <input type="text" id="nombre_empresa" name="nombre_empresa"
                           value="FINANTEC S.A." required>
                </div>

                <div>
                    <label for="fecha_reporte">Fecha del Reporte</label>
                    <input type="text" id="fecha_reporte" name="fecha_reporte"
                           value="A DICIEMBRE 31, 2024" required>
                </div>

                <!-- ==================== MATERIA PRIMA ===================== -->

                <div class="full"><h3>Materia Prima</h3></div>

                <div>
                    <label for="inv_ini_mp">Inventario Inicial Materia Prima</label>
                    <input type="text" id="inv_ini_mp" name="inv_ini_mp"
                           value="10.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="compras_mp">Compras de Materia Prima</label>
                    <input type="text" id="compras_mp" name="compras_mp"
                           value="50.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="gastos_compra_mp">Gastos de Compra</label>
                    <input type="text" id="gastos_compra_mp" name="gastos_compra_mp"
                           value="5.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="inv_final_mp">Inventario Final Materia Prima</label>
                    <input type="text" id="inv_final_mp" name="inv_final_mp"
                           value="15.000,00" inputmode="decimal">
                </div>

                <!-- ================= MANO DE OBRA =================== -->

                <div class="full"><h3>Mano de obra y costos</h3></div>

                <div>
                    <label for="mano_obra_directa">Mano de Obra Directa</label>
                    <input type="text" id="mano_obra_directa" name="mano_obra_directa"
                           value="25.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="costos_indirectos_fab">Costos Indirectos de Fabricación</label>
                    <input type="text" id="costos_indirectos_fab" name="costos_indirectos_fab"
                           value="10.000,00" inputmode="decimal">
                </div>

                <!-- ================= INVENTARIOS =================== -->

                <div class="full"><h3>Inventarios de producción</h3></div>

                <div>
                    <label for="inv_ini_pp">Inventario Inicial Productos en Proceso</label>
                    <input type="text" id="inv_ini_pp" name="inv_ini_pp"
                           value="8.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="inv_final_pp">Inventario Final Productos en Proceso</label>
                    <input type="text" id="inv_final_pp" name="inv_final_pp"
                           value="12.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="inv_ini_pt">Inventario Inicial Productos Terminados</label>
                    <input type="text" id="inv_ini_pt" name="inv_ini_pt"
                           value="18.000,00" inputmode="decimal">
                </div>

                <div>
                    <label for="inv_final_pt">Inventario Final Productos Terminados</label>
                    <input type="text" id="inv_final_pt" name="inv_final_pt"
                           value="10.000,00" inputmode="decimal">
                </div>

            </div> <!-- FIN GRID -->



            <!-- ==================== BOTONES ==================== -->

            <div style="margin-top:14px;">

                <!-- Vista previa HTML -->
                <button class="button btn-primary"
                        type="submit"
                        name="action"
                        value="generate_html">
                    Ver en navegador 
                </button>

                <!-- Guardar archivo -->
                <button class="button btn-ghost"
                        type="submit"
                        name="action"
                        value="save_html">
                    Guardar 
                </button>

                <!-- Botón limpiar -->
                <button class="button"
                        type="button"
                        onclick="limpiarCampos()"
                        style="background:#ef4444;color:#fff;border-radius:8px;">
                    Limpiar
                </button>

            </div>
        </form>
    </div>
</div>


<!-- ================================================================== -->
<!-- SCRIPTS DE FORMATEO Y UTILIDADES -->
<!-- ================================================================== -->

<script>
/**
 * Aplica una máscara de formato numérico en tiempo real.
 * Uso permitido:
 *   1234.56  ó  1,234.56  ó  1.234,56
 * Resultado siempre estandarizado:
 *   1.234,56
 */
function formatNumberInput(el) {
    let v = el.value;
    if (!v) return;

    // Elimina caracteres no permitidos
    v = v.replace(/[^\d,.-]/g, '');

    const lastComma = v.lastIndexOf(',');
    const lastDot   = v.lastIndexOf('.');

    // Determina cuál es el separador decimal
    let decimalSep = (lastComma > lastDot) ? ',' : '.';

    let parts   = v.split(decimalSep);
    let intPart = parts[0].replace(/[.,]/g, '');
    let decPart = parts[1] || '';

    // Agregar puntos para miles
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (decPart.length > 0) {
        decPart = decPart.substring(0, 2); // máximo 2 decimales
        el.value = intPart + ',' + decPart;
    } else {
        el.value = intPart;
    }
}

// Aplica la máscara a todos los campos decimales
document.querySelectorAll('input[inputmode="decimal"]').forEach(el => {
    el.addEventListener('input', () => formatNumberInput(el));
});


/**
 * Restablece el formulario a valores limpios y predeterminados.
 */
function limpiarCampos() {

    // Restaurar textos principales
    document.getElementById('nombre_empresa').value = "FINANTEC S.A.";
    document.getElementById('fecha_reporte').value = "A DICIEMBRE 31, 2024";

    // Lista de campos numéricos
    const numericInputs = [
        'inv_ini_mp', 'compras_mp', 'gastos_compra_mp', 'inv_final_mp',
        'mano_obra_directa', 'costos_indirectos_fab',
        'inv_ini_pp', 'inv_final_pp', 'inv_ini_pt', 'inv_final_pt'
    ];

    // Se limpian todos los campos numéricos
    numericInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });

    // Enfoca el primer campo para mejorar la experiencia del usuario
    document.getElementById('nombre_empresa').focus();
}

</script>

</body>
</html>
