<?php
// play/index.php
session_start();
require_once '../config/db.php';

$frases_file = __DIR__ . '/../locales/frases.json';
$frases_json = file_exists($frases_file) ? file_get_contents($frases_file) : '{}';

// Inyección: Detectar si el alumno tiene Nick y Avatar ya configurados
$loggedUser = null;
if (isset($_SESSION['user_id'])) {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT nick, avatar_id FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $loggedUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EduGame - Jugar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        /* TEMA MODERNO - ESTILO GALIEDU */
        :root { 
            --bg-gradient: linear-gradient(135deg, #46178f 0%, #25076b 100%);
            --primary: #333; 
            --white: #ffffff;
            --correct: #66bf39;
            --wrong: #ff3355;
            --btn-shadow: 0 4px 0 rgba(0,0,0,0.2);
        }
        
        * { box-sizing: border-box; }

        body { 
            margin:0; 
            font-family:'Montserrat', sans-serif; 
            background: var(--bg-gradient);
            display:flex; 
            flex-direction:column; 
            min-height:100vh; 
            overflow-x: hidden; 
            color: var(--white); 
        }
        
        /* PANTALLAS */
        .screen { 
            display: none; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            width: 100%; 
            padding: 20px; 
            padding-bottom: 80px; /* Espacio para footer */
            animation: fadeIn 0.3s ease;
        }
        .screen.active { display: flex; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* LOGIN & INPUTS */
        .logo-title { font-size: 3rem; font-weight: 900; margin-bottom: 20px; text-shadow: 2px 2px 0 rgba(0,0,0,0.2); letter-spacing: 2px; }
        
        .login-box { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 15px; 
            width: 100%; 
            max-width: 400px; 
            text-align:center; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.2); 
            color: var(--primary);
        }
        
        /* Input PIN tipo texto para evitar spinners feos pero teclado numérico */
        input { 
            padding: 15px; 
            border-radius: 8px; 
            border: 2px solid #ddd; 
            width: 100%; 
            font-size: 1.2rem; 
            text-align: center; 
            margin-bottom: 15px; 
            font-weight: 800; 
            background: #f9fafb; 
            color: #333;
            transition: 0.2s;
        }
        input:focus { outline: none; border-color: #46178f; background: white; }
        
        .btn-play { 
            background: #333; 
            color: white; 
            border: none; 
            padding: 15px; 
            border-radius: 8px; 
            font-size: 1.1rem; 
            font-weight: 800; 
            width: 100%; 
            cursor: pointer; 
            box-shadow: 0 4px 0 #000; 
            transition: transform 0.1s; 
            margin-top: 10px; 
        }
        .btn-play:active { transform: translateY(4px); box-shadow: none; }

        /* SELECTOR IDIOMA */
        .lang-selector { margin-bottom: 20px; }
        .lang-selector select { padding: 8px 15px; border-radius: 20px; border: none; background: rgba(255,255,255,0.2); color: white; font-weight: bold; cursor: pointer; backdrop-filter: blur(5px); }
        .lang-selector select option { color: #333; }

        /* BARRA INFERIOR DEL JUGADOR */
        .player-footer {
            position: fixed; bottom: 0; left: 0; width: 100%; height: 60px;
            background: rgba(0,0,0,0.3); backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 20px; z-index: 100;
        }
        .pf-left { display: flex; align-items: center; gap: 10px; font-weight: bold; font-size: 1.1rem; }
        .pf-avatar { font-size: 1.8rem; }
        .pf-score { background: var(--white); color: #333; padding: 5px 15px; border-radius: 20px; font-weight: 800; font-size: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        /* HEADER SUPERIOR */
        .top-status {
            position: absolute; top: 15px; left: 0; width: 100%;
            display: flex; justify-content: space-between; align-items: center; padding: 0 20px;
        }
        .q-bubble { background: var(--white); color: #333; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; box-shadow: 0 2px 0 rgba(0,0,0,0.2); font-size: 1.1rem; }
        .timer-display { font-size: 1.5rem; font-weight: 900; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }

        /* FEEDBACK */
        .feedback-container { text-align: center; width: 100%; max-width: 500px; animation: popIn 0.3s; }
        @keyframes popIn { from { transform: scale(0.8); opacity:0; } to { transform: scale(1); opacity:1; } }

        .feedback-banner { background: var(--correct); color: white; padding: 25px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .feedback-banner.wrong { background: var(--wrong); }
        .fb-title { font-size: 2.5rem; font-weight: 900; margin-bottom: 10px; text-transform: uppercase; }
        .fb-icon { font-size: 5rem; display: block; margin: 10px 0; }
        
        .streak-box { background: rgba(0,0,0,0.3); padding: 15px 30px; border-radius: 15px; margin-top: 20px; display: inline-block; min-width: 200px; backdrop-filter: blur(5px); }
        .motivation-msg { margin-top: 30px; font-size: 1.3rem; font-style: italic; opacity: 0.9; font-weight: 600; }

        /* BOTONES JUEGO */
        .game-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; width: 100%; max-width: 600px; height: 50vh; margin-top: 20px; }
        .game-btn { border: none; border-radius: 10px; cursor: pointer; color: white; font-size: 1.1rem; display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow: 0 6px 0 rgba(0,0,0,0.2); transition: transform 0.1s; position: relative; padding: 10px; }
        .game-btn:active { transform: translateY(4px); box-shadow: none; }
        .shape-icon { font-size: 2.5rem; margin-bottom: 8px; }
        .btn-text { font-weight: 700; line-height: 1.2; }
        
        .bg-0 { background-color: #e21b3c; } /* Rojo */
        .bg-1 { background-color: #1368ce; } /* Azul */
        .bg-2 { background-color: #d89e00; } /* Amarillo */
        .bg-3 { background-color: #26890c; } /* Verde */

        /* AVATARES */
        .avatar-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; width:100%; max-height: 50vh; overflow-y: auto; padding: 5px; }
        .avatar-card { background: var(--white); padding: 10px; border-radius: 10px; font-size: 2.5rem; cursor: pointer; border: 4px solid transparent; transition: transform 0.2s; box-shadow: 0 4px 0 rgba(0,0,0,0.1); }
        .avatar-card:hover { transform: translateY(-2px); }
        .avatar-card.selected { border-color: #46178f; background: #e0f2fe; transform: scale(0.95); box-shadow: none; }

        /* PREGUNTA TEXTO (Opcional en móvil) */
        .mobile-q-text { background: var(--white); color: #333; padding: 15px; border-radius: 10px; font-weight: bold; margin-bottom: 15px; text-align: center; width: 100%; max-width: 600px; box-shadow: 0 4px 0 rgba(0,0,0,0.1); font-size: 1.1rem; display: none; }
        
        /* ESPERA */
        .wait-animation { font-size: 5rem; margin-bottom: 20px; animation: bounce 1s infinite alternate; }
        @keyframes bounce { from { transform: translateY(0); } to { transform: translateY(-20px); } }

        /* PANTALLA FINAL */
        #screen-end .login-box { max-width: 500px; padding: 40px; }
        .podium-row { display: flex; justify-content: space-between; padding: 15px 10px; border-bottom: 1px solid #eee; align-items: center; font-size: 1.1rem; }
        .rank-medal { font-size: 1.5rem; width: 40px; text-align: center; }

    </style>
    <script src="../assets/js/app.js"></script>
</head>
<body>

    <div id="screen-login" class="screen active">
        <div class="lang-selector">
            <select id="langSelect" onchange="changeLanguage()">
                <option value="es">🇪🇸 Español</option>
                <option value="gl">🇪🇸 Galego</option>
                <option value="en">🇬🇧 English</option>
            </select>
        </div>
        <div class="logo-title">EduGame</div>
        <div class="login-box">
            <input type="text" inputmode="numeric" pattern="[0-9]*" id="inputPin" placeholder="PIN" maxlength="6" autocomplete="off">
            
            <?php if (!$loggedUser || empty($loggedUser['nick'])): ?>
                <input type="text" id="inputNick" placeholder="Nick" maxlength="15">
            <?php else: ?>
                <div style="margin-bottom: 15px; font-weight: 800; color: var(--primary);">
                    Hola, <span id="displayLoggedNick"><?php echo htmlspecialchars($loggedUser['nick']); ?></span>
                </div>
                <input type="hidden" id="inputNick" value="<?php echo htmlspecialchars($loggedUser['nick']); ?>">
            <?php endif; ?>

            <button class="btn-play" onclick="step1Login()" id="btnEnter">Entrar</button>
        </div>
    </div>

    <div id="screen-avatar" class="screen">
        <h2 id="txtChooseAvatar">Personaliza tu Héroe</h2>
        
        <div class="avatar-preview-box">
            <div class="preview-emoji-container">
                <span id="previewAvatar">👤</span>
                <span id="previewHat" class="avatar-hat"></span>
            </div>
        </div>

        <div id="step-avatar-list">
            <p>1. Elige tu Avatar</p>
            <div class="avatar-grid" id="avatarList"></div>
        </div>

        <div id="step-hat-list" style="display:none;">
            <p>2. Añade un Accesorio</p>
            <div class="avatar-grid" id="hatList"></div>
        </div>

        <div style="margin-top:20px; display:flex; gap:10px;">
            <button class="btn-play" id="btnPrev" style="display:none; background:#666;" onclick="toggleAvatarStep(1)">Atrás</button>
            <button class="btn-play" id="btnNext" onclick="toggleAvatarStep(2)">Siguiente</button>
            <button class="btn-play" id="btnConfirmAvatar" style="display:none;" onclick="step2Avatar()">¡Listo!</button>
        </div>
    </div>

    <div id="screen-wait" class="screen">
        <div class="top-status">
            <div class="q-bubble" id="waitQNum">#</div>
        </div>
        
        <div style="text-align: center; width: 100%;">
            <div class="wait-animation">👀</div>
            <div style="font-size: 2rem; font-weight: 800; margin-bottom: 10px;" id="waitText">Ya estás dentro</div>
            <div style="font-size: 1.2rem; opacity: 0.8;" id="waitSubText">Mira la pantalla</div>
            <div id="waitPhrase" style="margin-top: 40px; font-size: 1.3rem; font-style: italic; opacity: 0.9; font-weight: 600; padding: 0 20px; min-height: 3em;"></div>
        </div>
    </div>

    <div id="screen-play" class="screen">
        <div class="top-status">
            <div class="q-bubble" id="playQNum">1</div>
            <div class="timer-display" id="timer"></div> 
        </div>
        
        <div class="mobile-q-text" id="playerQuestionText"></div>

        <div class="game-grid" id="gameButtonsContainer">
            </div>
    </div>

    <div id="screen-feedback" class="screen">
        <div class="feedback-container">
            <div class="feedback-banner" id="fbBanner">
                <div class="fb-title" id="fbTitle">Correcto</div>
                <i class="fa-solid fa-check fb-icon" id="fbIcon"></i>
            </div>
            
            <div class="streak-box">
                <div style="font-size:0.9rem; margin-bottom:5px;" id="txtStreak">Racha</div>
                <div style="font-size:2rem; font-weight:bold;">
                    <span class="streak-fire">🔥</span> <span id="fbStreak">0</span>
                </div>
            </div>

            <div class="motivation-msg" id="fbMsg">...</div>
        </div>
    </div>

    <div id="screen-end" class="screen">
        <div class="login-box" style="background: rgba(255,255,255,0.95);">
            <h1 style="color:#46178f; margin-top:0;" id="txtGameOver">Fin</h1>
            <div style="font-size:1.2rem; color:#666;" id="txtFinalScore">Puntuación Final</div>
            <div style="font-size:3.5rem; font-weight:900; color:#46178f; margin: 10px 0;" id="finalScoreDisplay">0</div>
            
            <div id="top3List" style="text-align:left; margin-top:20px; border-top:1px solid #eee; padding-top:10px;"></div>
            
            <a href="index.php" class="btn-play" style="text-decoration:none; display:block; margin-top:30px;" id="btnExit">Salir al Inicio</a>
        </div>
    </div>

    <div class="player-footer" id="playerFooter" style="display:none;">
        <div class="pf-left">
            <span class="pf-avatar" id="myAvatarDisplay">👤</span>
            <span id="myNickDisplay">Jugador</span>
        </div>
        <div class="pf-score" id="myScoreDisplay">0</div>
    </div>

<script>
    // --- 1. CONFIGURACIÓN IDIOMAS ---
    const FRASES = <?php echo $frases_json; ?>;
    
    const UI_TEXTS = {
        es: { 
            pin: "PIN de Juego", nick: "Tu Apodo", enter: "Entrar", choose: "Elige tu personaje", ready: "¡Listo!", 
            wait: "Ya estás dentro", wait_sub: "Mira la pantalla", q: "Pregunta", sent: "Respuesta Enviada", 
            wait_others: "Espera a los demás...", correct: "¡Correcto!", wrong: "Incorrecto", streak: "Racha", 
            game_over: "¡Fin de la Partida!", score: "Puntuación Final", exit: "Salir" 
        },
        gl: { 
            pin: "PIN do Xogo", nick: "O teu Apodo", enter: "Entrar", choose: "Elixe o teu personaxe", ready: "¡Listo!", 
            wait: "Xa estás dentro", wait_sub: "Mira a pantalla", q: "Pregunta", sent: "Resposta Enviada", 
            wait_others: "Agarda polos demais...", correct: "¡Correcto!", wrong: "Incorrecto", streak: "Racha", 
            game_over: "¡Fin da Partida!", score: "Puntuación Final", exit: "Saír" 
        },
        en: { 
            pin: "Game PIN", nick: "Nickname", enter: "Enter", choose: "Choose your character", ready: "Ready!", 
            wait: "You're in!", wait_sub: "See your name on screen?", q: "Question", sent: "Answer Sent", 
            wait_others: "Wait for others...", correct: "Correct!", wrong: "Incorrect", streak: "Streak", 
            game_over: "Game Over!", score: "Final Score", exit: "Exit" 
        }
    };

    let currentLang = 'es';

    function changeLanguage() {
        const sel = document.getElementById('langSelect');
        currentLang = sel.value;
        const t = UI_TEXTS[currentLang];
        
        document.getElementById('inputPin').placeholder = t.pin;
        if(document.getElementById('inputNick').type !== 'hidden'){
            document.getElementById('inputNick').placeholder = t.nick;
        }
        document.getElementById('btnEnter').innerText = t.enter;
        document.getElementById('txtChooseAvatar').innerText = t.choose;
        document.getElementById('btnConfirmAvatar').innerText = t.ready;
        document.getElementById('txtStreak').innerText = t.streak;
        document.getElementById('txtGameOver').innerText = t.game_over;
        document.getElementById('txtFinalScore').innerText = t.score;
        document.getElementById('btnExit').innerText = t.exit;
    }

    // --- 2. VARS JUEGO ---
    const SHAPES = ['▲', '◆', '●', '■']; // Define los iconos de los botones
    let mySessionId = null;
    let gamePartidaId = null;
    let lastPhase = '';
    let currentScore = 0;
    let myNick = '';

    // Variables temporales para el selector de dos pasos (Gamificación)
    let tempAvatarId = 1;
    let tempHatId = 0;

    function initGrids() {
        const aGrid = document.getElementById('avatarList');
        aGrid.innerHTML = '';
        Object.entries(AvatarManager.baseAvatars).forEach(([id, icon]) => {
            aGrid.innerHTML += `<div class="avatar-card" onclick="selectAvatar(${id}, '${icon}', this)">${icon}</div>`;
        });

        const hGrid = document.getElementById('hatList');
        hGrid.innerHTML = '';
        Object.entries(AvatarManager.hats).forEach(([id, icon]) => {
            hGrid.innerHTML += `<div class="avatar-card" onclick="selectHat(${id}, '${icon}', this)">${icon || '❌'}</div>`;
        });
    }

    function selectAvatar(id, icon, el) {
        tempAvatarId = id;
        document.getElementById('previewAvatar').innerText = icon;
        document.querySelectorAll('#avatarList .avatar-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
    }

    function selectHat(id, icon, el) {
        tempHatId = id;
        const hatEl = document.getElementById('previewHat');
        hatEl.innerText = icon;
        // Lanzar animación elegante definida en style.css
        hatEl.classList.remove('animating');
        void hatEl.offsetWidth; 
        if(id > 0) hatEl.classList.add('animating');
        
        document.querySelectorAll('#hatList .avatar-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
    }

    function toggleAvatarStep(step) {
        document.getElementById('step-avatar-list').style.display = (step === 1) ? 'block' : 'none';
        document.getElementById('step-hat-list').style.display = (step === 2) ? 'block' : 'none';
        document.getElementById('btnNext').style.display = (step === 1) ? 'block' : 'none';
        document.getElementById('btnPrev').style.display = (step === 2) ? 'block' : 'none';
        document.getElementById('btnConfirmAvatar').style.display = (step === 2) ? 'block' : 'none';
    }

    // --- 3. LÓGICA ---

    async function step1Login() {
        // Limpiamos espacios y convertimos a mayúsculas
        const pin = document.getElementById('inputPin').value.trim().toUpperCase();
        const nick = document.getElementById('inputNick').value.trim();
        if(!pin || !nick) return alert("Faltan datos");
        myNick = nick;
        try {
            // Nota: Se usa 'credentials: include' para que PHP reconozca al alumno logueado
            const res = await fetch('../api/juego.php', { 
                method: 'POST', 
                credentials: 'include', 
                body: JSON.stringify({action:'unirse', pin, nick}) 
            });
            
            const json = await res.json();
            if(json.success) { 
                mySessionId = json.id_sesion; 
                gamePartidaId = json.id_partida;
                document.getElementById('myNickDisplay').innerText = myNick;
                
                if (json.has_avatar) {
                    document.getElementById('myAvatarDisplay').innerHTML = AvatarManager.render(json.avatar_id, json.sombrero_id || 0);
                    document.getElementById('playerFooter').style.display = 'flex';
                    showWaitScreen("Ya estás dentro", "Mira la pantalla");
                    startPolling();
                } else {
                    initGrids(); // Inicializamos las rejillas dinámicas desde AvatarManager
                    showScreen('screen-avatar'); 
                }
            }
            else { alert(json.error); }
        } catch(e) { console.error(e); alert("Error de conexión"); }
    }

    async function step2Avatar() {
        document.getElementById('btnConfirmAvatar').disabled = true;
        try {
            const res = await fetch('../api/juego.php', { 
                method: 'POST', 
                body: JSON.stringify({
                    action:'seleccionar_avatar', 
                    id_sesion:mySessionId, 
                    avatar_id: tempAvatarId, 
                    sombrero_id: tempHatId 
                }) 
            });
            const json = await res.json();
            if(json.success) { 
                document.getElementById('myAvatarDisplay').innerHTML = AvatarManager.render(tempAvatarId, tempHatId);
                document.getElementById('playerFooter').style.display = 'flex'; 
                showWaitScreen(UI_TEXTS[currentLang].wait, UI_TEXTS[currentLang].wait_sub);
                startPolling(); 
            }
            else { alert(json.error); document.getElementById('btnConfirmAvatar').disabled = false; }
        } catch(e) { alert("Error"); }
    }

    function startPolling() {
        setInterval(async () => {
            if(!gamePartidaId) return;
            
            let gameData = null;

            // 1. Prioridad: Archivo JSON caché (Optimización original)
            try {
                const r = await fetch(`../temp/partida_${gamePartidaId}.json?t=${Date.now()}`);
                if(r.ok) {
                    const json = await r.json(); 
                    if(json.success) { gameData = json.data; }
                }
            } catch(e) {}

            // 2. Fallback: API directa en momentos clave (resultados)
            if (!gameData || (gameData.estado_pregunta === 'resultados' && lastPhase !== 'resultados')) {
                try {
                    const res = await fetch('../api/juego.php', {method:'POST', body:JSON.stringify({action:'estado_jugador', id_sesion:mySessionId})});
                    const json = await res.json();
                    if(json.success) { gameData = json.data; }
                } catch(e){}
            }

            if(gameData) updateUI(gameData);

        }, 1500); 
    }

    // --- 3. LÓGICA Y ESTADO DEL CRONÓMETRO ---
    window.timerInterval = null; 
    window.lastTimerQ = null;

    function updateUI(data) {
        const t = UI_TEXTS[currentLang];
        
        // --- PERSISTENCIA LOCAL: Evitamos que el sombrero desaparezca si no viene en el poll ---
        if (data.avatar_id) window.myLastAvatar = data.avatar_id;
        if (data.sombrero_id !== undefined) window.myLastHat = data.sombrero_id;

        try {
            // Usamos los datos guardados en la ventana para mayor estabilidad
            if (window.myLastAvatar && typeof AvatarManager !== 'undefined') {
                const container = document.getElementById('myAvatarDisplay');
                if (container) {
                    container.innerHTML = AvatarManager.render(window.myLastAvatar, window.myLastHat || 0);
                    document.getElementById('playerFooter').style.display = 'flex';
                }
            }
        } catch (e) {
            console.warn("Error visual en avatar, pero el juego continúa.");
        }

        if (data.puntuacion !== undefined && data.puntuacion !== null) {
            currentScore = parseInt(data.puntuacion);
            document.getElementById('myScoreDisplay').innerText = currentScore;
        }

        const qIdx = data.pregunta_actual_index || 1;
        document.getElementById('waitQNum').innerText = "#" + qIdx;
        document.getElementById('playQNum').innerText = "#" + qIdx;

        // --- MANEJO DE ESTADOS ---
        if (data.estado === 'sala_espera') {
            showWaitScreen(t.wait, t.wait_sub);
            if (window.timerInterval) clearInterval(window.timerInterval);
        } 
        else if (data.estado === 'jugando') {
            if (data.estado_pregunta === 'intro') {
                showWaitScreen(t.q + " " + qIdx, t.wait_others);
                if (window.timerInterval) clearInterval(window.timerInterval);
            }
            else if (data.estado_pregunta === 'respondiendo') {
                // Sincronización del cronómetro local
                if (window.lastTimerQ !== qIdx) {
                    window.lastTimerQ = qIdx;
                    if (window.timerInterval) clearInterval(window.timerInterval);
                    
                    // Priorizamos tiempo_restante del servidor. 
                    // Si llega a 0 por lag del polling, usamos tiempo_limite para evitar el "20".
                    let left = (data.tiempo_restante && data.tiempo_restante > 0) 
                               ? parseInt(data.tiempo_restante) 
                               : parseInt(data.tiempo_limite || 0);
                    
                    const timerEl = document.getElementById('timer');
                    if(timerEl) {
                        timerEl.innerText = left;
                        window.timerInterval = setInterval(() => {
                            if (left > 0) {
                                left--;
                                timerEl.innerText = left;
                            } else {
                                clearInterval(window.timerInterval);
                                timerEl.innerText = 0;
                            }
                        }, 1000);
                    }    
                }

                // Cambio de pantalla: De "espera" a "juego"
                if (lastPhase !== 'respondiendo') {
                    showScreen('screen-play');
                    if (data.json_opciones) renderButtons(data.json_opciones); 
                }
            }
            else if (data.estado_pregunta === 'resultados') {
                if (window.timerInterval) clearInterval(window.timerInterval);
                if (lastPhase !== 'resultados') {
                    showScreen('screen-feedback');
                    const racha = parseInt(data.racha || 0);
                    const banner = document.getElementById('fbBanner');
                    document.getElementById('fbStreak').innerText = racha;

                    if (racha > 0) {
                        banner.className = "feedback-banner";
                        document.getElementById('fbTitle').innerText = t.correct;
                        document.getElementById('fbIcon').className = "fa-solid fa-check fb-icon";
                    } else {
                        banner.className = "feedback-banner wrong";
                        document.getElementById('fbTitle').innerText = t.wrong;
                        document.getElementById('fbIcon').className = "fa-solid fa-xmark fb-icon";
                    }
                    
                    // MOSTRAR MENSAJE DE ÁNIMO SEGÚN RACHA
                    document.getElementById('fbMsg').innerText = getRandomPhrase(racha > 0 ? 'subiendo' : 'bajando');

                    // MOSTRAR TOP 3 EN LA PANTALLA DEL ALUMNO
                    if(data.top_ranking) {
                        const msgBox = document.getElementById('fbMsg');
                        let html = '<div style="margin-top:20px; background:rgba(255,255,255,0.1); padding:15px; border-radius:10px; text-align:left;">';
                        data.top_ranking.forEach((u, i) => {
                            html += `<div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem;">
                                <span>${i+1}. ${u.nombre_nick}</span>
                                <b>${u.puntuacion}</b>
                            </div>`;
                        });
                        html += '</div>';
                        msgBox.innerHTML += html;
                    }
                }
            }
        }
        else if (data.estado === 'finalizada') {
            if (window.timerInterval) clearInterval(window.timerInterval);
            if (lastPhase !== 'finalizada') {
                showScreen('screen-end');
                document.getElementById('playerFooter').style.display = 'none';
                document.getElementById('finalScoreDisplay').innerText = currentScore;
            }
        }
        lastPhase = data.estado_pregunta || data.estado; 
    }

    function renderButtons(jsonStr) {
        const div = document.getElementById('gameButtonsContainer');
        div.innerHTML = '';
        let opts = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
        if(!opts) return;
        
        opts.forEach((o, i) => {
            const shape = SHAPES[i] || '';
            div.innerHTML += `<button class="game-btn bg-${i}" onclick="answer(${i})">
                                <span class="shape-icon">${shape}</span>
                                <span class="btn-text">${o.texto}</span>
                              </button>`;
        });
    }

    async function answer(idx) {
        const t = UI_TEXTS[currentLang];
        showWaitScreen(t.sent, t.wait_others);
        
        try {
            await fetch('../api/juego.php', {
                method:'POST', 
                body:JSON.stringify({action:'responder', id_sesion:mySessionId, respuesta:{indice:idx}})
            });
        } catch(e) {}
    }

    function showScreen(id) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
    }
    
    function showWaitScreen(title, sub) {
        showScreen('screen-wait');
        document.getElementById('waitText').innerText = title;
        document.getElementById('waitSubText').innerText = sub;
        // Limpiamos la frase para que no aparezca en la espera inicial del lobby
        if(document.getElementById('waitPhrase')) document.getElementById('waitPhrase').innerText = "";
    }

    function getRandomPhrase(category) {
        const list = FRASES[currentLang] ? FRASES[currentLang][category] : (FRASES['es'] ? FRASES['es'][category] : []);
        if(!list || list.length === 0) return "";
        return list[Math.floor(Math.random() * list.length)];
    }
</script>
</body>
</html>