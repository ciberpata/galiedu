<?php
// games/persecucion/handler.php

function persecucion_procesar_respuesta($db, $data) {
    $idSesion = $data['id_sesion'];
    $respuestaIndex = $data['respuesta'];
    
    // 1. Obtener información de la partida y el jugador
    $sql = "SELECT p.id_partida, p.id_pregunta_actual, pr.json_opciones, p.estado_pregunta, js.racha
            FROM partidas p
            JOIN preguntas pr ON p.id_pregunta_actual = pr.id_pregunta
            JOIN jugadores_sesion js ON js.id_sesion = ?
            WHERE p.id_partida = js.id_partida";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idSesion]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info || $info['estado_pregunta'] !== 'respondiendo') {
        return ['success' => false, 'error' => 'Pregunta cerrada'];
    }

    // 2. Validar respuesta
    $opciones = json_decode($info['json_opciones'], true);
    $esCorrecta = (isset($opciones[$respuestaIndex]) && $opciones[$respuestaIndex]['es_correcta'] == 1);

    if ($esCorrecta) {
        // LÓGICA TURBO: Puntos base 1000. Si racha >= 3, bono de racha.
        $nuevaRacha = $info['racha'] + 1;
        $puntos = 1000;
        if ($nuevaRacha >= 3) $puntos = 1500; // El "Turbo" da un 50% más de avance

        $db->prepare("UPDATE jugadores_sesion SET puntuacion = puntuacion + ?, racha = ?, bloqueado_hasta = NULL WHERE id_sesion = ?")
           ->execute([$puntos, $nuevaRacha, $idSesion]);
    } else {
        // LÓGICA OBSTÁCULO: Si falla, racha a 0 y bloqueo de 3 segundos
        $bloqueo = date('Y-m-d H:i:s', strtotime('+3 seconds'));
        $db->prepare("UPDATE jugadores_sesion SET racha = 0, bloqueado_hasta = ? WHERE id_sesion = ?")
           ->execute([$bloqueo, $idSesion]);
    }

    if (function_exists('actualizarFicheroCache')) {
        actualizarFicheroCache($db, $info['id_partida']);
    }

    return ['success' => true, 'correcta' => $esCorrecta, 'bloqueo' => !$esCorrecta];
}

function persecucion_enriquecer_estado_jugador($db, $data) {
    // Comprobamos si el jugador está bajo el efecto de un obstáculo (frenado)
    $stmt = $db->prepare("SELECT bloqueado_hasta FROM jugadores_sesion WHERE id_sesion = ?");
    $stmt->execute([$_SESSION['id_sesion'] ?? 0]); // O pasar id_sesion en $data
    $bloqueo = $stmt->fetchColumn();
    
    $data['bloqueado'] = ($bloqueo && strtotime($bloqueo) > time());
    return $data;
}

function persecucion_obtener_ranking($db, $idPartida) {
    $sql = "SELECT nombre_nick, puntuacion, avatar_id, sombrero_id, racha, bloqueado_hasta 
            FROM jugadores_sesion 
            WHERE id_partida = ? AND avatar_id > 0 
            ORDER BY puntuacion DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idPartida]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}