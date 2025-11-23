<?php
// ======================================================================
// Estructura.php
// Contiene clases de estructuras de datos (Cola, Pila) y utilidades clave
// ======================================================================

// ----------------------------------------------------------------------
// CLASE COLA (Queue) para reportes pendientes
// ----------------------------------------------------------------------
class ColaReportes {
    private $items = [];

    public function enqueue($item) {
        array_push($this->items, $item);
    }

    public function dequeue() {
        return array_shift($this->items);
    }

    public function isEmpty() {
        return empty($this->items);
    }

    public function size() {
        return count($this->items);
    }

    public function getItems() {
        return $this->items;
    }

    // ★★★ Nuevo método ★★★
    public function obtenerTodos() {
        return $this->items;
    }
}

// ----------------------------------------------------------------------
// CLASE PILA (Stack) para historial de navegación
// ----------------------------------------------------------------------
class PilaHistorial {
    private $items = [];

    public function push($item) {
        array_push($this->items, $item);
    }

    public function pop() {
        return array_pop($this->items);
    }

    public function isEmpty() {
        return empty($this->items);
    }
}

// ----------------------------------------------------------------------
// FUNCIONES DE UTILIDAD
// ----------------------------------------------------------------------

/**
 * Registra una operación de reporte en el archivo CSV de historial.
 * @param string $archivo_generado Nombre del archivo creado.
 * @param string $estado Estado de la operación (e.g., 'Generado', 'Vista Previa').
 */
function log_reporte_to_csv($archivo_generado, $estado) {
    // Definición de la ruta del archivo CSV
    $csv_file = __DIR__ . '/registros_reportes.csv';
    $usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Anonimo');
    $fecha = date("d/m/Y");
    $hora = date("H:i:s");

    // Construir la línea CSV (usando ';' como separador)
    $linea = [
        $fecha,
        $hora,
        $usuario,
        $archivo_generado,
        $estado
    ];
    $csv_line = implode(';', $linea) . "\n";

    // Escribir la cabecera si el archivo no existe o está vacío
    if (!file_exists($csv_file) || filesize($csv_file) === 0) {
        $header = "Fecha;Hora;Usuario;Archivo_Generado;Estado\n";
        file_put_contents($csv_file, $header, FILE_APPEND);
    }

    // Escribir la nueva línea al archivo CSV
    file_put_contents($csv_file, $csv_line, FILE_APPEND);
}

/**
 * Verifica que la sesión de usuario esté autenticada.
 */
function verificar_autenticacion() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// ---------- INSTANCIAS GLOBALES (Se inicializan si no existen) ----------
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($colaReportesPendientes)) $colaReportesPendientes = new ColaReportes();
if (!isset($historialNavegacion)) $historialNavegacion = new PilaHistorial();

?>