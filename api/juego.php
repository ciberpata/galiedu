<?php
// api/juego.php
header("Content-Type: application/json; charset=UTF-8");
session_start(); // NECESARIO para identificar al alumno logueado

require_once '../config/db.php';
require_once '../helpers/logger.php'; 

$db = (new Database())->getConnection();
$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'unirse': unirsePartida($db, $input); break;
        case 'seleccionar_avatar': seleccionarAvatar($db, $input); break;
        case 'responder':
            // 1. Obtener el modo de la partida actual
            $stmtM = $db->prepare("SELECT m.slug FROM partidas p JOIN modos_juego m ON p.id_modo = m.id_modo WHERE p.id_partida = (SELECT id_partida FROM jugadores_sesion WHERE id_sesion = ?)");
            $stmtM->execute([$input['id_sesion']]);
            $slug = $stmtM->fetchColumn() ?: 'quiz';

            // 2. Cargar el handler dinámico
            $handlerPath = dirname(__DIR__) . "/games/{$slug}/handler.php";
            if (file_exists($handlerPath)) {
                require_once $handlerPath;
                // Cada handler debe implementar una función estandarizada: [slug]_procesar_respuesta
                $funcionHandler = "{$slug}_procesar_respuesta";
                echo json_encode($funcionHandler($db, $input));
            } else {
                echo json_encode(['error' => 'Handler no encontrado']);
            }
            break;
        case 'estado_jugador': obtenerEstado($db, $input['id_sesion']); break;
        default: echo json_encode(['error' => 'Acción desconocida']);
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}

// --- FUNCIONES ---

function unirsePartida($db, $data) {
    // 1. LIMPIEZA TOTAL: Quitamos espacios y pasamos a MAYÚSCULAS
    $pin = strtoupper(trim($data['pin'] ?? ''));
    $nick = trim($data['nick'] ?? '');
    
    // Identificamos si es un usuario con cuenta o un invitado anónimo
    $idUsuarioRegistrado = (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) ? $_SESSION['user_id'] : null;
    // Iniciamos en 0 para que los invitados (anonimos) tengan que elegir su personaje
    $avatarId = 0; 
    $sombreroId = 0;

    if ($idUsuarioRegistrado) {
        $stmtU = $db->prepare("SELECT nick, avatar_id, sombrero_id FROM usuarios WHERE id_usuario = ?");
        $stmtU->execute([$idUsuarioRegistrado]);
        $uProfile = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($uProfile) {
            $nick = !empty($uProfile['nick']) ? $uProfile['nick'] : $nick;
            $avatarId = (int)$uProfile['avatar_id'];
            $sombreroId = (int)($uProfile['sombrero_id'] ?? 0);
        }
    }

    if (empty($nick)) throw new Exception("¡Ups! Introduce un apodo para poder jugar.");
    if (empty($pin)) throw new Exception("Debes introducir un PIN válido.");
    
    // 2. CONSULTA ROBUSTA: Buscamos la partida
    // IMPORTANTE: Asegúrate de que los estados coincidan exactamente con tu DB
    // Obtenemos también el slug del modo de juego
    // Permitimos unirse incluso si la partida ya ha empezado ('jugando')
    $stmt = $db->prepare("
        SELECT p.id_partida, p.estado, m.slug 
        FROM partidas p 
        JOIN modos_juego m ON p.id_modo = m.id_modo 
        WHERE UPPER(p.codigo_pin) = ? AND p.estado IN ('sala_espera', 'creada', 'jugando')
    ");
    $stmt->execute([$pin]);
    $partida = $stmt->fetch(PDO::FETCH_ASSOC);

    // DIAGNÓSTICO: Si no se encuentra, verificamos si es por el estado
    if (!$partida) {
        // Buscamos la partida sin filtrar por estado para saber el error real
        $stmtDebug = $db->prepare("SELECT estado FROM partidas WHERE UPPER(codigo_pin) = ?");
        $stmtDebug->execute([$pin]);
        $estadoReal = $stmtDebug->fetchColumn();

        if ($estadoReal) {
            // Ahora solo bloqueamos si la partida ya se ha cerrado definitivamente
            throw new Exception("La partida ya ha finalizado (Estado: " . $estadoReal . "). No puedes unirte.");
        } else {
            throw new Exception("No encontramos ninguna partida con el PIN: " . $pin);
        }
    }

    // 3. Verificación de Nick Duplicado
    $stmtNick = $db->prepare("SELECT COUNT(*) FROM jugadores_sesion WHERE id_partida = ? AND nombre_nick = ?");
    $stmtNick->execute([$partida['id_partida'], $nick]);
    if ($stmtNick->fetchColumn() > 0) throw new Exception("Ese nombre ya está en esta sala. Elige otro.");

    try {
        // 4. Inserción (Asegúrate de haber añadido la columna sombrero_id en la tabla jugadores_sesion)
        $sql = "INSERT INTO jugadores_sesion (id_partida, nombre_nick, avatar_id, sombrero_id, ip, id_usuario_registrado) VALUES (?, ?, ?, ?, ?, ?)";
        $db->prepare($sql)->execute([$partida['id_partida'], $nick, $avatarId, $sombreroId, $_SERVER['REMOTE_ADDR'], $idUsuarioRegistrado]);
        
        echo json_encode([
            'success' => true, 
            'id_sesion' => $db->lastInsertId(), 
            'id_partida' => $partida['id_partida'],
            'nick' => $nick,
            'slug' => $partida['slug'], // <--- Añadido
            'avatar_id' => $avatarId,
            'sombrero_id' => $sombreroId,
            'has_avatar' => ($avatarId > 0)
        ]);
    } catch (PDOException $e) {
        throw new Exception("Error técnico al entrar. ¿Has añadido la columna sombrero_id a la tabla?");
    }
}

function seleccionarAvatar($db, $data) {
    $idSesion = $data['id_sesion'];
    $avatarId = (int)$data['avatar_id'];
    $sombreroId = (int)($data['sombrero_id'] ?? 0);
    
    $stmt = $db->prepare("UPDATE jugadores_sesion SET avatar_id = ?, sombrero_id = ? WHERE id_sesion = ?");
    $stmt->execute([$avatarId, $sombreroId, $idSesion]);
    echo json_encode(['success' => true]);
}

// Nueva función para forzar el avance del proyector
function actualizarFicheroCache($db, $idPartida) {
    try {
        $sql = "SELECT p.estado, p.estado_pregunta, p.pregunta_actual_index, p.tiempo_inicio_pregunta, p.id_partida, p.codigo_pin,
                       pr.texto as texto_pregunta, pr.json_opciones, pr.tipo, pr.tiempo_limite,
                       u.nombre as nombre_anfitrion
                FROM partidas p
                JOIN usuarios u ON p.id_anfitrion = u.id_usuario
                LEFT JOIN preguntas pr ON p.id_pregunta_actual = pr.id_pregunta
                WHERE p.id_partida = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idPartida]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Sincronización de tiempo
            $data['tiempo_limite'] = (int)($data['tiempo_limite'] ?? 0);
            if ($data['estado_pregunta'] === 'respondiendo' && !empty($data['tiempo_inicio_pregunta'])) {
                $inicio = new DateTime($data['tiempo_inicio_pregunta']);
                $ahora = new DateTime();
                $diff = $ahora->getTimestamp() - $inicio->getTimestamp();
                $restante = $data['tiempo_limite'] - $diff;
                $data['tiempo_restante'] = ($restante > 0) ? (int)$restante : 0;
            } else {
                $data['tiempo_restante'] = 0;
            }

            // --- PROTECCIÓN EXTRA: También ofuscamos el fichero de caché ---
            if (defined('PROD_MODE') && PROD_MODE === true && $data['estado_pregunta'] === 'respondiendo') {
                $opciones = json_decode($data['json_opciones'], true);
                if (is_array($opciones)) {
                    foreach ($opciones as &$opcion) {
                        unset($opcion['es_correcta']);
                    }
                    $data['json_opciones'] = json_encode($opciones);
                }
            }

            $path = "../temp/partida_" . $data['id_partida'] . ".json";
            file_put_contents($path, json_encode(['success' => true, 'data' => $data]), LOCK_EX);
        }
    } catch (Exception $e) {}
}

    function obtenerEstado($db, $idSesion) {
        $sql = "SELECT p.estado, p.estado_pregunta, p.pregunta_actual_index, p.tiempo_inicio_pregunta, p.id_partida, p.codigo_pin,
                (SELECT COUNT(*) FROM partida_preguntas WHERE id_partida = p.id_partida) as total_preguntas,
                js.puntuacion, js.racha, js.avatar_id, js.sombrero_id,
                pr.texto as texto_pregunta, pr.json_opciones, pr.tipo, pr.tiempo_limite,
                u.nombre as nombre_anfitrion, u.foto_perfil as foto_anfitrion
            FROM jugadores_sesion js
            JOIN partidas p ON js.id_partida = p.id_partida
            JOIN usuarios u ON p.id_anfitrion = u.id_usuario
            LEFT JOIN preguntas pr ON p.id_pregunta_actual = pr.id_pregunta
            WHERE js.id_sesion = ?";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([$idSesion]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) { echo json_encode(['error' => 'Sesión no encontrada']); return; }
        
        $data['tiempo_limite'] = (int)($data['tiempo_limite'] ?? 0);
        
        if ($data['estado_pregunta'] === 'respondiendo' && $data['tiempo_inicio_pregunta']) {
            $inicio = new DateTime($data['tiempo_inicio_pregunta']);
            $ahora = new DateTime();
            $diff = $ahora->getTimestamp() - $inicio->getTimestamp();
            $restante = $data['tiempo_limite'] - $diff;
            $data['tiempo_restante'] = $restante > 0 ? (int)$restante : 0;
        } else {
            $data['tiempo_restante'] = 0;
        }

        if ($data['estado'] === 'finalizada') {
            $stmtTop = $db->prepare("SELECT nombre_nick, puntuacion, avatar_id FROM jugadores_sesion WHERE id_partida = ? AND avatar_id > 0 ORDER BY puntuacion DESC LIMIT 3");
            $stmtTop->execute([$data['id_partida']]);
            $data['top_ranking'] = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Delegamos el enriquecimiento del estado del jugador al handler del modo
        $stmtM = $db->prepare("SELECT m.slug FROM partidas p JOIN modos_juego m ON p.id_modo = m.id_modo WHERE p.id_partida = ?");
        $stmtM->execute([$data['id_partida']]);
        $slug = $stmtM->fetchColumn() ?: 'quiz';

        $handlerPath = "../games/{$slug}/handler.php";
        if (file_exists($handlerPath)) {
            require_once $handlerPath;
            $funcionEnriquecer = "{$slug}_enriquecer_estado_jugador";
            if (function_exists($funcionEnriquecer)) {
                $data = $funcionEnriquecer($db, $data);
            }
        }

        // LÍNEA CRÍTICA AÑADIDA:
        echo json_encode(['success' => true, 'data' => $data]);
    }
?>