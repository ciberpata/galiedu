<style>
    .stun-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:200; backdrop-filter: blur(5px); }
    .crash-icon { font-size: 5rem; animation: shake 0.5s infinite; }
    @keyframes shake { 0% { transform: rotate(0); } 25% { transform: rotate(10deg); } 50% { transform: rotate(-10deg); } 75% { transform: rotate(5deg); } 100% { transform: rotate(0); } }
    /* Banner fijo en la parte superior, por encima del layout flex */
    #persecucionQuestion {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 150;
        background: rgba(30,0,60,0.92);
        color: white;
        padding: 14px 20px;
        font-size: clamp(1rem, 2.5vw, 1.4rem);
        font-weight: 700;
        text-align: center;
        line-height: 1.3;
        border-bottom: 3px solid rgba(255,255,255,0.2);
        backdrop-filter: blur(6px);
        display: none;
    }
    /* Empujar el contenido del juego para que no quede bajo el banner */
    #screen-play.active { padding-top: 70px; }
</style>

<div id="screen-play" class="screen">
    <div id="stunLayer" class="stun-overlay" style="display:none;">
        <div class="crash-icon">💥</div>
        <h2 style="color:white; margin-top: 15px;">¡HAS CHOCADO!</h2>
        <p style="color: #ccc;">Espera 3 segundos para reincorporarte...</p>
    </div>

    <div class="question-banner" id="persecucionQuestion" style="display:none;"></div>
    <div class="game-grid" id="persecucionButtons"></div>
</div>

<script>
window.GameModule = {
    isBlocked: false,
    lastPreguntaIndex: -1,
    update: function(data) {
        if (data.estado_pregunta === 'respondiendo') {
            showScreen('screen-play');
            this.checkStatus(data.bloqueado);

            // Crear el banner dinámicamente si no existe en el DOM (fallback robusto)
            let qBanner = document.getElementById('persecucionQuestion');
            if (!qBanner) {
                qBanner = document.createElement('div');
                qBanner.id = 'persecucionQuestion';
                document.body.appendChild(qBanner);
            }
            if (data.texto_pregunta) {
                qBanner.textContent = data.texto_pregunta;
                qBanner.style.display = 'block';
            }

            // Redibujar botones si cambia la pregunta
            const currentIdx = data.pregunta_actual_index ?? -1;
            if (currentIdx !== this.lastPreguntaIndex) {
                document.getElementById('persecucionButtons').innerHTML = '';
                this.lastPreguntaIndex = currentIdx;
            }

            this.renderButtons(data.json_opciones);
        } else {
            const qBanner = document.getElementById('persecucionQuestion');
            if (qBanner) qBanner.style.display = 'none';
            document.getElementById('persecucionButtons').innerHTML = '';
            this.lastPreguntaIndex = -1;
            showWaitScreen("¡Carrera!", "Mira el proyector...");
        }
    },
    checkStatus: function(isBlocked) {
        this.isBlocked = isBlocked;
        const stunLayer = document.getElementById('stunLayer');
        const controles = document.getElementById('persecucionButtons');
        if (isBlocked) {
            stunLayer.style.display = 'flex';
            controles.style.pointerEvents = 'none';
        } else {
            stunLayer.style.display = 'none';
            controles.style.pointerEvents = 'auto';
        }
    },
    renderButtons: function(json) {
        // Guardia: si no hay opciones aún (pregunta no cargada), no hacer nada
        if (!json || json === 'null') return;
        const container = document.getElementById('persecucionButtons');
        if (container.children.length > 0) return;
        try {
            JSON.parse(json).forEach((opt, i) => {
                const b = document.createElement('button');
                b.className = `game-btn bg-${i}`;
                b.onclick = () => this.send(i);
                b.innerHTML = `<span class="shape-icon">${SHAPES[i]}</span>`;
                container.appendChild(b);
            });
        } catch(e) {
            console.error('Error al parsear opciones:', e, json);
        }
    },
    send: async function(idx) {
        if (this.isBlocked) return;
        const res = await fetch('../api/juego.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'responder', id_sesion: mySessionId, respuesta: idx })
        });
        const json = await res.json();
        if (json.bloqueo) {
            this.checkStatus(true);
            setTimeout(() => this.checkStatus(false), 3000);
        } else {
            showWaitScreen("¡Correcto!", "¡Acelerando!");
        }
    }
};
</script>