<?php
// ========== DELETE EVENT API ENDPOINT ========== //
// CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Start session and check authentication
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$authUserId = (int) $_SESSION['user_id'];

// Include database configuration (expected to provide a $pdo PDO instance)
require_once __DIR__ . '/../../config/database.php';

// Fallback: try to build a PDO if include didn't provide $pdo
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbName = getenv('DB_NAME') ?: 'your_db';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// Only allow POST for deletion
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate event_id
if (empty($input['event_id']) || !is_numeric($input['event_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'event_id is required and must be numeric']);
    exit;
}
$eventId = (int) $input['event_id'];

try {
    // Verify event exists and belongs to authenticated user
    $stmt = $pdo->prepare('SELECT id, user_id FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    if ((int)$event['user_id'] !== $authUserId) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: you do not have permission to delete this event']);
        exit;
    }

    // Delete the event
    $del = $pdo->prepare('DELETE FROM events WHERE id = :id');
    $del->execute([':id' => $eventId]);

    echo json_encode(['success' => true, 'message' => 'Event deleted', 'event_id' => $eventId]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while deleting the event']);
    exit;
}
// TODO: Set CORS headers and content type
// TODO: Handle preflight OPTIONS request
// TODO: Check user authentication via session
// TODO: Include database configuration
// TODO: Validate request method is POST
// TODO: Get and validate JSON input
// TODO: Check required event ID
// TODO: Verify event belongs to user before deletion
// TODO: Delete event from database
// TODO: Return success response
// TODO: Handle errors and invalid event ID
?>