<?php
    // views/proyector.php
    $pin = $_GET['pin'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Proyector - PIN: <?php echo $pin; ?></title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/img/bg-proyector.jpg');
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

        <script src="assets/js/app.js"></script>
    </head>

    <body>
        <div id="screen-lobby" class="screen-layer active">
            <div class="lobby-header">
                <img id="userLogoLobby" src="assets/uploads/6925eda979617.png" class="app-logo-proj" style="height:100px; margin-bottom:10px;">
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

        <div id="screen-game" class="screen-layer">
            <div class="game-top">
                <div class="top-left">
                    <img id="userLogoGame" src="assets/uploads/6925eda979617.png" class="app-logo-proj">
                    <div class="timer-circle" id="timer">0</div>
                    <div id="answersCounter" style="font-size:2rem; font-weight:bold; color:white; margin-left: 20px;">0 Resp.</div>
                </div>

                <div class="control-btn-group">
                    <button class="nav-btn btn-end-game" onclick="endGame(this)">
                        Terminar <i class="fa-solid fa-stop"></i>
                    </button>
                    <button class="nav-btn btn-next-q" onclick="forceRanking(this)">
                        Siguiente <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <div class="top-right">
                    <div class="q-badge" id="qCounterDisplay">Pregunta 1</div>
                    <div style="font-weight:bold; font-size:1.5rem;">PIN: <?php echo $pin; ?></div>
                </div>
            </div>

            <div class="question-area">
                <div class="question-text" id="qText">...</div>
            </div>
            <div class="answers-grid" id="answersGrid"></div>
            <div id="introOverlay" class="intro-overlay" style="display:none;">
                <div id="introText" style="font-size:4rem; color:#a5b4fc; text-align:center; padding: 0 50px;">Pregunta</div>
                <div id="introCount" style="font-size:15rem; font-weight:900; color:#facc15;">5</div>
            </div>
        </div>

        <div id="screen-ranking" class="screen-layer">
            <div class="game-top">
                <div style="font-size:3rem; font-weight:bold; flex: 1;">Clasificación</div>
                <div class="control-btn-group">
                    <button class="nav-btn btn-end-game" onclick="endGame(this)">
                        Terminar <i class="fa-solid fa-stop"></i>
                    </button>
                    <button class="nav-btn btn-next-q" onclick="nextQuestion(this)">
                        Siguiente <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div id="resultsChartContainer" style="display:none; width:90%; max-width:850px; margin: 20px auto; text-align:center;">
                <div id="resFeedbackText" style="margin-bottom:20px;">
                    <div id="resQuestion" style="font-size:2.2rem; font-weight:800; color:#fff; margin-bottom:10px; text-shadow: 2px 2px 5px rgba(0,0,0,0.5);"></div>
                    <div id="resCorrectAns" style="font-size:2.2rem; font-weight:bold; background:rgba(255,255,255,0.1); display:inline-block; padding:10px 35px; border-radius:15px; border: 3px solid transparent; transition: all 0.5s ease;"></div>
                </div>
                <div class="chart-container" id="resultsChart"></div>
            </div>
            
            <div class="ranking-container" id="rankingList"></div>
        </div>

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
                await fetch('api/partidas.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'iniciar_juego',
                        id_partida: gameId
                    })
                });
            }

            // Botón Siguiente (Desde Ranking -> Nueva Pregunta)
            async function nextQuestion(btn) {
                if (!gameId) return;
                btn.disabled = true;
                await fetch('api/partidas.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'siguiente_fase',
                        id_partida: gameId
                    })
                });
                setTimeout(() => {
                    btn.disabled = false;
                }, 3000);
            }

            // Botón Siguiente (Desde Pregunta -> Ranking)
            async function forceRanking(btn) {
                if (!gameId) return;
                btn.disabled = true;
                await fetch('api/partidas.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'siguiente_fase',
                        id_partida: gameId
                    })
                });
                setTimeout(() => {
                    btn.disabled = false;
                }, 2000);
            }

            // Botón Terminar Partida (Cierra todo)
            async function endGame(btn) {
                if (!gameId) return;
                if (!confirm("¿Seguro que quieres terminar la partida ahora?")) return;
                btn.disabled = true;
                await fetch('api/partidas.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'finalizar',
                        id_partida: gameId
                    })
                });
            }

            window.deletePlayer = async function(idSesion) {
                if (!confirm("¿Expulsar a este jugador?")) return;
                try {
                    await fetch('api/partidas.php', {
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
                    const res = await fetch(`api/partidas.php?action=info_proyector&codigo_pin=${PIN}`);
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
                const res = await fetch(`api/partidas.php?action=ver_jugadores&id_partida=${id}`);
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

            function startIntroTimer(idPartida) {
                let count = 5;
                const el = document.getElementById('introCount');
                if (window.introInterval) clearInterval(window.introInterval);

                el.innerText = count;
                window.introInterval = setInterval(() => {
                    count--;
                    el.innerText = count;
                    if (count <= 0) {
                        clearInterval(window.introInterval);
                        fetch('api/partidas.php', {
                            method: 'POST',
                            body: JSON.stringify({
                                action: 'siguiente_fase',
                                id_partida: idPartida
                            })
                        });
                    }
                }, 1000);
            }

            async function syncLoop() {
                if (!gameId) return;
                try {
                    let state = null;
                    const res = await fetch(`api/partidas.php?action=estado_juego&codigo_pin=${PIN}`);
                    if (res.ok) {
                        const json = await res.json();
                        state = json.data;
                    }

                    if (!state) return;

                    // Reset do flag se a fase mudou no servidor
                    if (currentPhase !== (state.estado_pregunta || state.estado)) {
                        window.isAdvancing = false;
                    }

                    // --- GESTÃO DE ESTADOS ---
                    if (state.estado === 'sala_espera') {
                        switchScreen('screen-lobby');
                        loadLobbyPlayers(gameId);
                    } else if (state.estado === 'jugando') {
                        if (state.estado_pregunta === 'intro') {
                            switchScreen('screen-game');
                            document.getElementById('introOverlay').style.display = 'flex';
                            document.getElementById('introText').innerText = state.texto_pregunta;
                            document.getElementById('qCounterDisplay').innerText = "Pregunta " + state.pregunta_actual_index;

                            if (currentPhase !== 'intro' || lastQIndex !== state.pregunta_actual_index) {
                                currentPhase = 'intro';
                                lastQIndex = state.pregunta_actual_index;
                                startIntroTimer(state.id_partida);
                            }
                        } else if (state.estado_pregunta === 'respondiendo') {
                            switchScreen('screen-game');
                            document.getElementById('introOverlay').style.display = 'none';
                            document.getElementById('qText').innerText = state.texto_pregunta;

                            if (document.getElementById('answersGrid').innerHTML === '' || currentPhase !== 'respondiendo') {
                                renderAnswers(state.json_opciones);
                            }
                            currentPhase = 'respondiendo';

                            let left = parseInt(state.tiempo_restante || 0);
                            document.getElementById('timer').innerText = left;

                            // --- AVANCE AUTOMÁTICO A GRÁFICA DE RESULTADOS ---
                            const todasRespondidas = (state.total_jugadores > 0 && state.respuestas_recibidas >= state.total_jugadores);
                            if ((left <= 0 || todasRespondidas) && !window.isAdvancing) {
                                window.isAdvancing = true;
                                forceRanking(document.querySelector('.btn-next-q'));
                            }

                            if (state.respuestas_recibidas !== undefined) {
                                document.getElementById('answersCounter').innerText = `${state.respuestas_recibidas} / ${state.total_jugadores} Resp.`;
                            }
                        } else if (state.estado_pregunta === 'resultados') {
                            if (currentPhase !== 'resultados') {
                                currentPhase = 'resultados';
                                switchScreen('screen-ranking');
                                loadRanking(state.id_partida);

                                // --- OCULTAR BOTÓN SIGUIENTE EN LA ÚLTIMA PREGUNTA ---
                                const isLast = (parseInt(state.pregunta_actual_index) >= parseInt(state.total_preguntas));
                                const nextBtns = document.querySelectorAll('.btn-next-q');
                                nextBtns.forEach(b => b.style.display = isLast ? 'none' : 'inline-block');
                            }
                        }
                    } else if (state.estado === 'finalizada') {
                // 1. Obtenemos el ranking final
                const resRanking = await fetch(`api/partidas.php?action=ranking_parcial&id_partida=${gameId}`);
                const jsonRanking = await resRanking.json();
                
                // SEGURIDAD: Si no hay jugadores, evitamos el error usando un array vacío
                const r = jsonRanking.ranking || [];

                // 2. Detenemos el bucle para que el proyector no intente re-renderizar
                gameId = null; 

                // 3. Inyectamos el HTML del podium
                document.body.innerHTML = `
                <div style="height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:flex-start; padding-top:20px; background: radial-gradient(circle, #25076b, #111); text-align:center; color:white; overflow:hidden;">
                    
                    <h1 style="font-size:4.5rem; color:#facc15; text-shadow: 0 0 30px rgba(0,0,0,1); margin-top: 0.2em; margin-bottom: 3.5em; font-weight:900;">🏆 PODIUM FINAL 🏆</h1>
                    
                    <div class="podium-wrap" style="display:flex; align-items:flex-end; justify-content:center; gap:25px; height:22em; margin-bottom:40px;">
                        
                        ${r[1] ? `
                        <div class="podium-box">
                            <div style="margin-bottom:15px; font-size:4rem;">${AvatarManager.render(r[1].avatar_id, r[1].sombrero_id || 0)}</div>
                            <div style="font-size:1.6rem; font-weight:bold; margin-bottom:10px;">${r[1].nombre_nick}</div>
                            <div class="box-2" style="height:180px; width:180px; display:flex; align-items:center; justify-content:center; font-size:4rem; border-radius:15px 15px 0 0; font-weight:900; color:#333; background:linear-gradient(135deg, #e2e8f0, #94a3b8);">2</div>
                            <div style="margin-top:10px; font-size:1.3rem;">${r[1].puntuacion} pts</div>
                        </div>` : ''}
                        
                        ${r[0] ? `
                        <div class="podium-box" style="z-index:10;">
                            <div style="margin-bottom:10px; font-size:5.8rem;">${AvatarManager.render(r[0].avatar_id, r[0].sombrero_id || 0)}</div>
                            <div style="font-size:2.6rem; color:#facc15; font-weight:900; margin-bottom:15px;">👑 ${r[0].nombre_nick}</div>
                            <div class="box-1" style="height:280px; width:220px; display:flex; align-items:center; justify-content:center; font-size:7rem; border-radius:15px 15px 0 0; font-weight:900; color:#333; background:linear-gradient(135deg, #ffd700, #f59e0b); box-shadow:0 0 50px rgba(250,204,21,0.5);">1</div>
                            <div style="font-size:1.8rem; font-weight:bold; margin-top:10px;">${r[0].puntuacion} pts</div>
                        </div>` : ''}
                        
                        ${r[2] ? `
                        <div class="podium-box">
                            <div style="margin-bottom:15px; font-size:3.5rem;">${AvatarManager.render(r[2].avatar_id, r[2].sombrero_id || 0)}</div>
                            <div style="font-size:1.5rem; font-weight:bold; margin-bottom:10px;">${r[2].nombre_nick}</div>
                            <div class="box-3" style="height:120px; width:180px; display:flex; align-items:center; justify-content:center; font-size:4rem; border-radius:15px 15px 0 0; font-weight:900; color:#fff; background:linear-gradient(135deg, #d97706, #92400e);">3</div>
                            <div style="margin-top:10px; font-size:1.3rem;">${r[2].puntuacion} pts</div>
                        </div>` : ''}
                    </div>
                    
                    <button onclick="location.href='index.php'" class="nav-btn" style="margin-top:5px; background:#fff; color:#333; padding: 10px 60px; font-size:1.5rem; border-radius:50px; font-weight:bold; border:none; cursor:pointer;">FINALIZAR</button>
                </div>`;
            }
                } catch (e) {
                    console.error("Error en syncLoop:", e);
                }
            }

            async function loadRanking(idPartida) {
                try {
                    // 1. Llamadas simultáneas a la API para obtener estadísticas, estado y clasificación
                    const [resStats, resState, resRank] = await Promise.all([
                        fetch(`api/partidas.php?action=get_stats_pregunta&id_partida=${idPartida}`),
                        fetch(`api/partidas.php?action=estado_juego&codigo_pin=${PIN}`),
                        fetch(`api/partidas.php?action=ranking_parcial&id_partida=${idPartida}`)
                    ]);
                    
                    const stats = await resStats.json();
                    const stateJson = await resState.json();
                    const jsonRank = await resRank.json();

                    // 2. MOSTRAR PREGUNTA Y RESPUESTA CORRECTA CON COLOR DINÁMICO
                    if (stateJson.success && stateJson.data) {
                        // Colores oficiales: Rojo, Azul, Amarillo, Verde
                        const colores = ['#ef4444', '#3b82f6', '#eab308', '#22c55e'];
                        
                        const pregunta = stateJson.data.texto_pregunta || "";
                        const opciones = JSON.parse(stateJson.data.json_opciones || "[]");
                        
                        // Identificamos el índice y el objeto de la respuesta correcta
                        const idxCorrecto = opciones.findIndex(o => o.es_correcta == true || o.es_correcta == 1);
                        const objCorrecta = opciones[idxCorrecto];
                        const colorFinal = (idxCorrecto !== -1) ? colores[idxCorrecto] : "#fff";

                        const elQ = document.getElementById('resQuestion');
                        const elA = document.getElementById('resCorrectAns');

                        if (elQ) elQ.innerText = pregunta;
                        if (elA) {
                            elA.innerText = "✓ " + (objCorrecta ? objCorrecta.texto : "No definida");
                            // Aplicamos el color visual coincidente
                            elA.style.color = colorFinal;
                            elA.style.borderColor = colorFinal;
                            elA.style.boxShadow = `0 0 15px ${colorFinal}44`; 
                        }
                    }

                    // 3. RENDERIZAR GRÁFICO DE BARRAS
                    const chartDiv = document.getElementById('resultsChart');
                    chartDiv.innerHTML = '';
                    document.getElementById('resultsChartContainer').style.display = 'block';

                    const coloresBarras = ['#ef4444', '#3b82f6', '#eab308', '#22c55e'];
                    const opcionesData = JSON.parse(stateJson.data.json_opciones || "[]");
                    const idxCorrectoBarra = opcionesData.findIndex(o => o.es_correcta);

                    for(let i=0; i<4; i++) {
                        const dato = stats.find(s => parseInt(s.indice) === i);
                        const total = dato ? parseInt(dato.total) : 0;
                        
                        const bar = document.createElement('div');
                        // Resaltamos la barra si es la correcta
                        bar.className = 'bar' + (i === idxCorrectoBarra ? ' correct-answer-indicator' : '');
                        bar.style.height = Math.max(total * 40, 20) + 'px';
                        bar.style.backgroundColor = coloresBarras[i];
                        bar.innerHTML = `<span class="bar-value">${total}</span><span class="bar-label">${shapes[i]}</span>`;
                        chartDiv.appendChild(bar);
                    }

                    // 4. CLASIFICACIÓN CON RANKING ANIMADO
                    const divRankList = document.getElementById('rankingList');
                    if(jsonRank.success) {
                        const ranking = jsonRank.ranking;
                        // Obtenemos las filas que ya existen en pantalla para compararlas
                        const currentRows = [...divRankList.querySelectorAll('.rank-row')];
                        const newNicks = new Set(ranking.map(p => p.nombre_nick));

                        // A. Quitamos a los que han salido del Top 5
                        currentRows.forEach(row => {
                            if(!newNicks.has(row.dataset.nick)) {
                                row.style.opacity = '0';
                                setTimeout(() => row.remove(), 600);
                            }
                        });

                        // B. Actualizamos posiciones o creamos nuevas filas
                        ranking.forEach((p, i) => {
                            let el = divRankList.querySelector(`.rank-row[data-nick="${p.nombre_nick}"]`);
                            const targetTop = i * 85; // Desplazamiento vertical según puesto

                            if(!el) {
                                // Si el jugador es nuevo en el top, lo creamos invisible abajo
                                el = document.createElement('div');
                                el.className = 'rank-row';
                                el.dataset.nick = p.nombre_nick;
                                el.style.opacity = '0';
                                el.style.transform = `translateY(${targetTop + 50}px)`;
                                divRankList.appendChild(el);
                            }

                            // Actualizamos contenido (puntos y avatar)
                            el.innerHTML = `
                                <div style="display:flex; align-items:center; gap:20px;">
                                    <span style="background:#46178f; color:white; width:45px; height:45px; border-radius:50%; display:flex; justify-content:center; align-items:center; font-weight:bold;">${i+1}</span>
                                    <span style="font-size:2rem; font-weight:800;">${AvatarManager.render(p.avatar_id, p.sombrero_id || 0)} ${p.nombre_nick}</span>
                                </div>
                                <span style="font-size:2rem; font-weight:900;">${p.puntuacion} pts</span>`;
                            
                            // C. Disparamos la animación de movimiento
                            setTimeout(() => {
                                el.style.transform = `translateY(${targetTop}px)`;
                                el.style.opacity = '1';
                            }, 50);
                        });
                    }
                } catch (error) {
                    console.error("Error crítico en loadRanking:", error);
                }
            }

            function renderAnswers(jsonStr) {
                const grid = document.getElementById('answersGrid');
                grid.innerHTML = '';
                try {
                    const opts = (typeof jsonStr === 'string') ? JSON.parse(jsonStr) : jsonStr;
                    opts.forEach((o, i) => {
                        grid.innerHTML += `<div class="answer-card ans-${i}"><div class="shape-icon">${shapes[i]}</div><div>${o.texto}</div></div>`;
                    });
                } catch (e) {}
            }

            init();
        </script>
    </body>
</html>