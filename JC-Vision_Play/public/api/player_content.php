<?php
// api/player_content.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db_connection.php';

if (!isset($_GET['key'])) {
    http_response_code(400);
    echo json_encode(["error" => "Chave do player necessária."]);
    exit();
}

$key = $_GET['key'];

$database = new DbConnection();
$db = $database->getConnection();

// 1. Get Screen and Playlist ID by Key
$query = "SELECT s.id as screen_id, s.name as screen_name, s.assigned_playlist 
          FROM screens s 
          WHERE s.player_key = :key LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(':key', $key);
$stmt->execute();
$screen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$screen) {
    http_response_code(404);
    echo json_encode(["error" => "Tela não encontrada ou chave inválida."]);
    exit();
}

// Update Last Seen
$updateQuery = "UPDATE screens SET last_seen = NOW() WHERE id = :id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':id', $screen['screen_id']);
$updateStmt->execute();

if (!$screen['assigned_playlist']) {
    echo json_encode(["status" => "idle", "screen" => $screen['screen_name'], "message" => "Nenhuma playlist atribuída."]);
    exit();
}

// 2. Get Playlist Items
$queryPlaylist = "SELECT items FROM playlists WHERE id = :id";
$stmtP = $db->prepare($queryPlaylist);
$stmtP->bindParam(':id', $screen['assigned_playlist']);
$stmtP->execute();
$playlist = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$playlist) {
    echo json_encode(["status" => "error", "message" => "Playlist atribuída não encontrada."]);
    exit();
}

$items = json_decode($playlist['items'], true);
if (empty($items)) {
    echo json_encode(["status" => "empty", "message" => "Playlist vazia."]);
    exit();
}

// 3. Enrich Items with Media URL and Type
$enrichedItems = [];
$ids = array_column($items, 'mediaId');

if (!empty($ids)) {
    // Prepare IN clause
    $in  = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT id, url, type, name FROM media WHERE id IN ($in)";
    $stmtM = $db->prepare($sql);
    $stmtM->execute($ids);
    $mediaMap = [];
    while ($row = $stmtM->fetch(PDO::FETCH_ASSOC)) {
        $mediaMap[$row['id']] = $row;
    }

    foreach ($items as $item) {
        if (isset($mediaMap[$item['mediaId']])) {
            $media = $mediaMap[$item['mediaId']];
            $enrichedItems[] = [
                'id' => $media['id'],
                'url' => $media['url'],
                'type' => $media['type'], // image, video, link
                'name' => $media['name'],
                'duration' => isset($item['duration']) ? intval($item['duration']) : 10
            ];
        }
    }
}

echo json_encode([
    "status" => "playing",
    "screen" => $screen['screen_name'],
    "playlist" => $enrichedItems
]);
?>