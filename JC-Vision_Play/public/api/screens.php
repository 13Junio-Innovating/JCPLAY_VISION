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

if (!$db) {
    http_response_code(500);
    echo json_encode(["error" => "Falha na conexão com o banco de dados."]);
    exit();
}

// Rotas
switch ($method) {
    case 'GET':
        if ($userId) {
            $query = "SELECT * FROM screens WHERE user_id = :user_id ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $screens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["data" => $screens]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID do usuário necessário."]);
        }
        break;

    case 'POST':
        if (!empty($data->name) && !empty($data->user_id)) {
            $uuid = generate_uuid();
            $playerKey = bin2hex(random_bytes(8)); // 16 chars
            
            $query = "INSERT INTO screens (id, name, player_key, user_id) VALUES (:id, :name, :player_key, :user_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $uuid);
            $stmt->bindParam(':name', $data->name);
            $stmt->bindParam(':player_key', $playerKey);
            $stmt->bindParam(':user_id', $data->user_id);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["message" => "Tela criada.", "id" => $uuid, "player_key" => $playerKey]);
            } else {
                http_response_code(503);
                echo json_encode(["error" => "Não foi possível criar a tela."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Dados incompletos."]);
        }
        break;
        
    case 'PUT':
        if (!empty($data->id) && isset($data->assigned_playlist)) {
            $query = "UPDATE screens SET assigned_playlist = :assigned_playlist WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':assigned_playlist', $data->assigned_playlist);
            $stmt->bindParam(':id', $data->id);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Tela atualizada."]);
            } else {
                http_response_code(503);
                echo json_encode(["error" => "Erro ao atualizar."]);
            }
        }
        else if (!empty($data->id) && isset($data->notification_emails)) {
             // ... lógica similar
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if ($id) {
            $query = "DELETE FROM screens WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Tela excluída."]);
            } else {
                http_response_code(503);
                echo json_encode(["error" => "Erro ao excluir."]);
            }
        } else {
             http_response_code(400);
             echo json_encode(["error" => "ID necessário."]);
        }
        break;
}
