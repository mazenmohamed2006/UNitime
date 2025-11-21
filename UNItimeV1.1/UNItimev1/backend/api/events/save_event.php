<?php
// ========== SAVE EVENT API ENDPOINT ========== //
// CORS and JSON response headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$userId = (int) $_SESSION['user_id'];

// Include database configuration (expects a PDO instance in $pdo)
require_once __DIR__ . '/../../config/database.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

// Only allow POST for actual save
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read and decode JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Required fields
$title = isset($data['title']) ? trim($data['title']) : '';
$date = isset($data['date']) ? trim($data['date']) : '';
$description = isset($data['description']) ? trim($data['description']) : null;
$start_time = isset($data['start_time']) ? trim($data['start_time']) : null;
$end_time = isset($data['end_time']) ? trim($data['end_time']) : null;
$eventId = isset($data['id']) ? (int)$data['id'] : null;

if ($title === '' || $date === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: title and date']);
    exit;
}

// Validate date (YYYY-MM-DD)
$dObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dObj || $dObj->format('Y-m-d') !== $date) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Expected YYYY-MM-DD']);
    exit;
}

// Validate times if provided (HH:MM)
$validateTime = function ($t) {
    if ($t === null || $t === '') return true;
    $tObj = DateTime::createFromFormat('H:i', $t);
    return ($tObj && $tObj->format('H:i') === $t);
};
if (!$validateTime($start_time) || !$validateTime($end_time)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid time format. Expected HH:MM']);
    exit;
}

try {
    if ($eventId) {
        // Verify event belongs to user
        $stmt = $pdo->prepare('SELECT id, user_id FROM events WHERE id = :id');
        $stmt->execute([':id' => $eventId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit;
        }
        if ((int)$existing['user_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: cannot modify this event']);
            exit;
        }

        // Update event
        $updateSql = 'UPDATE events SET title = :title, date = :date, description = :description, start_time = :start_time, end_time = :end_time, updated_at = NOW() WHERE id = :id';
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            ':title' => $title,
            ':date' => $date,
            ':description' => $description,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':id' => $eventId
        ]);
        echo json_encode(['success' => true, 'message' => 'Event updated', 'id' => $eventId]);
        exit;
    } else {
        // Insert new event
        $insertSql = 'INSERT INTO events (user_id, title, date, description, start_time, end_time, created_at, updated_at) VALUES (:user_id, :title, :date, :description, :start_time, :end_time, NOW(), NOW())';
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':date' => $date,
            ':description' => $description,
            ':start_time' => $start_time,
            ':end_time' => $end_time
        ]);
        $newId = (int)$pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Event created', 'id' => $newId]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    exit;
}

// TODO: Set CORS headers and content type
// TODO: Handle preflight OPTIONS request
// TODO: Check user authentication via session
// TODO: Include database configuration
// TODO: Validate request method is POST
// TODO: Get and validate JSON input
// TODO: Check required fields (title, date)
// TODO: Validate date and time formats
// TODO: If event ID provided, update existing event
// TODO: If no event ID, create new event
// TODO: Verify event belongs to user for updates
// TODO: Return success response with event ID
// TODO: Handle errors and validation failures