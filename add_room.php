<?php
// Set JSON response header
header("Content-Type: application/json");

// Allow CORS for testing (optional, remove or restrict in production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "resource";  // Update this if your DB name differs

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection success
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Retrieve and sanitize POST data
$room_number = trim($_POST['room_number'] ?? '');
$room_type = trim($_POST['room_type'] ?? '');
$floor = trim($_POST['floor'] ?? '');
$block_name = trim($_POST['block_name'] ?? '');

// Validate required fields
if (empty($room_number) || empty($room_type) || empty($floor) || empty($block_name)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error",
        "message" => "Please provide room_number, room_type, floor, and block_name."
    ]);
    exit();
}

// Use prepared statement to check if classroom already exists
$check_sql = "SELECT id FROM classrooms WHERE room_number = ? AND block_name = ? AND floor = ?";
$stmt = $conn->prepare($check_sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare statement: " . $conn->error
    ]);
    exit();
}
$stmt->bind_param("sss", $room_number, $block_name, $floor);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode([
        "status" => "error",
        "message" => "Classroom with the same room number already exists in this block and floor."
    ]);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Insert new classroom
$insert_sql = "INSERT INTO classrooms (room_number, room_type, floor, block_name) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare insert statement: " . $conn->error
    ]);
    exit();
}
$stmt->bind_param("ssss", $room_number, $room_type, $floor, $block_name);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode([
        "status" => "success",
        "message" => "Classroom added successfully."
    ]);
} else {
    // Insert failure
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add classroom: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
