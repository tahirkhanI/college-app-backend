<?php
header("Content-Type: application/json");

// Database configuration for XAMPP
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP default has no password
$dbname = "resource";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

// Fetch all classrooms
$query = "SELECT id, room_number, room_type, floor, block_name FROM classrooms";
$result = $conn->query($query);

$classrooms = [];
while ($row = $result->fetch_assoc()) {
    $classrooms[] = $row;
}

echo json_encode([
    "success" => true,
    "classrooms" => $classrooms
]);

$conn->close();
?>
