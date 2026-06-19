<?php
/**
 * api/reading.php
 * ================
 * Accepts water level readings from Arduino via:
 *   - POST with JSON body (preferred)
 *   - GET with query parameters (fallback for modules with buggy HTTP stacks)
 *
 * POST /api/reading.php
 * Content-Type: application/json
 * Body: { "device_id": "RF01", "water_level_cm": 12.5, ... }
 *
 * GET /api/reading.php?device_id=RF01&water_level_cm=12.5&...
 *
 * Returns:
 *   {"success": true, "id": 1234}  or  {"success": false, "error": "..."}
 */

header('Content-Type: application/json');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Read input from POST (JSON body) or GET (query string) ---
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST — read JSON body
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $data = json_decode($rawInput, true);
        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
            exit();
        }
    }
} else {
    // GET — read from query string
    $data = [
        'device_id'     => $_GET['device_id'] ?? null,
        'water_level_cm'=> $_GET['water_level_cm'] ?? null,
        'distance_cm'   => $_GET['distance_cm'] ?? null,
        'battery_v'     => $_GET['battery_v'] ?? null,
        'signal'        => $_GET['signal'] ?? 0,
        'alert'         => $_GET['alert'] ?? null,
        'reading_mode'  => $_GET['reading_mode'] ?? 'lte',
        'timestamp'     => $_GET['timestamp'] ?? null
    ];
}

// Validate required fields
if (empty($data['water_level_cm']) && $data['water_level_cm'] !== '0' && $data['water_level_cm'] !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or empty water_level_cm']);
    exit();
}

// --- Validate required fields ---
$required = ['water_level_cm', 'distance_cm', 'battery_v'];
$missing = [];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        $missing[] = $field;
    }
}
if (!empty($missing)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: ' . implode(', ', $missing)]);
    exit();
}

// --- Sanitize & prepare ---
$device_id    = isset($data['device_id'])    ? trim($data['device_id'])    : 'RF01';
$water_level  = floatval($data['water_level_cm']);
$distance     = floatval($data['distance_cm']);
$battery      = floatval($data['battery_v']);
$signal       = isset($data['signal'])       ? intval($data['signal'])     : 0;
$alert        = isset($data['alert'])        ? trim($data['alert'])        : null;
$reading_mode = isset($data['reading_mode']) ? trim($data['reading_mode']) : 'serial_usb';

if (empty($alert)) {
    $alert = null;  // store as NULL in DB
}

// Validate ranges
if ($water_level < 0 || $water_level > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'water_level_cm out of range (0-500)']);
    exit();
}
if ($battery < 0 || $battery > 30) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'battery_v out of range']);
    exit();
}
if ($signal < 0 || $signal > 31) {
    $signal = 0;
}

// --- Connect to database ---
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'water_monitoring';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// --- Insert the reading ---
// Use custom timestamp if provided, otherwise NOW()
date_default_timezone_set('Asia/Manila');
$received_at = !empty($data['timestamp']) ? $data['timestamp'] : date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO water_level_readings
        (device_id, water_level_cm, distance_cm, battery_v, signal_strength, alert, reading_mode, received_at)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt->bind_param("sdddisss", $device_id, $water_level, $distance, $battery, $signal, $alert, $reading_mode, $received_at);

if ($stmt->execute()) {
    $inserted_id = $stmt->insert_id;
    echo json_encode([
        'success'      => true,
        'id'           => $inserted_id,
        'device_id'    => $device_id,
        'water_level'  => $water_level,
        'received_at'  => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
