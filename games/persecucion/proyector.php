<style>
    #persecucion-proyector {
        background: url('../assets/img/bg-proyector.jpg') no-repeat center center;
        background-size: cover; height: 100vh; position: relative; overflow: hidden;
    }
    .race-container { display: flex; width: 100%; height: 85vh; padding: 20px; box-sizing: border-box; }
    .race-track { 
        position: relative; flex: 1; background: rgba(0,0,0,0.2); 
        border-radius: 20px; border: 4px solid rgba(255,255,255,0.1); overflow: hidden;
    }
    .sidebar-ranking {
        width: 300px; margin-left: 20px; background: rgba(0,0,0,0.6);
        border-radius: 20px; padding: 20px; color: white; border: 2px solid #facc15;
        display: none; /* OCULTO POR DEFECTO */
    }
    .racer { 
        position: absolute; transition: left 1s linear, top 0.5s ease;
        display: flex; flex-direction: column; align-items: center; width: 120px;
    }
    .racer-name { 
        background: #fff; color: #000; padding: 2px 10px; border-radius: 10px;
        font-weight: 800; font-size: 0.8rem; margin-top: 5px; white-space: nowrap;
    }
    .finish-line { 
        position: absolute; right: 50px; top: 0; bottom: 0; width: 40px;
        background: repeating-linear-gradient(0deg, #fff, #fff 20px, #000 20px, #000 40px);
        display: none; /* OCULTO POR DEFECTO */
    }
    .turbo-glow { filter: drop-shadow(0 0 15px #facc15) brightness(1.2); animation: pulseTurbo 0.5s infinite alternate; }
    @keyframes pulseTurbo { from { transform: scale(1); } to { transform: scale(1.1); } }
</style>

<div id="persecucion-proyector">
    <div style="padding: 10px; text-align: center;">
        <h1 id="qText" style="color:white; text-shadow: 2px 2px 4px #000;">ESPERANDO CORREDORES...</h1>
    </div>
    <div class="race-container">
        <div class="race-track" id="raceTrack">
            <div class="finish-line" id="metaVisual"></div>
        </div>
        <div class="sidebar-ranking" id="rankingSidebar">
            <h3 style="color:#facc15; text-align:center; margin-bottom:15px;">POSICIONES</h3>
            <div id="leaderList"></div>
        </div>
    </div>
</div>

<script>
const GameModule = {
    rankingInterval: null,
    currentId: null,

    update: function(state) {
        document.getElementById('qText').innerText = state.texto_pregunta || "¡Bienvenidos!";
        this.currentId = state.id_partida;

        // VISIBILIDAD: Solo mostramos meta y ranking si la partida está 'jugando' o 'finalizada'
        // Si está en 'sala_espera', estos elementos permanecen ocultos.
        const isPlaying = (state.estado === 'jugando' || state.estado === 'finalizada');
        document.getElementById('rankingSidebar').style.display = isPlaying ? 'block' : 'none';
        document.getElementById('metaVisual').style.display = isPlaying ? 'block' : 'none';

        if (!this.rankingInterval) {
            this.sync();
            this.rankingInterval = setInterval(() => this.sync(), 1500);
        }
    },

    sync: async function() {
        if(!this.currentId) return;
        const res = await fetch(`api/partidas.php?action=ranking_parcial&id_partida=${this.currentId}&t=${Date.now()}`);
        const json = await res.json();
        if(!json.success || !json.ranking) return;

        const track = document.getElementById('raceTrack');
        const leaders = document.getElementById('leaderList');
        leaders.innerHTML = '';

        json.ranking.forEach((p, i) => {
            // Actualizar Sidebar
            leaders.innerHTML += `<div style="margin-bottom:8px;">${i+1}. ${p.nombre_nick} (${p.puntuacion})</div>`;

            // Actualizar Pista
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

            racer.innerHTML = `
                <div style="font-size:3.5rem;">${AvatarManager.render(p.avatar_id, p.sombrero_id)}</div>
                <div class="racer-name">${isStunned ? '🛑 ' : ''}${p.nombre_nick}</div>
            `;
            
            racer.style.top = (20 + (i * 95)) + "px";
            const progress = Math.min((p.puntuacion / 10000) * 85, 88);
            racer.style.left = progress + "%";
        });
    }
};
</script>