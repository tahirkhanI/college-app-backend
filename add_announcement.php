<?php
header("Content-Type: application/json");

// Database connection
$conn = new mysqli("localhost", "root", "", "resource");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Read POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data["title"], $data["message"], $data["recipient"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$title = $conn->real_escape_string($data["title"]);
$message = $conn->real_escape_string($data["message"]);
$recipient = $conn->real_escape_string($data["recipient"]);
$admin_id = isset($data["admin_id"]) ? (int)$data["admin_id"] : "NULL";
$send_now = isset($data["send_now"]) && $data["send_now"] == true;

// Handle send_time
$send_time = $send_now
    ? "CURRENT_TIMESTAMP"
    : (isset($data["send_time"]) ? "'" . $conn->real_escape_string($data["send_time"]) . "'" : "CURRENT_TIMESTAMP");

// SQL Insert
$sql = "INSERT INTO announcements_admin (title, message, recipient, send_time, admin_id) 
        VALUES ('$title', '$message', '$recipient', $send_time, $admin_id)";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "id" => $conn->insert_id]);
} else {
    http_response_code(400);
    echo json_encode(["error" => $conn->error]);
}

$conn->close();
?>
