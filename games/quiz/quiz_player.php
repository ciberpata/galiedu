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
const QuizPlayer = {
    update: function(data) {
        const t = UI_TEXTS[currentLang];
        const playQNum = document.getElementById('playQNum');
        if(playQNum) playQNum.innerText = "#" + (data.pregunta_actual_index || 1);

        if (data.estado === 'jugando') {
            if (data.estado_pregunta === 'intro') {
                showWaitScreen(t.q + " " + (data.pregunta_actual_index || 1), t.wait_others);
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
                    document.getElementById('fbMsg').innerText = getRandomPhrase(racha > 0 ? 'subiendo' : 'bajando');
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
        if (window.lastTimerQ !== data.pregunta_actual_index) {
            window.lastTimerQ = data.pregunta_actual_index;
            if (window.timerInterval) clearInterval(window.timerInterval);
            let left = (data.tiempo_restante && data.tiempo_restante > 0) ? parseInt(data.tiempo_restante) : parseInt(data.tiempo_limite || 0);
            const timerEl = document.getElementById('timer');
            if(timerEl) {
                timerEl.innerText = left;
                window.timerInterval = setInterval(() => {
                    if (left > 0) { left--; timerEl.innerText = left; } 
                    else { clearInterval(window.timerInterval); timerEl.innerText = 0; }
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
            div.innerHTML += `<button class="game-btn bg-${i}" onclick="QuizPlayer.answer(${i})">
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

function initGrids() {
    const aGrid = document.getElementById('avatarList'); 
    if(aGrid) {
        aGrid.innerHTML = '';
        Object.entries(AvatarManager.baseAvatars).forEach(([id, icon]) => {
            aGrid.innerHTML += `<div class="avatar-card" onclick="selectAvatar(${id}, '${icon}', this)">${icon}</div>`;
        });
    }
    const hGrid = document.getElementById('hatList'); 
    if(hGrid) {
        hGrid.innerHTML = '';
        Object.entries(AvatarManager.hats).forEach(([id, icon]) => {
            hGrid.innerHTML += `<div class="avatar-card" onclick="selectHat(${id}, '${icon}', this)">${icon || '❌'}</div>`;
        });
    }
}

function selectAvatar(id, icon, el) {
    tempAvatarId = id; 
    const preview = document.getElementById('previewAvatar');
    if(preview) preview.innerText = icon;
    document.querySelectorAll('#avatarList .avatar-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}

function selectHat(id, icon, el) {
    tempHatId = id; 
    const hatEl = document.getElementById('previewHat');
    if(hatEl) {
        hatEl.innerText = icon; 
        hatEl.classList.remove('animating'); 
        void hatEl.offsetWidth; 
        if(id > 0) hatEl.classList.add('animating');
    }
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
</script>