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
                <button class="nav-btn btn-next-q" onclick="QuizProyector.forceRanking(this)">
                    Saltar <i class="fa-solid fa-forward"></i>
                </button>
            </div>
            <div class="top-right">
                <div class="q-badge" id="qCounterDisplay">Pregunta 1</div>
            </div>
        </div>
        <div class="question-area">
            <div class="question-text" id="qText">Cargando pregunta...</div>
        </div>
        <div class="answers-grid" id="answersGrid"></div>
        
        <div id="introOverlay" class="intro-overlay" style="display:none;">
            <div id="introText" style="font-size:4rem; color:#a5b4fc; text-align:center; padding: 0 50px;"></div>
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
                <button class="nav-btn btn-next-q" onclick="QuizProyector.nextQuestion(this)">
                    Siguiente <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div id="resultsFeedbackArea" style="display:none; text-align:center; padding: 20px 0;">
            <div id="resQuestionTitle" style="font-size:2rem; font-weight:800; color:#fff; margin-bottom:10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);"></div>
            <div id="resCorrectAnsBox" style="font-size:2.2rem; font-weight:bold; background:rgba(34,197,94,0.2); border:3px solid #22c55e; color:#22c55e; display:inline-block; padding:15px 40px; border-radius:20px; box-shadow: 0 0 20px rgba(34,197,94,0.4);"></div>
        </div>

        <div class="ranking-container" id="rankingList"></div>
    </div>
</div>

<script>
const QuizProyector = {
    localTimer: null,
    isAdvancing: false,

    update: async function(state) {
        // Lógica de visibilidad del botón Siguiente
        const curIdx = parseInt(state.pregunta_actual_index || 0);
        const totalQ = parseInt(state.total_preguntas || 0);
        const isLast = (curIdx >= totalQ && totalQ > 0);
        
        document.querySelectorAll('.btn-next-q').forEach(b => {
            b.style.display = isLast ? 'none' : 'inline-block';
        });

        if (state.estado === 'jugando') {
            if (state.estado_pregunta === 'intro') {
                this.isAdvancing = false;
                switchScreen('screen-game');
                document.getElementById('introOverlay').style.display = 'flex';
                document.getElementById('introText').innerText = state.texto_pregunta;
                document.getElementById('qCounterDisplay').innerText = "Pregunta " + curIdx;
                this.startIntroTimer(state.id_partida);
            } 
            else if (state.estado_pregunta === 'respondiendo') {
                switchScreen('screen-game');
                document.getElementById('introOverlay').style.display = 'none';
                document.getElementById('qText').innerText = state.texto_pregunta;
                
                if (currentPhase !== 'respondiendo') {
                    this.renderAnswers(state.json_opciones);
                }
                
                // Punto 2: Sincronizar cronómetro fluido
                this.syncLocalTimer(state.tiempo_restante || 0);
                
                document.getElementById('answersCounter').innerText = `${state.respuestas_recibidas || 0} / ${state.total_jugadores || 0} Resp.`;

                // Punto 1: Auto-avance si todos responden
                if (state.total_jugadores > 0 && state.respuestas_recibidas >= state.total_jugadores && !this.isAdvancing) {
                    this.isAdvancing = true;
                    this.forceRanking();
                }
            } 
            else if (state.estado_pregunta === 'resultados') {
                this.isAdvancing = false;
                if (currentPhase !== 'resultados') {
                    currentPhase = 'resultados';
                    switchScreen('screen-ranking');
                    this.loadRanking(state.id_partida);
                }
            }
        } 
        else if (state.estado === 'finalizada') {
            this.showPodium();
        }
    },

    syncLocalTimer: function(seconds) {
        if (this.localTimer) clearInterval(this.localTimer);
        let time = parseInt(seconds);
        const el = document.getElementById('timer');
        el.innerText = time;
        this.localTimer = setInterval(() => {
            if (time > 0) {
                time--;
                el.innerText = time;
            } else {
                clearInterval(this.localTimer);
            }
        }, 1000);
    },

    loadRanking: async function(idPartida) {
        try {
            const [resState, resRank] = await Promise.all([
                fetch(`../api/partidas.php?action=estado_juego&codigo_pin=${PIN}`),
                fetch(`../api/partidas.php?action=ranking_parcial&id_partida=${idPartida}`)
            ]);
            const stateJson = await resState.json();
            const jsonRank = await resRank.json();

            // Punto 3: Mostrar respuesta correcta
            if (stateJson.success && stateJson.data) {
                const feedbackArea = document.getElementById('resultsFeedbackArea');
                feedbackArea.style.display = 'block';
                document.getElementById('resQuestionTitle').innerText = stateJson.data.texto_pregunta;
                
                const opciones = JSON.parse(stateJson.data.json_opciones || "[]");
                const correcta = opciones.find(o => o.es_correcta == 1 || o.es_correcta == true);
                document.getElementById('resCorrectAnsBox').innerText = "✓ " + (correcta ? correcta.texto : "Desconocida");
            }

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
        } catch (e) { console.error("Error en ranking:", e); }
    },

    startIntroTimer: function(idPartida) {
        if (window.introTimerActive) return;
        window.introTimerActive = true;
        let count = 5;
        const el = document.getElementById('introCount');
        let timer = setInterval(async () => {
            count--;
            if(el) el.innerText = count;
            if (count <= 0) {
                clearInterval(timer);
                window.introTimerActive = false;
                await fetch('../api/partidas.php', { 
                    method: 'POST', 
                    body: JSON.stringify({ action: 'siguiente_fase', id_partida: idPartida }) 
                });
            }
        }, 1000);
    },

    renderAnswers: function(jsonStr) {
        const grid = document.getElementById('answersGrid');
        if(!grid) return;
        grid.innerHTML = '';
        const opts = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
        opts.forEach((o, i) => {
            grid.innerHTML += `
                <div class="answer-card ans-${i}">
                    <span class="shape-icon">${shapes[i]}</span>
                    <span>${o.texto}</span>
                </div>`;
        });
    },

    forceRanking: async function() {
        await fetch('../api/partidas.php', { 
            method: 'POST', 
            body: JSON.stringify({ action: 'siguiente_fase', id_partida: gameId }) 
        });
    },

    nextQuestion: async function(btn) {
        if(btn) btn.disabled = true;
        await fetch('../api/partidas.php', { 
            method: 'POST', 
            body: JSON.stringify({ action: 'siguiente_fase', id_partida: gameId }) 
        });
    },

    showPodium: async function() {
        if(this.podiumRendered) return;
        this.podiumRendered = true;
        const res = await fetch(`../api/partidas.php?action=ranking_parcial&id_partida=${gameId}`);
        const json = await res.json();
        const r = json.ranking || [];
        
        document.body.innerHTML = `
        <div style="height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; background: radial-gradient(circle, #25076b, #111); color:white;">
            <h1 style="font-size:4rem; color:#facc15; margin-bottom:1em;">🏆 PODIUM FINAL 🏆</h1>
            <div style="display:flex; align-items:flex-end; gap:30px; height:300px;">
                ${r[1] ? `<div style="text-align:center;"><div style="font-size:3rem;">${AvatarManager.render(r[1].avatar_id, r[1].sombrero_id || 0)}</div><div style="background:#94a3b8; height:150px; width:150px; border-radius:15px 15px 0 0; display:flex; flex-direction:column; justify-content:center; color:#333;"><strong>2º</strong><br>${r[1].nombre_nick}<br>${r[1].puntuacion}</div></div>` : ''}
                ${r[0] ? `<div style="text-align:center;"><div style="font-size:4rem;">${AvatarManager.render(r[0].avatar_id, r[0].sombrero_id || 0)}</div><div style="background:#ffd700; height:220px; width:180px; border-radius:15px 15px 0 0; display:flex; flex-direction:column; justify-content:center; color:#333;"><strong>1º</strong><br>${r[0].nombre_nick}<br>${r[0].puntuacion}</div></div>` : ''}
                ${r[2] ? `<div style="text-align:center;"><div style="font-size:2.5rem;">${AvatarManager.render(r[2].avatar_id, r[2].sombrero_id || 0)}</div><div style="background:#d97706; height:100px; width:150px; border-radius:15px 15px 0 0; display:flex; flex-direction:column; justify-content:center; color:#fff;"><strong>3º</strong><br>${r[2].nombre_nick}<br>${r[2].puntuacion}</div></div>` : ''}
            </div>
            <button onclick="location.href='index.php'" class="nav-btn" style="margin-top:50px;">FINALIZAR</button>
        </div>`;
    }
};
</script>