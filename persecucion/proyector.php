<style>
    .race-track { position: relative; width: 95%; height: 60vh; margin: 20px auto; background: rgba(0,0,0,0.2); border-radius: 30px; border: 10px solid rgba(255,255,255,0.05); padding: 20px; box-sizing: border-box; }
    .lane { height: 18%; position: relative; border-bottom: 2px dashed rgba(255,255,255,0.1); display: flex; align-items: center; }
    .racer { position: absolute; transition: left 0.8s cubic-bezier(0.25, 0.1, 0.25, 1); display: flex; flex-direction: column; align-items: center; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.5)); }
    .racer-name { background: #fff; color: #111; padding: 2px 10px; border-radius: 20px; font-weight: 800; font-size: 0.9rem; margin-top: 5px; white-space: nowrap; }
    .turbo-glow { filter: drop-shadow(0 0 15px #facc15) brightness(1.2); animation: pulseTurbo 0.5s infinite alternate; }
    .stunned { opacity: 0.4; filter: grayscale(1); }
    @keyframes pulseTurbo { from { transform: scale(1); } to { transform: scale(1.1); } }
    .finish-line { position: absolute; right: 40px; top: 0; bottom: 0; width: 20px; background: repeating-linear-gradient(0deg, #fff, #fff 20px, #000 20px, #000 40px); }
</style>

<div id="persecucion-proyector">
    <div style="text-align:center; padding: 20px;">
        <h1 id="qText" style="font-size: 2.5rem; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">¡PREPARAOS!</h1>
    </div>
    <div class="race-track" id="raceTrack">
        <div class="finish-line"></div>
    </div>
</div>

<script>
const GameModule = {
    update: function(state) {
        document.getElementById('qText').innerText = state.texto_pregunta || "¡Carrera en curso!";
        this.syncRacers(state.id_partida);
    },
    syncRacers: async function(idPartida) {
        const res = await fetch(`../api/partidas.php?action=ranking_parcial&id_partida=${idPartida}&t=${Date.now()}`);
        const json = await res.json();
        if(!json.success) return;

        const track = document.getElementById('raceTrack');
        const maxScore = 10000; // Meta a los 10.000 puntos

        json.ranking.forEach((p, i) => {
            let racer = document.getElementById(`racer-${p.nombre_nick.replace(/\s+/g, '-')}`);
            if(!racer) {
                racer = document.createElement('div');
                racer.id = `racer-${p.nombre_nick.replace(/\s+/g, '-')}`;
                racer.className = 'racer';
                track.appendChild(racer);
            }
            
            // Lógica de estados visuales
            racer.classList.toggle('turbo-glow', parseInt(p.racha) >= 3);
            const isStunned = p.bloqueado_hasta && new Date(p.bloqueado_hasta) > new Date();
            racer.classList.toggle('stunned', isStunned);

            racer.innerHTML = `
                <div style="font-size:3.5rem;">${AvatarManager.render(p.avatar_id, p.sombrero_id)}</div>
                <div class="racer-name">${isStunned ? '🛑 ' : ''}${p.nombre_nick}</div>
            `;
            
            // Posicionamiento según carril y progreso
            racer.style.top = (i * 20) + "%"; 
            const progress = Math.min((p.puntuacion / maxScore) * 85, 88);
            racer.style.left = progress + "%";
        });
    }
};
</script>