<?php
// tools/minify.php
header('Content-Type: text/html; charset=utf-8');
$jsFile = '../assets/js/app.js';
$minFile = '../assets/js/app.min.js';

if (file_exists($jsFile)) {
    // Leemos el original asegurando que no se rompan los emojis
    $content = file_get_contents($jsFile);
    
    // 1. Quitamos comentarios de bloque y de línea
    $content = preg_replace('#/\*.*?\*/#s', '', $content);
    $content = preg_replace('#(?<!:)//.*#', '', $content);
    
    // 2. Quitamos tabulaciones y retornos de carro (lo convertimos todo a una línea)
    $content = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $content);
    
    // 3. Colapsamos múltiples espacios a uno solo
    $content = preg_replace('/\s+/', ' ', $content);
    
    // 4. Quitamos espacios alrededor de símbolos clave para comprimir al máximo
    $minified = preg_replace('/\s*([\{\}\=\+\-\*\/\(\)\,\;\:\|])\s*/', '$1', $content);

    // Guardamos con bloqueo de archivo para evitar archivos corruptos
    if (file_put_contents($minFile, trim($minified), LOCK_EX)) {
        echo "<h1>¡ÉXITO TOTAL!</h1>";
        echo "<p>El archivo <b>app.min.js</b> ahora es una sola línea gigante.</p>";
        echo "<p>Nueva marca de tiempo para caché: " . filemtime($minFile) . "</p>";
    } else {
        echo "<h1>ERROR</h1><p>No se pudo escribir en el servidor. Revisa permisos de carpeta.</p>";
    }
} else {
    echo "<h1>ERROR</h1><p>No encuentro el archivo app.js en assets/js/</p>";
}