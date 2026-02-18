<style>
/* DEFINICIÓN DE LA ANIMACIÓN DE FUEGO */
@keyframes streakFireAnim {
    0%, 100% { transform: scale(1) translateY(0); filter: brightness(100%); }
    50% { transform: scale(1.3) translateY(-8px); filter: brightness(150%); text-shadow: 0 0 15px orangered; }
}

/* Aseguramos que el emoji pueda recibir transformaciones */
.streak-fire {
    display: inline-block;
    transform-origin: center bottom;
}
</style>
<div id="screen-play" class="screen">
    <div class="top-status">
        <div class="q-bubble" id="playQNum">1</div>
        <div class="timer-display" id="timer"></div> 
    </div>
    <div class="mobile-q-text" id="playerQuestionText"></div>
    <div class="game-grid" id="gameButtonsContainer"></div>
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

<script>
const GameModule = {
    update: function(data) {
        const t = UI_TEXTS[currentLang];
        // Sumamos 1 al índice (0,1,2...) para mostrar (1,2,3...)
        const qNum = parseInt(data.pregunta_actual_index || 0) + 1;
        
        // Actualizamos el número en todas las burbujas posibles (juego y espera)
        ['playQNum', 'waitQNum'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.innerText = "#" + qNum;
        });

        // Si detectamos cambio de pregunta, forzamos reinicio de fase para evitar saltos
        if (window.lastTimerQ !== data.pregunta_actual_index) {
            lastPhase = 'cambio_pregunta';
            if (window.timerInterval) {
                clearInterval(window.timerInterval);
                window.timerInterval = null; // IMPORTANTE: Nullify para el syncTimer
            }
        }

        if (data.estado === 'jugando') {
            if (data.estado_pregunta === 'intro') {
                    showWaitScreen(t.q + " " + qNum, t.wait_others);
                    if (window.timerInterval) clearInterval(window.timerInterval);
                }
            else if (data.estado_pregunta === 'respondiendo') {
                this.syncTimer(data);
                if (lastPhase !== 'respondiendo') {
                    showScreen('screen-play');
                    this.renderButtons(data.json_opciones);
                }
            }
            else if (data.estado_pregunta === 'resultados') {
                if (window.timerInterval) clearInterval(window.timerInterval);
                if (lastPhase !== 'resultados' && data.racha !== undefined) {
                    showScreen('screen-feedback');
                    const racha = parseInt(data.racha || 0);
                    const banner = document.getElementById('fbBanner');
                    const fireIcon = document.querySelector('.streak-fire');
                    document.getElementById('fbStreak').innerText = racha;

                    if (racha > 0) {
                        banner.className = "feedback-banner";
                        // EFECTO VISUAL: Si la racha activa el multiplicador (>= 3)
                        if (racha >= 3) {
                            document.getElementById('fbTitle').innerText = "¡A TOPE! 🔥";
                            // Usamos el nombre de la animación definida en el CSS de arriba
                            if(fireIcon) fireIcon.style.animation = "streakFireAnim 0.6s ease-in-out infinite";
                            document.getElementById('fbMsg').innerText = "¡Multiplicador x1.2 activado!";
                        } else {
                            document.getElementById('fbTitle').innerText = t.correct;
                            if(fireIcon) fireIcon.style.animation = "none";
                            document.getElementById('fbMsg').innerText = getRandomPhrase('subiendo');
                        }
                        document.getElementById('fbIcon').className = "fa-solid fa-check fb-icon";
                    } else {
                        banner.className = "feedback-banner wrong";
                        document.getElementById('fbTitle').innerText = t.wrong;
                        document.getElementById('fbIcon').className = "fa-solid fa-xmark fb-icon";
                        if(fireIcon) fireIcon.style.animation = "none";
                        document.getElementById('fbMsg').innerText = getRandomPhrase('bajando');
                    }
                }
            }
        } else if (data.estado === 'finalizada') {
            if (window.timerInterval) clearInterval(window.timerInterval);
            showScreen('screen-end');
            const footer = document.getElementById('playerFooter');
            if(footer) footer.style.display = 'none';
            document.getElementById('finalScoreDisplay').innerText = data.puntuacion || 0;
        }
    },

    syncTimer: function(data) {
        if (window.lastTimerQ !== data.pregunta_actual_index || !window.timerInterval) {
            window.lastTimerQ = data.pregunta_actual_index;
            if (window.timerInterval) clearInterval(window.timerInterval);
            
            let left = (data.tiempo_restante && data.tiempo_restante > 0) ? parseInt(data.tiempo_restante) : parseInt(data.tiempo_limite || 0);
            const timerEl = document.getElementById('timer');
            if(timerEl) {
                timerEl.innerText = left;
                window.timerInterval = setInterval(() => {
                    if (left > 0) { 
                        left--; 
                        timerEl.innerText = left; 
                    } else { 
                        clearInterval(window.timerInterval); 
                        window.timerInterval = null; // Nulled
                        timerEl.innerText = 0; 
                    }
                }, 1000);
            }
        }
    },

    renderButtons: function(jsonStr) {
        const div = document.getElementById('gameButtonsContainer');
        if(!div) return;
        div.innerHTML = '';
        let opts = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
        if(!opts) return;
        opts.forEach((o, i) => {
            div.innerHTML += `<button class="game-btn bg-${i}" onclick="GameModule.answer(${i})">
                <span class="shape-icon">${SHAPES[i]}</span>
                <span class="btn-text">${o.texto}</span>
            </button>`;
        });
    },

    answer: async function(idx) {
        if (window.timerInterval) clearInterval(window.timerInterval); // Solución al contador colgado
        showWaitScreen(UI_TEXTS[currentLang].sent, UI_TEXTS[currentLang].wait_others);
        try {
            await fetch('../api/juego.php', {
                method:'POST', 
                body:JSON.stringify({action:'responder', id_sesion:mySessionId, respuesta:{indice:idx}})
            });
        } catch(e) {}
    }
};
</script>