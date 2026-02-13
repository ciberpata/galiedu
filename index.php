<?php
// index.php

// 1. Cargamos la configuración
$settings = require 'config/settings.php';

// 2. Definimos la función de MINIFICACIÓN (Limpieza)
function minificar_agresivo_seguro($buffer) {
    if (trim($buffer) === '') return $buffer;
    $buffer = preg_replace('//', '', $buffer); 
    $buffer = preg_replace('/[ \t]+/', ' ', $buffer);        
    $buffer = preg_replace('/^[ \t]+/m', '', $buffer);       
    $buffer = preg_replace('/[ \t]+$/m', '', $buffer);       
    $buffer = preg_replace('/[\r\n]+/', "\n", $buffer);      
    return trim($buffer);
}

// 3. Definimos la función de OFUSCACIÓN TOTAL (Encriptado visual)
function ofuscar_html_extremo($buffer) {
    // Primero minificamos para ahorrar espacio en el cifrado
    $minified = minificar_agresivo_seguro($buffer);
    
    // Si no hay contenido, devolver vacío
    if (empty($minified)) return '';

    // Codificamos todo el HTML en Base64
    $encoded = base64_encode($minified);

    // Creamos el script que el navegador ejecutará para "pintar" la web
    // Usamos UTF-8 decode para respetar tildes y emojis
    $script = '<script type="text/javascript">';
    $script .= 'document.open();';
    $script .= 'document.write(decodeURIComponent(escape(window.atob("' . $encoded . '"))));';
    $script .= 'document.close();';
    $script .= '</script>';

    return $script;
}

// 4. Lógica del interruptor (Configurable desde settings.php)
if (isset($settings['minify_html']) && $settings['minify_html'] === true) {
    // CAMBIO IMPORTANTE: Usamos la función extrema en lugar de solo la segura
    ob_start("ofuscar_html_extremo");
} else {
    ob_start();
}

session_start();

// --- 1. SISTEMA DE RUTAS (ROUTER) ---
$view = $_GET['view'] ?? 'dashboard'; 

if (!isset($_GET['view'])) {
    $request_uri = strtok($_SERVER['REQUEST_URI'], '?');
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = str_replace('/index.php', '', $script_name);
    $route = str_replace($base_path, '', $request_uri);
    $clean_route = trim($route, '/');
    if ($clean_route && $clean_route !== 'index.php') {
        $view = $clean_route;
    }
}

// --- 2. ACCIONES PÚBLICAS Y LOGIN ---

if ($view === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login");
    exit();
}

if ($view === 'reset') {
    if (isset($_SESSION['user_id'])) { session_unset(); session_destroy(); session_start(); }
    require 'views/reset.php'; 
    exit();
}

if ($view === 'login') {
    if (isset($_SESSION['user_id'])) { 
        header("Location: dashboard"); 
        exit(); 
    }
    require 'login.php'; 
    exit(); // <--- IMPORTANTE: Detiene la ejecución aquí.
}

// --- 3. SEGURIDAD ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login"); 
    exit();
}

// --- 4. IDIOMAS ---
$supported_langs = ['es' => 'Español', 'gl' => 'Galego', 'en' => 'Inglés'];
$current_lang = $_SESSION['lang'] ?? 'es';

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
    $current_lang = $_GET['lang'];
    $query_params = $_GET;
    unset($query_params['lang']);
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($query_params)) $redirect_url .= '?' . http_build_query($query_params);
    header("Location: " . $redirect_url);
    exit();
} elseif (isset($_SESSION['lang']) && array_key_exists($_SESSION['lang'], $supported_langs)) {
    $current_lang = $_SESSION['lang'];
}

$trans_file = __DIR__ . "/locales/i18n.{$current_lang}.json";
$translations = file_exists($trans_file) ? json_decode(file_get_contents($trans_file), true) : [];
function __($key) { global $translations; return $translations[$key] ?? $key; }

// --- 5. RENDERIZADO DEL DASHBOARD ---
$allowed_views = ['dashboard', 'usuarios', 'partidas', 'preguntas', 'config', 'facturacion', 'perfil', 'proyector', 'auditoria', 'diagnostico', 'limpieza', 'planes'];
if ($view === 'panel-de-control') $view = 'dashboard';
if (!in_array($view, $allowed_views)) $view = 'dashboard';

if (!class_exists('Database')) include 'config/db.php';
$isCleanView = ($view === 'proyector');
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduGame - <?php echo ucfirst($view); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.3">
    <script>
        if(localStorage.getItem('theme_mode') === 'dark') document.body.classList.add('dark');
        const savedHue = sessionStorage.getItem('temp_theme_color');
        if(savedHue) document.documentElement.style.setProperty('--hue', savedHue);
    </script>
</head>
<body>
    <?php if (!$isCleanView) include 'includes/sidebar.php'; ?>

    <main class="<?php echo $isCleanView ? '' : 'main-content'; ?>">
        <?php if (!$isCleanView) include 'includes/header.php'; ?>

        <div class="content-scroll-area">
            <?php 
            $file = "views/{$view}.php";
            if (file_exists($file)) include $file;
            else echo "<div class='card'><h2>Error 404</h2><p>Vista no encontrada.</p></div>";
            ?>
        </div>
    </main>
    <script src="assets/js/app.min.js?v=<?php echo file_exists('assets/js/app.min.js') ? filemtime('assets/js/app.min.js') : time(); ?>"></script>
</body>
</html>