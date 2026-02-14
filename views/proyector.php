<?php
    // views/proyector.php
    $pin = $_GET['pin'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Proyector - PIN: <?php echo $pin; ?></title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../assets/img/bg-proyector.jpg');
                background-size: cover;
                background-position: center;
                color: white;
                margin: 0;
                font-family: 'Roboto', sans-serif;
                overflow: hidden;
                height: 100vh;
            }

            /* Contenedores */
            .screen-layer {
                display: none;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                flex-direction: column;
                z-index: 1;
            }

            .screen-layer.active {
                display: flex;
                z-index: 100;
            }

            /* Lobby */
            .lobby-header {
                text-align: center;
                padding-top: 40px;
                flex: 0 0 auto;
            }

            .pin-box {
                font-size: 8rem;
                font-weight: 900;
                letter-spacing: 15px;
                margin: 10px 0;
                color: white;
                text-shadow: 0 0 30px rgba(99, 102, 241, 1);
            }

            .players-grid {
                flex: 1;
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                padding: 20px 40px;
                justify-content: center;
                align-content: flex-start;
                overflow-y: auto;
            }

            .player-chip {
                background: rgba(255, 255, 255, 0.9);
                color: #333;
                padding: 10px 45px 10px 20px;
                border-radius: 50px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 1.3rem;
                font-weight: bold;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
                transition: all 0.3s ease;
                position: relative;
                animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            .player-chip:hover {
                transform: scale(1.05);
                z-index: 10;
                background: white;
            }

            .kick-btn {
                position: absolute;
                right: 5px;
                top: 50%;
                transform: translateY(-50%);
                width: 30px;
                height: 30px;
                background: #fee2e2;
                color: #ef4444;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                opacity: 0;
                transition: 0.2s;
                font-size: 1rem;
            }

            .player-chip:hover .kick-btn {
                opacity: 1;
            }

            .kick-btn:hover {
                background: #fecaca;
                transform: translateY(-50%) scale(1.1);
            }

            @keyframes popIn {
                from {
                    transform: scale(0);
                }

                to {
                    transform: scale(1);
                }
            }

            .lobby-footer {
                flex: 0 0 100px;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(5px);
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 50px;
            }

            .nav-btn {
                padding: 15px 40px;
                background: white;
                color: #333;
                border: none;
                border-radius: 50px;
                font-weight: bold;
                cursor: pointer;
                font-size: 1.5rem;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
                transition: transform 0.2s;
                white-space: nowrap;
            }

            .nav-btn:hover {
                transform: scale(1.05);
            }

            .nav-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                background: #ccc;
            }

            /* Estilos específicos para los botones de control en la CABECERA DEL JUEGO */
            .control-btn-group {
                display: flex;
                gap: 15px;
                flex-shrink: 0;
                align-items: center;
            }

            .game-top .nav-btn {
                padding: 10px 25px;
                /* Más pequeños para la cabecera */
                font-size: 1.2rem;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
            }

            .btn-next-q {
                background: #22c55e;
                color: white;
                border: 2px solid white;
            }

            /* Verde */
            .btn-end-game {
                background: #ef4444;
                color: white;
                border: 2px solid white;
            }

            /* Rojo */

            /* Game Header */
            .game-top {
                flex: 0 0 100px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 40px;
                background: rgba(0, 0, 0, 0.85);
                border-bottom: 2px solid #444;
            }

            .top-left {
                display: flex;
                align-items: center;
                gap: 20px;
            }

            .top-right {
                display: flex;
                align-items: center;
                gap: 20px;
            }

            .timer-circle {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: #6366f1;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                font-weight: 900;
                border: 4px solid white;
            }

            .q-badge {
                background: white;
                color: #333;
                padding: 10px 25px;
                border-radius: 30px;
                font-weight: 900;
                font-size: 1.8rem;
                box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
            }

            .app-logo-proj {
                height: 80px;
                width: auto;
                border-radius: 10px;
                border: 2px solid white;
                background: white;
            }

            .question-area {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 30px;
                background: rgba(255, 255, 255, 0.95);
                color: #333;
                margin: 30px;
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            }

            .question-text {
                font-size: clamp(2.5rem, 4vw, 4rem);
                text-align: center;
                font-weight: 800;
                line-height: 1.2;
            }

            .answers-grid {
                flex: 0 0 45vh;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 25px;
                padding: 0 50px 50px 50px;
            }

            .answer-card {
                display: flex;
                align-items: center;
                padding: 30px 40px;
                border-radius: 20px;
                font-size: clamp(1.8rem, 2.5vw, 2.5rem);
                color: white;
                font-weight: bold;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            }

            .ans-0 {
                background: #ef4444;
            }

            .ans-1 {
                background: #3b82f6;
            }

            .ans-2 {
                background: #eab308;
                color: black;
            }

            .ans-3 {
                background: #22c55e;
            }

            .shape-icon {
                margin-right: 30px;
                font-size: 3rem;
            }

            .intro-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                z-index: 200;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            /* Contenedor relativo para permitir posicionamiento absoluto de las filas */
            .ranking-container { 
                width: 100%; 
                max-width: 900px; 
                margin: 0 auto; 
                position: relative;
                height: 500px; /* Altura fija para que el scroll no salte */
                overflow: visible;
            }

            .rank-row { 
                background: white; 
                color: #333; 
                width: 100%; 
                padding: 15px 40px; 
                border-radius: 15px; 
                font-size: 2.5rem; 
                font-weight: bold; 
                display: flex; 
                justify-content: space-between; 
                align-items: center;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                /* VITAL: Transición suave de posición y opacidad */
                position: absolute;
                left: 0;
                transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
            }

            /* Resaltado de la respuesta correcta en el gráfico */
            .correct-answer-indicator {
                border: 6px solid #ffffff;
                box-shadow: 0 0 40px #ffffff;
                transform: scale(1.1);
                z-index: 5;
                position: relative;
            }

            /* Icono de Check sobre la barra correcta */
            .correct-answer-indicator::after {
                content: '✓';
                position: absolute;
                top: -50px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 2.5rem;
                color: white;
                font-weight: 900;
                text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            }

            .rank-row.up {
                border-left: 15px solid #22c55e;
                animation: slideIn 0.5s;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Gráfico Centrado */
            #resultsChartContainer {
                display: none;
                width: 90%;
                max-width: 800px;
                margin: 40px auto;
                background: rgba(255, 255, 255, 0.05);
                padding: 40px;
                border-radius: 30px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .chart-container {
                display: flex;
                align-items: flex-end;
                justify-content: center;
                gap: 30px;
                height: 250px;
            }

            .bar {
                width: 70px;
                border-radius: 12px 12px 4px 4px;
                position: relative;
                transition: height 1s cubic-bezier(0.17, 0.67, 0.83, 0.67);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            }

            /* Podium Innovador */
            .podium-wrap {
                display: flex;
                align-items: flex-end;
                justify-content: center;
                gap: 15px;
                height: 400px;
                margin-top: 40px;
                perspective: 1000px;
            }

            .podium-box {
                width: 180px;
                text-align: center;
                position: relative;
                transform-style: preserve-3d;
                animation: float 4s infinite ease-in-out;
            }

            .box-1 {
                height: 260px;
                background: linear-gradient(135deg, #ffd700, #f59e0b);
                order: 2;
                z-index: 3;
            }

            .box-2 {
                height: 180px;
                background: linear-gradient(135deg, #e2e8f0, #94a3b8);
                order: 1;
                z-index: 2;
            }

            .box-3 {
                height: 120px;
                background: linear-gradient(135deg, #d97706, #92400e);
                order: 3;
                z-index: 1;
            }

            .podium-box {
                border-radius: 20px 20px 0 0;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                padding-bottom: 20px;
                color: white;
                font-weight: 900;
                font-size: 4rem;
            }

            .winner-crown {
                position: absolute;
                top: -60px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 4rem;
                animation: crownRotate 3s infinite;
            }

            @keyframes float {
                0%,
                100% {
                    transform: translateY(0);
                }

                50% {
                    transform: translateY(-10px);
                }
            }

            @keyframes crownRotate {
                0% {
                    transform: translateX(-50%) rotate(-10deg);
                }

                50% {
                    transform: translateX(-50%) rotate(10deg);
                }

                100% {
                    transform: translateX(-50%) rotate(-10deg);
                }
            }
        </style>
    </head>

    <body>
        <div id="screen-lobby" class="screen-layer active">
            <div class="lobby-header">
                <img id="userLogoLobby" src="../assets/uploads/6925eda979617.png" class="app-logo-proj" style="height:100px; margin-bottom:10px;">
                <h2 id="academyName" style="margin:0; font-size: 2rem;">EduGame</h2>
                <div class="join-url" style="font-size: 1.5rem; margin-top: 10px;">edugame.com/play</div>
                <div class="pin-box"><?php echo $pin; ?></div>
            </div>

            <div class="players-grid" id="lobbyPlayers"></div>

            <div class="lobby-footer">
                <div style="font-size: 2rem; color:white;"><i class="fa-solid fa-users"></i> <span id="playerCount">0</span></div>
                <button id="btnStart" class="nav-btn" style="background:#22c55e; color:white;" disabled>Comenzar</button>
            </div>
        </div> 
    
        <script src="../assets/js/app.js"></script>
        <?php include dirname(__DIR__) . '/games/quiz/quiz_proyector.php'; ?>

        <script>
            const PIN = "<?php echo $pin; ?>";
            const shapes = ['▲', '◆', '●', '■'];
            let gameId = null;
            let currentPhase = '';
            let lastQIndex = -1;
            let playerCount = 0;
            let prevScores = {};
            let isStarting = false;
            window.isAdvancing = false;

            // --- ACCIONES DE BOTONES ---

            async function startGame() {
                if (!gameId || isStarting) return;
                if (playerCount === 0) {
                    alert("¡Esperando jugadores!");
                    return;
                }
                isStarting = true;
                document.getElementById('btnStart').disabled = true;
                document.getElementById('btnStart').innerText = "Iniciando...";
                await fetch('../api/partidas.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'iniciar_juego',
                        id_partida: gameId
                    })
                });
            }

            async function endGame(btn) {
                if (!gameId) return;
                if (!confirm("¿Seguro que quieres terminar la partida ahora?")) return;
                btn.disabled = true;
                const res = await fetch('../api/partidas.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'finalizar',
                        id_partida: gameId
                    })
                });
                // Forzamos un refresco inmediato para cargar el podium
                const json = await res.json();
                if(json.success) {
                    syncLoop(); // Esto activará el cambio a 'finalizada' en el siguiente ciclo
                }
            }

            window.deletePlayer = async function(idSesion) {
                if (!confirm("¿Expulsar a este jugador?")) return;
                try {
                    await fetch('../api/partidas.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            action: 'expulsar_jugador',
                            id_sesion: idSesion
                        })
                    });
                    loadLobbyPlayers(gameId);
                } catch (e) {
                    console.error(e);
                }
            };

            document.getElementById('btnStart').onclick = startGame;

            // --- INICIALIZACIÓN Y BUCLE ---

            async function init() {
                try {
                    const res = await fetch(`../api/partidas.php?action=info_proyector&codigo_pin=${PIN}`);
                    const json = await res.json();
                    if (!json.success) {
                        document.body.innerHTML = "<h1 style='color:white;text-align:center;'>Partida no encontrada</h1>";
                        return;
                    }
                    gameId = json.data.id_partida;
                    document.getElementById('academyName').innerText = json.data.nombre_visual;

                    if (json.data.logo_visual) {
                        document.getElementById('userLogoLobby').src = json.data.logo_visual;
                        document.getElementById('userLogoGame').src = json.data.logo_visual;
                    }

                    setInterval(syncLoop, 1000);
                } catch (e) {}
            }

            async function loadLobbyPlayers(id) {
                const res = await fetch(`../api/partidas.php?action=ver_jugadores&id_partida=${id}`);
                const json = await res.json();
                if (json.success) {
                    const list = json.data;
                    playerCount = list.length;
                    document.getElementById('playerCount').innerText = playerCount;

                    if (!isStarting) {
                        document.getElementById('btnStart').disabled = (playerCount === 0);
                    }

                    const grid = document.getElementById('lobbyPlayers');
                    const currentApiIds = new Set(list.map(p => parseInt(p.id_sesion)));

                    [...grid.children].forEach(child => {
                        const id = parseInt(child.dataset.id);
                        if (!currentApiIds.has(id)) {
                            child.style.transform = 'scale(0)';
                            setTimeout(() => {
                                if (child.parentNode) child.remove();
                            }, 400);
                        }
                    });

                    list.forEach(p => {
                        if (!document.querySelector(`.player-chip[data-id="${p.id_sesion}"]`)) {
                            const div = document.createElement('div');
                            div.className = 'player-chip';
                            div.dataset.id = p.id_sesion;
                            div.innerHTML = `${AvatarManager.render(p.avatar_id, p.sombrero_id || 0)} <span style="margin-left:5px;">${p.nombre_nick}</span> <span class="kick-btn" onclick="deletePlayer(${p.id_sesion})"><i class="fa-solid fa-xmark"></i></span>`;
                            grid.appendChild(div);
                        }
                    });
                }
            }

            function switchScreen(id) {
                document.querySelectorAll('.screen-layer').forEach(d => d.classList.remove('active'));
                document.getElementById(id).classList.add('active');
            }

            async function syncLoop() {
                if (!gameId) return;
                try {
                    let state = null;
                    const res = await fetch(`../api/partidas.php?action=estado_juego&codigo_pin=${PIN}`);
                    if (res.ok) {
                        const json = await res.json();
                        state = json.data;
                    }

                    if (!state) return;

                    // El Shell maneja el Lobby
                    if (state.estado === 'sala_espera') {
                        switchScreen('screen-lobby');
                        loadLobbyPlayers(gameId);
                    } 
                    // El módulo maneja el resto de fases de juego
                    else {
                        QuizProyector.update(state);
                    }

                } catch (e) { console.error("Error en syncLoop:", e); }
            }

            init();
        </script>
    </body>
</html>