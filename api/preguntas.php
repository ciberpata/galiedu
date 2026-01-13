<?php
// api/preguntas.php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once '../config/db.php';
require_once '../helpers/logger.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 6) {
        http_response_code(403);
        throw new Exception('No autorizado');
    }

    $db = (new Database())->getConnection();
    $uid = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];

    // Manejo de descarga de plantilla (GET directo)
    if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
        handleDownloadTemplate();
        exit;
    }

    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    $input = (strpos($contentType, 'application/json') !== false) ? json_decode(file_get_contents("php://input"), true) : $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            handleList($db, $uid, $role);
            break;
        case 'get_one':
            handleGetOne($db, $uid, $role);
            break;
        case 'create_manual':
            handleCreateManual($db, $uid, $role, $input);
            break;
        case 'update_manual':
            handleUpdate($db, $uid, $role, $input);
            break;
        case 'duplicate':
            handleDuplicate($db, $uid, $role, $input);
            break;
        case 'delete':
            handleDelete($db, $uid, $role, $input);
            break;
        case 'restore':
            handleRestore($db, $uid, $role, $input);
            break;
        case 'bulk_action':
            handleBulkAction($db, $uid, $role, $input);
            break;
        case 'list_subjects':
            handleListSubjects($db);
            break; // Para el datalist de asignatura
        case 'list_sections':
            handleListSections($db);
            break; // Para el datalist de sección
        case 'validate_import':
            handleValidateImport();
            break;
        case 'execute_import':
            handleExecuteImport($db, $uid, $role, $input);
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage(), 'error' => $e->getMessage()]);
    exit;
}

// --- FUNCIONES PRINCIPALES ---

function handleList($db, $uid, $role)
{
    // Parámetros
    $scope = $_GET['scope'] ?? 'mine';
    $search = $_GET['search'] ?? '';
    $special = $_GET['special'] ?? '';

    // Filtros estándar
    $f_idioma = $_GET['f_idioma'] ?? '';
    $f_tipo = $_GET['f_tipo'] ?? '';
    $f_seccion = $_GET['f_seccion'] ?? '';
    $f_usuario = $_GET['f_usuario'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';

    // Ordenación
    $sort = $_GET['sort'] ?? 'id_pregunta';
    $order = $_GET['order'] ?? 'DESC';
    $allowedSorts = ['id_pregunta', 'texto', 'seccion', 'idioma', 'creado_en', 'doble_valor', 'veces_usada', 'nivel_educativo', 'dificultad', 'id_asignatura', 'nombre_propietario'];
    if (!in_array($sort, $allowedSorts)) $sort = 'id_pregunta';
    if ($order !== 'ASC' && $order !== 'DESC') $order = 'DESC';

    // Query Base - MODIFICADA para incluir el rol del propietario
    $sql = "SELECT SQL_CALC_FOUND_ROWS p.*, u.nombre as nombre_propietario, u.id_rol as rol_propietario, pad.nombre as nombre_academia,
            (SELECT COUNT(*) FROM partida_preguntas WHERE id_pregunta = p.id_pregunta) as veces_usada
            FROM preguntas p 
            JOIN usuarios u ON p.id_propietario = u.id_usuario 
            LEFT JOIN usuarios pad ON u.id_padre = pad.id_usuario";

    // Lógica de Papelera
    if ($special === 'trash') {
        $sql .= " WHERE p.fecha_eliminacion IS NOT NULL";
    } else {
        $sql .= " WHERE p.fecha_eliminacion IS NULL";
    }
    $params = [];

    // --- LÓGICA DE VISIBILIDAD (SCOPE) ACTUALIZADA ---
    if ($role == 1) {
        // Superadmin ve todo
    } elseif ($role == 2) {
        // ACADEMIA: Ve sus preguntas + subordinados + GLOBALES (SA)
        $sql .= " AND (p.id_propietario = ? OR u.id_padre = ? OR u.id_rol = 1)";
        $params[] = $uid;
        $params[] = $uid;
    } else {
        // PROFESORES y EDITORES
        if ($scope === 'shared_bank' && in_array($role, [3, 4, 5])) {
            $stmtP = $db->prepare("SELECT id_padre FROM usuarios WHERE id_usuario = ?");
            $stmtP->execute([$uid]);
            $padre = $stmtP->fetchColumn();

            if ($padre) {
                // Ve lo del padre, lo de los editores y lo global
                $sql .= " AND p.id_propietario != ? AND (p.id_propietario = ? OR p.id_propietario IN (SELECT id_usuario FROM usuarios WHERE id_padre = ? AND id_rol = 5) OR u.id_rol = 1)";
                $params[] = $uid;
                $params[] = $padre;
                $params[] = $padre;
            } else {
                $sql .= " AND (u.id_rol = 1 AND p.id_propietario != ?)";
                $params[] = $uid;
            }
        } else {
            // Vista normal: Mis preguntas + Globales
            $sql .= " AND (p.id_propietario = ? OR u.id_rol = 1)";
            $params[] = $uid;
        }
    }

    // --- FILTROS ---

    if ($special === 'orphan') {
        $sql .= " AND (p.seccion IS NULL OR p.seccion = '')";
    } elseif (!empty($f_seccion)) {
        $sql .= " AND p.seccion LIKE ?";
        $params[] = "%$f_seccion%";
    }

    if (!empty($search)) {
        $sql .= " AND (p.texto LIKE ? OR p.seccion LIKE ? OR p.etiquetas LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    if (!empty($_GET['f_asignatura'])) {
        $sql .= " AND p.id_asignatura LIKE ?";
        $params[] = "%" . $_GET['f_asignatura'] . "%";
    }
    if (!empty($_GET['f_dificultad'])) {
        $sql .= " AND p.dificultad = ?";
        $params[] = $_GET['f_dificultad'];
    }
    if (!empty($_GET['f_nivel'])) {
        $sql .= " AND p.nivel_educativo LIKE ?";
        $params[] = "%" . $_GET['f_nivel'] . "%";
    }
    if (!empty($_GET['f_taxonomia'])) {
        $sql .= " AND p.etiquetas LIKE ?";
        $params[] = "%" . $_GET['f_taxonomia'] . "%";
    }
    if (!empty($f_idioma)) {
        $sql .= " AND p.idioma = ?";
        $params[] = $f_idioma;
    }
    if (!empty($f_usuario)) {
        $sql .= " AND p.id_propietario = ?";
        $params[] = $f_usuario;
    }

    if (!empty($dateFrom)) {
        $sql .= " AND p.creado_en >= ?";
        $params[] = $dateFrom . " 00:00:00";
    }
    if (!empty($dateTo)) {
        $sql .= " AND p.creado_en <= ?";
        $params[] = $dateTo . " 23:59:59";
    }

    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;

    $sql .= " ORDER BY $sort $order LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = $db->query("SELECT FOUND_ROWS()")->fetchColumn();

    echo json_encode(['data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit]);
}

function handleGetOne($db, $uid, $role)
{
    $id = $_GET['id'] ?? 0;
    // Modificado para no obtener preguntas borradas lógicamente
    $stmt = $db->prepare("SELECT * FROM preguntas WHERE id_pregunta = ? AND fecha_eliminacion IS NULL");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) throw new Exception("Pregunta no encontrada o eliminada");
    echo json_encode(['data' => $data, 'success' => true]);
}

function handleCreateManual($db, $uid, $role, $data)
{
    saveQuestion($db, $uid, $role, $data, null);
}

function handleUpdate($db, $uid, $role, $data)
{
    saveQuestion($db, $uid, $role, $data, $data['id_pregunta']);
}

function saveQuestion($db, $uid, $role, $data, $idToUpdate = null)
{
    $targetId = $uid;
    $compartida = 0;

    if ($role == 1 || $role == 2) {
        $targetId = !empty($data['target_user_id']) ? $data['target_user_id'] : $uid;
        if ($role == 2 && $targetId == $uid) $compartida = 1;
    } elseif ($role == 5) {
        $stmtP = $db->prepare("SELECT id_padre FROM usuarios WHERE id_usuario = ?");
        $stmtP->execute([$uid]);
        $padre = $stmtP->fetchColumn();
        if ($padre) {
            $targetId = $padre;
            $compartida = 1;
        }
    }

    $ops = $data['opciones'] ?? [];
    $opcionesF = [];
    foreach ($ops as $o) {
        $opcionesF[] = ['texto' => $o['texto'], 'es_correcta' => (bool)($o['es_correcta'] ?? false)];
    }
    $json = json_encode($opcionesF, JSON_UNESCAPED_UNICODE);

    $doble = (int)($data['doble_valor'] ?? 0);
    $tiempo = (int)($data['tiempo_limite'] ?? 20);

    // --- NUEVOS CAMPOS DE TAXONOMÍA ---
    $id_asignatura = !empty($data['id_asignatura']) ? trim($data['id_asignatura']) : null;
    $nivel = !empty($data['nivel_educativo']) ? trim($data['nivel_educativo']) : null;
    $dificultad = !empty($data['dificultad']) ? (int)$data['dificultad'] : 1;
    $etiquetas = !empty($data['etiquetas']) ? trim($data['etiquetas']) : null;

    if ($idToUpdate) {
        if (!checkOwner($db, $idToUpdate, $uid, $role)) throw new Exception("No permiso edición");
        $sql = "UPDATE preguntas SET texto=?, seccion=?, tipo=?, idioma=?, tiempo_limite=?, doble_valor=?, json_opciones=?, compartida=?, 
                id_asignatura=?, nivel_educativo=?, dificultad=?, etiquetas=? 
                WHERE id_pregunta=?";
        $db->prepare($sql)->execute([
            trim($data['texto']),
            trim($data['seccion']),
            $data['tipo'],
            $data['idioma'],
            $tiempo,
            $doble,
            $json,
            $compartida,
            $id_asignatura,
            $nivel,
            $dificultad,
            $etiquetas,
            $idToUpdate
        ]);
        Logger::registrar($db, $uid, 'UPDATE', 'preguntas', $idToUpdate);
    } else {
        $sql = "INSERT INTO preguntas (id_propietario, texto, seccion, tipo, idioma, tiempo_limite, doble_valor, json_opciones, compartida, id_asignatura, nivel_educativo, dificultad, etiquetas) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $db->prepare($sql)->execute([
            $targetId,
            trim($data['texto']),
            trim($data['seccion']),
            $data['tipo'],
            $data['idioma'],
            $tiempo,
            $doble,
            $json,
            $compartida,
            $id_asignatura,
            $nivel,
            $dificultad,
            $etiquetas
        ]);
        Logger::registrar($db, $uid, 'INSERT', 'preguntas', $db->lastInsertId());
    }
    echo json_encode(['success' => true]);
}

function handleDuplicate($db, $uid, $role, $input)
{
    $id = $input['id_pregunta'];
    // Seleccionamos todos los campos nuevos para que la copia sea idéntica
    $stmt = $db->prepare("SELECT texto, json_opciones, seccion, tipo, tiempo_limite, idioma, id_asignatura, nivel_educativo, dificultad, etiquetas FROM preguntas WHERE id_pregunta = ?");
    $stmt->execute([$id]);
    $orig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orig) throw new Exception("Original no encontrada");

    $sql = "INSERT INTO preguntas (texto, json_opciones, seccion, tipo, tiempo_limite, id_propietario, idioma, id_asignatura, nivel_educativo, dificultad, etiquetas) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $db->prepare($sql)->execute([
        $orig['texto'] . " (Copia)",
        $orig['json_opciones'],
        $orig['seccion'],
        $orig['tipo'],
        $orig['tiempo_limite'],
        $uid, // El nuevo dueño es quien duplica
        $orig['idioma'],
        $orig['id_asignatura'],
        $orig['nivel_educativo'],
        $orig['dificultad'],
        $orig['etiquetas']
    ]);

    Logger::registrar($db, $uid, 'INSERT', 'preguntas', $db->lastInsertId(), ['action' => 'duplicate', 'from' => $id]);
    echo json_encode(['success' => true]);
}

function handleDelete($db, $uid, $role, $data)
{
    $id = $data['id_pregunta'] ?? $data['id'];
    if (!checkOwner($db, $id, $uid, $role)) throw new Exception("No autorizado");
    $db->prepare("UPDATE preguntas SET fecha_eliminacion = NOW() WHERE id_pregunta = ?")->execute([$id]);
    Logger::registrar($db, $uid, 'SOFT_DELETE', 'preguntas', $id);
    echo json_encode(['success' => true, 'mensaje' => 'Pregunta movida a la papelera']);
}

function handleRestore($db, $uid, $role, $data)
{
    $id = $data['id_pregunta'] ?? $data['id'];
    if (!checkOwner($db, $id, $uid, $role)) throw new Exception("No autorizado");
    $db->prepare("UPDATE preguntas SET fecha_eliminacion = NULL WHERE id_pregunta = ?")->execute([$id]);
    Logger::registrar($db, $uid, 'RESTORE', 'preguntas', $id);
    echo json_encode(['success' => true, 'mensaje' => 'Pregunta restaurada']);
}

function handleBulkAction($db, $uid, $role, $data)
{
    $ids = $data['ids'] ?? $data['question_ids'] ?? [];
    $type = $data['type'] ?? '';
    if (empty($ids)) throw new Exception("No hay elementos seleccionados");
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $params = $ids;
    if ($type === 'delete') {
        $sql = "UPDATE preguntas SET fecha_eliminacion = NOW() WHERE id_pregunta IN ($in)";
    } elseif ($type === 'restore') {
        $sql = "UPDATE preguntas SET fecha_eliminacion = NULL WHERE id_pregunta IN ($in)";
    } elseif ($type === 'reassign') {
        $target = $data['target'] ?? $data['new_owner_id'];
        $sql = "UPDATE preguntas SET id_propietario = ? WHERE id_pregunta IN ($in)";
        array_unshift($params, $target);
    }
    $db->prepare($sql)->execute($params);
    echo json_encode(["success" => true]);
}

function checkOwner($db, $idPregunta, $uid, $role)
{
    if ($role == 1) return true;
    $stmt = $db->prepare("SELECT id_propietario FROM preguntas WHERE id_pregunta = ?");
    $stmt->execute([$idPregunta]);
    $owner = $stmt->fetchColumn();
    if ($owner == $uid) return true;
    if ($role == 2) {
        $stmt2 = $db->prepare("SELECT id_padre FROM usuarios WHERE id_usuario = ?");
        $stmt2->execute([$owner]);
        if ($stmt2->fetchColumn() == $uid) return true;
    }
    return false;
}

// --- IMPORTACIÓN COMPLETA ---

function handleDownloadTemplate()
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=plantilla_preguntas.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    // Cabeceras actualizadas con taxonomía
    fputcsv($out, ['texto_pregunta', 'seccion', 'idioma', 'opcion_a', 'opcion_b', 'opcion_c', 'opcion_d', 'respuesta_correcta', 'tiempo_limite', 'doble_valor', 'asignatura', 'nivel', 'dificultad', 'etiquetas'], ';', '"', '\\');
    fputcsv($out, ['Ejemplo: ¿Capital de España?', 'Geografía', 'es', 'Barcelona', 'Madrid', 'Sevilla', 'Valencia', '2', '20', '0', 'Historia', '1º ESO', '3', 'geografía, capitales, europa'], ';', '"', '\\');
    fclose($out);
}

function handleValidateImport()
{
    if (empty($_FILES['archivo'])) {
        echo json_encode(['status' => 'error', 'mensaje' => 'No archivo']);
        return;
    }
    $file = $_FILES['archivo']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, ['xls', 'xlsx', 'ods'])) {
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $headers = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $val = $cell->getValue();
                    if ($val !== null && $val !== '') $headers[] = (string)$val;
                }
            }
            echo json_encode(['status' => 'need_mapping', 'headers' => $headers]);
            return;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error leyendo archivo: ' . $e->getMessage()]);
            return;
        }
    }
    $fh = fopen($file, 'r');
    $line = fgets($fh);
    $delim = (substr_count($line, ';') > substr_count($line, ',')) ? ';' : ',';
    rewind($fh);
    $header = fgetcsv($fh, 0, $delim, '"', '\\');

    if (!$header) {
        echo json_encode(['status' => 'error', 'mensaje' => 'CSV inválido o vacío']);
        return;
    }

    // SIEMPRE devolvemos 'need_mapping' para que el usuario vea la tabla de columnas
    echo json_encode(['status' => 'need_mapping', 'headers' => $header]);
}

function handleExecuteImport($db, $uid, $role, $postData)
{
    // Misma lógica de asignación que saveQuestion
    $targetId = $uid;
    $compartida = 0;

    if ($role == 1 || $role == 2) {
        $targetId = !empty($postData['target_user_id']) ? $postData['target_user_id'] : $uid;
        if ($role == 2 && $targetId == $uid) $compartida = 1;
    } elseif ($role == 5) {
        $stmtP = $db->prepare("SELECT id_padre FROM usuarios WHERE id_usuario = ?");
        $stmtP->execute([$uid]);
        $padre = $stmtP->fetchColumn();
        if ($padre) {
            $targetId = $padre;
            $compartida = 1;
        }
    }

    $file = $_FILES['archivo']['tmp_name'];
    $mappingJSON = $postData['mapping'] ?? null;
    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

    $inserted = 0;
    $skipped = 0;
    $stmtCheck = $db->prepare("SELECT id_pregunta FROM preguntas WHERE texto = ? AND id_propietario = ?");
    // Query INSERT actualizada con los nuevos campos
    $sqlIns = "INSERT INTO preguntas (id_propietario, texto, seccion, tipo, idioma, tiempo_limite, doble_valor, json_opciones, compartida, id_asignatura, nivel_educativo, dificultad, etiquetas)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmtIns = $db->prepare($sqlIns);

    // Lógica Excel
    if ($mappingJSON && in_array($ext, ['xls', 'xlsx', 'ods'])) {
        $map = json_decode($mappingJSON, true);
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            $getVal = function ($key) use ($map, $sheet, $rowIdx) {
                if (!isset($map[$key])) return '';
                $colIndex = $map[$key] + 1;
                $colString = Coordinate::stringFromColumnIndex($colIndex);
                return trim($sheet->getCell($colString . $rowIdx)->getValue() ?? '');
            };
            $texto = $getVal('texto');
            if (empty($texto)) continue;
            $stmtCheck->execute([$texto, $targetId]);
            if ($stmtCheck->fetch()) {
                $skipped++;
                continue;
            }

            $opciones = [];
            $rcRaw = strtolower($getVal('correcta'));
            $rcIndex = -1;
            if (in_array($rcRaw, ['1', 'a', 'opcion_a', 'opcion a', 'si', 'sí', 'verdadero'])) $rcIndex = 0;
            elseif (in_array($rcRaw, ['2', 'b', 'opcion_b', 'opcion b', 'no', 'falso'])) $rcIndex = 1;
            elseif (in_array($rcRaw, ['2', 'b', 'opcion_b', 'no', 'falso'])) $rcIndex = 1;
            elseif (in_array($rcRaw, ['3', 'c'])) $rcIndex = 2;
            elseif (in_array($rcRaw, ['4', 'd'])) $rcIndex = 3;

            $opciones[] = ['texto' => $getVal('a'), 'es_correcta' => ($rcIndex === 0)];
            $opciones[] = ['texto' => $getVal('b'), 'es_correcta' => ($rcIndex === 1)];
            if ($getVal('c')) $opciones[] = ['texto' => $getVal('c'), 'es_correcta' => ($rcIndex === 2)];
            if ($getVal('d')) $opciones[] = ['texto' => $getVal('d'), 'es_correcta' => ($rcIndex === 3)];

            $tipo = (count($opciones) == 2 && strtolower($opciones[0]['texto']) == 'verdadero') ? 'verdadero_falso' : 'quiz';

            $stmtIns->execute([
                $targetId,
                $texto,
                $getVal('seccion'),
                $tipo,
                $getVal('idioma') ?: 'es',
                (int)($getVal('tiempo') ?: 20),
                (int)($getVal('doble') ?: 0),
                json_encode($opciones, JSON_UNESCAPED_UNICODE),
                $compartida,
                $getVal('asignatura'),
                $getVal('nivel'),
                (int)($getVal('dificultad') ?: 1),
                $getVal('etiquetas')
            ]);
            $inserted++;
        }
    } else {
        // Lógica CSV
        $fh = fopen($file, 'r');
        $line = fgets($fh);
        $delim = (substr_count($line, ';') > substr_count($line, ',')) ? ';' : ',';
        rewind($fh);
        $header = fgetcsv($fh, 0, $delim, '"', '\\');
        $decodedMap = json_decode($mappingJSON, true);
        $map = (!empty($decodedMap)) ? $decodedMap : getCsvMap($header);
        while (($row = fgetcsv($fh, 0, $delim, '"', '\\')) !== false) {
            $v = function ($k) use ($row, $map) {
                return isset($map[$k]) ? trim($row[$map[$k]] ?? '') : '';
            };
            $texto = $v('texto');
            if (empty($texto)) continue;

            $stmtCheck->execute([$texto, $targetId]);
            if ($stmtCheck->fetch()) {
                $skipped++;
                continue;
            }

            // Traducir respuestas 1,2,3,4 a índices 0,1,2,3
            $rcRaw = strtolower($v('correcta'));
            $rcIndex = -1;
            if (in_array($rcRaw, ['1', 'a', 'opcion_a', 'si', 'sí', 'verdadero'])) $rcIndex = 0;
            elseif (in_array($rcRaw, ['2', 'b', 'opcion_b', 'no', 'falso'])) $rcIndex = 1;
            elseif (in_array($rcRaw, ['3', 'c', 'opcion_c'])) $rcIndex = 2;
            elseif (in_array($rcRaw, ['4', 'd', 'opcion_d'])) $rcIndex = 3;

            $opciones = [];
            $opciones[] = ['texto' => $v('a'), 'es_correcta' => ($rcIndex === 0)];
            $opciones[] = ['texto' => $v('b'), 'es_correcta' => ($rcIndex === 1)];
            if ($v('c')) $opciones[] = ['texto' => $v('c'), 'es_correcta' => ($rcIndex === 2)];
            if ($v('d')) $opciones[] = ['texto' => $v('d'), 'es_correcta' => ($rcIndex === 3)];

            $tipo = (count($opciones) == 2 && strtolower($v('a')) == 'verdadero') ? 'verdadero_falso' : 'quiz';

            $stmtIns->execute([
                $targetId,
                $texto,
                $v('seccion'),
                $tipo,
                $v('idioma') ?: 'es',
                (int)($v('tiempo') ?: 20),
                (int)($v('doble') ?: 0),
                json_encode($opciones, JSON_UNESCAPED_UNICODE),
                $compartida,
                $v('asignatura'),
                $v('nivel'),
                (int)($v('dificultad') ?: 1),
                $v('etiquetas')
            ]);
            $inserted++;
        }
    }

    Logger::registrar($db, $uid, 'IMPORT', 'preguntas', null, ['inserted' => $inserted, 'skipped' => $skipped]);
    echo json_encode(['status' => 'ok', 'insertadas' => $inserted, 'saltados' => $skipped, 'mensaje' => "$inserted preguntas importadas."]);
}

function getCsvMap($header) {
    $map = [];
    foreach ($header as $i => $col) {
        // Limpiamos caracteres invisibles, acentos y pasamos a minúsculas
        $k = strtolower(trim($col));
        $k = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $k);
        
        if (strpos($k, 'texto') !== false || strpos($k, 'pregunta') !== false) $map['texto'] = $i;
        if (strpos($k, 'seccion') !== false) $map['seccion'] = $i;
        if (strpos($k, 'opcion_a') !== false || $k == 'a' || $k == 'opcion a') $map['a'] = $i;
        if (strpos($k, 'opcion_b') !== false || $k == 'b' || $k == 'opcion b') $map['b'] = $i;
        if (strpos($k, 'opcion_c') !== false || $k == 'c' || $k == 'opcion c') $map['c'] = $i;
        if (strpos($k, 'opcion_d') !== false || $k == 'd' || $k == 'opcion d') $map['d'] = $i;
        if (strpos($k, 'correcta') !== false || $k == 'respuesta') $map['correcta'] = $i;
        if (strpos($k, 'tiempo') !== false) $map['tiempo'] = $i;
        if (strpos($k, 'idioma') !== false) $map['idioma'] = $i;
        if (strpos($k, 'doble') !== false) $map['doble'] = $i;
        if (strpos($k, 'asignatura') !== false || $k == 'asig') $map['asignatura'] = $i;
        if (strpos($k, 'nivel') !== false) $map['nivel'] = $i;
        if (strpos($k, 'dificultad') !== false) $map['dificultad'] = $i;
        if (strpos($k, 'etiquetas') !== false) $map['etiquetas'] = $i;
    }
    return $map;
}

function handleListSubjects($db)
{
    $stmt = $db->query("SELECT DISTINCT id_asignatura FROM preguntas WHERE id_asignatura IS NOT NULL AND id_asignatura != '' ORDER BY id_asignatura ASC");
    $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['data' => $data]);
}

function handleListSections($db)
{
    $stmt = $db->query("SELECT DISTINCT seccion FROM preguntas WHERE seccion IS NOT NULL AND seccion != '' ORDER BY seccion ASC");
    $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['data' => $data]);
}
