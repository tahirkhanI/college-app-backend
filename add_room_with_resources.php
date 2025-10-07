<?php
header("Content-Type: application/json");

// DB config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "resource";

// Connect
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Validate required POST data
$building = $_POST['building'] ?? '';
$room_number = $_POST['room_number'] ?? '';
$seating_capacity = $_POST['seating_capacity'] ?? '';
$resources = $_POST['resources'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($building) || empty($room_number) || empty($seating_capacity)) {
    echo json_encode(["status" => "error", "message" => "Required fields missing"]);
    exit();
}

// Sanitize inputs (basic)
$building = $conn->real_escape_string($building);
$room_number = $conn->real_escape_string($room_number);
$resources = $conn->real_escape_string($resources);
$description = $conn->real_escape_string($description);
$seating_capacity = (int)$seating_capacity;

// Insert into DB
$sql = "INSERT INTO rooms (building, room_number, seating_capacity, resources, description) 
        VALUES ('$building', '$room_number', $seating_capacity, '$resources', '$description')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Room added successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add room: " . $conn->error]);
}

$conn->close();
?>
