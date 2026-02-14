<?php
function quiz_procesar_respuesta($db, $data) {
    $idSesion = $data['id_sesion'];
    $respuestaJson = json_encode($data['respuesta']); 
    
    $sql = "SELECT p.id_partida, p.tiempo_inicio_pregunta, p.estado_pregunta, p.id_anfitrion,
            pr.id_pregunta, pr.json_opciones, pr.tiempo_limite,
            js.nombre_nick
            FROM partidas p
            JOIN preguntas pr ON p.id_pregunta_actual = pr.id_pregunta
            JOIN jugadores_sesion js ON js.id_sesion = ? AND js.id_partida = p.id_partida
            WHERE p.id_partida = js.id_partida";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$idSesion]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info || $info['estado_pregunta'] !== 'respondiendo') {
        return ['success' => false, 'error' => 'Tiempo agotado o pregunta cerrada'];
    }

    $opciones = json_decode($info['json_opciones'], true);
    $esCorrecta = false;
    $indiceResp = $data['respuesta']['indice'] ?? -1;
    if (isset($opciones[$indiceResp]) && $opciones[$indiceResp]['es_correcta']) {
        $esCorrecta = true;
    }

    $puntosGanados = 0;
    $stmtRacha = $db->prepare("SELECT racha FROM jugadores_sesion WHERE id_sesion = ?");
    $stmtRacha->execute([$idSesion]);
    $rachaActual = $stmtRacha->fetchColumn();

    if ($esCorrecta) {
        $inicio = new DateTime($info['tiempo_inicio_pregunta']);
        $ahora = new DateTime();
        $segundosTranscurridos = ($ahora->getTimestamp() - $inicio->getTimestamp()) + ($ahora->format('u') - $inicio->format('u')) / 1000000;
        if ($segundosTranscurridos < 0) $segundosTranscurridos = 0;
        
        $tLimite = (int)$info['tiempo_limite'];
        if ($segundosTranscurridos > $tLimite) $segundosTranscurridos = $tLimite;

        $factorTiempo = 1 - ($segundosTranscurridos / $tLimite);
        $puntosGanados = round(500 + (500 * $factorTiempo));
        
        $nuevaRacha = $rachaActual + 1;
        $bonusRacha = min(($nuevaRacha - 1) * 100, 500); 
        if ($nuevaRacha > 1) $puntosGanados += $bonusRacha;

        $db->prepare("UPDATE jugadores_sesion SET puntuacion = puntuacion + ?, racha = ? WHERE id_sesion = ?")
           ->execute([$puntosGanados, $nuevaRacha, $idSesion]);
    } else {
        $db->prepare("UPDATE jugadores_sesion SET racha = 0 WHERE id_sesion = ?")->execute([$idSesion]);
    }

    $db->prepare("INSERT INTO respuestas_log (id_sesion, id_pregunta, respuesta_json, es_correcta, tiempo_tardado) VALUES (?, ?, ?, ?, ?)")
       ->execute([$idSesion, $info['id_pregunta'], $respuestaJson, $esCorrecta ? 1 : 0, $segundosTranscurridos ?? 0]);

    return ['success' => true, 'correcta' => $esCorrecta, 'puntos' => $puntosGanados];
}