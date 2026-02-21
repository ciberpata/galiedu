<?php
    // games/persecucion/handler.php

    function persecucion_procesar_respuesta($db, $data) {
        $idSesion = $data['id_sesion'];
        $respuestaIndex = $data['respuesta'];
        
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

        $opciones = json_decode($info['json_opciones'], true);
        $esCorrecta = (isset($opciones[$respuestaIndex]) && $opciones[$respuestaIndex]['es_correcta'] == 1);

        if ($esCorrecta) {
            $nuevaRacha = $info['racha'] + 1;
            $puntos = ($nuevaRacha >= 3) ? 1500 : 1000; 
            $db->prepare("UPDATE jugadores_sesion SET puntuacion = puntuacion + ?, racha = ?, bloqueado_hasta = NULL WHERE id_sesion = ?")
            ->execute([$puntos, $nuevaRacha, $idSesion]);
            
            $stmtPuntos = $db->prepare("SELECT puntuacion FROM jugadores_sesion WHERE id_sesion = ?");
            $stmtPuntos->execute([$idSesion]);
            if ($stmtPuntos->fetchColumn() >= 10000) {
                $db->prepare("UPDATE partidas SET estado = 'finalizada' WHERE id_partida = ?")->execute([$info['id_partida']]);
            }
        } else {
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
        $bloqueo = $data['bloqueado_hasta'] ?? null;
        $data['bloqueado'] = ($bloqueo && strtotime($bloqueo) > time());
        return $data;
    }

    function persecucion_obtener_ranking($db, $idPartida) {
        $sql = "SELECT nombre_nick, puntuacion, avatar_id, sombrero_id, racha, bloqueado_hasta 
                FROM jugadores_sesion 
                WHERE id_partida = ? 
                ORDER BY puntuacion DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idPartida]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
?>