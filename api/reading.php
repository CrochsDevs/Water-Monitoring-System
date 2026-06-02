<?php
/**
 * api/reading.php
 * ================
 * Accepts water level readings from Arduino (via USB-serial bridge
 * in the future, or directly from the Air780E/SIM800L module).
 *
 * POST /api/reading.php
 * Content-Type: application/json
 *
 * Body:
 * {
 *   "device_id": "RF01",
 *   "water_level_cm": 12.5,
 *   "distance_cm": 187.5,
 *   "battery_v": 12.4,
 *   "signal": 18,
 *   "alert": "high_water"        // optional: null, "high_water", "low_water", "sensor_error", "low_battery"
 * }
 *
 * Returns:
 *   {"success": true, "id": 1234}  or  {"success": false, "error": "..."}
 *
 * Note: This endpoint does NOT require authentication — the Arduino
 * cannot log in. For production, add an API key check.
 */

header('Content-Type: application/json');

// CORS — allow the serial bridge script or any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

// Read raw JSON body
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body. Send JSON.']);
    exit();
}

$data = json_decode($rawInput, true);
if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
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
$stmt = $conn->prepare("
    INSERT INTO water_level_readings
        (device_id, water_level_cm, distance_cm, battery_v, signal_strength, alert, reading_mode, received_at)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt->bind_param("sdddiss", $device_id, $water_level, $distance, $battery, $signal, $alert, $reading_mode);

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
