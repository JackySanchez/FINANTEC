<?php
// ======================================================================
// login.php - Versión Unificada de Login y Registro (FINANTEC)
// Usa la misma página para ambas acciones POST.
// ======================================================================

// Iniciar sesión (necesario para la redirección después del login)
session_start();

// --- VERIFICACIÓN DE AUTENTICACIÓN ---
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    header('Location: index.php');
    exit();
}

$mensaje_error = '';
$mensaje_exito = '';
$usuarios_file = 'usuarios.json';
$input_usuario = '';
// No inicializamos $input_password, ya que no se debe mantener en el formulario.


// --- FUNCIONES NECESARIAS (Reemplazo de Estructura.php para este archivo) ---

// Simulación de log_action si no está en Estructura.php
function log_action($message) {
    $log_file = 'acciones.log';
    $time = date('Y-m-d H:i:s');
    // fwrite(fopen($log_file, 'a'), "[$time] $message" . PHP_EOL); 
}

/** Carga los usuarios (con manejo de creación por defecto) */
function cargar_usuarios() {
    global $usuarios_file;
    if (!file_exists($usuarios_file)) {
        // Crear el archivo con un usuario por defecto hasheado
        $default_users = ['valeria' => password_hash('12345678', PASSWORD_DEFAULT)];
        guardar_usuarios($default_users);
        return $default_users;
    }
    $json_data = @file_get_contents($usuarios_file);
    return json_decode($json_data, true) ?: [];
}

/** Guarda los usuarios */
function guardar_usuarios(array $usuarios) {
    global $usuarios_file;
    file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT));
}

// ----------------------------------------------------------------------
// --- LÓGICA PRINCIPAL ---
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuarios = cargar_usuarios();
    $action = $_POST['action'] ?? 'login';
    
    // Obtener y sanitizar entradas
    // Usamos filter_input para mejor seguridad
    $input_usuario = strtolower(trim(filter_input(INPUT_POST, 'usuario') ?? ''));
    $input_password = filter_input(INPUT_POST, 'password');

    if (empty($input_usuario) || empty($input_password)) {
         $mensaje_error = 'El usuario y la contraseña no pueden estar vacíos.';
    }

    // ===================================
    // 1. LÓGICA DE REGISTRO
    // ===================================
    elseif ($action === 'register') {
        if (isset($usuarios[$input_usuario])) {
            $mensaje_error = 'El nombre de usuario ya existe.';
        } elseif (strlen($input_password) < 8) {
             $mensaje_error = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            // Cifra la contraseña antes de guardarla (CRÍTICO)
            $usuarios[$input_usuario] = password_hash($input_password, PASSWORD_DEFAULT);
            guardar_usuarios($usuarios);
            $mensaje_exito = "🎉 ¡Registro exitoso! Ahora puedes iniciar sesión como **$input_usuario**.";
            log_action("NUEVO USUARIO REGISTRADO: " . $input_usuario);
            $input_usuario = ''; // Limpiar el campo usuario tras éxito
        }
    } 
    
    // ===================================
    // 2. LÓGICA DE LOGIN
    // ===================================
    elseif ($action === 'login') {
        // Verificar contraseña hasheada (CRÍTICO)
        if (isset($usuarios[$input_usuario]) && password_verify($input_password, $usuarios[$input_usuario])) {
            // Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true); 
            
            $_SESSION['autenticado'] = true;
            $_SESSION['usuario'] = $input_usuario;
            log_action("INICIO DE SESIÓN EXITOSO: " . $input_usuario);
            
            header('Location: index.php');
            exit();
        } else {
            $mensaje_error = '❌ Usuario o contraseña incorrectos.';
            log_action("INICIO DE SESIÓN FALLIDO para usuario: " . $input_usuario);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FINANTEC - Acceso</title>
    <link rel="stylesheet" href="style.css"> 
    
    <style>
        /* Estilos Override para Login */
        body {
            /* Fondo oscuro para contraste, ajustado a tu imagen */
            background-color: #0A2540 !important; 
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            /* Eliminamos el gradiente de style.css para usar el fondo sólido oscuro */
        }
        
        .login-container {
            width: 100%;
            max-width: 380px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            text-align: center;
        }
        
        .login-container h2 {
            color: #0057A5;
            font-size: 2em;
            margin-bottom: 30px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        /* Campos de entrada con estilos de FINANTEC */
        .login-container input[type="text"], 
        .login-container input[type="password"] {
            width: calc(100% - 24px); /* Ajuste del padding */
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #B0BEC5;
            border-radius: 8px;
            font-size: 15px;
            background-color: #F9FBFD;
            box-sizing: border-box;
        }

        /* Grupo de botones con separación y estilos específicos */
        .button-group { 
            display: flex; 
            justify-content: space-between; 
            gap: 10px; 
            margin-top: 25px;
        }
        
        .button-group button { 
            padding: 12px 0; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 50%;
            font-weight: bold;
            transition: 0.2s ease;
            text-transform: uppercase;
        }
        
        /* Botón Entrar (Login) - Azul Corporativo */
        .btn-login { 
            background-color: #0057A5; 
            color: white; 
            box-shadow: 0 4px #004585;
        }
        .btn-login:hover {
            background-color: #004585;
            transform: translateY(1px);
            box-shadow: 0 3px #003060;
        }

        /* Botón Registrar - Naranja Corporativo */
        .btn-register { 
            background-color: #FF9800; 
            color: white; 
            box-shadow: 0 4px #F57C00;
        }
        .btn-register:hover {
            background-color: #F57C00;
            transform: translateY(1px);
            box-shadow: 0 3px #D96F00;
        }

        /* Mensajes de estado (replicando estilos de style.css) */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: left;
        }
        .error-message {
            background-color: #FFCDD2; /* Rojo claro */
            color: #C62828; /* Rojo oscuro */
            border-left: 6px solid #C62828;
        }
        .success-message {
            background-color: #C8E6C9; /* Verde claro */
            color: #2E7D32; /* Verde oscuro */
            border-left: 6px solid #2E7D32;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>FINANTEC</h2>
        
        <?php if ($mensaje_error): ?>
            <div class="message error-message">
                <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensaje_exito): ?>
            <div class="message success-message">
                <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" 
                   name="usuario" 
                   placeholder="Usuario" 
                   required 
                   value="<?php echo htmlspecialchars($input_usuario); ?>">
                   
            <input type="password" 
                   name="password" 
                   placeholder="Contraseña" 
                   required>
                   
            <div class="button-group">
                <button type="submit" name="action" value="login" class="btn-login">Entrar</button>
                
                <button type="submit" name="action" value="register" class="btn-register">Registrar</button>
            </div>
        </form>
    </div>
</body>
</html>