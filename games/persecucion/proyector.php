<style>
    #persecucion-proyector {
        background: url('../assets/img/bg-proyector.jpg') no-repeat center center;
        background-size: cover; height: 100vh; position: relative; overflow: hidden;
    }
    .race-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 20px; background: rgba(0,0,0,0.75); border-bottom: 2px solid rgba(255,255,255,0.15);
    }
    .race-header .q-info {
        color: white; font-size: 1.1rem; font-weight: 700; display: flex; gap: 20px; align-items: center;
    }
    .race-header .pin-badge {
        background: #6366f1; color: white; padding: 4px 16px; border-radius: 20px;
        font-size: 1.3rem; font-weight: 900; letter-spacing: 3px;
    }
    .race-header .q-badge-small {
        background: white; color: #333; padding: 4px 14px; border-radius: 20px; font-weight: 800;
    }
    .race-question {
        text-align: center; padding: 8px 40px;
        color: white; font-size: clamp(1.2rem, 2vw, 1.8rem); font-weight: 700;
        text-shadow: 2px 2px 6px #000;
        background: rgba(0,0,0,0.4);
    }
    .race-container { display: flex; width: 100%; height: calc(85vh - 80px); padding: 10px 20px 10px 20px; box-sizing: border-box; }
    .race-track { 
        position: relative; flex: 1; background: rgba(0,0,0,0.2); 
        border-radius: 20px; border: 4px solid rgba(255,255,255,0.1); overflow: hidden;
    }
    .sidebar-ranking {
        width: 260px; margin-left: 15px; background: rgba(0,0,0,0.6);
        border-radius: 20px; padding: 15px; color: white; border: 2px solid #facc15;
        display: none; overflow-y: auto;
    }
    .racer { 
        position: absolute; transition: left 1s linear, top 0.5s ease;
        display: flex; flex-direction: column; align-items: center; width: 110px;
    }
    .racer-name { 
        background: #fff; color: #000; padding: 2px 8px; border-radius: 10px;
        font-weight: 800; font-size: 0.75rem; margin-top: 3px; white-space: nowrap;
        display: flex; align-items: center; gap: 4px;
    }
    .kick-racer-btn {
        background: #ef4444; color: white; border: none; border-radius: 50%;
        width: 16px; height: 16px; font-size: 0.6rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.2s; flex-shrink: 0;
    }
    .racer:hover .kick-racer-btn { opacity: 1; }
    .finish-line { 
        position: absolute; right: 50px; top: 0; bottom: 0; width: 40px;
        background: repeating-linear-gradient(0deg, #fff, #fff 20px, #000 20px, #000 40px);
        display: none;
    }
    .turbo-glow { filter: drop-shadow(0 0 15px #facc15) brightness(1.2); animation: pulseTurbo 0.5s infinite alternate; }
    @keyframes pulseTurbo { from { transform: scale(1); } to { transform: scale(1.1); } }
    .race-controls {
        position: absolute; bottom: 20px; right: 20px; z-index: 999; display: flex; gap: 10px;
    }
    .race-controls button {
        padding: 12px 28px; border: 2px solid white; border-radius: 50px;
        font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: transform 0.1s;
    }
    .race-controls button:hover { transform: scale(1.05); }
    .btn-siguiente { background: #22c55e; color: white; }
    .btn-terminar  { background: #ef4444; color: white; }
</style>

<div id="persecucion-proyector" class="screen-layer">

    <!-- Cabecera con PIN, pregunta actual y controles -->
    <div class="race-header">
        <div class="q-info">
            <span class="pin-badge" id="racePinBadge">------</span>
            <span class="q-badge-small" id="raceQBadge">Pregunta 0/0</span>
        </div>
        <div class="race-controls" style="position:static; padding:0; margin:0;">
            <button class="btn-siguiente" id="btnSiguienteRace" onclick="GameModule.nextFase()">▶ Abrir respuestas</button>
            <button class="btn-terminar"  onclick="endGame(this)">⏹ Terminar</button>
        </div>
    </div>

    <!-- Pregunta actual -->
    <div class="race-question" id="qText">ESPERANDO CORREDORES...</div>

    <div class="race-container">
        <div class="race-track" id="raceTrack">
            <div class="finish-line" id="metaVisual"></div>
        </div>
        <div class="sidebar-ranking" id="rankingSidebar">
            <h3 style="color:#facc15; text-align:center; margin-bottom:10px; font-size:1rem;">POSICIONES</h3>
            <div id="leaderList"></div>
        </div>
    </div>
</div>

<script>
const GameModule = {
    rankingInterval: null,
    currentId: null,
    lastQIndex: -1,

    update: function(state) {
        switchScreen('persecucion-proyector');

        // Estado FINALIZADA: mostrar podium/ranking final
        if (state.estado === 'finalizada') {
            if (this.rankingInterval) {
                clearInterval(this.rankingInterval);
                this.rankingInterval = null;
            }
            document.getElementById('qText').textContent = '🏁 ¡CARRERA TERMINADA!';
            document.getElementById('rankingSidebar').style.display = 'block';
            document.getElementById('metaVisual').style.display = 'block';
            const btnNext = document.getElementById('btnSiguienteRace');
            if (btnNext) btnNext.style.display = 'none';
            this.sync(); // Actualización final del ranking
            return;
        }

        // PIN con # y contador de pregunta
        document.getElementById('racePinBadge').textContent = '# ' + (state.codigo_pin || '------');
        const qIdx = (parseInt(state.pregunta_actual_index) || 0) + 1;
        const qTotal = parseInt(state.total_preguntas) || '?';
        document.getElementById('raceQBadge').textContent = `Pregunta ${qIdx}/${qTotal}`;

        // Texto de la pregunta
        document.getElementById('qText').textContent = state.texto_pregunta || '¡Preparados!';

        this.currentId = state.id_partida;

        // En persecución, la fase 'intro' no tiene sentido — avanzamos automáticamente
        if (state.estado_pregunta === 'intro') {
            setTimeout(() => this.nextFase(), 800);
        }

        // El botón solo es útil en 'respondiendo' (para cerrar respuestas) y 'resultados'
        const btnNext = document.getElementById('btnSiguienteRace');
        if (btnNext) {
            if (state.estado_pregunta === 'intro') {
                btnNext.style.display = 'none'; // se auto-avanza, no hace falta
            } else if (state.estado_pregunta === 'respondiendo') {
                btnNext.style.display = '';
                btnNext.textContent = '⏸ Cerrar respuestas';
            } else if (state.estado_pregunta === 'resultados') {
                btnNext.style.display = '';
                btnNext.textContent = '⏭ Siguiente pregunta';
            }
        }

        // Visibilidad de meta y ranking
        const isPlaying = (state.estado === 'jugando' || state.estado === 'finalizada');
        document.getElementById('rankingSidebar').style.display = isPlaying ? 'block' : 'none';
        document.getElementById('metaVisual').style.display   = isPlaying ? 'block' : 'none';

        if (!this.rankingInterval) {
            this.sync();
            this.rankingInterval = setInterval(() => this.sync(), 1500);
        }
    },

    nextFase: async function() {
        if (!this.currentId) return;
        const btn = document.getElementById('btnSiguienteRace');
        if (btn) btn.disabled = true;
        try {
            const res = await fetch('../api/partidas.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'siguiente_fase', id_partida: this.currentId })
            });
            const json = await res.json();
            console.log('Fase avanzada:', json.fase);
        } catch(e) { console.error(e); }
        if (btn) btn.disabled = false;
    },

    kickPlayer: async function(idSesion, nickEscapado) {
        if (!confirm(`¿Expulsar a ${nickEscapado}?`)) return;
        try {
            await fetch('../api/partidas.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'expulsar_jugador', id_sesion: idSesion })
            });
            // El siguiente ciclo de sync() eliminará al corredor del DOM
            const el = document.getElementById(`racer-${nickEscapado}`);
            if (el) el.remove();
        } catch(e) { console.error(e); }
    },

    sync: async function() {
        if(!this.currentId) return;
        const res = await fetch(`../api/partidas.php?action=ranking_parcial&id_partida=${this.currentId}&t=${Date.now()}`);
        const json = await res.json();
        if(!json.success || !json.ranking) return;

        const track = document.getElementById('raceTrack');
        const leaders = document.getElementById('leaderList');
        leaders.innerHTML = '';

        // Escalado dinámico según número de jugadores
        const n = json.ranking.length;
        const trackH = track.clientHeight || 500;
        const slotH = Math.min(95, Math.floor((trackH - 20) / Math.max(n, 1)));
        const avatarSize = slotH >= 80 ? '3rem' : slotH >= 55 ? '2rem' : '1.4rem';
        const nameSize  = slotH >= 80 ? '0.75rem' : '0.6rem';

        // Limpiar corredores expulsados
        const nicksActivos = new Set(json.ranking.map(p => p.nombre_nick.replace(/[^a-z0-9]/gi, '-')));
        track.querySelectorAll('.racer').forEach(el => {
            if (!nicksActivos.has(el.id.replace('racer-', ''))) el.remove();
        });

        json.ranking.forEach((p, i) => {
            leaders.innerHTML += `<div style="margin-bottom:6px; font-size:0.9rem;">${i+1}. ${p.nombre_nick} <b>(${p.puntuacion})</b></div>`;

            const safeNick = p.nombre_nick.replace(/[^a-z0-9]/gi, '-');
            let racer = document.getElementById(`racer-${safeNick}`);
            if(!racer) {
                racer = document.createElement('div');
                racer.id = `racer-${safeNick}`;
                racer.className = 'racer';
                track.appendChild(racer);
            }

            racer.classList.toggle('turbo-glow', parseInt(p.racha) >= 3);
            const isStunned = p.bloqueado_hasta && new Date(p.bloqueado_hasta) > new Date();
            racer.style.opacity = isStunned ? '0.4' : '1';

            // avatar-display necesita position:relative para que el sombrero funcione
            const avatarVisual = (p.avatar_id && p.avatar_id > 0)
                ? AvatarManager.render(p.avatar_id, p.sombrero_id)
                : `<span style="font-size:${avatarSize}">👤</span>`;

            racer.innerHTML = `
                <div style="font-size:${avatarSize}; position:relative; display:inline-block; line-height:1;">${avatarVisual}</div>
                <div class="racer-name" style="font-size:${nameSize};">
                    ${isStunned ? '🛑 ' : ''}${p.nombre_nick}
                    <button class="kick-racer-btn" onclick="GameModule.kickPlayer(${p.id_sesion ?? 0},'${safeNick}')" title="Expulsar">✕</button>
                </div>
            `;

            racer.style.top  = (10 + (i * slotH)) + "px";
            const progress   = Math.min((p.puntuacion / 10000) * 85, 88);
            racer.style.left = progress + "%";
        });
    }
};
</script>