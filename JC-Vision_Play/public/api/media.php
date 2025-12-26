<?php
// Prevent any output before we are ready
ob_start();

// Suppress all HTML errors immediately
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers early
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Custom error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
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

    error_log("[$errorType] $errstr in $errfile on line $errline");
    return true; 
});

// Handle Fatal Errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
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
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$db) {
    http_response_code(500);
    echo json_encode(["error" => "Falha na conexão com o banco de dados."]);
    exit();
}

switch ($method) {
    case 'GET':
        $ids = isset($_GET['ids']) ? $_GET['ids'] : null;
        
        if ($ids) {
            $idList = explode(',', $ids);
            // Construct string of ? placeholders
            $placeholders = str_repeat('?,', count($idList) - 1) . '?';
            $query = "SELECT * FROM media WHERE id IN ($placeholders)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($idList)) {
                $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["data" => $media, "count" => count($media)]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao buscar mídias por IDs."]);
            }
        } elseif ($userId) {
            $query = "SELECT * FROM media WHERE uploaded_by = :uploaded_by ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':uploaded_by', $userId);
            
            if ($stmt->execute()) {
                $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["data" => $media, "count" => count($media)]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao buscar mídias."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID do usuário ou lista de IDs necessária."]);
        }
        break;

    case 'POST':
        // Check for empty POST which means post_max_size was exceeded
        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            http_response_code(413); // Payload Too Large
            echo json_encode(["error" => "Arquivo muito grande. Limite do servidor excedido."]);
            exit();
        }

        if (isset($_FILES['file'])) {
            // Check for specific upload errors
            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                $errorMsg = "Erro no upload: " . $_FILES['file']['error'];
                if ($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE) {
                     $errorMsg = "Arquivo muito grande (limite do servidor).";
                }
                echo json_encode(["error" => $errorMsg]);
                exit();
            }

            // File Upload
            $uploadedBy = isset($_POST['uploaded_by']) ? $_POST['uploaded_by'] : null;
            $duration = isset($_POST['duration']) ? $_POST['duration'] : 10;
            $type = isset($_POST['type']) ? $_POST['type'] : 'image'; 
            
            if (!$uploadedBy) {
                http_response_code(400);
                echo json_encode(["error" => "ID do usuário necessário."]);
                exit();
            }

            // Use __DIR__ to be safe
            $targetDir = __DIR__ . "/../uploads/";
            if (!file_exists($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    http_response_code(500);
                    echo json_encode(["error" => "Falha ao criar diretório de uploads: $targetDir"]);
                    exit();
                }
            }
            
            $fileName = basename($_FILES["file"]["name"]);
            // Generate unique name to avoid collisions
            $uniqueName = uniqid() . "_" . $fileName;
            $targetFilePath = $targetDir . $uniqueName;
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                $fileUrl = "/uploads/" . $uniqueName;
                $uuid = generate_uuid();
                
                $query = "INSERT INTO media (id, name, url, type, duration, uploaded_by) VALUES (:id, :name, :url, :type, :duration, :uploaded_by)";
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(":id", $uuid);
                $stmt->bindParam(":name", $fileName);
                $stmt->bindParam(":url", $fileUrl);
                $stmt->bindParam(":type", $type);
                $stmt->bindParam(":duration", $duration);
                $stmt->bindParam(":uploaded_by", $uploadedBy);
                
                if ($stmt->execute()) {
                    echo json_encode(["message" => "Arquivo enviado com sucesso.", "url" => $fileUrl, "id" => $uuid]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao salvar no banco de dados."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao mover arquivo enviado."]);
            }

        } else {
            // JSON Body (Link)
            $input = file_get_contents("php://input");
            $data = json_decode($input);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(["error" => "JSON inválido.", "raw" => $input]);
                exit();
            }
            
            if (!empty($data->name) && !empty($data->url) && !empty($data->uploaded_by)) {
                $uuid = generate_uuid();
                $query = "INSERT INTO media (id, name, url, type, duration, uploaded_by) VALUES (:id, :name, :url, :type, :duration, :uploaded_by)";
                $stmt = $db->prepare($query);
                
                $type = isset($data->type) ? $data->type : 'video'; 
                $duration = isset($data->duration) ? $data->duration : 10;
                
                $stmt->bindParam(":id", $uuid);
                $stmt->bindParam(":name", $data->name);
                $stmt->bindParam(":url", $data->url);
                $stmt->bindParam(":type", $type);
                $stmt->bindParam(":duration", $duration);
                $stmt->bindParam(":uploaded_by", $data->uploaded_by);
                
                if ($stmt->execute()) {
                    echo json_encode(["message" => "Link adicionado com sucesso.", "id" => $uuid]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao adicionar link no banco."]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Dados incompletos.", "data" => $data]);
            }
        }
        break;
        
    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        $id = isset($_GET['id']) ? $_GET['id'] : (isset($data->id) ? $data->id : null);
        
        if ($id) {
            $query = "SELECT url FROM media WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                if (strpos($row['url'], '/uploads/') === 0) {
                    // Adjust path using __DIR__
                    $filePath = __DIR__ . "/.." . $row['url'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $query = "DELETE FROM media WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    echo json_encode(["message" => "Mídia excluída com sucesso."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao excluir mídia."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Mídia não encontrada."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID necessário."]);
        }
        break;
}
