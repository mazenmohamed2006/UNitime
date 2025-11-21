<?php
// ========== GET EVENTS API ENDPOINT ========== //
// Set CORS and content-type
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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

// Include database configuration
require_once __DIR__ . '/../../config/database.php';

// Establish DB connection (accepts either a provided PDO instance or DB_* constants)
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } elseif (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        // Read constants via constant() to avoid undefined constant notices/errors from static analyzers
        $host = constant('DB_HOST');
        $name = constant('DB_NAME');
        $username = constant('DB_USER');
        $password = defined('DB_PASS') ? constant('DB_PASS') : '';

        $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
        $db = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        throw new Exception('No valid database configuration found.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Query user's events ordered by date and start time
try {
    $sql = "SELECT id, title, description, event_date, start_time, end_time, location
            FROM events
            WHERE user_id = :user_id
            ORDER BY event_date ASC, start_time ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query error']);
    exit;
}

// Handle empty result set
if (!$rows) {
    echo json_encode(['success' => true, 'events' => []]);
    exit;
}

// Format events for frontend
$events = [];
foreach ($rows as $r) {
    // Normalize values
    $date = $r['event_date'];
    $startTime = $r['start_time'];
    $endTime = $r['end_time'];

    // Build ISO datetimes for frontend (if times are present)
    $startIso = null;
    $endIso = null;
    if ($date && $startTime) {
        $dtStart = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $startTime) 
                   ?: DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $startTime);
        if ($dtStart) {
            $startIso = $dtStart->format(DateTime::ATOM);
        }
    } elseif ($date) {
        $dtStart = DateTime::createFromFormat('Y-m-d', $date);
        if ($dtStart) $startIso = $dtStart->format('Y-m-d');
    }
    if ($date && $endTime) {
        $dtEnd = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $endTime)
                 ?: DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $endTime);
        if ($dtEnd) {
            $endIso = $dtEnd->format(DateTime::ATOM);
        }
    }

    $events[] = [
        'id' => $r['id'],
        'title' => $r['title'],
        'description' => $r['description'],
        'date' => $date,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'start' => $startIso,
        'end' => $endIso,
        'location' => $r['location'],
    ];
}

// Return JSON response
echo json_encode(['success' => true, 'events' => $events]);
// TODO: Set CORS headers and content type
// TODO: Handle preflight OPTIONS request
// TODO: Check user authentication via session
// TODO: Include database configuration
// TODO: Query database for user's events
// TODO: Order events by date and time
// TODO: Return events array in JSON response
// TODO: Handle empty result set
// TODO: Format dates for frontend
?>