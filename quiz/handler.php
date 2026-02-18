<?php
    function quiz_procesar_respuesta($db, $data) {
        $idSesion = $data['id_sesion'];
        // Buscamos si el jugador es registrado para las estadísticas
        $stmtUser = $db->prepare("SELECT id_usuario_registrado FROM jugadores_sesion WHERE id_sesion = ?");
        $stmtUser->execute([$idSesion]);
        $idReg = $stmtUser->fetchColumn();
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
            // Cálculo de tiempo con precisión de microsegundos para un ranking más justo
            $segundosTranscurridos = ($ahora->getTimestamp() - $inicio->getTimestamp()) + ($ahora->format('u') - $inicio->format('u')) / 1000000;
            if ($segundosTranscurridos < 0) $segundosTranscurridos = 0;

            $tLimite = (int)$info['tiempo_limite'];
            if ($segundosTranscurridos > $tLimite) $segundosTranscurridos = $tLimite;

            // BONO POR RAPIDEZ: Base de 1.000 puntos que disminuye linealmente según el tiempo empleado
            $factorTiempo = 1 - ($segundosTranscurridos / $tLimite);
            $puntosGanados = round(1000 * $factorTiempo);

            // Garantizamos un suelo de 100 puntos por acierto para mantener la motivación
            if ($puntosGanados < 100) $puntosGanados = 100;

            // MULTIPLICADOR POR RACHA: A partir de 3 aciertos seguidos, aplicamos un bono de x1.2
            $nuevaRacha = $rachaActual + 1;
            if ($nuevaRacha >= 3) {
                $puntosGanados = round($puntosGanados * 1.2);
            }

            $db->prepare("UPDATE jugadores_sesion SET puntuacion = puntuacion + ?, racha = ? WHERE id_sesion = ?")
            ->execute([$puntosGanados, $nuevaRacha, $idSesion]);
        }

        // Registramos siempre para que el proyector pueda contar la respuesta y avanzar
        $db->prepare("INSERT INTO respuestas_log (id_sesion, id_pregunta, respuesta_json, es_correcta, tiempo_tardado) VALUES (?, ?, ?, ?, ?)")
        ->execute([$idSesion, $info['id_pregunta'], $respuestaJson, $esCorrecta ? 1 : 0, $segundosTranscurridos ?? 0]);

        // Forzamos actualización usando la función definida en api/juego.php
        if (function_exists('actualizarFicheroCache')) {
            actualizarFicheroCache($db, $info['id_partida']);
        }

        return ['success' => true, 'correcta' => $esCorrecta, 'puntos' => $puntosGanados];
    }

    /*
    * Añade al estado de la partida la información específica del Quiz (preguntas, tiempo, etc.)
    */
    function quiz_enriquecer_estado($db, $data) {
        // 1. Obtener datos de la pregunta actual y anfitrión
        $sql = "SELECT p.id_partida, 
                (SELECT COUNT(*) FROM partida_preguntas WHERE id_partida = p.id_partida) as total_preguntas,
                pr.texto as texto_pregunta, pr.json_opciones, pr.tipo, pr.tiempo_limite,
                u.nombre as nombre_anfitrion
            FROM partidas p
            JOIN usuarios u ON p.id_anfitrion = u.id_usuario
            LEFT JOIN preguntas pr ON p.id_pregunta_actual = pr.id_pregunta
            WHERE p.id_partida = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$data['id_partida']]);
        $quizData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($quizData) {
            $data = array_merge($data, $quizData);
            
            // 2. Cálculo de tiempo restante
            $data['tiempo_limite'] = (int)($data['tiempo_limite'] ?? 0);
            $data['tiempo_restante'] = 0;
            
            if ($data['estado_pregunta'] === 'respondiendo' && $data['tiempo_inicio_pregunta']) {
                $inicio = new DateTime($data['tiempo_inicio_pregunta']);
                $ahora = new DateTime();
                $diff = $ahora->getTimestamp() - $inicio->getTimestamp();
                $restante = $data['tiempo_limite'] - $diff;
                $data['tiempo_restante'] = $restante > 0 ? (int)$restante : 0;
            }

            // 3. Limpieza para 'intro'
            if ($data['estado_pregunta'] === 'intro') { $data['json_opciones'] = null; }

            // 4. Ofuscación de respuesta correcta (Solo en producción y fase de respuesta)
            if (defined('PROD_MODE') && PROD_MODE === true && $data['estado_pregunta'] === 'respondiendo') {
                $opciones = json_decode($data['json_opciones'], true);
                if (is_array($opciones)) {
                    foreach ($opciones as &$opcion) {
                        unset($opcion['es_correcta']); 
                    }
                    $data['json_opciones'] = json_encode($opciones);
                }
            }
        }
        return $data;
    }

    /*
    * Obtiene el conteo de respuestas por cada opción (específico para Quiz)
    */
    function quiz_obtener_stats_pregunta($db, $idPartida) {
        // Obtenemos la pregunta actual de la partida
        $stmtP = $db->prepare("SELECT id_pregunta_actual FROM partidas WHERE id_partida = ?");
        $stmtP->execute([$idPartida]);
        $idPregunta = $stmtP->fetchColumn();

        // Contamos respuestas extrayendo el 'indice' del JSON (formato del Quiz)
        $stmt = $db->prepare("
            SELECT JSON_EXTRACT(respuesta_json, '$.indice') as indice, COUNT(*) as total 
            FROM respuestas_log 
            WHERE id_pregunta = ? AND id_sesion IN (SELECT id_sesion FROM jugadores_sesion WHERE id_partida = ?)
            GROUP BY indice");
        $stmt->execute([$idPregunta, $idPartida]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Limpia y prepara los datos que recibe el Alumno (específico para Quiz)
     */
    function quiz_enriquecer_estado_jugador($db, $data) {
        // Si estamos en fase de respuesta, borramos la solución del JSON
        if (defined('PROD_MODE') && PROD_MODE === true && ($data['estado_pregunta'] ?? '') === 'respondiendo') {
            $opciones = json_decode($data['json_opciones'] ?? '[]', true);
            if (is_array($opciones)) {
                foreach ($opciones as &$opcion) {
                    unset($opcion['es_correcta']);
                }
                $data['json_opciones'] = json_encode($opciones);
            }
        }
        return $data;
    }

    /*
     * Obtiene el ranking parcial para el modo Quiz (Top 5 por puntos)
     */
    function quiz_obtener_ranking($db, $idPartida) {
        $sql = "SELECT nombre_nick, puntuacion, avatar_id, sombrero_id, racha 
                FROM jugadores_sesion 
                WHERE id_partida = ? AND avatar_id > 0 
                ORDER BY puntuacion DESC LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idPartida]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Acciones finales al terminar un Quiz
     */
    function quiz_finalizar_partida($db, $idPartida) {
        // Aquí podrías añadir lógica para marcar la participación de los alumnos registrados
        // o limpiar datos temporales específicos si fuera necesario.
        // Por ahora, el Quiz no requiere acciones extra, pero la estructura queda lista.
        return true;
    }