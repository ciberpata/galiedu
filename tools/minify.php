<?php
// tools/minify.php
header('Content-Type: text/html; charset=utf-8');

$jsFile = '../assets/js/app.js';
$minFile = '../assets/js/app.min.js';

if (file_exists($jsFile)) {
    // 1. Leemos el original
    $content = file_get_contents($jsFile);
    
    // --- FASE 1: MINIFICACIÓN (Reducir tamaño) ---
    
    // Quitar comentarios de bloque /* ... */
    $content = preg_replace('#/\*.*?\*/#s', '', $content);
    // Quitar comentarios de línea // ... (asegurando no romper URLs http://)
    $content = preg_replace('#(?<!:)//.*#', '', $content);
    // Quitar tabulaciones y saltos de línea
    $content = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $content);
    // Colapsar espacios múltiples
    $content = preg_replace('/\s+/', ' ', $content);
    // Quitar espacios alrededor de símbolos
    $minified = preg_replace('/\s*([\{\}\=\+\-\*\/\(\)\,\;\:\|])\s*/', '$1', $content);
    
    $minified = trim($minified);

    // --- FASE 2: OFUSCACIÓN (Ocultar lógica) ---
    
    // Codificamos el código JS en Base64
    $b64 = base64_encode($minified);
    
    // Creamos un "cargador" que descodifica y ejecuta el código en el navegador.
    // Usamos una técnica compatible con emojis (UTF-8).
    $obfuscated = "eval(decodeURIComponent(escape(window.atob('{$b64}'))));";

    // --- GUARDADO ---
    
    if (file_put_contents($minFile, $obfuscated, LOCK_EX)) {
        echo "<div style='font-family:sans-serif; padding:20px; text-align:center;'>";
        echo "<h1 style='color:green;'>¡MINIFICADO Y OFUSCADO!</h1>";
        echo "<p>El archivo <b>app.min.js</b> ha sido generado.</p>";
        echo "<hr>";
        echo "<p><b>Peso original:</b> " . filesize($jsFile) . " bytes</p>";
        echo "<p><b>Peso final:</b> " . filesize($minFile) . " bytes</p>";
        echo "<p><i>El código ahora es ilegible para humanos pero funcional para el navegador.</i></p>";
        echo "<a href='../index.php' style='display:inline-block; padding:10px 20px; background:#333; color:white; text-decoration:none; border-radius:5px;'>Volver al Inicio</a>";
        echo "</div>";
    } else {
        echo "<h1>ERROR</h1><p>No se pudo escribir en el servidor. Revisa permisos.</p>";
    }
} else {
    echo "<h1>ERROR</h1><p>No encuentro el archivo app.js en assets/js/</p>";
}
?>