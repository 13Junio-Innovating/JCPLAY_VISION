<?php
// Prevent any output before we are ready
ob_start();

// Suppress all HTML errors immediately
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Custom error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Only handle errors that match the current error_reporting level
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorType = 'Unknown Error';
    switch ($errno) {
        case E_ERROR: $errorType = 'Fatal Error'; break;
        case E_WARNING: $errorType = 'Warning'; break;
        case E_PARSE: $errorType = 'Parse Error'; break;
        case E_NOTICE: $errorType = 'Notice'; break;
    }

    // Log the actual error
    error_log("[$errorType] $errstr in $errfile on line $errline");
    return true; 
});

// Handle Fatal Errors (shutdown function)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        // Clear any buffered output (HTML)
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "error" => "Critical Server Error",
            "details" => $error['message']
        ]);
    } else {
        // If no error, flush buffer
        ob_end_flush();
    }
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';
require_once 'utils.php';

$database = new DbConnection();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : (isset($data->id) ? $data->id : null);

if (!$db) {
    http_response_code(500);
    echo json_encode(["error" => "Falha na conexão com o banco de dados."]);
    exit();
}

switch ($method) {
    case 'GET':
        if ($id) {
            $query = "SELECT * FROM playlists WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($playlist) {
                echo json_encode(["data" => $playlist]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Playlist não encontrada."]);
            }
        } elseif ($userId) {
            $query = "SELECT * FROM playlists WHERE created_by = :user_id ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["data" => $playlists]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID do usuário ou ID da playlist necessário."]);
        }
        break;

    case 'POST':
        if (!empty($data->name) && !empty($data->user_id)) {
            $uuid = generate_uuid();
            $query = "INSERT INTO playlists (id, name, description, items, created_by) VALUES (:id, :name, :description, :items, :created_by)";
            $stmt = $db->prepare($query);
            
            $description = isset($data->description) ? $data->description : '';
            $items = isset($data->items) ? json_encode($data->items) : '[]';
            
            $stmt->bindParam(":id", $uuid);
            $stmt->bindParam(":name", $data->name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":items", $items);
            $stmt->bindParam(":created_by", $data->user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Playlist criada com sucesso.", "id" => $uuid]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao criar playlist."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Dados incompletos."]);
        }
        break;

    case 'PUT':
        if (!empty($data->id)) {
            $updates = [];
            $params = [':id' => $data->id];
            
            if (isset($data->name)) {
                $updates[] = "name = :name";
                $params[':name'] = $data->name;
            }
            if (isset($data->description)) {
                $updates[] = "description = :description";
                $params[':description'] = $data->description;
            }
            if (isset($data->items)) {
                $updates[] = "items = :items";
                $params[':items'] = json_encode($data->items);
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(["error" => "Nenhum dado para atualizar."]);
                break;
            }
            
            $query = "UPDATE playlists SET " . implode(", ", $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($params)) {
                echo json_encode(["message" => "Playlist atualizada com sucesso."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao atualizar playlist."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID da playlist necessário."]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $query = "DELETE FROM playlists WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Playlist excluída com sucesso."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao excluir playlist."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID necessário."]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["error" => "Método não permitido."]);
        break;
}
?>
