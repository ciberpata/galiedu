<style>
    .stun-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:200; backdrop-filter: blur(5px); }
    .crash-icon { font-size: 5rem; animation: shake 0.5s infinite; }
    @keyframes shake { 0% { transform: rotate(0); } 25% { transform: rotate(10deg); } 50% { transform: rotate(-10deg); } 75% { transform: rotate(5deg); } 100% { transform: rotate(0); } }
</style>

<div id="screen-play" class="screen">
    <div id="stunLayer" class="stun-overlay" style="display:none;">
        <div class="crash-icon">💥</div>
        <h2 style="color:white; margin-top: 15px;">¡HAS CHOCADO!</h2>
        <p style="color: #ccc;">Espera 3 segundos para reincorporarte...</p>
    </div>
    
    <div class="game-grid" id="persecucionButtons"></div>
</div>

<script>
const GameModule = {
    isBlocked: false,
    update: function(data) {
        if (data.estado_pregunta === 'respondiendo') {
            showScreen('screen-play');
            // Leemos el estado de bloqueo que nos envía el servidor
            this.checkStatus(data.bloqueado);
            this.renderButtons(data.json_opciones);
        } else {
            showWaitScreen("¡Carrera!", "Mira el proyector...");
        }
    },
    checkStatus: function(isBlocked) {
        this.isBlocked = isBlocked;
        const stunLayer = document.getElementById('stunLayer');
        const controles = document.getElementById('persecucionButtons');
        
        if (isBlocked) {
            stunLayer.style.display = 'flex';
            controles.style.pointerEvents = 'none'; // Deshabilita clics fantasma
        } else {
            stunLayer.style.display = 'none';
            controles.style.pointerEvents = 'auto'; // Reactiva botones
        }
    },
    renderButtons: function(json) {
        const container = document.getElementById('persecucionButtons');
        if(container.children.length > 0) return; // Evita redibujar en cada pulso AJAX
        
        JSON.parse(json).forEach((opt, i) => {
            const b = document.createElement('button');
            b.className = `game-btn bg-${i}`;
            b.onclick = () => this.send(i);
            b.innerHTML = `<span class="shape-icon">${SHAPES[i]}</span>`;
            container.appendChild(b);
        });
    },
    send: async function(idx) {
        if(this.isBlocked) return;
        
        const res = await fetch('../api/juego.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'responder', id_sesion: mySessionId, respuesta: idx })
        });
        const json = await res.json();
        
        if(json.bloqueo) {
            this.checkStatus(true);
            // El servidor ya lo tiene bloqueado, pero esto ayuda visualmente
            setTimeout(() => this.checkStatus(false), 3000); 
        } else {
            showWaitScreen("¡Correcto!", "¡Acelerando!");
        }
    }
};
</script>