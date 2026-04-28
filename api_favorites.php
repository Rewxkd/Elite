<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

switch ($method) {
    case 'GET':
        // Get user's favorites by game type
        $stmt = $conn->prepare("SELECT game_type FROM favorites WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        break;

    case 'POST':
        // Add favorite by game type
        $data = json_decode(file_get_contents('php://input'), true);
        $game_type = trim(strval($data['game_type'] ?? ''));
        if ($game_type === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid game_type']);
            exit;
        }
        $game_type = strtolower($game_type);
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, game_type)
            SELECT ?, ? FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM favorites WHERE user_id = ? AND game_type = ?)");
        $stmt->bind_param('issi', $user_id, $game_type, $user_id, $game_type);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Added to favorites']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add']);
        }
        break;

    case 'DELETE':
        // Remove favorite by game type
        $data = json_decode(file_get_contents('php://input'), true);
        $game_type = trim(strval($data['game_type'] ?? ''));
        if ($game_type === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid game_type']);
            exit;
        }
        $game_type = strtolower($game_type);
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND game_type = ?");
        $stmt->bind_param('is', $user_id, $game_type);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>