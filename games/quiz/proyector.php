<div id="quiz-module-layers">
    <div id="screen-game" class="screen-layer">
        <div class="game-top">
            <div class="top-left">
                <img id="userLogoGame" src="../assets/uploads/6925eda979617.png" class="app-logo-proj">
                <div class="timer-circle" id="timer">0</div>
                <div id="answersCounter" style="font-size:2rem; font-weight:bold; color:white; margin-left: 20px;">0 Resp.</div>
            </div>
            <div class="control-btn-group">
                <button class="nav-btn btn-end-game" onclick="endGame(this)">
                    Terminar <i class="fa-solid fa-stop"></i>
                </button>
                <button class="nav-btn btn-next-q" onclick="GameModule.forceRanking()">
                    Saltar <i class="fa-solid fa-forward"></i>
                </button>
            </div>
            <div class="top-right">
                <div class="q-badge" id="qCounterDisplay">1 / 1</div>
            </div>
        </div>
        <div class="question-area">
            <div class="question-text" id="qText">Cargando pregunta...</div>
        </div>
        <div class="answers-grid" id="answersGrid"></div>
        
        <div id="introOverlay" class="intro-overlay" style="display:none;">
            <div id="introText" style="font-size:3.5rem; color:#a5b4fc; text-align:center; padding: 0 50px; font-weight: 800;"></div>
            <div id="introCount" style="font-size:15rem; font-weight:900; color:#facc15; text-shadow: 0 0 50px rgba(250,204,21,0.5);">5</div>
        </div>
    </div>

    <div id="screen-ranking" class="screen-layer">
        <div class="game-top">
            <div style="font-size:3rem; font-weight:bold; flex: 1;">Clasificación</div>
            <div class="control-btn-group">
                <button class="nav-btn btn-end-game" onclick="endGame(this)">
                    Terminar <i class="fa-solid fa-stop"></i>
                </button>
                <button class="nav-btn btn-next-q" onclick="GameModule.nextQuestion(this)">
                    Siguiente <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>
        <div id="resultsFeedbackArea" style="display:none; text-align:center; padding: 20px 0;">
            <div id="resQuestionTitle" style="font-size:2rem; font-weight:800; color:#fff; margin-bottom:10px;"></div>
            <div id="resCorrectAnsBox" style="font-size:2.2rem; font-weight:bold; background:rgba(34,197,94,0.2); border:3px solid #22c55e; color:#22c55e; display:inline-block; padding:15px 40px; border-radius:20px;"></div>
        </div>
        <div class="ranking-container" id="rankingList"></div>
    </div>
</div>

<script>
const GameModule = {
    localTimer: null,
    introTimer: null,
    currentLocalTime: null,
    isAdvancing: false,
    isRequesting: false,
    lastStateKey: '', // Para detectar cambios reales de fase

    // Función para limpiar todos los procesos al cambiar de estado
    resetTimers: function() {
        if (this.localTimer) clearInterval(this.localTimer);
        if (this.introTimer) clearInterval(this.introTimer);
        this.localTimer = null;
        this.introTimer = null;
        this.currentLocalTime = null;
        window.introTimerActive = false;
    },

    update: async function(state) {
        // 1. Detección de cambio de fase real para evitar reinicios constantes
        // Generamos una clave única combinando ID, Índice y Fase
        const currentStateKey = `${state.id_partida}_${state.pregunta_actual_index}_${state.estado_pregunta}`;
        const hasStateChanged = (this.lastStateKey !== currentStateKey);
        
        if (hasStateChanged) {
            // DETECCIÓN DE CAMBIO: Limpiamos cronómetros y desbloqueamos el avance
            if (this.localTimer) clearInterval(this.localTimer);
            if (this.introTimer) clearInterval(this.introTimer);
            this.localTimer = null;
            this.introTimer = null;
            this.currentLocalTime = null;
            window.introTimerActive = false;
            
            this.isAdvancing = false; // Solo aquí permitimos un nuevo avance automático
            this.lastStateKey = currentStateKey;
            console.log("Nuevo estado detectado:", state.estado_pregunta, "Pregunta:", state.pregunta_actual_index);
        }

        // 2. Conteo de preguntas (Sincronizado desde el servidor)
        // Forzamos que sea mínimo 1 para evitar el "Pregunta 0"
        const curIdx = Math.max(1, parseInt(state.pregunta_actual_index || 1));
        const totalQ = parseInt(state.total_preguntas || 1);
        const isLast = (curIdx >= totalQ);
        
        const counterEl = document.getElementById('qCounterDisplay');
        if (counterEl) counterEl.innerText = curIdx + " / " + totalQ;

        // 3. Gestión de botones
        document.querySelectorAll('.btn-next-q').forEach(b => {
            if (isLast && state.estado_pregunta === 'resultados') {
                b.innerHTML = 'Ver Podium <i class="fa-solid fa-trophy"></i>';
                b.style.display = 'inline-block';
                b.onclick = () => endGame(b);
            } else if (state.estado_pregunta === 'resultados') {
                b.innerHTML = 'Siguiente <i class="fa-solid fa-arrow-right"></i>';
                b.style.display = 'inline-block';
                b.onclick = () => this.forceRanking(state.id_partida);
            } else {
                b.style.display = 'none'; // Ocultar botones durante la pregunta para evitar saltos accidentales
            }
        });

        // 4. Lógica de fases de juego
        if (state.estado === 'jugando') {
            if (state.estado_pregunta === 'intro') {
                switchScreen('screen-game');
                document.getElementById('introOverlay').style.display = 'flex';
                document.getElementById('introText').innerText = state.texto_pregunta;
                // Iniciamos el temporizador de 5s para pasar a 'respondiendo'
                this.startIntroTimer(state.id_partida);
            } 
            else if (state.estado_pregunta === 'respondiendo') {
                switchScreen('screen-game');
                document.getElementById('introOverlay').style.display = 'none';
                document.getElementById('qText').innerText = state.texto_pregunta;
                
                // Renderizamos opciones solo si cambiamos de fase
                if (typeof currentPhase !== 'undefined' && currentPhase !== 'respondiendo') {
                    this.renderAnswers(state.json_opciones);
                    currentPhase = 'respondiendo';
                }
                
                // Sincronizamos cronómetro
                this.syncLocalTimer(state.tiempo_restante || 0, state.id_partida);
                
                const recibidas = parseInt(state.respuestas_recibidas || 0);
                const totales = parseInt(state.total_jugadores || 0);
                const counterResp = document.getElementById('answersCounter');
                if (counterResp) counterResp.innerText = recibidas + " / " + totales + " Resp.";

                // AUTO-AVANCE: Si todos han respondido y no estamos ya avanzando
                if (totales > 0 && recibidas >= totales && !this.isAdvancing) {
                    this.isAdvancing = true; // Bloqueo de seguridad
                    this.forceRanking(state.id_partida);
                }
            } 
            else if (state.estado_pregunta === 'resultados') {
                if (typeof currentPhase !== 'undefined' && currentPhase !== 'resultados') {
                    currentPhase = 'resultados';
                    switchScreen('screen-ranking');
                    this.loadRanking(state, state.id_partida);
                }
            }
        } 
        else if (state.estado === 'finalizada') {
            this.showPodium();
        }
    },
        if (state.estado === 'jugando') {
            if (state.estado_pregunta === 'intro') {
                switchScreen('screen-game');
                document.getElementById('introOverlay').style.display = 'flex';
                document.getElementById('introText').innerText = state.texto_pregunta;
                this.startIntroTimer(state.id_partida);
            } 
            else if (state.estado_pregunta === 'respondiendo') {
                switchScreen('screen-game');
                document.getElementById('introOverlay').style.display = 'none';
                document.getElementById('qText').innerText = state.texto_pregunta;
                
                if (typeof currentPhase !== 'undefined' && currentPhase !== 'respondiendo') {
                    this.renderAnswers(state.json_opciones);
                    currentPhase = 'respondiendo';
                }
                
                this.syncLocalTimer(state.tiempo_restante || 0, state.id_partida);
                
                const recibidas = parseInt(state.respuestas_recibidas || 0);
                const totales = parseInt(state.total_jugadores || 0);
                const counterResp = document.getElementById('answersCounter');
                if (counterResp) counterResp.innerText = recibidas + " / " + totales + " Resp.";

                // Avance automático si todos responden
                if (totales > 0 && recibidas >= totales && !this.isAdvancing) {
                    this.isAdvancing = true; 
                    this.forceRanking(state.id_partida);
                }
            } 
            else if (state.estado_pregunta === 'resultados') {
                if (typeof currentPhase !== 'undefined' && currentPhase !== 'resultados') {
                    currentPhase = 'resultados';
                    switchScreen('screen-ranking');
                    this.loadRanking(state, state.id_partida);
                }
            }
        } 
        else if (state.estado === 'finalizada') {
            this.showPodium();
        }
    },

    syncLocalTimer: function(seconds, idPartida) {
        let serverTime = parseInt(seconds);
        const el = document.getElementById('timer');
        if (!el || (this.currentLocalTime !== null && Math.abs(this.currentLocalTime - serverTime) <= 1)) return;
        
        if (this.localTimer) clearInterval(this.localTimer);
        this.currentLocalTime = serverTime;
        el.innerText = this.currentLocalTime;

        this.localTimer = setInterval(() => {
            if (this.currentLocalTime > 0) {
                this.currentLocalTime--;
                el.innerText = this.currentLocalTime;
            } else {
                clearInterval(this.localTimer);
                if (!this.isAdvancing) {
                    this.isAdvancing = true;
                    this.forceRanking(idPartida);
                }
            }
        }, 1000);
    },

    startIntroTimer: function(idPartida) {
        // Usamos una variable global de ventana para asegurar que no hay dos corriendo
        if (window.introTimerActive) return;
        window.introTimerActive = true;
        
        let count = 5;
        const el = document.getElementById('introCount');
        if (el) el.innerText = count;

        if (this.introTimer) clearInterval(this.introTimer);
        this.introTimer = setInterval(async () => {
            count--;
            if (el) el.innerText = count;
            
            if (count <= 0) {
                clearInterval(this.introTimer);
                window.introTimerActive = false;
                
                // Solo avanzamos si el profesor no ha saltado ya manualmente
                if (!this.isAdvancing) {
                    this.isAdvancing = true;
                    this.forceRanking(idPartida);
                }
            }
        }, 1000);
    }

    forceRanking: async function(idPartida) {
        const pId = (typeof idPartida === 'number' || typeof idPartida === 'string') ? idPartida : gameId;
        if (!pId || this.isRequesting) return;
        
        this.isRequesting = true;
        try {
            await fetch('../api/partidas.php', { 
                method: 'POST', 
                body: JSON.stringify({ action: 'siguiente_fase', id_partida: pId }) 
            });
        } finally {
            this.isRequesting = false;
        }
    },

    nextQuestion: async function(btn) {
        if(btn) btn.disabled = true;
        this.forceRanking();
    },

    loadRanking: async function(state, idPartida) {
        try {
            const feedbackArea = document.getElementById('resultsFeedbackArea');
            if (feedbackArea) {
                feedbackArea.style.display = 'block';
                document.getElementById('resQuestionTitle').innerText = state.texto_pregunta;
                const opciones = JSON.parse(state.json_opciones || "[]");
                const correcta = opciones.find(o => o.es_correcta == 1 || o.es_correcta == true);
                document.getElementById('resCorrectAnsBox').innerText = "✓ " + (correcta ? correcta.texto : "...");
            }

            const resRank = await fetch(`../api/partidas.php?action=ranking_parcial&id_partida=${idPartida}`);
            const jsonRank = await resRank.json();

            const divRankList = document.getElementById('rankingList');
            if(jsonRank.success && divRankList) {
                divRankList.innerHTML = '';
                jsonRank.ranking.forEach((p, i) => {
                    divRankList.innerHTML += `
                        <div class="rank-row" style="transform: translateY(${i * 85}px); opacity: 1;">
                            <div style="display:flex; align-items:center; gap:20px;">
                                <span style="font-size:2.5rem;">${AvatarManager.render(p.avatar_id, p.sombrero_id || 0)}</span>
                                <span>${p.nombre_nick}</span>
                            </div>
                            <span style="color:#46178f;">${p.puntuacion} pts</span>
                        </div>`;
                });
            }
        } catch (e) {}
    },

    renderAnswers: function(jsonStr) {
        const grid = document.getElementById('answersGrid');
        if(!grid) return;
        grid.innerHTML = '';
        const opts = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
        const iconList = (typeof shapes !== 'undefined') ? shapes : ['▲', '◆', '●', '■'];
        opts.forEach((o, i) => {
            grid.innerHTML += `<div class="answer-card ans-${i}"><span class="shape-icon">${iconList[i]}</span><span>${o.texto}</span></div>`;
        });
    },

    showPodium: async function() {
        if(this.podiumRendered) return;
        this.podiumRendered = true;
        this.resetTimers();
        
        const res = await fetch(`../api/partidas.php?action=ranking_parcial&id_partida=${gameId}`);
        const json = await res.json();
        const r = json.ranking || [];
        
        document.body.innerHTML = `
        <div style="height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; background: radial-gradient(circle, #25076b, #111); color:white; overflow:hidden; font-family:'Montserrat', sans-serif;">
            <h1 style="font-size:4.5rem; color:#facc15; margin-bottom:1em; text-shadow: 0 0 30px rgba(250,204,21,0.6); animation: zoomIn 0.8s both;">🏆 PODIUM FINAL 🏆</h1>
            <div style="display:flex; align-items:flex-end; gap:30px; height:400px; padding-bottom:20px;">
                ${r[1] ? `
                <div style="display:flex; flex-direction:column; align-items:center; animation: slideUp 1s 0.4s both;">
                    <div style="font-size:3.5rem; margin-bottom:10px;">${AvatarManager.render(r[1].avatar_id, r[1].sombrero_id || 0)}</div>
                    <div style="background:linear-gradient(180deg, #94a3b8 0%, #475569 100%); width:180px; height:180px; border-radius:20px 20px 0 0; display:flex; flex-direction:column; justify-content:center; align-items:center; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 3px solid #cbd5e1;">
                        <span style="font-size:2.5rem; font-weight:900;">2º</span>
                        <span style="font-size:1.4rem; font-weight:700; text-align:center; padding:0 10px;">${r[1].nombre_nick}</span>
                        <span style="font-size:1.1rem; opacity:0.9;">${r[1].puntuacion} pts</span>
                    </div>
                </div>` : ''}
                ${r[0] ? `
                <div style="display:flex; flex-direction:column; align-items:center; animation: slideUp 1.2s both; position:relative;">
                    <span style="font-size:5rem; position:absolute; top:-65px; animation: bounce 2s infinite;">👑</span>
                    <div style="font-size:5rem; margin-bottom:10px;">${AvatarManager.render(r[0].avatar_id, r[0].sombrero_id || 0)}</div>
                    <div style="background:linear-gradient(180deg, #facc15 0%, #b45309 100%); width:220px; height:280px; border-radius:20px 20px 0 0; display:flex; flex-direction:column; justify-content:center; align-items:center; box-shadow: 0 15px 40px rgba(251,191,36,0.3); border: 4px solid #fef08a;">
                        <span style="font-size:4rem; font-weight:900;">1º</span>
                        <span style="font-size:1.8rem; font-weight:800; text-align:center; padding:0 10px;">${r[0].nombre_nick}</span>
                        <span style="font-size:1.3rem; font-weight:700;">${r[0].puntuacion} pts</span>
                    </div>
                </div>` : ''}ss
                ${r[2] ? `
                <div style="display:flex; flex-direction:column; align-items:center; animation: slideUp 1s 0.7s both;">
                    <div style="font-size:3rem; margin-bottom:10px;">${AvatarManager.render(r[2].avatar_id, r[2].sombrero_id || 0)}</div>
                    <div style="background:linear-gradient(180deg, #d97706 0%, #78350f 100%); width:180px; height:120px; border-radius:20px 20px 0 0; display:flex; flex-direction:column; justify-content:center; align-items:center; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 3px solid #fbbf24;">
                        <span style="font-size:2rem; font-weight:900;">3º</span>
                        <span style="font-size:1.3rem; font-weight:700; text-align:center; padding:0 10px;">${r[2].nombre_nick}</span>
                        <span style="font-size:1rem; opacity:0.9;">${r[2].puntuacion} pts</span>
                    </div>
                </div>` : ''}
            </div>
            <button onclick="location.href='index.php'" class="nav-btn" style="margin-top:50px; padding:20px 60px; font-size:1.8rem; background:#facc15; color:#111; border-radius:50px; font-weight:800; border:none; cursor:pointer;">FINALIZAR</button>
        </div>
        <style>
            @keyframes slideUp { from { transform: translateY(600px); opacity:0; } to { transform: translateY(0); opacity:1; } }
            @keyframes zoomIn { from { transform: scale(0.5); opacity:0; } to { transform: scale(1); opacity:1; } }
            @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        </style>`;
    }
};
</script>