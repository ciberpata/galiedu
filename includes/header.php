<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Configuración básica
$vista = $_GET['view'] ?? 'dashboard';
$lang = $_SESSION['lang'] ?? 'es';
$tema_db = $_SESSION['tema_pref'] ?? '210'; 

// Datos para el título e icono según la vista
$headerData = [
    'dashboard' => ['icon' => 'fa-chart-line', 'title' => __('control_panel')],
    'usuarios' => ['icon' => 'fa-users', 'title' => __('user_management')],
    'partidas' => ['icon' => 'fa-play', 'title' => __('game_management')],
    'preguntas' => ['icon' => 'fa-circle-question', 'title' => __('question_management')],
    'facturacion' => ['icon' => 'fa-file-invoice-dollar', 'title' => __('billing')],
    'perfil' => ['icon' => 'fa-user-circle', 'title' => __('my_profile')],
    'config' => ['icon' => 'fa-gears', 'title' => __('configuration')],
    'proyector' => ['icon' => 'fa-tv', 'title' => 'Proyector']
];

$currentIcon = $headerData[$vista]['icon'] ?? 'fa-folder';
$currentTitle = $headerData[$vista]['title'] ?? ucfirst($vista);
?>

<script>
    (function() {
        const dbTheme = "<?php echo $tema_db; ?>";
        const sessionTheme = sessionStorage.getItem('temp_theme_color');
        document.documentElement.style.setProperty('--hue', sessionTheme || dbTheme);
    })();
</script>

<header class="top-header">
        <canvas id="bg-canvas"></canvas>
    <div class="header-left">
        <button id="sidebarToggle" class="btn-icon mobile-only" title="Abrir Menú">
            <i class="fa-solid fa-bars"></i>
        </button>
        
        <h1 class="header-page-title">
            <i class="fa-solid <?php echo $currentIcon; ?>"></i> 
            <span><?php echo $currentTitle; ?></span>
        </h1>
    </div>

    <div class="header-actions">
        
        <div class="dropdown-wrapper">
            <button class="btn-icon btn-mini" onclick="toggleHeaderMenu('langMenu')" title="Cambiar Idioma">
                <i class="fa-solid fa-globe"></i>
            </button>
            <div id="langMenu" class="dropdown-content mini-menu">
                <a href="#" onclick="changeLanguage('es')">🇪🇸 Español</a>
                <a href="#" onclick="changeLanguage('gl')">🌾 Galego</a>
                <a href="#" onclick="changeLanguage('en')">🇬🇧 English</a>
            </div>
        </div>

        <div class="dropdown-wrapper">
            <button class="btn-icon btn-mini" onclick="toggleHeaderMenu('colorMenu')" title="Tema de Color">
                <i class="fa-solid fa-palette"></i>
            </button>
            <div id="colorMenu" class="dropdown-content color-grid">
                <button class="color-dot" style="background:#2563eb" onclick="setSessionTheme('210')"></button>
                <button class="color-dot" style="background:#7c3aed" onclick="setSessionTheme('270')"></button>
                <button class="color-dot" style="background:#16a34a" onclick="setSessionTheme('142')"></button>
                <button class="color-dot" style="background:#f59e0b" onclick="setSessionTheme('35')"></button>
                <button class="color-dot" style="background:#ef4444" onclick="setSessionTheme('0')"></button>
                <button class="color-dot" style="background:#d946ef" onclick="setSessionTheme('300')"></button>
            </div>
        </div>

        <button onclick="toggleTheme()" class="btn-icon btn-mini" title="<?php echo __('night_mode'); ?>">
            <i class="fa-solid fa-moon"></i>
        </button>

        <button onclick="toggleBackgroundEffect()" class="btn-icon btn-mini" title="<?php echo __('spectacular_background_effect'); ?>">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </button>

        <a href="logout" onclick="sessionStorage.clear();" class="btn-icon btn-mini btn-logout-custom" title="Cerrar Sesión">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>

</header>

<script>
// 1. FUNCIONES GLOBALES (Mantener fuera para asegurar funcionamiento de iconos)
window.toggleHeaderMenu = function(menuId) {
    document.querySelectorAll('.dropdown-content').forEach(menu => {
        if (menu.id !== menuId) menu.classList.remove('show');
    });
    const menu = document.getElementById(menuId);
    if (menu) menu.classList.toggle('show');
};

window.changeLanguage = function(lang) {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
};

window.setSessionTheme = function(hue) {
    document.documentElement.style.setProperty('--hue', hue);
    sessionStorage.setItem('temp_theme_color', hue);
    const colorMenu = document.getElementById('colorMenu');
    if (colorMenu) colorMenu.classList.remove('show');
};

window.toggleBackgroundEffect = function() {
    const canvas = document.getElementById('bg-canvas');
    const isHidden = localStorage.getItem('hide_bg_canvas') === 'true';
    if (isHidden) {
        localStorage.setItem('hide_bg_canvas', 'false');
        if (canvas) canvas.style.display = 'block';
    } else {
        localStorage.setItem('hide_bg_canvas', 'true');
        if (canvas) canvas.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // 2. SIDEBAR Y ESTADO INICIAL
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const canvas = document.getElementById('bg-canvas');

    if (localStorage.getItem('hide_bg_canvas') === 'true' && canvas) {
        canvas.style.display = 'none';
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });
    }

    // 3. MOTOR CANVAS - VALORES INTERMEDIOS (Visible pero elegante)
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particles = [];
        const particleCount = 60; // Valor intermedio (antes 40, original 75)

        function initCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            particles = [];
            for (let i = 0; i < particleCount; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    size: Math.random() * 2 + 0.5, // Tamaño intermedio
                    vX: (Math.random() - 0.5) * 0.3, // Velocidad intermedia (antes 0.2)
                    vY: (Math.random() - 0.5) * 0.3
                });
            }
        }

        function animate() {
            if (canvas.style.display === 'none') {
                requestAnimationFrame(animate);
                return;
            }
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const themeColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
            
            particles.forEach((p, i) => {
                p.x += p.vX; p.y += p.vY;
                if (p.x > canvas.width) p.x = 0; if (p.x < 0) p.x = canvas.width;
                if (p.y > canvas.height) p.y = 0; if (p.y < 0) p.y = canvas.height;

                ctx.fillStyle = themeColor;
                ctx.globalAlpha = 0.22; // Opacidad intermedia (antes 0.15, original 0.25)
                ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2); ctx.fill();

                for (let j = i + 1; j < particles.length; j++) {
                    const dx = p.x - particles[j].x;
                    const dy = p.y - particles[j].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 140) { // Distancia de conexión intermedia
                        ctx.strokeStyle = themeColor;
                        ctx.globalAlpha = (1 - dist / 140) * 0.12; // Brillo de líneas intermedio
                        ctx.lineWidth = 0.6;
                        ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(particles[j].x, particles[j].y); ctx.stroke();
                    }
                }
            });
            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', initCanvas);
        initCanvas(); animate();
    }
});
</script>